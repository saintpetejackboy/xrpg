<?php
// /auth/login.php - Enhanced login with rate limiting and security
require_once __DIR__ . '/../config/db.php';
$config = require __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';
require_once __DIR__ . '/RateLimiter.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize rate limiter
$rateLimiter = new RateLimiter($pdo);
$clientIP = RateLimiter::getClientIP();

function logError($message, $details = '') {
    error_log("WebAuthn Login Error: $message " . ($details ? "Details: $details" : ""));
}

function sendError($code, $message, $details = '', $retryAfter = null) {
    logError($message, $details);
    http_response_code($code);
    $response = ['error' => $message, 'details' => $details];
    if ($retryAfter) {
        $response['retry_after'] = $retryAfter;
        header("Retry-After: $retryAfter");
    }
    echo json_encode($response);
    exit;
}

// Convert base64url string to binary data
function base64urlToBinary($data) {
    $data = str_replace(['-', '_'], ['+', '/'], $data);
    while (strlen($data) % 4) {
        $data .= '=';
    }
    return base64_decode($data);
}

// Convert binary data to base64url string
function binaryToBase64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Enhanced credential repository with security logging
class SecureCredentialRepository implements \Webauthn\PublicKeyCredentialSourceRepository {
    private $user;
    private $pdo;
    private $ip;
    
    public function __construct($user, $pdo, $ip) {
        $this->user = $user;
        $this->pdo = $pdo;
        $this->ip = $ip;
    }
    
    public function findOneByCredentialId(string $publicKeyCredentialId): ?\Webauthn\PublicKeyCredentialSource {
        $incomingCredentialIdBase64url = binaryToBase64url($publicKeyCredentialId);
        
        if ($incomingCredentialIdBase64url === $this->user['passkey_id']) {
            try {
                $aaguid = null;
                if (class_exists('Symfony\Component\Uid\Uuid')) {
                    try {
                        $aaguid = \Symfony\Component\Uid\Uuid::v4();
                    } catch (Exception $e) {
                        $aaguid = null;
                    }
                }
                
                // Log successful credential lookup
                $this->logAuthEvent('credential_found', 'Passkey credential found for login');
                
                return new \Webauthn\PublicKeyCredentialSource(
                    $publicKeyCredentialId,
                    'public-key',
                    [],
                    'none',
                    new EmptyTrustPath(),
                    $aaguid,
                    $this->user['passkey_public_key'],
                    base64_decode($this->user['user_id']),
                    0
                );
                
            } catch (Exception $e) {
                error_log("Failed to create PublicKeyCredentialSource: " . $e->getMessage());
                $this->logAuthEvent('credential_error', 'Failed to create credential source: ' . $e->getMessage());
                return null;
            }
        }
        
        $this->logAuthEvent('credential_not_found', 'Passkey credential not found');
        return null;
    }
    
    public function findAllForUserEntity(\Webauthn\PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
        return [];
    }
    
    public function saveCredentialSource(\Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource): void {
        // Not needed for login
    }
    
    private function logAuthEvent($eventType, $description) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $this->user['id'],
                $this->user['username'],
                $eventType,
                $description,
                $this->ip,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log auth event: " . $e->getMessage());
        }
    }
}

// STEP 1: Generate login options
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        
        // Check rate limits first (before revealing if user exists)
        $rateCheck = $rateLimiter->checkRateLimit('login', $clientIP, $username);
        if (!$rateCheck['allowed']) {
            $message = match($rateCheck['reason']) {
                'temporarily_blocked' => 'Too many login attempts. Please try again later.',
                'too_many_attempts' => 'Login temporarily blocked due to multiple failures.',
                default => 'Login rate limit exceeded.'
            };
            sendError(429, $message, '', $rateCheck['retry_after'] ?? null);
        }
        
        // Check for suspicious activity
        if ($rateLimiter->detectSuspiciousActivity($clientIP, $username)) {
            sendError(429, 'Suspicious activity detected. Please try again later.', '', 1800);
        }
        
        if (!$username) {
            sendError(400, 'Missing username');
        }

        // Validate username format (but don't reveal if it exists yet)
        if (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            sendError(400, 'Invalid username format');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Log failed attempt but don't reveal user doesn't exist
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO auth_log (username, event_type, description, ip_addr, user_agent) 
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $username,
                    'login_failed',
                    'Login attempt for non-existent user',
                    $clientIP,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            } catch (Exception $e) {
                error_log("Failed to log failed login: " . $e->getMessage());
            }
            
            sendError(404, 'User not found');
        }

        // Create credential descriptor
        $credentialIdBinary = base64urlToBinary($user['passkey_id']);
        $allowCredentials = [
            new PublicKeyCredentialDescriptor(
                'public-key',
                $credentialIdBinary
            )
        ];

        // Generate challenge (binary first, then base64url for transmission)
        $challengeBinary = random_bytes(32);
        $challengeBase64url = rtrim(strtr(base64_encode($challengeBinary), '+/', '-_'), '=');
        
        // Store in session with enhanced security
        $_SESSION['login_challenge'] = $challengeBase64url;
        $_SESSION['login_user_id'] = $user['id'];
        $_SESSION['login_username'] = $user['username'];
        $_SESSION['login_expires'] = time() + 300; // 5 minute expiration
        $_SESSION['login_ip'] = $clientIP; // Prevent session hijacking
        $_SESSION['login_user_agent_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

        // Create request options with binary challenge
        $requestOptions = new PublicKeyCredentialRequestOptions($challengeBinary);
        
        $requestOptions = $requestOptions
            ->allowCredentials(...$allowCredentials)
            ->setTimeout(60000)
            ->setRpId($config['rp_id'])
            ->setUserVerification('discouraged');

        $_SESSION['login_request_options'] = serialize($requestOptions);
        
        // Log login attempt start
        try {
            $stmt = $pdo->prepare('
                INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $user['username'],
                'login_start',
                'Login challenge generated',
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log login start: " . $e->getMessage());
        }
        
        echo json_encode([
            'challenge' => $challengeBase64url,
            'allowCredentials' => array_map(function($cred) {
                return [
                    'type' => $cred->getType(),
                    'id' => binaryToBase64url($cred->getId())
                ];
            }, $allowCredentials),
            'timeout' => 60000,
            'rpId' => $config['rp_id'],
            'userVerification' => 'discouraged'
        ]);
        exit;

    } catch (Exception $e) {
        sendError(500, 'Failed to generate login options', $e->getMessage());
    }
}

// STEP 2: Validate login
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Enhanced session validation
        if (!isset($_SESSION['login_expires']) || $_SESSION['login_expires'] < time()) {
            sendError(400, 'Login session expired');
        }
        
        if (!isset($_SESSION['login_ip']) || $_SESSION['login_ip'] !== $clientIP) {
            sendError(400, 'Session IP mismatch - possible security issue');
        }
        
        $currentUserAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        if (!isset($_SESSION['login_user_agent_hash']) || 
            $_SESSION['login_user_agent_hash'] !== $currentUserAgentHash) {
            sendError(400, 'Session user agent mismatch - possible security issue');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError(400, 'Invalid JSON input');
        }

        $challenge = $_SESSION['login_challenge'] ?? null;
        $userId = $_SESSION['login_user_id'] ?? null;
        $username = $_SESSION['login_username'] ?? null;
        $requestOptions = isset($_SESSION['login_request_options']) ? 
            unserialize($_SESSION['login_request_options']) : null;

        if (!$challenge || !$userId || !$requestOptions || !$username) {
            sendError(400, 'Session expired or invalid');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            sendError(404, 'User not found');
        }

        // Load credential
        $attestationStatementSupportManager = new \Webauthn\AttestationStatement\AttestationStatementSupportManager();
        $attestationObjectLoader = new \Webauthn\AttestationStatement\AttestationObjectLoader($attestationStatementSupportManager);
        $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        $credential = $credentialLoader->loadArray($input);

        // Create repository and validator with enhanced security
        $credentialRepository = new SecureCredentialRepository($user, $pdo, $clientIP);
        $validator = new AuthenticatorAssertionResponseValidator($credentialRepository);
        
        // Validate assertion
        $publicKeyCredentialSource = $validator->check(
            $credential->getRawId(),
            $credential->getResponse(),
            $requestOptions,
            $config['webauthn_origin'],
            base64_decode($user['user_id'])
        );

        // Login successful - update last login time
        try {
            $stmt = $pdo->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([$user['id']]);
        } catch (Exception $e) {
            error_log("Failed to update last login: " . $e->getMessage());
        }

        // Set secure session
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user'] = [
            'id' => $user['id'], 
            'username' => $user['username'], 
            'type' => 'player',
            'login_time' => time(),
            'login_ip' => $clientIP
        ];

        // Log successful login
        try {
            $stmt = $pdo->prepare('
                INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $user['id'],
                $user['username'],
                'login',
                'Successful passkey authentication',
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log successful login: " . $e->getMessage());
        }

        // Clear login session data
        unset($_SESSION['login_challenge'], $_SESSION['login_user_id'], 
              $_SESSION['login_request_options'], $_SESSION['login_username'],
              $_SESSION['login_expires'], $_SESSION['login_ip'], 
              $_SESSION['login_user_agent_hash']);

        echo json_encode([
            'ok' => true, 
            'message' => 'Login successful',
            'username' => $user['username']
        ]);
        exit;

    } catch (Exception $e) {
        // Log authentication failure
        $username = $_SESSION['login_username'] ?? 'unknown';
        $userId = $_SESSION['login_user_id'] ?? null;
        
        try {
            $stmt = $pdo->prepare('
                INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent, rate_limited) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $username,
                'login_failed',
                'Authentication failed: ' . $e->getMessage(),
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                false
            ]);
        } catch (Exception $logError) {
            error_log("Failed to log authentication failure: " . $logError->getMessage());
        }
        
        sendError(401, 'Authentication failed', $e->getMessage());
    }
}

sendError(405, 'Method not allowed');
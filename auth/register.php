<?php
// /auth/register.php - Enhanced registration with rate limiting and security
require_once __DIR__ . '/../config/db.php';
$config = require __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';
require_once __DIR__ . '/RateLimiter.php';

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAttestationResponseValidator;

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
    error_log("WebAuthn Registration Error: $message " . ($details ? "Details: $details" : ""));
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

// Enhanced username validation
function validateUsername($username) {
    $username = trim($username);
    
    if (empty($username)) {
        return ['valid' => false, 'error' => 'Username is required'];
    }
    
    if (strlen($username) < 3) {
        return ['valid' => false, 'error' => 'Username must be at least 3 characters'];
    }
    
    if (strlen($username) > 50) {
        return ['valid' => false, 'error' => 'Username must be less than 50 characters'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['valid' => false, 'error' => 'Username can only contain letters, numbers, underscores, and dashes'];
    }
    
    // Check for reserved names
    $reserved = ['admin', 'root', 'system', 'api', 'www', 'test', 'guest', 'null', 'undefined'];
    if (in_array(strtolower($username), $reserved)) {
        return ['valid' => false, 'error' => 'This username is reserved'];
    }
    
    return ['valid' => true, 'username' => $username];
}

// Convert binary data to base64url string
function binaryToBase64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// STEP 1: Generate registration options
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check rate limits first
        $rateCheck = $rateLimiter->checkRateLimit('register', $clientIP);
        if (!$rateCheck['allowed']) {
            $message = match($rateCheck['reason']) {
                'temporarily_blocked' => 'Too many registration attempts. Please try again later.',
                'too_many_attempts' => 'Registration temporarily blocked due to multiple failures.',
                default => 'Registration rate limit exceeded.'
            };
            sendError(429, $message, '', $rateCheck['retry_after'] ?? null);
        }
        
        // Check account creation limits
        $creationCheck = $rateLimiter->checkAccountCreationLimits($clientIP);
        if (!$creationCheck['allowed']) {
            $message = match($creationCheck['reason']) {
                'daily_limit_reached' => 'Daily account creation limit reached. Try again tomorrow.',
                'total_limit_reached' => 'Maximum accounts per IP address reached.',
                'too_soon' => 'Please wait before creating another account.',
                default => 'Account creation temporarily restricted.'
            };
            sendError(429, $message, '', $creationCheck['retry_after'] ?? null);
        }
        
        // Check for suspicious activity
        if ($rateLimiter->detectSuspiciousActivity($clientIP)) {
            sendError(429, 'Suspicious activity detected. Please try again later.', '', 3600);
        }
        
        $username = trim($_POST['username'] ?? '');
        $validation = validateUsername($username);
        if (!$validation['valid']) {
            sendError(400, $validation['error']);
        }
        $username = $validation['username'];

        // Check if username is taken
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            sendError(409, 'Username already taken');
        }

        // Generate user ID
        $userId = random_bytes(16);
        $userIdBase64 = base64_encode($userId);

        // Create RP entity
        $rpEntity = new PublicKeyCredentialRpEntity(
            $config['rp_name'],
            $config['rp_id']
        );

        // Create user entity
        $userEntity = new PublicKeyCredentialUserEntity(
            $username,
            $userId,
            $username
        );

        // Generate challenge
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        
        // Store in session with expiration
        $_SESSION['register_challenge'] = $challenge;
        $_SESSION['register_username'] = $username;
        $_SESSION['register_user_id'] = $userIdBase64;
        $_SESSION['register_expires'] = time() + 300; // 5 minute expiration
        $_SESSION['register_ip'] = $clientIP; // Prevent session hijacking

        // Create options
        $creationOptions = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            $challenge,
            [
                new \Webauthn\PublicKeyCredentialParameters('public-key', -7),   // ES256
                new \Webauthn\PublicKeyCredentialParameters('public-key', -257)  // RS256
            ]
        );

        $_SESSION['register_creation_options'] = serialize($creationOptions);

        echo json_encode($creationOptions);
        exit;

    } catch (Exception $e) {
        sendError(500, 'Failed to generate registration options', $e->getMessage());
    }
}

// STEP 2: Process registration
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        // Validate session
        if (!isset($_SESSION['register_expires']) || $_SESSION['register_expires'] < time()) {
            sendError(400, 'Registration session expired');
        }
        
        if (!isset($_SESSION['register_ip']) || $_SESSION['register_ip'] !== $clientIP) {
            sendError(400, 'Session IP mismatch - possible security issue');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError(400, 'Invalid JSON input');
        }

        $username = $_SESSION['register_username'] ?? null;
        $userIdBase64 = $_SESSION['register_user_id'] ?? null;
        $creationOptions = isset($_SESSION['register_creation_options']) ? 
            unserialize($_SESSION['register_creation_options']) : null;

        if (!$username || !$userIdBase64 || !$creationOptions) {
            sendError(400, 'Session expired or invalid');
        }

        // Load credential
        $attestationStatementSupportManager = new \Webauthn\AttestationStatement\AttestationStatementSupportManager();
        $attestationObjectLoader = new \Webauthn\AttestationStatement\AttestationObjectLoader($attestationStatementSupportManager);
        $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        $credential = $credentialLoader->loadArray($input);

        // Validate
        $validator = new AuthenticatorAttestationResponseValidator();
        $publicKeyCredentialSource = $validator->check(
            $credential->getResponse(),
            $creationOptions,
            $config['webauthn_origin']
        );

        // Convert binary credential ID to base64url string
        $credentialIdBinary = $publicKeyCredentialSource->getPublicKeyCredentialId();
        $credentialIdBase64url = binaryToBase64url($credentialIdBinary);
        
        // Begin transaction for data consistency
        $pdo->beginTransaction();
        
        try {
            // Insert user
            $stmt = $pdo->prepare('INSERT INTO users (username, user_id, passkey_id, passkey_public_key) VALUES (?, ?, ?, ?)');
            $result = $stmt->execute([
                $username,
                $userIdBase64,
                $credentialIdBase64url,
                $publicKeyCredentialSource->getCredentialPublicKey()
            ]);

            if (!$result) {
                throw new Exception('Failed to create user account');
            }

            $userId = $pdo->lastInsertId();
            
			// Create initial user stats (all other columns pick up their DEFAULTs)
			$stmt = $pdo->prepare('
				INSERT INTO user_stats (user_id)
				VALUES (?)
			');
			$stmt->execute([$userId]);

            
            // Create default user preferences
            $stmt = $pdo->prepare('
                INSERT INTO user_preferences (user_id) VALUES (?)
            ');
            $stmt->execute([$userId]);
            
            // Log successful registration
            $stmt = $pdo->prepare('
                INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $username,
                'passkey_register',
                'New account created with passkey',
                $clientIP,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Clear registration session data
        unset($_SESSION['register_username'], $_SESSION['register_challenge'], 
              $_SESSION['register_creation_options'], $_SESSION['register_user_id'],
              $_SESSION['register_expires'], $_SESSION['register_ip']);

        echo json_encode([
            'ok' => true, 
            'message' => 'Registration successful',
            'username' => $username
        ]);
        exit;

    } catch (Exception $e) {
        sendError(500, 'Registration failed', $e->getMessage());
    }
}

sendError(405, 'Method not allowed');
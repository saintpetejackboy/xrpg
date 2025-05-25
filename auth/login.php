<?php
// /auth/login.php - Debug version with extensive logging
require_once __DIR__ . '/../config/db.php';
$config = require __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';
require_once __DIR__ . '/RateLimiter.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Symfony\Component\Uid\Uuid; // Add this import

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$rateLimiter = new RateLimiter($pdo);
$clientIP    = RateLimiter::getClientIP();

// Debug logging function
function debugLog($message, $data = null) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] LOGIN DEBUG: $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . (is_string($data) ? $data : json_encode($data));
    }
    error_log($logEntry);
}

function base64urlToBinary(string $data): string {
    $base64 = strtr($data, '-_', '+/');
    return base64_decode(str_pad($base64, strlen($base64) % 4, '=', STR_PAD_RIGHT));
}

function binaryToBase64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

class DebugPasskeyRepository implements \Webauthn\PublicKeyCredentialSourceRepository {
    private array $user;
    private \PDO  $pdo;
    private string $ip;

    public function __construct(array $user, \PDO $pdo, string $ip) {
        $this->user = $user;
        $this->pdo  = $pdo;
        $this->ip   = $ip;
        debugLog("Repository created for user", ['user_id' => $user['id'], 'username' => $user['username']]);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?\Webauthn\PublicKeyCredentialSource {
        debugLog("Looking for credential", ['raw_id_length' => strlen($publicKeyCredentialId)]);
        
        // Convert binary credential ID to base64url for database lookup
        $credentialIdBase64url = binaryToBase64url($publicKeyCredentialId);
        
        debugLog("Converted credential ID", [
            'base64url' => $credentialIdBase64url,
            'original_bytes' => bin2hex($publicKeyCredentialId)
        ]);

        // Look up passkey in user_passkeys table
        $stmt = $this->pdo->prepare('
            SELECT credential_id, public_key, LENGTH(public_key) as key_length, device_name, last_used 
            FROM user_passkeys 
            WHERE user_id = ? AND credential_id = ?
        ');
        $stmt->execute([$this->user['id'], $credentialIdBase64url]);
        $passkey = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($passkey) {
            debugLog("Passkey found in database", [
                'credential_id' => $passkey['credential_id'],
                'key_length' => $passkey['key_length'],
                'device_name' => $passkey['device_name'],
                'last_used' => $passkey['last_used'],
                'key_type' => gettype($passkey['public_key']),
                'key_first_10_bytes' => bin2hex(substr($passkey['public_key'], 0, 10))
            ]);
            
            $this->log('login', 'Passkey found in user_passkeys table');
            
            // Update last_used timestamp
            try {
                $updateStmt = $this->pdo->prepare('
                    UPDATE user_passkeys 
                    SET last_used = NOW() 
                    WHERE user_id = ? AND credential_id = ?
                ');
                $updateStmt->execute([$this->user['id'], $credentialIdBase64url]);
                debugLog("Updated last_used timestamp");
            } catch (Exception $e) {
                debugLog("Failed to update last_used", $e->getMessage());
            }
            
            return $this->buildCredentialSource($publicKeyCredentialId, $passkey['public_key']);
        }

        // If not found, let's see what passkeys exist for this user
        $debugStmt = $this->pdo->prepare('
            SELECT credential_id, LENGTH(public_key) as key_length, device_name 
            FROM user_passkeys 
            WHERE user_id = ?
        ');
        $debugStmt->execute([$this->user['id']]);
        $allPasskeys = $debugStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        debugLog("Available passkeys for user", [
            'user_id' => $this->user['id'],
            'passkey_count' => count($allPasskeys),
            'passkeys' => $allPasskeys
        ]);

        $this->log('fail', 'Credential not found for user');
        return null;
    }

    public function findAllForUserEntity(\Webauthn\PublicKeyCredentialUserEntity $userEntity): array {
        debugLog("findAllForUserEntity called (not used in authentication)");
        return [];
    }

    public function saveCredentialSource(\Webauthn\PublicKeyCredentialSource $credentialSource): void {
        debugLog("saveCredentialSource called (not used in authentication)");
    }

    private function buildCredentialSource(string $credentialId, string $publicKey): \Webauthn\PublicKeyCredentialSource {
        debugLog("Building credential source", [
            'credential_id_length' => strlen($credentialId),
            'public_key_length' => strlen($publicKey),
            'public_key_type' => gettype($publicKey),
            'user_handle_b64' => $this->user['user_id']
        ]);
        
        $userHandle = base64_decode($this->user['user_id']);
        
        debugLog("User handle decoded", [
            'original' => $this->user['user_id'],
            'decoded_length' => strlen($userHandle),
            'decoded_hex' => bin2hex($userHandle)
        ]);
        
        // Create a nil UUID for the AAGUID since we don't store it
        $nilUuid = Uuid::fromString('00000000-0000-0000-0000-000000000000');
        
        debugLog("Created nil UUID for AAGUID", [
            'uuid_string' => $nilUuid->toRfc4122()
        ]);
        
        $source = new \Webauthn\PublicKeyCredentialSource(
            $credentialId,
            'public-key',
            [],
            'none',
            new EmptyTrustPath(),
            $nilUuid, // Fixed: Use nil UUID instead of null
            $publicKey,
            $userHandle,
            0
        );
        
        debugLog("Credential source built successfully");
        return $source;
    }

    private function log(string $type, string $description): void {
        // Valid event types from ENUM definition
        $validTypes = ['login', 'logout', 'fail', 'passkey_register', 'password_reset', 'permission_change', 'other'];
        $eventType = in_array($type, $validTypes) ? $type : 'other';
        
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
            debugLog("Logged authentication event", ['type' => $eventType, 'description' => $description]);
        } catch (\Throwable $e) {
            debugLog("Failed to log authentication event", $e->getMessage());
        }
    }
}

function sendError(int $code, string $message, string $details = null, int $retryAfter = null, bool $allowFallback = false): void {
    debugLog("Sending error response", [
        'code' => $code,
        'message' => $message,
        'details' => $details,
        'allow_fallback' => $allowFallback
    ]);
    
    http_response_code($code);
    $response = ['error' => $message];
    
    if ($details) $response['details'] = $details;
    if ($retryAfter) {
        header("Retry-After: $retryAfter");
        $response['retry_after'] = $retryAfter;
    }
    if ($allowFallback) $response['allow_fallback'] = true;
    
    echo json_encode($response);
    exit;
}

// STEP 1: Generate authentication options
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("Starting login step 1 (options generation)");
    
    $username = trim($_POST['username'] ?? '');
    if (!$username) {
        debugLog("Missing username");
        sendError(400, 'Missing username');
    }

    debugLog("Looking up user", $username);

    // Find user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$user) {
        debugLog("User not found", $username);
        sendError(404, 'User not found');
    }

    debugLog("User found", [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'user_id_b64' => $user['user_id'],
        'has_fallback_password' => !empty($user['fallback_password_hash'])
    ]);

    // Get all passkeys for this user from user_passkeys table
    $stmt = $pdo->prepare('
        SELECT credential_id, LENGTH(public_key) as key_length, device_name, created_at, last_used 
        FROM user_passkeys 
        WHERE user_id = ?
    ');
    $stmt->execute([$user['id']]);
    $userPasskeys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    debugLog("Found passkeys for user", [
        'passkey_count' => count($userPasskeys),
        'passkeys' => $userPasskeys
    ]);

    if (empty($userPasskeys)) {
        debugLog("No passkeys found for user");
        sendError(404, 'No passkeys found for this user');
    }

    // Build allowCredentials array
    $allowCredentials = [];
    foreach ($userPasskeys as $passkey) {
        try {
            $credentialIdBinary = base64urlToBinary($passkey['credential_id']);
            $allowCredentials[] = new PublicKeyCredentialDescriptor(
                'public-key',
                $credentialIdBinary
            );
            
            debugLog("Added credential to allowCredentials", [
                'credential_id_b64url' => $passkey['credential_id'],
                'binary_length' => strlen($credentialIdBinary),
                'device_name' => $passkey['device_name']
            ]);
        } catch (Exception $e) {
            debugLog("Failed to process credential", [
                'credential_id' => $passkey['credential_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    if (empty($allowCredentials)) {
        debugLog("No valid credentials could be processed");
        sendError(500, 'No valid credentials found');
    }

    // Generate challenge
    $challengeRaw = random_bytes(32);
    $challengeBase64url = binaryToBase64url($challengeRaw);

    debugLog("Generated challenge", [
        'challenge_b64url' => $challengeBase64url,
        'challenge_raw_length' => strlen($challengeRaw),
        'challenge_hex' => bin2hex($challengeRaw)
    ]);

    // Store authentication data in session
    $_SESSION['login_challenge'] = $challengeBase64url;
    $_SESSION['login_ip'] = $clientIP;
    $_SESSION['login_user_agent_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $_SESSION['login_user'] = $user;
    $_SESSION['login_expires'] = time() + 300; // 5 minute expiration

    // Create request options
    $requestOptions = (new PublicKeyCredentialRequestOptions(
        $challengeRaw,
        $config['rp_id'],
        $allowCredentials
    ))
    ->setTimeout(60000)
    ->setUserVerification('discouraged');

    $_SESSION['login_options'] = serialize($requestOptions);

    debugLog("Stored session data", [
        'expires' => $_SESSION['login_expires'],
        'ip' => $clientIP,
        'rp_id' => $config['rp_id']
    ]);

    // Return options to client
    $responseData = [
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
    ];

    debugLog("Sending response to client", $responseData);

    echo json_encode($responseData);
    exit;
}

// STEP 2: Verify authentication
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    debugLog("Starting login step 2 (credential verification)");
    
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    debugLog("Received credential assertion", ['keys' => array_keys($body)]);
    
    // Validate session
    if (!isset($_SESSION['login_options'], $_SESSION['login_user'])) {
        debugLog("Missing session data", [
            'has_options' => isset($_SESSION['login_options']),
            'has_user' => isset($_SESSION['login_user'])
        ]);
        sendError(400, 'No login session in progress');
    }
    
    if (!isset($_SESSION['login_expires']) || $_SESSION['login_expires'] < time()) {
        debugLog("Session expired", [
            'expires' => $_SESSION['login_expires'] ?? 'not set',
            'current_time' => time()
        ]);
        sendError(400, 'Login session expired');
    }
    
    if ($_SESSION['login_ip'] !== $clientIP) {
        debugLog("IP mismatch", [
            'session_ip' => $_SESSION['login_ip'],
            'client_ip' => $clientIP
        ]);
        sendError(400, 'IP address mismatch');
    }
    
    if (hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '') !== $_SESSION['login_user_agent_hash']) {
        debugLog("User agent mismatch");
        sendError(400, 'User agent mismatch');
    }

    debugLog("Session validation passed");

    $user = $_SESSION['login_user'];
    $requestOptions = unserialize($_SESSION['login_options']);

    debugLog("Retrieved session data", [
        'user' => $user['username'],
        'user_id' => $user['id'],
        'options_type' => get_class($requestOptions)
    ]);

    try {
        // Load credential from client response
        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
        $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        
        debugLog("Created credential loader");
        
        $credential = $credentialLoader->loadArray($body);
        
        debugLog("Loaded credential from client", [
            'id' => $credential->getId(),
            'raw_id_length' => strlen($credential->getRawId()),
            'type' => $credential->getType(),
            'raw_id_hex' => bin2hex($credential->getRawId())
        ]);
        
        // Create repository and validator
        $repository = new DebugPasskeyRepository($user, $pdo, $clientIP);
        $validator = new AuthenticatorAssertionResponseValidator($repository);

        debugLog("Created repository and validator");

        // Verify the assertion
        debugLog("Starting assertion verification", [
            'origin' => $config['webauthn_origin'],
            'user_handle' => $user['user_id']
        ]);
        
        $validator->check(
            $credential->getRawId(),
            $credential->getResponse(),
            $requestOptions,
            $config['webauthn_origin'],
            base64_decode($user['user_id'])
        );

        debugLog("Assertion verification successful!");

        // Authentication successful - create session
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'type' => 'player',
            'login_ip' => $clientIP,
            'login_time' => time()
        ];

        debugLog("Created user session", ['username' => $user['username'], 'user_id' => $user['id']]);

        // Log successful login
        $stmt = $pdo->prepare('
            INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user['id'], 
            $user['username'],
            'login', 
            'Successful WebAuthn login',
            $clientIP, 
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        debugLog("Logged successful authentication");

        // Clear login session data
        foreach (['login_challenge', 'login_options', 'login_user', 'login_ip', 'login_user_agent_hash', 'login_expires'] as $key) {
            unset($_SESSION[$key]);
        }

        debugLog("Cleared login session data");

        echo json_encode([
            'ok' => true,
            'username' => $user['username']
        ]);
        exit;

    } catch (\Throwable $e) {
        debugLog("Authentication failed with exception", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Check if user has fallback password set
        $allowFallback = !empty($user['fallback_password_hash']);
        
        debugLog("Checking fallback options", ['allow_fallback' => $allowFallback]);
        
        // Log failed login attempt
        $stmt = $pdo->prepare('
            INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $user['id'],
            $user['username'],
            'fail',
            'WebAuthn authentication failed: ' . $e->getMessage(),
            $clientIP,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        sendError(401, 'Authentication failed', $e->getMessage(), null, $allowFallback);
    }
}

debugLog("Invalid HTTP method", $_SERVER['REQUEST_METHOD']);
sendError(405, 'Method not allowed');
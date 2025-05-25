<?php
// /auth/register.php - Debug version with extensive logging
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

// Debug logging function
function debugLog($message, $data = null) {
    $logEntry = "[" . date('Y-m-d H:i:s') . "] REGISTER DEBUG: $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . (is_string($data) ? $data : json_encode($data));
    }
    error_log($logEntry);
}

// Initialize rate limiter
$rateLimiter = new RateLimiter($pdo);
$clientIP = RateLimiter::getClientIP();

function logError($message, $details = '') {
    debugLog("ERROR: $message", $details);
    error_log("WebAuthn Registration Error: $message " . ($details ? "Details: $details" : ""));
}

function sendError($code, $message, $details = '', $retryAfter = null) {
    debugLog("Sending error response", ['code' => $code, 'message' => $message, 'details' => $details]);
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
    debugLog("Starting registration step 1 (options generation)");
    
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
        
        debugLog("Rate limit check passed");
        
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
        
        debugLog("Creation limit check passed");
        
        // Check for suspicious activity
        if ($rateLimiter->detectSuspiciousActivity($clientIP)) {
            sendError(429, 'Suspicious activity detected. Please try again later.', '', 3600);
        }
        
        $username = trim($_POST['username'] ?? '');
        debugLog("Validating username", $username);
        
        $validation = validateUsername($username);
        if (!$validation['valid']) {
            sendError(400, $validation['error']);
        }
        $username = $validation['username'];

        // Check if username is taken
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            debugLog("Username already taken", $username);
            sendError(409, 'Username already taken');
        }

        debugLog("Username available", $username);

        // Generate user ID
        $userId = random_bytes(16);
        $userIdBase64 = base64_encode($userId);
        
        debugLog("Generated user ID", ['raw_length' => strlen($userId), 'base64' => $userIdBase64]);

        // Create RP entity
        $rpEntity = new PublicKeyCredentialRpEntity(
            $config['rp_name'],
            $config['rp_id']
        );

        debugLog("Created RP entity", ['name' => $config['rp_name'], 'id' => $config['rp_id']]);

        // Create user entity
        $userEntity = new PublicKeyCredentialUserEntity(
            $username,
            $userId,
            $username
        );

        debugLog("Created user entity", $username);

        // Generate challenge
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        
        debugLog("Generated challenge", ['challenge' => $challenge, 'length' => strlen($challenge)]);
        
        // Store in session with expiration
        $_SESSION['register_challenge'] = $challenge;
        $_SESSION['register_username'] = $username;
        $_SESSION['register_user_id'] = $userIdBase64;
        $_SESSION['register_expires'] = time() + 300; // 5 minute expiration
        $_SESSION['register_ip'] = $clientIP; // Prevent session hijacking

        debugLog("Stored session data", [
            'username' => $username,
            'user_id' => $userIdBase64,
            'expires' => $_SESSION['register_expires'],
            'ip' => $clientIP
        ]);

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

        debugLog("Registration options created successfully");

        echo json_encode($creationOptions);
        exit;

    } catch (Exception $e) {
        debugLog("Exception in step 1", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        sendError(500, 'Failed to generate registration options', $e->getMessage());
    }
}

// STEP 2: Process registration
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    debugLog("Starting registration step 2 (credential processing)");
    
    try {
        // Validate session
        if (!isset($_SESSION['register_expires']) || $_SESSION['register_expires'] < time()) {
            debugLog("Session expired", ['expires' => $_SESSION['register_expires'] ?? 'not set', 'current_time' => time()]);
            sendError(400, 'Registration session expired');
        }
        
        if (!isset($_SESSION['register_ip']) || $_SESSION['register_ip'] !== $clientIP) {
            debugLog("IP mismatch", ['session_ip' => $_SESSION['register_ip'] ?? 'not set', 'client_ip' => $clientIP]);
            sendError(400, 'Session IP mismatch - possible security issue');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            debugLog("Invalid JSON input");
            sendError(400, 'Invalid JSON input');
        }

        debugLog("Received credential data", ['keys' => array_keys($input)]);

        $username = $_SESSION['register_username'] ?? null;
        $userIdBase64 = $_SESSION['register_user_id'] ?? null;
        $creationOptions = isset($_SESSION['register_creation_options']) ? 
            unserialize($_SESSION['register_creation_options']) : null;

        if (!$username || !$userIdBase64 || !$creationOptions) {
            debugLog("Missing session data", [
                'username' => $username ? 'present' : 'missing',
                'user_id' => $userIdBase64 ? 'present' : 'missing',
                'options' => $creationOptions ? 'present' : 'missing'
            ]);
            sendError(400, 'Session expired or invalid');
        }

        debugLog("Session validation passed", $username);

        // Load credential
        $attestationStatementSupportManager = new \Webauthn\AttestationStatement\AttestationStatementSupportManager();
        $attestationObjectLoader = new \Webauthn\AttestationStatement\AttestationObjectLoader($attestationStatementSupportManager);
        $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        
        debugLog("Created credential loader");
        
        $credential = $credentialLoader->loadArray($input);
        
        debugLog("Loaded credential", ['id' => $credential->getId(), 'type' => $credential->getType()]);

        // Validate
        $validator = new AuthenticatorAttestationResponseValidator();
        
        debugLog("Starting credential validation", ['origin' => $config['webauthn_origin']]);
        
        $publicKeyCredentialSource = $validator->check(
            $credential->getResponse(),
            $creationOptions,
            $config['webauthn_origin']
        );

        debugLog("Credential validation successful");

        // Get credential details
        $credentialIdBinary = $publicKeyCredentialSource->getPublicKeyCredentialId();
        $credentialIdBase64url = binaryToBase64url($credentialIdBinary);
        $publicKeyBinary = $publicKeyCredentialSource->getCredentialPublicKey();
        
        debugLog("Credential details", [
            'credential_id_length' => strlen($credentialIdBinary),
            'credential_id_base64url' => $credentialIdBase64url,
            'public_key_length' => strlen($publicKeyBinary),
            'public_key_type' => gettype($publicKeyBinary),
            'public_key_first_10_bytes' => bin2hex(substr($publicKeyBinary, 0, 10))
        ]);
        
        // Begin transaction for data consistency
        $pdo->beginTransaction();
        
        debugLog("Started database transaction");
        
        try {
            // Insert user (no passkey columns in users table anymore)
            $stmt = $pdo->prepare('INSERT INTO users (username, user_id) VALUES (?, ?)');
            $result = $stmt->execute([$username, $userIdBase64]);
            if (!$result) {
                throw new Exception('Failed to create user account');
            }
            $userId = $pdo->lastInsertId();
            
            debugLog("Created user account", ['user_id' => $userId, 'username' => $username]);

            // Insert passkey into user_passkeys table - STORE AS BINARY
            $stmt = $pdo->prepare('INSERT INTO user_passkeys (user_id, credential_id, public_key, device_name) VALUES (?, ?, ?, ?)');
            $result = $stmt->execute([
                $userId,
                $credentialIdBase64url,
                $publicKeyBinary, // Store the raw binary data
                'Primary Device'
            ]);
            
            if (!$result) {
                throw new Exception('Failed to store passkey');
            }
            
            debugLog("Stored passkey", [
                'user_id' => $userId,
                'credential_id' => $credentialIdBase64url,
                'public_key_stored_length' => strlen($publicKeyBinary)
            ]);

            // Verify what was actually stored
            $verifyStmt = $pdo->prepare('SELECT credential_id, public_key, LENGTH(public_key) as key_length FROM user_passkeys WHERE user_id = ?');
            $verifyStmt->execute([$userId]);
            $stored = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            debugLog("Verified stored data", [
                'stored_credential_id' => $stored['credential_id'],
                'stored_key_length' => $stored['key_length'],
                'stored_key_type' => gettype($stored['public_key']),
                'stored_key_first_10_bytes' => bin2hex(substr($stored['public_key'], 0, 10))
            ]);

            // Create initial user stats
            $stmt = $pdo->prepare('INSERT INTO user_stats (user_id) VALUES (?)');
            $stmt->execute([$userId]);
            
            debugLog("Created user stats");
            
            // Create default user preferences
            $stmt = $pdo->prepare('INSERT INTO user_preferences (user_id) VALUES (?)');
            $stmt->execute([$userId]);
            
            debugLog("Created user preferences");
            
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
            
            debugLog("Logged registration event");
            
            $pdo->commit();
            
            debugLog("Transaction committed successfully");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            debugLog("Transaction rolled back", ['error' => $e->getMessage()]);
            throw $e;
        }

        // Clear registration session data
        unset($_SESSION['register_username'], $_SESSION['register_challenge'], 
              $_SESSION['register_creation_options'], $_SESSION['register_user_id'],
              $_SESSION['register_expires'], $_SESSION['register_ip']);

        debugLog("Registration completed successfully", $username);

        echo json_encode([
            'ok' => true, 
            'message' => 'Registration successful',
            'username' => $username
        ]);
        exit;

    } catch (Exception $e) {
        debugLog("Registration failed", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        sendError(500, 'Registration failed', $e->getMessage());
    }
}

sendError(405, 'Method not allowed');
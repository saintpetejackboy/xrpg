<?php
// /auth/register.php
require_once __DIR__ . '/../config/db.php';
$config = require __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAttestationResponseValidator;

session_start();

header('Content-Type: application/json');

function logError($message, $details = '') {
    error_log("WebAuthn Registration Error: $message " . ($details ? "Details: $details" : ""));
}

function sendError($code, $message, $details = '') {
    logError($message, $details);
    http_response_code($code);
    echo json_encode(['error' => $message, 'details' => $details]);
    exit;
}

// STEP 1: Generate WebAuthn registration options
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        if (!$username) {
            sendError(400, 'Missing username');
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            sendError(400, 'Username must be between 3 and 50 characters');
        }

        // Check if username taken
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            sendError(409, 'Username already taken');
        }

        // Generate a stable user ID for this user
        $userId = random_bytes(16);
        $userIdBase64 = base64_encode($userId);

        // Build RP entity
        $rpEntity = new PublicKeyCredentialRpEntity(
            $config['rp_name'],
            $config['rp_id']
        );

        // Build User entity
        $userEntity = new PublicKeyCredentialUserEntity(
            $username,
            $userId,    // binary user id
            $username   // display name
        );

        // Generate challenge as base64url
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $_SESSION['register_challenge'] = $challenge;
        $_SESSION['register_username'] = $username;
        $_SESSION['register_user_id'] = $userIdBase64; // Store as base64 for database

        // Build creation options
        $creationOptions = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            $challenge,
            [
                new \Webauthn\PublicKeyCredentialParameters('public-key', -7),   // ES256
                new \Webauthn\PublicKeyCredentialParameters('public-key', -257)  // RS256
            ],
            null,       // authenticatorSelection
            'none',     // attestation
            [],         // excludeCredentials
            null        // extensions
        );

        $_SESSION['register_creation_options'] = serialize($creationOptions);

        echo json_encode($creationOptions);
        exit;

    } catch (Exception $e) {
        sendError(500, 'Failed to generate registration options', $e->getMessage());
    }
}

// STEP 2: Receive credential from JS and store in DB
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
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

        // Load and validate the credential
        $attestationStatementSupportManager = new \Webauthn\AttestationStatement\AttestationStatementSupportManager();
        $attestationObjectLoader = new \Webauthn\AttestationStatement\AttestationObjectLoader($attestationStatementSupportManager);
        $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        $credential = $credentialLoader->loadArray($input);

        $validator = new AuthenticatorAttestationResponseValidator();
        $publicKeyCredentialSource = $validator->check(
            $credential->getResponse(),
            $creationOptions,
            $config['webauthn_origin']
        );

        // Store in database with correct format
        $stmt = $pdo->prepare('INSERT INTO users (username, user_id, passkey_id, passkey_public_key) VALUES (?, ?, ?, ?)');
        $result = $stmt->execute([
            $username,
            $userIdBase64,  // Store user ID as base64
            $publicKeyCredentialSource->getPublicKeyCredentialId(),  // Store credential ID as base64url string
            $publicKeyCredentialSource->getCredentialPublicKey()     // Store public key as binary
        ]);

        if (!$result) {
            sendError(500, 'Failed to save user to database');
        }

        // Clear session data
        unset($_SESSION['register_username'], $_SESSION['register_challenge'], 
              $_SESSION['register_creation_options'], $_SESSION['register_user_id']);

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
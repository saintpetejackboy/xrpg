<?php
// /auth/passkey-manage.php - Add/Remove WebAuthn passkeys for existing user
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\AttestationObjectLoader;

// Only allow logged-in users
if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$config = require __DIR__ . '/../config/environment.php';

// Helper
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

// ---- Step 1: Generate registration options for this user ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get current user
    $stmt = $pdo->prepare('SELECT user_id, username FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // User entity (use user_id from registration)
    $userEntity = new PublicKeyCredentialUserEntity(
        $user['username'],
        base64_decode($user['user_id']),
        $user['username']
    );
    $rpEntity = new PublicKeyCredentialRpEntity($config['rp_name'], $config['rp_id']);
    $challenge = random_bytes(32);

    // Exclude credentials user already has
    $stmt = $pdo->prepare('SELECT credential_id FROM user_passkeys WHERE user_id=?');
    $stmt->execute([$userId]);
    $exclude = [];
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $exclude[] = [
            'type' => 'public-key',
            'id' => base64url_decode($row['credential_id'])
        ];
    }

    $options = new PublicKeyCredentialCreationOptions(
        $rpEntity,
        $userEntity,
        $challenge,
        [
            new \Webauthn\PublicKeyCredentialParameters('public-key', -7),   // ES256
            new \Webauthn\PublicKeyCredentialParameters('public-key', -257)  // RS256
        ]
    );
    $options->excludeCredentials = $exclude;

    // Save challenge in session for verification
    $_SESSION['addkey_challenge'] = base64url_encode($challenge);

    // Respond
    echo json_encode([
        'challenge' => base64url_encode($challenge),
        'rp' => [
            'name' => $config['rp_name'],
            'id' => $config['rp_id']
        ],
        'user' => [
            'id' => $user['user_id'], // base64
            'name' => $user['username'],
            'displayName' => $user['username']
        ],
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],
            ['type' => 'public-key', 'alg' => -257]
        ],
        'timeout' => 60000,
        'excludeCredentials' => array_map(function($c) {
            return [
                'type' => 'public-key',
                'id' => base64url_encode($c['id'])
            ];
        }, $exclude)
    ]);
    exit;
}

// ---- Step 2: Complete registration, save the device for this user ----
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
        exit;
    }

    // Load credential
    $attestationStatementSupportManager = new AttestationStatementSupportManager();
    $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);
    $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
    $credential = $credentialLoader->loadArray($input);

    // Retrieve registration challenge
    $challenge = $_SESSION['addkey_challenge'] ?? null;
    if (!$challenge) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Session expired, please try again']);
        exit;
    }

    // Get user again (just to check user_id)
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'User not found']);
        exit;
    }

    $creationOptions = new PublicKeyCredentialCreationOptions(
        new PublicKeyCredentialRpEntity($config['rp_name'], $config['rp_id']),
        new PublicKeyCredentialUserEntity(
            $username,
            base64_decode($user['user_id']),
            $username
        ),
        base64url_decode($challenge)
    );

    // Validate and get public key from credential
    $validator = new AuthenticatorAttestationResponseValidator();
    $publicKeyCredentialSource = $validator->check(
        $credential->getResponse(),
        $creationOptions,
        $config['webauthn_origin']
    );

    // Save credential for this user (as base64url, NOT raw binary)
    $credentialId = base64url_encode($publicKeyCredentialSource->getPublicKeyCredentialId());
    $publicKey = base64url_encode($publicKeyCredentialSource->getCredentialPublicKey());

    $deviceName = 'Device ' . date('Y-m-d H:i:s'); // Or let client supply

    // Prevent duplicates
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_passkeys WHERE user_id=? AND credential_id=?');
    $stmt->execute([$userId, $credentialId]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['ok' => false, 'error' => 'Device already registered']);
        exit;
    }

    // Store new passkey
    $stmt = $pdo->prepare('
        INSERT INTO user_passkeys (user_id, credential_id, public_key, device_name, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([
        $userId,
        $credentialId,
        $publicKey,
        $deviceName
    ]);

    // Success!
    echo json_encode(['ok' => true]);
    exit;
}

// ---- Step 3: Remove device ----
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $passkeyId = (int)($input['id'] ?? 0);

    if (!$passkeyId) {
        echo json_encode(['ok' => false, 'error' => 'Invalid passkey id']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM user_passkeys WHERE id=? AND user_id=?');
    $stmt->execute([$passkeyId, $userId]);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

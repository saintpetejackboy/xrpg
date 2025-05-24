<?php
// /auth/login.php
require_once __DIR__ . '/../config/db.php';
$config = require __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../thirdparty/vendor/autoload.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialSource;

session_start();

header('Content-Type: application/json');

function logError($message, $details = '') {
    error_log("WebAuthn Login Error: $message " . ($details ? "Details: $details" : ""));
}

function sendError($code, $message, $details = '') {
    logError($message, $details);
    http_response_code($code);
    echo json_encode(['error' => $message, 'details' => $details]);
    exit;
}

// Step 1: Generate WebAuthn login options
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        if (!$username) {
            sendError(400, 'Missing username');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if (!$user) {
            sendError(404, 'User not found');
        }

        // The passkey_id should be stored as base64url in the database
        // Create credential descriptor with the stored ID
        $allowCredentials = [
            new PublicKeyCredentialDescriptor(
                'public-key',
                $user['passkey_id'] // Should be base64url encoded string
            )
        ];

        // Generate challenge as base64url
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $_SESSION['login_challenge'] = $challenge;
        $_SESSION['login_user_id'] = $user['id'];

        $requestOptions = new PublicKeyCredentialRequestOptions(
            $challenge,
            timeout: 60000,
            rpId: $config['rp_id'],
            allowCredentials: $allowCredentials,
            userVerification: 'discouraged'
        );

        $_SESSION['login_request_options'] = serialize($requestOptions);
        echo json_encode($requestOptions);
        exit;

    } catch (Exception $e) {
        sendError(500, 'Failed to generate login options', $e->getMessage());
    }
}

// Step 2: Validate assertion
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            sendError(400, 'Invalid JSON input');
        }

        $challenge = $_SESSION['login_challenge'] ?? null;
        $userId = $_SESSION['login_user_id'] ?? null;
        $requestOptions = isset($_SESSION['login_request_options']) ? unserialize($_SESSION['login_request_options']) : null;

        if (!$challenge || !$userId || !$requestOptions) {
            sendError(400, 'Session expired or invalid');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            sendError(404, 'User not found');
        }

        // Load and validate the credential
        $attestationStatementSupportManager = new \Webauthn\AttestationStatement\AttestationStatementSupportManager();
        $attestationObjectLoader = new \Webauthn\AttestationStatement\AttestationObjectLoader($attestationStatementSupportManager);
        $credentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
        $credential = $credentialLoader->loadArray($input);

        // Create a simple credential repository with fixed TrustPath implementation
        $credentialRepository = new class($user) implements \Webauthn\PublicKeyCredentialSourceRepository {
            private $user;
            
            public function __construct($user) {
                $this->user = $user;
            }
            
            public function findOneByCredentialId(string $publicKeyCredentialId): ?\Webauthn\PublicKeyCredentialSource {
                if ($publicKeyCredentialId === $this->user['passkey_id']) {
                    return new \Webauthn\PublicKeyCredentialSource(
                        $this->user['passkey_id'],
                        'public-key',
                        [],
                        'none',
                        new class implements \Webauthn\TrustPath\TrustPath {
                            public function jsonSerialize(): array { 
                                return []; 
                            }
                            public function isTrustChainValid(): bool { 
                                return true; 
                            }
                            public static function createFromArray(array $data): \Webauthn\TrustPath\TrustPath {
                                return new self();
                            }
                        },
                        base64_decode($this->user['user_id']),
                        $this->user['passkey_public_key']
                    );
                }
                return null;
            }
            
            public function findAllForUserEntity(\Webauthn\PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
                return [];
            }
            
            public function saveCredentialSource(\Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource): void {
                // Not needed for login validation
            }
        };

        $validator = new AuthenticatorAssertionResponseValidator($credentialRepository);
        
        // Validate the assertion
        $publicKeyCredentialSource = $validator->check(
            $credential->getRawId(),
            $credential->getResponse(),
            $requestOptions,
            $config['webauthn_origin'],
            base64_decode($user['user_id'])
        );

        // Login successful - set session
        $_SESSION['user'] = [
            'id' => $user['id'], 
            'username' => $user['username'], 
            'type' => 'player'
        ];

        // Clear session challenge
        unset($_SESSION['login_challenge'], $_SESSION['login_user_id'], $_SESSION['login_request_options']);

        echo json_encode(['ok' => true, 'message' => 'Login successful']);
        exit;

    } catch (Exception $e) {
        sendError(401, 'Authentication failed', $e->getMessage());
    }
}

sendError(405, 'Method not allowed');
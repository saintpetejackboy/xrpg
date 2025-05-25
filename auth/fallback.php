<?php
// /auth/fallback.php - Handle fallback passphrase set/remove
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user']['id'];

// Read input
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'set') {
    $passphrase = $data['passphrase'] ?? '';
    if (strlen($passphrase) < 6) {
        echo json_encode(['ok' => false, 'error' => 'Passphrase too short']);
        exit;
    }
    $hash = password_hash($passphrase, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET fallback_password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'remove') {
    $stmt = $pdo->prepare("UPDATE users SET fallback_password_hash = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Invalid action']);

<?php
// /auth/logout.php - Secure logout handler
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = null;
$userId = null;

// Capture user info before clearing session
if (isset($_SESSION['user'])) {
    $username = $_SESSION['user']['username'] ?? null;
    $userId = $_SESSION['user']['id'] ?? null;
}

// Log the logout event
if ($username && $userId) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $stmt = $pdo->prepare('INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $username,
            'logout',
            'User logged out',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Failed to log logout: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Return success response
echo json_encode([
    'ok' => true,
    'message' => 'Logged out successfully'
]);
exit;
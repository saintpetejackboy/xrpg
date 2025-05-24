<?php
// /players/save-settings.php - Save user preferences to database

header('Content-Type: application/json');

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Handle session user data (might be JSON string or array)
$user = $_SESSION['user'];
if (is_string($user)) {
    $user = json_decode($user, true);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid user session data']);
        exit;
    }
}

$userId = $user['id'];

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $requiredFields = ['theme_mode', 'accent_color', 'accent_secondary', 'font_family', 'border_radius', 'shadow_intensity', 'ui_opacity'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate theme_mode
    if (!in_array($data['theme_mode'], ['dark', 'light'])) {
        throw new Exception('Invalid theme_mode. Must be "dark" or "light"');
    }
    
    // Validate font_family
    if (!in_array($data['font_family'], ['sans', 'mono', 'game', 'display'])) {
        throw new Exception('Invalid font_family. Must be one of: sans, mono, game, display');
    }
    
    // Validate color formats (hex colors)
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['accent_color'])) {
        throw new Exception('Invalid accent_color format. Must be a valid hex color');
    }
    
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data['accent_secondary'])) {
        throw new Exception('Invalid accent_secondary format. Must be a valid hex color');
    }
    
    // Validate numeric ranges
    $borderRadius = intval($data['border_radius']);
    if ($borderRadius < 9 || $borderRadius > 40) {
        throw new Exception('border_radius must be between 9 and 40');
    }
    
    $shadowIntensity = floatval($data['shadow_intensity']);
    if ($shadowIntensity < 0.05 || $shadowIntensity > 0.5) {
        throw new Exception('shadow_intensity must be between 0.05 and 0.5');
    }
    
    $uiOpacity = floatval($data['ui_opacity']);
    if ($uiOpacity < 0.8 || $uiOpacity > 1.0) {
        throw new Exception('ui_opacity must be between 0.8 and 1.0');
    }
    
    // Connect to database
    require_once __DIR__ . '/../config/db.php';
    
    // Check if user preferences exist
    $stmt = $pdo->prepare('SELECT id FROM user_preferences WHERE user_id = ?');
    $stmt->execute([$userId]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update existing preferences
        $stmt = $pdo->prepare('
            UPDATE user_preferences 
            SET theme_mode = ?, accent_color = ?, accent_secondary = ?, font_family = ?, 
                border_radius = ?, shadow_intensity = ?, ui_opacity = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ');
        $result = $stmt->execute([
            $data['theme_mode'],
            $data['accent_color'],
            $data['accent_secondary'],
            $data['font_family'],
            $borderRadius,
            $shadowIntensity,
            $uiOpacity,
            $userId
        ]);
    } else {
        // Insert new preferences
        $stmt = $pdo->prepare('
            INSERT INTO user_preferences (user_id, theme_mode, accent_color, accent_secondary, font_family, border_radius, shadow_intensity, ui_opacity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $result = $stmt->execute([
            $userId,
            $data['theme_mode'],
            $data['accent_color'],
            $data['accent_secondary'],
            $data['font_family'],
            $borderRadius,
            $shadowIntensity,
            $uiOpacity
        ]);
    }
    
    if (!$result) {
        throw new Exception('Failed to save preferences to database');
    }
    
    // Log this activity
    try {
        $stmt = $pdo->prepare('
            INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userId,
            isset($user['username']) ? $user['username'] : 'unknown',
            'other',
            'Updated theme preferences',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Don't fail if logging fails
        error_log("Failed to log settings update: " . $e->getMessage());
    }
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Settings saved successfully',
        'data' => [
            'theme_mode' => $data['theme_mode'],
            'accent_color' => $data['accent_color'],
            'accent_secondary' => $data['accent_secondary'],
            'font_family' => $data['font_family'],
            'border_radius' => $borderRadius,
            'shadow_intensity' => $shadowIntensity,
            'ui_opacity' => $uiOpacity
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Settings save error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
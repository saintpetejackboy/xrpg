<?php
// /players/save-settings.php - API endpoint for saving user preferences

session_start();
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/load-preferences.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get user info
$userInfo = getUserInfo($_SESSION['user']);
$userId = $userInfo['id'];

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate and sanitize settings
$validSettings = [
    'theme_mode' => ['light', 'dark'],
    'font_family' => ['sans', 'mono', 'game', 'display']
];

$allowedKeys = [
    'theme_mode', 'accent_color', 'accent_secondary', 
    'border_radius', 'shadow_intensity', 'ui_opacity', 'font_family'
];

$settings = [];

foreach ($allowedKeys as $key) {
    if (!isset($data[$key])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $key"]);
        exit;
    }
    
    $value = $data[$key];
    
    // Validate specific fields
    switch ($key) {
        case 'theme_mode':
            if (!in_array($value, $validSettings['theme_mode'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid theme mode']);
                exit;
            }
            break;
            
        case 'font_family':
            if (!in_array($value, $validSettings['font_family'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid font family']);
                exit;
            }
            break;
            
        case 'accent_color':
        case 'accent_secondary':
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Invalid color format for $key"]);
                exit;
            }
            break;
            
        case 'border_radius':
            $value = intval($value);
            if ($value < 9 || $value > 40) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Border radius must be between 9 and 40']);
                exit;
            }
            break;
            
        case 'shadow_intensity':
            $value = floatval($value);
            if ($value < 0.05 || $value > 0.5) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Shadow intensity must be between 0.05 and 0.5']);
                exit;
            }
            break;
            
        case 'ui_opacity':
            $value = floatval($value);
            if ($value < 0.8 || $value > 1.0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'UI opacity must be between 0.8 and 1.0']);
                exit;
            }
            break;
    }
    
    $settings[$key] = $value;
}

try {
    // Check if user preferences exist
    $stmt = $pdo->prepare('SELECT id FROM user_preferences WHERE user_id = ?');
    $stmt->execute([$userId]);
    $existingPrefs = $stmt->fetch();
    
    if ($existingPrefs) {
        // Update existing preferences
        $stmt = $pdo->prepare('
            UPDATE user_preferences 
            SET theme_mode = ?, accent_color = ?, accent_secondary = ?, 
                border_radius = ?, shadow_intensity = ?, ui_opacity = ?, 
                font_family = ?, updated_at = NOW()
            WHERE user_id = ?
        ');
        $stmt->execute([
            $settings['theme_mode'],
            $settings['accent_color'],
            $settings['accent_secondary'],
            $settings['border_radius'],
            $settings['shadow_intensity'],
            $settings['ui_opacity'],
            $settings['font_family'],
            $userId
        ]);
    } else {
        // Create new preferences
        $stmt = $pdo->prepare('
            INSERT INTO user_preferences 
            (user_id, theme_mode, accent_color, accent_secondary, border_radius, shadow_intensity, ui_opacity, font_family, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([
            $userId,
            $settings['theme_mode'],
            $settings['accent_color'],
            $settings['accent_secondary'],
            $settings['border_radius'],
            $settings['shadow_intensity'],
            $settings['ui_opacity'],
            $settings['font_family']
        ]);
    }
    
    // Log the settings change
    try {
        $stmt = $pdo->prepare('
            INSERT INTO auth_log (user_id, event_type, description, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $userId,
            'settings_update',
            'User updated theme preferences',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the settings save
        error_log("Failed to log settings update: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Settings saved successfully',
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    error_log("Failed to save user preferences: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
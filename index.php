<?php
// Show all errors, warnings, and notices
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// XRPG Universal Router - index.php
// ---------------------------------
// All site entry traffic comes through here. This handles routing based on authentication state.
session_start();
require_once __DIR__ . '/config/environment.php';

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove trailing slash (except for root)
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Get current user
$user = $_SESSION['user'] ?? null;

// Handle API routes first (before authentication checks)
if (str_starts_with($path, '/api/')) {
    switch ($path) {
        case '/api/updates.php':
            require __DIR__ . '/api/updates.php';
            exit;
        case '/api/equipment.php':
            require __DIR__ . '/api/equipment.php';
            exit;
        case '/api/shop.php':
            require __DIR__ . '/api/shop.php';
            exit;
        case '/api/items.php':
            require __DIR__ . '/api/items.php';
            exit;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            exit;
    }
}

// Handle authentication routes
if (str_starts_with($path, '/auth/')) {
    switch ($path) {
        case '/auth/logout.php':
            require __DIR__ . '/auth/logout.php';
            exit;
        case '/auth/login.php':
        case '/auth/register.php':
            require __DIR__ . '/auth/' . basename($path);
            exit;
        default:
            http_response_code(404);
            echo '404 Not Found: Auth endpoint not found';
            exit;
    }
}

// ROUTING LOGIC BASED ON AUTHENTICATION STATE
if (!$user) {
    // Not logged in: Show the landing/welcome page for all non-auth routes
    require __DIR__ . '/pages/landing.php';
    exit;
}

// User is authenticated - route based on user type and path
if ($user['type'] === 'admin') {
    // Admin user routing
    if (str_starts_with($path, '/admin/')) {
        $adminFile = __DIR__ . $path;
        if (file_exists($adminFile)) {
            require $adminFile;
        } else {
            require __DIR__ . '/admin/admin-panel.php'; // Default admin page
        }
    } else {
        // Redirect admin users to admin panel for non-admin paths
        header('Location: /admin/admin-panel.php');
    }
    exit;
}

if ($user['type'] === 'player') {
    // Player routing - handle different player pages
    
    // Root path - go to player dashboard
    if ($path === '/' || $path === '') {
        require __DIR__ . '/players/index.php';
        exit;
    }
    
    // Player-specific routes
    if (str_starts_with($path, '/players/')) {
        $playerFile = __DIR__ . $path;
        
        // Check if the specific player file exists
        if (file_exists($playerFile)) {
            require $playerFile;
            exit;
        }
        
        // Handle .php extension if not provided
        if (!str_ends_with($path, '.php')) {
            $playerFileWithExt = $playerFile . '.php';
            if (file_exists($playerFileWithExt)) {
                require $playerFileWithExt;
                exit;
            }
        }
        
        // If no specific file found, show 404 for player routes
        http_response_code(404);
        echo '404 Not Found: Player page not found';
        exit;
    }
    
    // For any other path, redirect to player dashboard
    header('Location: /players/');
    exit;
}

// If for any reason user type is not handled
http_response_code(403);
echo '403 Forbidden: Account type not recognized.';
exit;
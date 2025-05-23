
<?php
// Show all errors, warnings, and notices
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// XRPG Universal Router - index.php
// ---------------------------------
// All site entry traffic comes through here. This handles routing based on authentication state.
//
// ENVIRONMENT NOTE:
// In local development, http://localhost/ points directly to /var/www/xrpg (see STRUCTURE.MD)
// In production, e.g. https://xrpg.win/, your vhost should ALSO point to this directory as document root.
//
// Update /config/environment.php if your domain or WebAuthn origins change!

session_start();
require_once __DIR__ . '/config/environment.php';

// --- Dummy auth/session system for demonstration (replace with real logic!) ---
// $_SESSION['user'] = [ 'type' => 'admin'|'player', ... ] if logged in
$user = $_SESSION['user'] ?? null;

// ROUTING LOGIC
if (!$user) {
    // Not logged in: Show the landing/welcome page (sign-in/register/appearance customizer)
    require __DIR__ . '/pages/landing.php';
    exit;
}

if ($user['type'] === 'admin') {
    // Admin user - go to admin panel
    header('Location: /admin/admin-panel.php');
    exit;
}

if ($user['type'] === 'player') {
    // Normal authenticated player; show a simple dashboard/profile (stub for now)
    require __DIR__ . '/players/index.php';
    exit;
}

// If for any reason user type is not handled
http_response_code(403);
echo '403 Forbidden: Account type not recognized.';
exit;


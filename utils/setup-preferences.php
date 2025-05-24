<?php
// /utils/setup-preferences.php - Utility script for setting up user preferences

// This script can be run from command line or browser to:
// 1. Create default preferences for existing users
// 2. Test the preferences system
// 3. Bulk update preferences

// Security check - remove in production or add proper authentication
if (php_sapi_name() !== 'cli' && !isset($_GET['allow_web'])) {
    die('This script should only be run from command line. Add ?allow_web=1 to override.');
}

require_once __DIR__ . '/../config/db.php';

echo "XRPG User Preferences Setup Utility\n";
echo "====================================\n\n";

try {
    // Check if user_preferences table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
    if (!$stmt->fetch()) {
        echo "❌ user_preferences table not found!\n";
        echo "Please run the database schema first.\n";
        exit(1);
    }
    
    echo "✅ user_preferences table found\n";
    
    // Get all users without preferences
    $stmt = $pdo->query("
        SELECT u.id, u.username 
        FROM users u 
        LEFT JOIN user_preferences up ON u.id = up.user_id 
        WHERE up.user_id IS NULL
    ");
    $usersWithoutPrefs = $stmt->fetchAll();
    
    echo "Found " . count($usersWithoutPrefs) . " users without preferences\n\n";
    
    if (empty($usersWithoutPrefs)) {
        echo "✅ All users already have preferences set up!\n";
        
        // Show statistics
        $stmt = $pdo->query("
            SELECT 
                theme_mode,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM user_preferences), 1) as percentage
            FROM user_preferences 
            GROUP BY theme_mode
        ");
        $themeStats = $stmt->fetchAll();
        
        echo "\nTheme Statistics:\n";
        foreach ($themeStats as $stat) {
            echo "  {$stat['theme_mode']}: {$stat['count']} users ({$stat['percentage']}%)\n";
        }
        
        // Show most popular colors
        $stmt = $pdo->query("
            SELECT accent_color, COUNT(*) as count 
            FROM user_preferences 
            GROUP BY accent_color 
            ORDER BY count DESC 
            LIMIT 5
        ");
        $colorStats = $stmt->fetchAll();
        
        echo "\nMost Popular Accent Colors:\n";
        foreach ($colorStats as $color) {
            echo "  {$color['accent_color']}: {$color['count']} users\n";
        }
        
    } else {
        // Create default preferences for users without them
        echo "Creating default preferences for users:\n";
        
        $defaultPrefs = [
            'theme_mode' => 'dark',
            'accent_color' => '#5299e0',
            'accent_secondary' => '#81aaff',
            'border_radius' => 18,
            'shadow_intensity' => 0.36,
            'ui_opacity' => 0.96,
            'font_family' => 'sans'
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences 
            (user_id, theme_mode, accent_color, accent_secondary, border_radius, shadow_intensity, ui_opacity, font_family)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $created = 0;
        foreach ($usersWithoutPrefs as $user) {
            try {
                $stmt->execute([
                    $user['id'],
                    $defaultPrefs['theme_mode'],
                    $defaultPrefs['accent_color'],
                    $defaultPrefs['accent_secondary'],
                    $defaultPrefs['border_radius'],
                    $defaultPrefs['shadow_intensity'],
                    $defaultPrefs['ui_opacity'],
                    $defaultPrefs['font_family']
                ]);
                echo "  ✅ Created preferences for user: {$user['username']}\n";
                $created++;
            } catch (Exception $e) {
                echo "  ❌ Failed to create preferences for user: {$user['username']} - " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n✅ Created preferences for {$created} users\n";
    }
    
    // Test the preferences system
    echo "\n" . str_repeat("-", 40) . "\n";
    echo "Testing Preferences System\n";
    echo str_repeat("-", 40) . "\n";
    
    // Get a random user to test with
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY RAND() LIMIT 1");
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        echo "Testing with user: {$testUser['username']}\n";
        
        // Test loading preferences
        $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$testUser['id']]);
        $prefs = $stmt->fetch();
        
        if ($prefs) {
            echo "✅ Successfully loaded preferences\n";
            echo "  Theme: {$prefs['theme_mode']}\n";
            echo "  Primary Color: {$prefs['accent_color']}\n";
            echo "  Font: {$prefs['font_family']}\n";
            
            // Test updating preferences
            $newColor = '#ff6b6b'; // Red color for testing
            $stmt = $pdo->prepare("UPDATE user_preferences SET accent_color = ? WHERE user_id = ?");
            $stmt->execute([$newColor, $testUser['id']]);
            
            // Verify update
            $stmt = $pdo->prepare("SELECT accent_color FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$testUser['id']]);
            $updatedColor = $stmt->fetchColumn();
            
            if ($updatedColor === $newColor) {
                echo "✅ Successfully updated preferences\n";
                
                // Restore original color
                $stmt = $pdo->prepare("UPDATE user_preferences SET accent_color = ? WHERE user_id = ?");
                $stmt->execute([$prefs['accent_color'], $testUser['id']]);
                echo "✅ Restored original color\n";
            } else {
                echo "❌ Failed to update preferences\n";
            }
        } else {
            echo "❌ Failed to load preferences\n";
        }
    }
    
    echo "\n✅ Setup complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Additional utility functions
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    switch ($argv[1]) {
        case 'stats':
            showDetailedStats($pdo);
            break;
        case 'reset':
            if (isset($argv[2])) {
                resetUserPreferences($pdo, $argv[2]);
            } else {
                echo "Usage: php setup-preferences.php reset <username>\n";
            }
            break;
        case 'export':
            exportPreferences($pdo);
            break;
        default:
            echo "Available commands:\n";
            echo "  stats - Show detailed statistics\n";
            echo "  reset <username> - Reset user preferences to defaults\n";
            echo "  export - Export all preferences to JSON\n";
    }
}

function showDetailedStats($pdo) {
    echo "\nDetailed Preferences Statistics\n";
    echo "==============================\n";
    
    // Theme distribution
    $stmt = $pdo->query("
        SELECT theme_mode, COUNT(*) as count 
        FROM user_preferences 
        GROUP BY theme_mode
    ");
    echo "\nTheme Mode Distribution:\n";
    while ($row = $stmt->fetch()) {
        echo "  {$row['theme_mode']}: {$row['count']} users\n";
    }
    
    // Font distribution
    $stmt = $pdo->query("
        SELECT font_family, COUNT(*) as count 
        FROM user_preferences 
        GROUP BY font_family 
        ORDER BY count DESC
    ");
    echo "\nFont Family Distribution:\n";
    while ($row = $stmt->fetch()) {
        echo "  {$row['font_family']}: {$row['count']} users\n";
    }
    
    // Average settings
    $stmt = $pdo->query("
        SELECT 
            AVG(border_radius) as avg_radius,
            AVG(shadow_intensity) as avg_shadow,
            AVG(ui_opacity) as avg_opacity
        FROM user_preferences
    ");
    $averages = $stmt->fetch();
    echo "\nAverage Settings:\n";
    echo "  Border Radius: " . round($averages['avg_radius'], 1) . "px\n";
    echo "  Shadow Intensity: " . round($averages['avg_shadow'], 3) . "\n";
    echo "  UI Opacity: " . round($averages['avg_opacity'], 3) . "\n";
}

function resetUserPreferences($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ User '$username' not found\n";
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE user_preferences 
        SET theme_mode = 'dark', accent_color = '#5299e0', accent_secondary = '#81aaff',
            border_radius = 18, shadow_intensity = 0.36, ui_opacity = 0.96, font_family = 'sans'
        WHERE user_id = ?
    ");
    
    if ($stmt->execute([$user['id']])) {
        echo "✅ Reset preferences for user '$username'\n";
    } else {
        echo "❌ Failed to reset preferences for user '$username'\n";
    }
}

function exportPreferences($pdo) {
    $stmt = $pdo->query("
        SELECT u.username, up.* 
        FROM user_preferences up 
        JOIN users u ON up.user_id = u.id
    ");
    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'preferences_export_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($filename, json_encode($preferences, JSON_PRETTY_PRINT));
    echo "✅ Exported " . count($preferences) . " preferences to $filename\n";
}

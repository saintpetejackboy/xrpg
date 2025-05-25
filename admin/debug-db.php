<?php
// /admin/debug_db.php - Check database structure and data
require_once __DIR__ . '/../config/db.php';

echo "XRPG Database Debug Report\n";
echo "==========================\n\n";

try {
    // Check table structures
    echo "1. TABLE STRUCTURES\n";
    echo "-------------------\n\n";
    
    // Users table structure
    echo "USERS TABLE:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($userColumns as $col) {
        echo sprintf("  %-20s %-15s %-8s %-8s %s\n", 
            $col['Field'], $col['Type'], $col['Null'], $col['Key'], $col['Default']);
    }
    echo "\n";
    
    // User_passkeys table structure
    echo "USER_PASSKEYS TABLE:\n";
    $stmt = $pdo->query("DESCRIBE user_passkeys");
    $passkeyColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($passkeyColumns as $col) {
        echo sprintf("  %-20s %-15s %-8s %-8s %s\n", 
            $col['Field'], $col['Type'], $col['Null'], $col['Key'], $col['Default']);
    }
    echo "\n";
    
    // Check data
    echo "2. DATA ANALYSIS\n";
    echo "----------------\n\n";
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetchColumn();
    echo "Total users: $userCount\n";
    
    // Count passkeys
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_passkeys");
    $passkeyCount = $stmt->fetchColumn();
    echo "Total passkeys: $passkeyCount\n\n";
    
    // Sample user data
    echo "SAMPLE USER DATA:\n";
    $stmt = $pdo->query("SELECT id, username, user_id, fallback_password_hash IS NOT NULL as has_fallback FROM users ORDER BY id DESC LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo sprintf("  ID: %-3s Username: %-15s UserID: %-30s Fallback: %s\n", 
            $user['id'], $user['username'], $user['user_id'], $user['has_fallback'] ? 'Yes' : 'No');
    }
    echo "\n";
    
    // Sample passkey data
    echo "SAMPLE PASSKEY DATA:\n";
    $stmt = $pdo->query("
        SELECT up.id, up.user_id, u.username, up.credential_id, 
               LENGTH(up.public_key) as key_length, 
               HEX(SUBSTRING(up.public_key, 1, 10)) as key_sample,
               up.device_name, up.created_at, up.last_used
        FROM user_passkeys up 
        JOIN users u ON up.user_id = u.id 
        ORDER BY up.id DESC 
        LIMIT 5
    ");
    $passkeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($passkeys as $pk) {
        echo "  Passkey ID: {$pk['id']}\n";
        echo "    User: {$pk['username']} (ID: {$pk['user_id']})\n";
        echo "    Credential ID: {$pk['credential_id']}\n";
        echo "    Public Key Length: {$pk['key_length']} bytes\n";
        echo "    Key Sample (hex): {$pk['key_sample']}\n";
        echo "    Device: {$pk['device_name']}\n";
        echo "    Created: {$pk['created_at']}\n";
        echo "    Last Used: {$pk['last_used']}\n";
        echo "\n";
    }
    
    // Check for data inconsistencies
    echo "3. DATA CONSISTENCY CHECKS\n";
    echo "--------------------------\n\n";
    
    // Users without passkeys
    $stmt = $pdo->query("
        SELECT u.id, u.username 
        FROM users u 
        LEFT JOIN user_passkeys up ON u.id = up.user_id 
        WHERE up.user_id IS NULL
    ");
    $usersWithoutPasskeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($usersWithoutPasskeys)) {
        echo "Users without passkeys:\n";
        foreach ($usersWithoutPasskeys as $user) {
            echo "  - {$user['username']} (ID: {$user['id']})\n";
        }
        echo "\n";
    } else {
        echo "✓ All users have passkeys\n\n";
    }
    
    // Check for empty credential IDs
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_passkeys WHERE credential_id = '' OR credential_id IS NULL");
    $emptyCredentials = $stmt->fetchColumn();
    
    if ($emptyCredentials > 0) {
        echo "⚠ Warning: $emptyCredentials passkeys with empty credential IDs\n";
    } else {
        echo "✓ All passkeys have credential IDs\n";
    }
    
    // Check for empty public keys
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_passkeys WHERE public_key = '' OR public_key IS NULL OR LENGTH(public_key) = 0");
    $emptyKeys = $stmt->fetchColumn();
    
    if ($emptyKeys > 0) {
        echo "⚠ Warning: $emptyKeys passkeys with empty public keys\n";
    } else {
        echo "✓ All passkeys have public keys\n";
    }
    
    // Check credential ID uniqueness
    $stmt = $pdo->query("
        SELECT credential_id, COUNT(*) as count 
        FROM user_passkeys 
        GROUP BY credential_id 
        HAVING count > 1
    ");
    $duplicateCredentials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicateCredentials)) {
        echo "⚠ Warning: Duplicate credential IDs found:\n";
        foreach ($duplicateCredentials as $dup) {
            echo "  - {$dup['credential_id']} (appears {$dup['count']} times)\n";
        }
    } else {
        echo "✓ All credential IDs are unique\n";
    }
    
    echo "\n";
    
    // Check recent authentication logs
    echo "4. RECENT AUTHENTICATION LOGS\n";
    echo "-----------------------------\n\n";
    
    $stmt = $pdo->query("
        SELECT username, event_type, description, ip_addr, created_at 
        FROM auth_log 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($logs)) {
        foreach ($logs as $log) {
            echo sprintf("[%s] %s - %s: %s (IP: %s)\n", 
                $log['created_at'], $log['username'], $log['event_type'], 
                $log['description'], $log['ip_addr']);
        }
    } else {
        echo "No authentication logs found\n";
    }
    
    echo "\n";
    
    // Environment check
    echo "5. ENVIRONMENT CHECK\n";
    echo "--------------------\n\n";
    
    $config = require __DIR__ . '/../config/environment.php';
    echo "RP Name: " . ($config['rp_name'] ?? 'NOT SET') . "\n";
    echo "RP ID: " . ($config['rp_id'] ?? 'NOT SET') . "\n";
    echo "WebAuthn Origin: " . ($config['webauthn_origin'] ?? 'NOT SET') . "\n";
    echo "Current Domain: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
    echo "Current Scheme: " . (($_SERVER['HTTPS'] ?? false) ? 'https' : 'http') . "\n";
    echo "\n";
    
    echo "✅ Database debug report completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

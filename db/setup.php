<?php
// db/setup.php: Run this to safely create user/auth tables if missing
require_once __DIR__ . '/../config/db.php';

// Run all SQL files in db/ that start with 'init-'
$initFiles = glob(__DIR__ . '/init-*.sql');

foreach ($initFiles as $file) {
    echo "Running $file...\n";
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        echo "✅ $file: Success\n";
    } catch (PDOException $e) {
        echo "❌ $file: " . $e->getMessage() . "\n";
        exit(1);
    }
}
echo "✅ All DB schemas ensured.\n";

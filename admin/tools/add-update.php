<?php
/**
 * Add Update Tool
 * 
 * Usage: php add-update.php "Your update message here"
 * 
 * This tool helps AI assistants and developers add entries to the updates log.
 */

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

// Get the update message from command line argument
$message = $argv[1] ?? null;

if (!$message) {
    echo "Usage: php add-update.php \"Your update message here\"\n";
    echo "Example: php add-update.php \"Added epic new battle animations\"\n";
    exit(1);
}

// Generate timestamp
$timestamp = date('Y-m-d H:i:s');

// Create the log entry
$entry = "$timestamp|🤖|$message\n";

// Append to updates.log
$logFile = __DIR__ . '/../../updates.log';
$result = file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

if ($result !== false) {
    echo "✅ Update added successfully!\n";
    echo "Entry: $entry";
} else {
    echo "❌ Failed to add update. Check file permissions.\n";
    exit(1);
}
?>
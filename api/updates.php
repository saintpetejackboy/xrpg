<?php
// /api/updates.php - Simple API endpoint for game updates

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes

// Simple in-memory updates (in a real app, this would come from a database)
$updates = [
    [
        'id' => 5,
        'emoji' => '🎨',
        'message' => 'New theme customization options added!',
        'timestamp' => '2025-05-24 14:15:00',
        'type' => 'feature'
    ],
    [
        'id' => 4,
        'emoji' => '🔒',
        'message' => 'Enhanced security with passkey authentication',
        'timestamp' => '2025-05-24 13:30:00',
        'type' => 'security'
    ],
    [
        'id' => 3,
        'emoji' => '⚔️',
        'message' => 'New dungeon system coming soon!',
        'timestamp' => '2025-05-24 12:45:00',
        'type' => 'announcement'
    ],
    [
        'id' => 2,
        'emoji' => '🎮',
        'message' => 'Player dashboard improvements',
        'timestamp' => '2025-05-24 11:20:00',
        'type' => 'improvement'
    ],
    [
        'id' => 1,
        'emoji' => '🚀',
        'message' => 'XRPG Beta Launch - Welcome adventurers!',
        'timestamp' => '2025-05-24 10:00:00',
        'type' => 'announcement'
    ]
];

// Add relative time to each update
foreach ($updates as &$update) {
    $updateTime = strtotime($update['timestamp']);
    $now = time();
    $diff = $now - $updateTime;
    
    if ($diff < 60) {
        $update['timeAgo'] = 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        $update['timeAgo'] = $minutes . 'm ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        $update['timeAgo'] = $hours . 'h ago';
    } else {
        $days = floor($diff / 86400);
        $update['timeAgo'] = $days . 'd ago';
    }
}

// Get limit from query parameter (default 5, max 20)
$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 20) : 5;
$updates = array_slice($updates, 0, $limit);

// Return JSON response
echo json_encode([
    'success' => true,
    'updates' => $updates,
    'total' => count($updates),
    'generated_at' => date('Y-m-d H:i:s')
]);

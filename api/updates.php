<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');


$logUrl = 'https://xrpg.win/updates.log';
$raw = @file_get_contents($logUrl);
if ($raw === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Could not fetch updates log.'
    ]);
    exit;
}

// split into non‐empty lines
$lines = preg_split('/\r\n|\n|\r/', trim($raw));
$all = [];
foreach ($lines as $line) {
    if ($line === '') continue;
    // expect TIMESTAMP|EMOJI|MESSAGE
    $parts = explode('|', $line, 3);
    if (count($parts) !== 3) continue;
    list($iso, $emoji, $message) = $parts;
    $all[] = [
        'timestamp' => $iso,
        'emoji'     => $emoji,
        'message'   => $message,
    ];
}

$total = count($all);
$limit = isset($_GET['limit'])
    ? min(intval($_GET['limit']), 50, $total)
    : min(50, $total);

// map emojis to types (fallback to “update”)
$typeMap = [
    '🎨' => 'feature',
    '🔒' => 'security',
    '🛡' => 'security',
    '🛡️'=> 'security',
    '⚔️' => 'announcement',
    '🎮' => 'improvement',
    '🚀' => 'announcement',
    '⚙️' => 'improvement',
    '🌟' => 'improvement',
];

$updates = [];
foreach (array_slice($all, 0, $limit) as $i => $u) {
    // relative time
    $t0 = strtotime($u['timestamp']);
    $diff = time() - $t0;
    if ($diff < 60) {
        $timeAgo = 'Just now';
    } elseif ($diff < 3600) {
        $timeAgo = floor($diff / 60) . 'm ago';
    } elseif ($diff < 86400) {
        $timeAgo = floor($diff / 3600) . 'h ago';
    } else {
        $timeAgo = floor($diff / 86400) . 'd ago';
    }

    $updates[] = [
        'id'        => $total - $i,                        // descending unique id
        'emoji'     => $u['emoji'],
        'message'   => $u['message'],
        'timestamp' => $u['timestamp'],
        'type'      => $typeMap[$u['emoji']] ?? 'update',
        'timeAgo'   => $timeAgo,
    ];
}

echo json_encode([
    'success'      => true,
    'updates'      => $updates,
    'total'        => count($updates),
    'generated_at' => date('Y-m-d H:i:s'),
]);

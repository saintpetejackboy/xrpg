<?php
// /players/create-character.php - Backend handler for character creation

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/db.php';

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendSuccess($message, $data = []) {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    sendError('Not authenticated', 401);
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get and validate input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON input');
}

$raceId = intval($input['race_id'] ?? 0);
$classId = intval($input['class_id'] ?? 0);
$jobId = intval($input['job_id'] ?? 0);

if (!$raceId || !$classId || !$jobId) {
    sendError('Missing required selections');
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if character already exists and is complete
    $stmt = $pdo->prepare('SELECT * FROM user_characters WHERE user_id = ?');
    $stmt->execute([$userId]);
    $existingCharacter = $stmt->fetch();
    
    if ($existingCharacter && $existingCharacter['is_character_complete']) {
        $pdo->rollBack();
        sendError('Character already exists and is complete');
    }
    
    // Validate selections exist and are active
    $stmt = $pdo->prepare('SELECT * FROM races WHERE id = ? AND is_active = 1');
    $stmt->execute([$raceId]);
    $race = $stmt->fetch();
    if (!$race) {
        $pdo->rollBack();
        sendError('Invalid race selection');
    }
    
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ? AND is_active = 1');
    $stmt->execute([$classId]);
    $class = $stmt->fetch();
    if (!$class) {
        $pdo->rollBack();
        sendError('Invalid class selection');
    }
    
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND is_active = 1');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    if (!$job) {
        $pdo->rollBack();
        sendError('Invalid job selection');
    }
    
    // Check class prerequisites (basic tier 1 classes should have no prerequisites)
    if ($class['tier'] > 1) {
        $stmt = $pdo->prepare('SELECT * FROM prerequisites WHERE target_type = ? AND target_id = ? AND is_active = 1');
        $stmt->execute(['class', $classId]);
        $classPrereqs = $stmt->fetchAll();
        
        // For character creation, we only allow tier 1 classes
        if (!empty($classPrereqs)) {
            $pdo->rollBack();
            sendError('Selected class has prerequisites and cannot be chosen during character creation');
        }
    }
    
    // Calculate base stats with racial, class, and job bonuses
    $baseStats = [
        'strength' => 10,
        'vitality' => 10,
        'agility' => 10,
        'intelligence' => 10,
        'wisdom' => 10,
        'luck' => 10
    ];
    
    $finalStats = [];
    foreach ($baseStats as $stat => $baseValue) {
        $finalStats[$stat] = $baseValue + 
            ($race[$stat . '_mod'] ?? 0) +
            ($class[$stat . '_bonus'] ?? 0) +
            ($job[$stat . '_bonus'] ?? 0);
    }
    
    // Calculate derived stats
    $maxHealth = $finalStats['vitality'] * 5 + 50; // Base 50 + 5 per vitality
    $idleGoldRate = $job['idle_gold_rate'] ?? 1.0;
    
    $now = date('Y-m-d H:i:s');
    
    // Create or update user_characters record
    if ($existingCharacter) {
        $stmt = $pdo->prepare('
            UPDATE user_characters 
            SET race_id = ?, class_id = ?, job_id = ?, 
                character_created_at = ?, class_selected_at = ?, job_selected_at = ?,
                is_character_complete = 1, updated_at = ?
            WHERE user_id = ?
        ');
        $stmt->execute([$raceId, $classId, $jobId, $now, $now, $now, $now, $userId]);
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO user_characters 
            (user_id, race_id, class_id, job_id, character_created_at, class_selected_at, job_selected_at, is_character_complete) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ');
        $stmt->execute([$userId, $raceId, $classId, $jobId, $now, $now, $now]);
    }
    
    // Create or update user_stats record
    $stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    $existingStats = $stmt->fetch();
    
    if ($existingStats) {
        $stmt = $pdo->prepare('
            UPDATE user_stats 
            SET strength = ?, vitality = ?, agility = ?, intelligence = ?, wisdom = ?, luck = ?,
                max_health = ?, health = ?, idle_gold_rate = ?, updated_at = ?
            WHERE user_id = ?
        ');
        $stmt->execute([
            $finalStats['strength'], $finalStats['vitality'], $finalStats['agility'],
            $finalStats['intelligence'], $finalStats['wisdom'], $finalStats['luck'],
            $maxHealth, $maxHealth, $idleGoldRate, $now, $userId
        ]);
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO user_stats 
            (user_id, level, experience, gold, health, max_health, strength, vitality, agility, intelligence, wisdom, luck, 
             class_experience, class_level, job_experience, job_level, idle_gold_rate) 
            VALUES (?, 1, 0, 100, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 0, 1, ?)
        ');
        $stmt->execute([
            $userId, $maxHealth, $maxHealth,
            $finalStats['strength'], $finalStats['vitality'], $finalStats['agility'],
            $finalStats['intelligence'], $finalStats['wisdom'], $finalStats['luck'],
            $idleGoldRate
        ]);
    }
    
    // Log character creation
    $stmt = $pdo->prepare('
        INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $userId,
        $user['username'],
        'other',
        "Character created - Race: {$race['name']}, Class: {$class['name']}, Job: {$job['name']}",
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success with character data
    sendSuccess('Character created successfully!', [
        'character' => [
            'race' => $race['name'],
            'class' => $class['name'],
            'job' => $job['name'],
            'stats' => $finalStats,
            'max_health' => $maxHealth,
            'idle_gold_rate' => $idleGoldRate
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Character creation error: " . $e->getMessage());
    sendError('Failed to create character: ' . $e->getMessage(), 500);
}

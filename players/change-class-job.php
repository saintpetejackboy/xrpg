<?php
// /players/apply-class-job-change.php - Backend handler for class and job changes

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

$newClassId = isset($input['class_id']) ? intval($input['class_id']) : null;
$newJobId = isset($input['job_id']) ? intval($input['job_id']) : null;

if (!$newClassId && !$newJobId) {
    sendError('No changes specified');
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get current character information
    $stmt = $pdo->prepare('
        SELECT uc.*, us.level, us.class_level, us.job_level, 
               us.strength, us.vitality, us.agility, us.intelligence, us.wisdom, us.luck,
               r.name as race_name, c.name as class_name, j.name as job_name
        FROM user_characters uc
        LEFT JOIN user_stats us ON uc.user_id = us.user_id
        LEFT JOIN races r ON uc.race_id = r.id
        LEFT JOIN classes c ON uc.class_id = c.id
        LEFT JOIN jobs j ON uc.job_id = j.id
        WHERE uc.user_id = ? AND uc.is_character_complete = 1
    ');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character) {
        $pdo->rollBack();
        sendError('Character not found or not complete');
    }
    
    // Check cooldowns
    $classSelectedTime = strtotime($character['class_selected_at']);
    $jobSelectedTime = strtotime($character['job_selected_at']);
    $lastClassChange = $character['last_class_change'] ? strtotime($character['last_class_change']) : $classSelectedTime;
    $lastJobChange = $character['last_job_change'] ? strtotime($character['last_job_change']) : $jobSelectedTime;
    
    $threeDaysAgo = time() - (3 * 24 * 60 * 60);
    $now = date('Y-m-d H:i:s');
    
    // Validate class change if requested
    if ($newClassId) {
        if ($lastClassChange > $threeDaysAgo) {
            $pdo->rollBack();
            $hoursLeft = ceil(($lastClassChange + (3 * 24 * 60 * 60) - time()) / 3600);
            sendError("Class change is on cooldown. Wait {$hoursLeft} more hours.");
        }
        
        // Validate class exists and is active
        $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ? AND is_active = 1');
        $stmt->execute([$newClassId]);
        $newClass = $stmt->fetch();
        if (!$newClass) {
            $pdo->rollBack();
            sendError('Invalid class selection');
        }
        
        // Check prerequisites
        $stmt = $pdo->prepare('SELECT * FROM prerequisites WHERE target_type = ? AND target_id = ? AND is_active = 1');
        $stmt->execute(['class', $newClassId]);
        $prereqs = $stmt->fetchAll();
        
        foreach ($prereqs as $prereq) {
            $requirement = json_decode($prereq['requirement'], true);
            
            switch ($prereq['prereq_type']) {
                case 'level':
                    if ($character['level'] < $requirement['min_level']) {
                        $pdo->rollBack();
                        sendError("Requires character level {$requirement['min_level']} (you are {$character['level']})");
                    }
                    break;
                    
                case 'class':
                    if ($character['class_name'] !== $requirement['class_name'] || 
                        $character['class_level'] < ($requirement['min_level'] ?? 1)) {
                        $pdo->rollBack();
                        $minLevel = $requirement['min_level'] ?? 1;
                        sendError("Requires {$requirement['class_name']} class level {$minLevel}");
                    }
                    break;
                    
                case 'stat':
                    $statValue = $character[$requirement['stat']] ?? 0;
                    if ($statValue < $requirement['min_value']) {
                        $pdo->rollBack();
                        $statName = ucfirst($requirement['stat']);
                        sendError("Requires {$requirement['min_value']} {$statName} (you have {$statValue})");
                    }
                    break;
            }
        }
    }
    
    // Validate job change if requested
    if ($newJobId) {
        if ($lastJobChange > $threeDaysAgo) {
            $pdo->rollBack();
            $hoursLeft = ceil(($lastJobChange + (3 * 24 * 60 * 60) - time()) / 3600);
            sendError("Job change is on cooldown. Wait {$hoursLeft} more hours.");
        }
        
        // Validate job exists and is active
        $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND is_active = 1');
        $stmt->execute([$newJobId]);
        $newJob = $stmt->fetch();
        if (!$newJob) {
            $pdo->rollBack();
            sendError('Invalid job selection');
        }
    }
    
    $changes = [];
    
    // Apply class change
    if ($newClassId && $newClassId != $character['class_id']) {
        $stmt = $pdo->prepare('UPDATE user_characters SET class_id = ?, last_class_change = ? WHERE user_id = ?');
        $stmt->execute([$newClassId, $now, $userId]);
        $changes[] = "Class changed to {$newClass['name']}";
    }
    
    // Apply job change
    if ($newJobId && $newJobId != $character['job_id']) {
        $stmt = $pdo->prepare('UPDATE user_characters SET job_id = ?, last_job_change = ? WHERE user_id = ?');
        $stmt->execute([$newJobId, $now, $userId]);
        $changes[] = "Job changed to {$newJob['name']}";
    }
    
    if (empty($changes)) {
        $pdo->rollBack();
        sendError('No actual changes were made');
    }
    
    // Recalculate stats with new class/job bonuses
    $currentClassId = $newClassId ?? $character['class_id'];
    $currentJobId = $newJobId ?? $character['job_id'];
    
    // Get race, class, and job data for stat calculation
    $stmt = $pdo->prepare('SELECT * FROM races WHERE id = ?');
    $stmt->execute([$character['race_id']]);
    $race = $stmt->fetch();
    
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$currentClassId]);
    $class = $stmt->fetch();
    
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$currentJobId]);
    $job = $stmt->fetch();
    
    // Calculate new stats (base 10 + racial mods + class bonuses + job bonuses)
    $baseStats = [
        'strength' => 10,
        'vitality' => 10,
        'agility' => 10,
        'intelligence' => 10,
        'wisdom' => 10,
        'luck' => 10
    ];
    
    $newStats = [];
    foreach ($baseStats as $stat => $baseValue) {
        $newStats[$stat] = $baseValue + 
            ($race[$stat . '_mod'] ?? 0) +
            ($class[$stat . '_bonus'] ?? 0) +
            ($job[$stat . '_bonus'] ?? 0);
    }
    
    // Update max health based on new vitality
    $newMaxHealth = $newStats['vitality'] * 5 + 50;
    $currentHealthPercent = $character['health'] / $character['max_health'];
    $newHealth = min($character['health'], round($newMaxHealth * $currentHealthPercent));
    
    // Update idle gold rate
    $newIdleGoldRate = $job['idle_gold_rate'] ?? 1.0;
    
    // Update user stats
    $stmt = $pdo->prepare('
        UPDATE user_stats 
        SET strength = ?, vitality = ?, agility = ?, intelligence = ?, wisdom = ?, luck = ?,
            max_health = ?, health = ?, idle_gold_rate = ?, updated_at = ?
        WHERE user_id = ?
    ');
    $stmt->execute([
        $newStats['strength'], $newStats['vitality'], $newStats['agility'],
        $newStats['intelligence'], $newStats['wisdom'], $newStats['luck'],
        $newMaxHealth, $newHealth, $newIdleGoldRate, $now, $userId
    ]);
    
    // Log the changes
    $changeDescription = implode(', ', $changes);
    $stmt = $pdo->prepare('
        INSERT INTO auth_log (user_id, username, event_type, description, ip_addr, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $userId,
        $user['username'],
        'other',
        "Character updated: {$changeDescription}",
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Return success with updated data
    sendSuccess('Changes applied successfully!', [
        'changes' => $changes,
        'new_stats' => $newStats,
        'new_max_health' => $newMaxHealth,
        'new_health' => $newHealth,
        'new_idle_gold_rate' => $newIdleGoldRate
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Class/Job change error: " . $e->getMessage());
    sendError('Failed to apply changes: ' . $e->getMessage(), 500);
}
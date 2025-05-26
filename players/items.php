<?php
// /api/equipment.php - API for equipping and unequipping items
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$instanceId = $input['instance_id'] ?? 0;

if (!$action || !$instanceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $userInfo = getUserInfo($_SESSION['user']);
    $userId = $userInfo['id'];
    
    // Get character ID
    $stmt = $pdo->prepare('SELECT id FROM user_characters WHERE user_id = ? AND is_character_complete = 1');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character) {
        throw new Exception('Character not found');
    }
    
    $characterId = $character['id'];
    
    switch ($action) {
        case 'equip':
            // For now, we'll simulate the equip action since we don't have the full database schema
            // In a real implementation, this would:
            // 1. Verify the item belongs to the player
            // 2. Check if the player meets requirements
            // 3. Handle two-handed weapons and slot conflicts
            // 4. Update character_equipment table
            // 5. Recalculate character stats
            
            // Simulate success response
            echo json_encode([
                'success' => true,
                'message' => 'Item equipped successfully',
                'equipped_item_id' => $instanceId
            ]);
            break;
            
        case 'unequip':
            // For now, we'll simulate the unequip action
            // In a real implementation, this would:
            // 1. Verify the item is equipped by the player
            // 2. Remove from character_equipment table
            // 3. Recalculate character stats
            
            // Simulate success response
            echo json_encode([
                'success' => true,
                'message' => 'Item unequipped successfully',
                'unequipped_item_id' => $instanceId
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Equipment API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request'
    ]);
}

/* 
REAL IMPLEMENTATION EXAMPLE (commented out since we don't have full schema):

case 'equip':
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Verify item ownership and get item details
        $stmt = $pdo->prepare('
            SELECT ii.*, i.name, i.default_slot_id, i.is_two_handed, i.item_type
            FROM item_instances ii
            JOIN items i ON ii.base_item_id = i.id
            WHERE ii.id = ? AND ii.owner_char_id = ?
        ');
        $stmt->execute([$instanceId, $characterId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            throw new Exception('Item not found or not owned by player');
        }
        
        if ($item['item_type'] !== 'weapon' && $item['item_type'] !== 'armor') {
            throw new Exception('This item cannot be equipped');
        }
        
        // Check level requirements, etc.
        // ... validation logic ...
        
        // Handle slot conflicts (especially two-handed weapons)
        if ($item['is_two_handed']) {
            // Remove items from both hands
            $stmt = $pdo->prepare('
                DELETE FROM character_equipment 
                WHERE character_id = ? AND slot_id IN (
                    SELECT id FROM equip_slots WHERE code IN ('left_hand', 'right_hand')
                )
            ');
            $stmt->execute([$characterId]);
        } else {
            // Remove item from specific slot
            $stmt = $pdo->prepare('
                DELETE FROM character_equipment 
                WHERE character_id = ? AND slot_id = ?
            ');
            $stmt->execute([$characterId, $item['default_slot_id']]);
        }
        
        // Equip the new item
        $stmt = $pdo->prepare('
            INSERT INTO character_equipment (character_id, slot_id, instance_id, equipped_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$characterId, $item['default_slot_id'], $instanceId]);
        
        // Recalculate character stats
        recalculateCharacterStats($characterId);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Item equipped successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    break;
*/
?>

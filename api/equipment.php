<?php
// /api/equipment.php - API for equipping, unequipping, and selling items (FULL IMPLEMENTATION)
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


// Get request data (JSON preferred, fall back to form data)
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}
$action     = $input['action']      ?? '';
$instanceId = (int)($input['instance_id'] ?? 0);


if (!$action || !$instanceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        throw new Exception('User session invalid');
    }

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
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Verify item ownership and get item details
                $stmt = $pdo->prepare('
                    SELECT ii.*, i.name, i.default_slot_id, i.is_two_handed, i.item_type, i.base_level
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
                
                if (!$item['default_slot_id']) {
                    throw new Exception('This item has no valid equipment slot');
                }
                
                // Check if item is already equipped
                $stmt = $pdo->prepare('
                    SELECT 1 FROM character_equipment 
                    WHERE character_id = ? AND instance_id = ?
                ');
                $stmt->execute([$characterId, $instanceId]);
                if ($stmt->fetch()) {
                    throw new Exception('Item is already equipped');
                }
                
                // Get character level to check requirements
                $stmt = $pdo->prepare('SELECT level FROM user_stats WHERE user_id = ?');
                $stmt->execute([$userId]);
                $stats = $stmt->fetch();
                
                if ($stats && $stats['level'] < $item['base_level']) {
                    throw new Exception('Character level too low for this item');
                }
                
                // Handle slot conflicts
                if ($item['is_two_handed']) {
                    // Remove items from both hands
                    $stmt = $pdo->prepare('
                        DELETE FROM character_equipment 
                        WHERE character_id = ? AND slot_id IN (
                            SELECT id FROM equip_slots WHERE code IN (\'left_hand\', \'right_hand\')
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
                
                // TODO: Recalculate character stats (implement this function)
                // recalculateCharacterStats($characterId);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => $item['name'] . ' equipped successfully',
                    'equipped_item_id' => $instanceId,
                    'slot_id' => $item['default_slot_id']
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'unequip':
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Verify the item is equipped by the player
                $stmt = $pdo->prepare('
                    SELECT ce.*, ii.*, i.name
                    FROM character_equipment ce
                    JOIN item_instances ii ON ce.instance_id = ii.id
                    JOIN items i ON ii.base_item_id = i.id
                    WHERE ce.character_id = ? AND ce.instance_id = ?
                ');
                $stmt->execute([$characterId, $instanceId]);
                $equipped = $stmt->fetch();
                
                if (!$equipped) {
                    throw new Exception('Item is not equipped by this character');
                }
                
                // Remove from character_equipment
                $stmt = $pdo->prepare('
                    DELETE FROM character_equipment 
                    WHERE character_id = ? AND instance_id = ?
                ');
                $stmt->execute([$characterId, $instanceId]);
                
                // TODO: Recalculate character stats (implement this function)
                // recalculateCharacterStats($characterId);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => $equipped['name'] . ' unequipped successfully',
                    'unequipped_item_id' => $instanceId,
                    'slot_id' => $equipped['slot_id']
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'sell':
            // Start transaction
            $pdo->beginTransaction();
            try {
                // Ensure item not currently equipped
                $stmt = $pdo->prepare('SELECT 1 FROM character_equipment WHERE character_id = ? AND instance_id = ?');
                $stmt->execute([$characterId, $instanceId]);
                if ($stmt->fetch()) {
                    throw new Exception('Cannot sell an item that is currently equipped');
                }

                // Verify ownership and get sel value
                $stmt = $pdo->prepare(
                    'SELECT ii.id, ii.owner_char_id, i.name, i.sell_value
                     FROM item_instances ii
                     JOIN items i ON ii.base_item_id = i.id
                     WHERE ii.id = ? AND ii.owner_char_id = ?'
                );
                $stmt->execute([$instanceId, $characterId]);
                $item = $stmt->fetch();
                if (!$item) {
                    throw new Exception('Item not found or not owned by player');
                }

                // Determine price (fallback to zero if undefined)
                $price = (float)($item['sell_value'] ?? 0);

                // Delete the item instance
                $stmt = $pdo->prepare('DELETE FROM item_instances WHERE id = ?');
                $stmt->execute([$instanceId]);

                // Credit the player's gold
                $stmt = $pdo->prepare('UPDATE user_stats SET gold = gold + ? WHERE user_id = ?');
                $stmt->execute([$price, $userId]);

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => "Sold {$item['name']} for {$price} gold",
                    'sold_item_id' => $instanceId,
                    'gold_gained' => $price
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
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
        'message' => $e->getMessage()
    ]);
}

// TODO: Re-implement recalculateCharacterStats when needed
function recalculateCharacterStats($characterId) {
    global $pdo;
    error_log("TODO: Implement recalculateCharacterStats for character $characterId");
}

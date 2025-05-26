<?php
// /api/items.php - API for using and selling items
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
$quantity = $input['quantity'] ?? 1;

if (!$action || !$instanceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $userInfo = getUserInfo($_SESSION['user']);
    $userId = $userInfo['id'];
    
    // Get character
    $stmt = $pdo->prepare('SELECT id FROM user_characters WHERE user_id = ? AND is_character_complete = 1');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character) {
        throw new Exception('Character not found');
    }
    
    $characterId = $character['id'];
    
    switch ($action) {
        case 'use':
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Get item details
                $stmt = $pdo->prepare('
                    SELECT ii.*, i.name, i.item_type, i.sell_value
                    FROM item_instances ii
                    JOIN items i ON ii.base_item_id = i.id
                    WHERE ii.id = ? AND ii.owner_char_id = ?
                ');
                $stmt->execute([$instanceId, $characterId]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    throw new Exception('Item not found or not owned by player');
                }
                
                if ($item['item_type'] !== 'consumable') {
                    throw new Exception('This item cannot be used');
                }
                
                if ($item['quantity'] < $quantity) {
                    throw new Exception('Insufficient quantity');
                }
                
                // Check if item is equipped (consumables shouldn't be, but safety check)
                $stmt = $pdo->prepare('
                    SELECT 1 FROM character_equipment 
                    WHERE character_id = ? AND instance_id = ?
                ');
                $stmt->execute([$characterId, $instanceId]);
                if ($stmt->fetch()) {
                    throw new Exception('Cannot use equipped item');
                }
                
                // Apply item effects
                $effects = applyItemEffects($userId, $instanceId, $quantity);
                
                // Reduce quantity or delete item
                if ($item['quantity'] <= $quantity) {
                    // Delete the item completely
                    $stmt = $pdo->prepare('DELETE FROM item_instances WHERE id = ?');
                    $stmt->execute([$instanceId]);
                    
                    // Also delete associated attributes
                    $stmt = $pdo->prepare('DELETE FROM item_instance_attributes WHERE instance_id = ?');
                    $stmt->execute([$instanceId]);
                } else {
                    // Reduce quantity
                    $stmt = $pdo->prepare('
                        UPDATE item_instances 
                        SET quantity = quantity - ? 
                        WHERE id = ?
                    ');
                    $stmt->execute([$quantity, $instanceId]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Used {$item['name']}!",
                    'item_name' => $item['name'],
                    'quantity_used' => $quantity,
                    'effect' => $effects
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
                // Get item details
                $stmt = $pdo->prepare('
                    SELECT ii.*, i.name, i.item_type, i.sell_value
                    FROM item_instances ii
                    JOIN items i ON ii.base_item_id = i.id
                    WHERE ii.id = ? AND ii.owner_char_id = ?
                ');
                $stmt->execute([$instanceId, $characterId]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    throw new Exception('Item not found or not owned by player');
                }
                
                if ($item['quantity'] < $quantity) {
                    throw new Exception('Insufficient quantity');
                }
                
                // Check if item is equipped
                $stmt = $pdo->prepare('
                    SELECT 1 FROM character_equipment 
                    WHERE character_id = ? AND instance_id = ?
                ');
                $stmt->execute([$characterId, $instanceId]);
                if ($stmt->fetch()) {
                    throw new Exception('Cannot sell equipped item. Unequip it first.');
                }
                
                // Calculate sell value
                $sellValuePerItem = $item['sell_value'] ?: 1;
                $totalGoldEarned = $sellValuePerItem * $quantity;
                
                // Add gold to player
                $stmt = $pdo->prepare('
                    UPDATE user_stats 
                    SET gold = gold + ? 
                    WHERE user_id = ?
                ');
                $stmt->execute([$totalGoldEarned, $userId]);
                
                // Reduce quantity or delete item
                if ($item['quantity'] <= $quantity) {
                    // Delete the item completely
                    $stmt = $pdo->prepare('DELETE FROM item_instances WHERE id = ?');
                    $stmt->execute([$instanceId]);
                    
                    // Also delete associated attributes
                    $stmt = $pdo->prepare('DELETE FROM item_instance_attributes WHERE instance_id = ?');
                    $stmt->execute([$instanceId]);
                } else {
                    // Reduce quantity
                    $stmt = $pdo->prepare('
                        UPDATE item_instances 
                        SET quantity = quantity - ? 
                        WHERE id = ?
                    ');
                    $stmt->execute([$quantity, $instanceId]);
                }
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => "Sold {$item['name']} for {$totalGoldEarned} gold!",
                    'item_name' => $item['name'],
                    'quantity_sold' => $quantity,
                    'gold_earned' => $totalGoldEarned
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
    error_log("Items API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Function to apply consumable item effects
function applyItemEffects($userId, $instanceId, $quantity) {
    global $pdo;
    
    try {
        // Get item attributes to determine effects
        $stmt = $pdo->prepare('
            SELECT ad.code, iia.value_num, iia.value_text
            FROM item_instance_attributes iia
            JOIN attribute_definitions ad ON iia.attribute_id = ad.id
            WHERE iia.instance_id = ?
        ');
        $stmt->execute([$instanceId]);
        $attributes = $stmt->fetchAll();
        
        $effects = [];
        
        foreach ($attributes as $attr) {
            $value = $attr['value_num'] ?: $attr['value_text'];
            $totalValue = $value * $quantity;
            
            switch ($attr['code']) {
                case 'hp_restore':
                    // Heal player
                    $stmt = $pdo->prepare('
                        UPDATE user_stats 
                        SET health = LEAST(health + ?, max_health)
                        WHERE user_id = ?
                    ');
                    $stmt->execute([$totalValue, $userId]);
                    $effects[] = "Restored {$totalValue} HP";
                    break;
                    
                case 'mp_restore':
                    // Restore mana (if we had a mana system)
                    $effects[] = "Restored {$totalValue} MP";
                    break;
                    
                case 'exp_boost':
                    // Give experience
                    $stmt = $pdo->prepare('
                        UPDATE user_stats 
                        SET experience = experience + ?
                        WHERE user_id = ?
                    ');
                    $stmt->execute([$totalValue, $userId]);
                    $effects[] = "Gained {$totalValue} experience";
                    break;
                    
                case 'gold_bonus':
                    // Give gold
                    $stmt = $pdo->prepare('
                        UPDATE user_stats 
                        SET gold = gold + ?
                        WHERE user_id = ?
                    ');
                    $stmt->execute([$totalValue, $userId]);
                    $effects[] = "Gained {$totalValue} gold";
                    break;
                    
                default:
                    // Unknown effect - just report it
                    $effects[] = "{$attr['code']}: {$totalValue}";
                    break;
            }
        }
        
        return implode(', ', $effects);
        
    } catch (Exception $e) {
        error_log("Failed to apply item effects: " . $e->getMessage());
        return "Item used successfully";
    }
}
?>

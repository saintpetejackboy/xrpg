<?php
// /api/shop.php - API for shop purchases
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
    $action   = $input['action']   ?? '';
    $itemId   = $input['item_id']  ?? 0;
    $quantity = $input['quantity'] ?? 1;

    if (!$action || !$itemId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    try {
        // We know the session stores ['user']['id']
        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            throw new Exception('User session invalid');
        }
    
    // Get character and current gold
    $stmt = $pdo->prepare('
        SELECT uc.id as character_id, us.gold, us.level, j.merchant_discount
        FROM user_characters uc
        JOIN user_stats us ON uc.user_id = us.user_id
        LEFT JOIN jobs j ON uc.job_id = j.id
        WHERE uc.user_id = ? AND uc.is_character_complete = 1
    ');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character) {
        throw new Exception('Character not found');
    }
    
    switch ($action) {
        case 'purchase':
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Get item details and shop info
                $stmt = $pdo->prepare('
                    SELECT i.*, ir.name as rarity_name, ir.base_multiplier,
                           si.price as shop_price, si.stock_qty
                    FROM items i
                    JOIN item_rarity ir ON i.rarity_id = ir.id
                    LEFT JOIN shop_inventory si ON i.id = si.item_id AND si.shop_id = 1
                    WHERE i.id = ?
                ');
                $stmt->execute([$itemId]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    throw new Exception('Item not found in shop');
                }
                
                // Check stock
                if ($item['stock_qty'] !== null && $item['stock_qty'] < $quantity) {
                    throw new Exception('Insufficient stock');
                }
                
                // Calculate price (use shop price if available, otherwise buy_value)
                $basePrice = $item['shop_price'] ?: $item['buy_value'];
                $merchantDiscount = $character['merchant_discount'] ?? 0;
                $finalPrice = floor($basePrice * (1 - $merchantDiscount / 100));
                $totalCost = $finalPrice * $quantity;
                
                // Check level requirement
                if ($character['level'] < $item['base_level']) {
                    throw new Exception('Character level too low for this item');
                }
                
                // Check if player has enough gold
                if ($character['gold'] < $totalCost) {
                    throw new Exception('Insufficient gold');
                }
                
                // Deduct gold
                $stmt = $pdo->prepare('
                    UPDATE user_stats 
                    SET gold = gold - ? 
                    WHERE user_id = ?
                ');
                $stmt->execute([$totalCost, $userId]);
                
                // Update shop stock if tracked
                if ($item['stock_qty'] !== null) {
                    $stmt = $pdo->prepare('
                        UPDATE shop_inventory 
                        SET stock_qty = stock_qty - ? 
                        WHERE shop_id = 1 AND item_id = ?
                    ');
                    $stmt->execute([$quantity, $itemId]);
                }
                
                // Create item instance(s) for player
                for ($i = 0; $i < $quantity; $i++) {
                    // Determine item level (for now, use item's base level)
                    $itemLevel = $item['base_level'];
                    
                    // Create item instance
                    $stmt = $pdo->prepare('
                        INSERT INTO item_instances 
                        (base_item_id, owner_char_id, level, rarity_id, quantity, acquired_at)
                        VALUES (?, ?, ?, ?, 1, NOW())
                    ');
                    $stmt->execute([
                        $itemId, 
                        $character['character_id'], 
                        $itemLevel, 
                        $item['rarity_id']
                    ]);
                    
                    $instanceId = $pdo->lastInsertId();
                    
                    // Generate item attributes based on templates
                    generateItemAttributes($instanceId, $itemId, $itemLevel, $item['rarity_id']);
                }
                
                $pdo->commit();
                
                // Get updated gold amount
                $newGold = $character['gold'] - $totalCost;
                
                echo json_encode([
                    'success' => true,
                    'message' => "Successfully purchased {$item['name']}!",
                    'item_name' => $item['name'],
                    'quantity' => $quantity,
                    'cost' => $totalCost,
                    'new_gold_amount' => $newGold
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
    error_log("Shop API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Function to generate item attributes based on templates
function generateItemAttributes($instanceId, $itemId, $itemLevel, $rarityId) {
    global $pdo;
    
    try {
        // Get attribute templates for this item
        $stmt = $pdo->prepare('
            SELECT iat.*, ad.code, ir.base_multiplier
            FROM item_attribute_templates iat
            JOIN attribute_definitions ad ON iat.attribute_id = ad.id
            JOIN item_rarity ir ON ir.id = ?
            WHERE iat.item_id = ?
        ');
        $stmt->execute([$rarityId, $itemId]);
        $templates = $stmt->fetchAll();
        
        foreach ($templates as $template) {
            // Calculate attribute value
            $minVal = $template['min_val'];
            $maxVal = $template['max_val'];
            
            // Apply rarity multiplier
            $rarityMultiplier = $template['base_multiplier'];
            $minVal = floor($minVal * $rarityMultiplier);
            $maxVal = floor($maxVal * $rarityMultiplier);
            
            // Roll random value in range
            $value = rand($minVal, $maxVal);
            
            // Apply level scaling if specified
            if ($template['scaling_rule'] === 'linear' && $itemLevel > 1) {
                $levelBonus = floor($value * 0.1 * ($itemLevel - 1));
                $value += $levelBonus;
            }
            
            // Insert attribute
            $stmt = $pdo->prepare('
                INSERT INTO item_instance_attributes 
                (instance_id, attribute_id, value_num)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$instanceId, $template['attribute_id'], $value]);
        }
        
    } catch (Exception $e) {
        error_log("Failed to generate item attributes: " . $e->getMessage());
        // Don't throw here - item creation should still succeed even if attributes fail
    }
}
?>

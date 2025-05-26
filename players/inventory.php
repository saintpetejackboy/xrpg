<?php
// /players/inventory.php - Player inventory and equipment management (DATABASE INTEGRATED)
session_start();
require_once __DIR__ . '/../config/environment.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    header('Location: /');
    exit;
}

// Connect to database and load utilities
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/load-preferences.php';

$userInfo = getUserInfo($_SESSION['user']);
$username = htmlspecialchars($userInfo['username']);
$userId = $userInfo['id'];

// Get character ID
try {
    $stmt = $pdo->prepare('SELECT id FROM user_characters WHERE user_id = ? AND is_character_complete = 1');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character) {
        header('Location: /players/character-creation.php');
        exit;
    }
    
    $characterId = $character['id'];
} catch (Exception $e) {
    error_log("Failed to load character: " . $e->getMessage());
    header('Location: /players/');
    exit;
}

// Load equipment slots
try {
    $stmt = $pdo->prepare('SELECT * FROM equip_slots ORDER BY slot_group, id');
    $stmt->execute();
    $equipSlots = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to load equip slots: " . $e->getMessage());
    $equipSlots = [];
}

// Load currently equipped items
try {
    $stmt = $pdo->prepare('
        SELECT 
            ce.slot_id, ce.equipped_at,
            ii.id as instance_id, ii.level, ii.quantity,
            i.name as item_name, i.description as item_description, i.item_type,
            ir.name as rarity_name, ir.color_hex as rarity_color, ir.emoji as rarity_emoji,
            es.code as slot_code, es.name as slot_name
        FROM character_equipment ce
        JOIN item_instances ii ON ce.instance_id = ii.id
        JOIN items i ON ii.base_item_id = i.id
        JOIN item_rarity ir ON ii.rarity_id = ir.id
        JOIN equip_slots es ON ce.slot_id = es.id
        WHERE ce.character_id = ?
        ORDER BY es.slot_group, es.id
    ');
    $stmt->execute([$characterId]);
    $equippedItems = $stmt->fetchAll();
    
    // Organize equipped items by slot ID
    $equippedBySlot = [];
    foreach ($equippedItems as $item) {
        $equippedBySlot[$item['slot_id']] = $item;
    }
} catch (Exception $e) {
    error_log("Failed to load equipped items: " . $e->getMessage());
    $equippedItems = [];
    $equippedBySlot = [];
}

// Load inventory items (not equipped)
try {
    $stmt = $pdo->prepare('
        SELECT 
            ii.id, ii.level, ii.quantity, ii.acquired_at,
            i.name as item_name, i.description as item_description, i.item_type,
            i.default_slot_id, es.code as slot_code,
            ir.name as rarity_name, ir.color_hex as rarity_color, ir.emoji as rarity_emoji
        FROM item_instances ii
        JOIN items i ON ii.base_item_id = i.id
        JOIN item_rarity ir ON ii.rarity_id = ir.id
        LEFT JOIN equip_slots es ON i.default_slot_id = es.id
        LEFT JOIN character_equipment ce ON ii.id = ce.instance_id AND ce.character_id = ?
        WHERE ii.owner_char_id = ? AND ce.instance_id IS NULL
        ORDER BY ii.acquired_at DESC, i.item_type, i.name
    ');
    $stmt->execute([$characterId, $characterId]);
    $inventoryItems = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Failed to load inventory items: " . $e->getMessage());
    $inventoryItems = [];
}

// Load item attributes for equipped and inventory items
$allItemIds = array_merge(
    array_column($equippedItems, 'instance_id'),
    array_column($inventoryItems, 'id')
);

$itemAttributes = [];
if (!empty($allItemIds)) {
    try {
        $placeholders = str_repeat('?,', count($allItemIds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                iia.instance_id,
                ad.name as attr_name, ad.unit, ad.data_type,
                iia.value_num, iia.value_text
            FROM item_instance_attributes iia
            JOIN attribute_definitions ad ON iia.attribute_id = ad.id
            WHERE iia.instance_id IN ($placeholders)
            ORDER BY iia.instance_id, ad.name
        ");
        $stmt->execute($allItemIds);
        $attributes = $stmt->fetchAll();
        
        // Organize attributes by instance ID
        foreach ($attributes as $attr) {
            if (!isset($itemAttributes[$attr['instance_id']])) {
                $itemAttributes[$attr['instance_id']] = [];
            }
            $itemAttributes[$attr['instance_id']][] = [
                'attr_name' => $attr['attr_name'],
                'value' => $attr['value_num'] ?: $attr['value_text'],
                'unit' => $attr['unit'] ?: '',
                'data_type' => $attr['data_type']
            ];
        }
    } catch (Exception $e) {
        error_log("Failed to load item attributes: " . $e->getMessage());
    }
}

// Load user preferences
$preferences = loadUserPreferences($pdo, $userId);

// Page-specific variables
$pageTitle = 'XRPG - Inventory';
$currentPage = 'inventory';
$headerTitle = 'XRPG - Inventory';
$footerInfo = 'Inventory Management • ' . $username;

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>
<link rel="stylesheet" href="/players/css/inventory.css">

<!-- Main Content -->
<main class="main-content">
    <div class="inventory-container">
        <!-- Equipment Section -->
        <div class="equipment-section">
            <h2 style="margin-top: 0;">⚔️ Equipment</h2>
            
            <!-- Character Equipment -->
            <div class="equipment-group">
                <div class="equipment-group-title">Character Gear</div>
                <div class="equipment-grid">
                    <?php foreach ($equipSlots as $slot): ?>
                        <?php if ($slot['slot_group'] === 'character'): ?>
                            <?php 
                            $equipped = $equippedBySlot[$slot['id']] ?? null;
                            $slotIcons = [
                                'head' => '🎩',
                                'chest' => '🎽',
                                'hands' => '🧤',
                                'left_hand' => '🗡️',
                                'right_hand' => '🛡️',
                                'legs' => '👖',
                                'boots' => '👢',
                                'ring' => '💍',
                                'necklace' => '📿'
                            ];
                            $icon = $slotIcons[$slot['code']] ?? '📦';
                            ?>
                            <div class="equipment-slot <?= $equipped ? '' : 'empty' ?>" 
                                 data-slot-id="<?= $slot['id'] ?>"
                                 data-slot-code="<?= htmlspecialchars($slot['code']) ?>"
                                 onclick="handleSlotClick(<?= $slot['id'] ?>, '<?= htmlspecialchars($slot['code']) ?>')">
                                <div class="slot-icon"><?= $icon ?></div>
                                <div class="slot-info">
                                    <div class="slot-name"><?= htmlspecialchars($slot['name']) ?></div>
                                    <?php if ($equipped): ?>
                                        <div class="item-name" style="color: <?= htmlspecialchars($equipped['rarity_color']) ?>">
                                            <?= htmlspecialchars($equipped['item_name']) ?>
                                        </div>
                                        <div class="item-level">Level <?= $equipped['level'] ?></div>
                                    <?php else: ?>
                                        <div class="item-name" style="color: var(--color-text-secondary)">Empty</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- House Equipment -->
            <div class="equipment-group">
                <div class="equipment-group-title">🏠 House Items</div>
                <div class="equipment-grid">
                    <?php
                    $houseSlots = array_filter($equipSlots, fn($slot) => $slot['slot_group'] === 'house');
                    $maxSlots = 10; // Default house slots
                    foreach ($houseSlots as $slot) {
                        $maxSlots = $slot['max_per_char'] ?? 10;
                        break;
                    }
                    ?>
                    <?php for ($i = 0; $i < $maxSlots; $i++): ?>
                        <div class="equipment-slot house-slot empty" 
                             data-slot-id="house-<?= $i ?>"
                             onclick="handleHouseSlotClick(<?= $i ?>)">
                            <div class="slot-icon">🏠</div>
                            <div class="slot-info">
                                <div class="slot-name">House Slot <?= $i + 1 ?></div>
                                <div class="item-name" style="color: var(--color-text-secondary)">Empty</div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- Inventory Section -->
        <div class="inventory-section">
            <h2 style="margin-top: 0;">🎒 Inventory</h2>
            
            <!-- Tabs -->
            <div class="inventory-tabs">
                <button class="inventory-tab active" onclick="switchTab('all')">All Items</button>
                <button class="inventory-tab" onclick="switchTab('weapon')">Weapons</button>
                <button class="inventory-tab" onclick="switchTab('armor')">Armor</button>
                <button class="inventory-tab" onclick="switchTab('consumable')">Consumables</button>
                <button class="inventory-tab" onclick="switchTab('material')">Materials</button>
                <button class="inventory-tab" onclick="switchTab('house')">House Items</button>
            </div>
            
            <!-- Inventory Grid -->
            <div class="inventory-grid" id="inventory-grid">
                <?php if (empty($inventoryItems)): ?>
                    <div class="no-items" style="grid-column: 1/-1;">
                        <p>🎒 Your inventory is empty!</p>
                        <p>Visit the <a href="/players/shop.php">shop</a> or complete dungeons to get items.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($inventoryItems as $item): ?>
                        <?php
                        $itemIcons = [
                            'weapon' => '⚔️',
                            'armor' => '🛡️',
                            'consumable' => '🧪',
                            'currency' => '💰',
                            'material' => '🔧',
                            'house' => '🏠',
                            'misc' => '📦'
                        ];
                        $icon = $itemIcons[$item['item_type']] ?? '📦';
                        ?>
                        <div class="inventory-item" 
                             data-item-type="<?= htmlspecialchars($item['item_type']) ?>"
                             data-instance-id="<?= $item['id'] ?>"
                             onclick="showItemDetails(<?= $item['id'] ?>)">
                            <div class="item-icon"><?= $icon ?></div>
                            <?php if ($item['rarity_emoji']): ?>
                                <div class="item-rarity"><?= htmlspecialchars($item['rarity_emoji']) ?></div>
                            <?php endif; ?>
                            <?php if ($item['quantity'] > 1): ?>
                                <div class="item-quantity">x<?= $item['quantity'] ?></div>
                            <?php endif; ?>
                            <div class="item-details">
                                <div class="item-detail-name" style="color: <?= htmlspecialchars($item['rarity_color']) ?>">
                                    <?= htmlspecialchars($item['item_name']) ?>
                                </div>
                                <div class="item-detail-level">Lv. <?= $item['level'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Item Details Modal -->
<div id="item-modal" class="item-modal" style="display: none;" onclick="closeItemModal(event)">
    <div class="item-modal-content" onclick="event.stopPropagation()">
        <div class="item-modal-header">
            <h3 class="item-modal-title" id="modal-item-name">Item Name</h3>
            <button class="item-modal-close" onclick="closeItemModal()">&times;</button>
        </div>
        
        <div id="modal-item-details">
            <!-- Item details will be inserted here -->
        </div>
        
        <div class="item-actions" id="modal-item-actions">
            <!-- Actions will be inserted here -->
        </div>
    </div>
</div>

<script>
// Store item data for quick access
window.itemData = <?= json_encode(array_merge($inventoryItems, $equippedItems)) ?>;
window.itemAttributes = <?= json_encode($itemAttributes) ?>;
window.equipSlots = <?= json_encode($equipSlots) ?>;
window.equippedBySlot = <?= json_encode($equippedBySlot) ?>;
</script>

<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/inventory.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>

<script>
    (function() {
    'use strict';

    // Debug function to check system state
    function debugInventorySystem() {
        console.group('XRPG Inventory System Debug');
        
        // Check required global objects
        console.log('XRPGPlayer available:', typeof window.XRPGPlayer !== 'undefined');
        console.log('itemData available:', typeof window.itemData !== 'undefined');
        console.log('itemData is array:', Array.isArray(window.itemData));
        console.log('equippedBySlot available:', typeof window.equippedBySlot !== 'undefined');
        console.log('itemAttributes available:', typeof window.itemAttributes !== 'undefined');
        
        // Check item data structure if available
        if (Array.isArray(window.itemData) && window.itemData.length > 0) {
            console.log('Sample item structure:', window.itemData[0]);
        }
        
        // Check event bindings
        const itemElements = document.querySelectorAll('.inventory-item');
        console.log('Inventory item elements found:', itemElements.length);
        
        console.groupEnd();
        
        return {
            dataAvailable: typeof window.itemData !== 'undefined' && 
                          typeof window.equippedBySlot !== 'undefined' && 
                          typeof window.itemAttributes !== 'undefined',
            itemCount: Array.isArray(window.itemData) ? window.itemData.length : 0,
            uiElementsFound: itemElements.length
        };
    }
    
    // Fix for showItemDetails function
    function fixedShowItemDetails(instanceId) {
        // Convert instanceId to number if it's a string
        instanceId = parseInt(instanceId, 10);
        
        if (isNaN(instanceId) || instanceId <= 0) {
            console.error('Invalid item ID provided:', instanceId);
            if (window.XRPGPlayer) window.XRPGPlayer.showStatus('Error: Invalid item ID', 'error', 3000);
            return;
        }
        
        // Check if itemData is available
        if (!window.itemData || !Array.isArray(window.itemData)) {
            console.error('Item data not available');
            if (window.XRPGPlayer) window.XRPGPlayer.showStatus('Error: Item data not loaded', 'error', 3000);
            return;
        }
        
        // Find the item
        const item = window.itemData.find(i => i && i.id === instanceId);
        if (!item) {
            console.error('Item not found with ID:', instanceId);
            if (window.XRPGPlayer) window.XRPGPlayer.showStatus('Error: Item not found', 'error', 3000);
            return;
        }
        
        // Rest of the function
        // Call the original function now that we've validated everything
        if (typeof window.showItemDetails === 'function') {
            window.showItemDetails(instanceId);
        } else {
            console.error('Original showItemDetails function not available');
        }
    }

    // Make the debug function available globally
    window.debugInventorySystem = debugInventorySystem;
    
    // Run debug on load
    document.addEventListener('DOMContentLoaded', function() {
        // Wait a bit to ensure all scripts have loaded
        setTimeout(function() {
            const debug = debugInventorySystem();
            
            if (!debug.dataAvailable) {
                console.error('Inventory system data not properly loaded!');
                if (window.XRPGPlayer && window.XRPGPlayer.showStatus) {
                    window.XRPGPlayer.showStatus('Inventory system error: Missing required data. Please refresh the page.', 'error', 8000);
                }
            }
            
            // Override item click handlers if they're not working
            document.querySelectorAll('.inventory-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    const itemId = parseInt(this.dataset.instanceId, 10);
                    if (!isNaN(itemId) && itemId > 0) {
                        fixedShowItemDetails(itemId);
                    }
                });
            });
        }, 1000);
    });
})();
</script>
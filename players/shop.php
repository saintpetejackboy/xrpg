<?php
// /players/shop.php - Game shop for purchasing items (DATABASE INTEGRATED)
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

// Get character and stats
try {
    $stmt = $pdo->prepare('
        SELECT uc.id as character_id, us.gold, us.level,
               j.merchant_discount
        FROM user_characters uc
        JOIN user_stats us ON uc.user_id = us.user_id
        LEFT JOIN jobs j ON uc.job_id = j.id
        WHERE uc.user_id = ? AND uc.is_character_complete = 1
    ');
    $stmt->execute([$userId]);
    $characterData = $stmt->fetch();
    
    if (!$characterData) {
        header('Location: /players/character-creation.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Failed to load character: " . $e->getMessage());
    header('Location: /players/');
    exit;
}

// Shop categories (hardcoded for now, could be moved to database)
$shopCategories = [
    'weapon' => [
        'name' => 'Weapons',
        'icon' => '⚔️',
        'description' => 'Sharp blades and powerful weapons for combat'
    ],
    'armor' => [
        'name' => 'Armor', 
        'icon' => '🛡️',
        'description' => 'Defensive gear to protect you in battle'
    ],
    'consumable' => [
        'name' => 'Consumables',
        'icon' => '🧪', 
        'description' => 'Helpful items for healing and enhancement'
    ],
    'material' => [
        'name' => 'Crafting',
        'icon' => '🔧',
        'description' => 'Resources for crafting and upgrading'
    ],
    'house' => [
        'name' => 'House',
        'icon' => '🏠',
        'description' => 'Furniture and decorations for your home'
    ]
];

// Load shop items from database
try {
    // First, let's get a default shop (shop ID 1) or create a generic shop
    $stmt = $pdo->prepare('
        SELECT 
            i.id, i.name, i.description, i.item_type, i.buy_value, i.base_level,
            ir.name as rarity_name, ir.color_hex as rarity_color, ir.emoji as rarity_emoji,
            si.stock_qty, si.price as shop_price
        FROM items i
        JOIN item_rarity ir ON i.rarity_id = ir.id
        LEFT JOIN shop_inventory si ON i.id = si.item_id AND si.shop_id = 1
        WHERE i.buy_value > 0 OR si.item_id IS NOT NULL
        ORDER BY i.item_type, i.base_level, i.name
    ');
    $stmt->execute();
    $dbItems = $stmt->fetchAll();

        // remove any accidental duplicate rows by item ID
    $unique = [];
    foreach ($dbItems as $row) {
        $unique[$row['id']] = $row;
    }
    $dbItems = array_values($unique);

    
    // Convert database items to shop format
    $shopItems = [];
    foreach ($dbItems as $dbItem) {
        // Use shop price if available, otherwise use buy_value
        $basePrice = $dbItem['shop_price'] ?: $dbItem['buy_value'];
        $inStock = $dbItem['stock_qty'] === null || $dbItem['stock_qty'] > 0;
        
        $shopItems[] = [
            'id' => $dbItem['id'],
            'name' => $dbItem['name'],
            'description' => $dbItem['description'],
            'category' => $dbItem['item_type'],
            'item_type' => $dbItem['item_type'],
            'base_price' => $basePrice,
            'level_req' => $dbItem['base_level'],
            'rarity' => $dbItem['rarity_name'],
            'rarity_color' => $dbItem['rarity_color'],
            'rarity_emoji' => $dbItem['rarity_emoji'],
            'in_stock' => $inStock,
            'stock_qty' => $dbItem['stock_qty']
        ];
    }
    
    // If no items found, create some basic items for the shop
    if (empty($shopItems)) {
        $shopItems = [
            [
                'id' => 1, 'name' => 'Simple Sword', 'description' => 'A basic iron sword for beginners.',
                'category' => 'weapon', 'item_type' => 'weapon', 'base_price' => 150, 'level_req' => 1,
                'rarity' => 'Common', 'rarity_color' => '#8e8e93', 'rarity_emoji' => '⚪', 'in_stock' => true
            ],
            [
                'id' => 2, 'name' => 'Wooden Shield', 'description' => 'A sturdy wooden shield for protection.',
                'category' => 'armor', 'item_type' => 'armor', 'base_price' => 80, 'level_req' => 1,
                'rarity' => 'Common', 'rarity_color' => '#8e8e93', 'rarity_emoji' => '⚪', 'in_stock' => true
            ],
            [
                'id' => 3, 'name' => 'Health Potion', 'description' => 'Restores health when consumed.',
                'category' => 'consumable', 'item_type' => 'consumable', 'base_price' => 25, 'level_req' => 1,
                'rarity' => 'Common', 'rarity_color' => '#8e8e93', 'rarity_emoji' => '⚪', 'in_stock' => true
            ]
        ];
    }
    
} catch (Exception $e) {
    error_log("Failed to load shop items: " . $e->getMessage());
    // Fallback to basic items
    $shopItems = [
        [
            'id' => 1, 'name' => 'Simple Sword', 'description' => 'A basic iron sword for beginners.',
            'category' => 'weapon', 'item_type' => 'weapon', 'base_price' => 150, 'level_req' => 1,
            'rarity' => 'Common', 'rarity_color' => '#8e8e93', 'rarity_emoji' => '⚪', 'in_stock' => true
        ]
    ];
}

// Load item stats for shop items
$shopItemIds = array_column($shopItems, 'id');
$itemStats = [];

if (!empty($shopItemIds)) {
    try {
        $placeholders = str_repeat('?,', count($shopItemIds) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                iat.item_id,
                ad.name as attr_name,
                iat.min_val, iat.max_val, iat.is_percent
            FROM item_attribute_templates iat
            JOIN attribute_definitions ad ON iat.attribute_id = ad.id
            WHERE iat.item_id IN ($placeholders)
            ORDER BY iat.item_id, ad.name
        ");
        $stmt->execute($shopItemIds);
        $attributes = $stmt->fetchAll();
        
        // Organize stats by item ID
        foreach ($attributes as $attr) {
            if (!isset($itemStats[$attr['item_id']])) {
                $itemStats[$attr['item_id']] = [];
            }
            $value = $attr['min_val'];
            if ($attr['max_val'] > $attr['min_val']) {
                $value = $attr['min_val'] . '-' . $attr['max_val'];
            }
            if ($attr['is_percent']) {
                $value .= '%';
            }
            $itemStats[$attr['item_id']][$attr['attr_name']] = $value;
        }
    } catch (Exception $e) {
        error_log("Failed to load item stats: " . $e->getMessage());
    }
}

// Add stats to shop items
foreach ($shopItems as &$item) {
    $item['stats'] = $itemStats[$item['id']] ?? [];
}

// Calculate prices with merchant discount
$merchantDiscount = $characterData['merchant_discount'] ?? 0;
foreach ($shopItems as &$item) {
    $item['final_price'] = $item['base_price'];
    if ($merchantDiscount > 0) {
        $item['final_price'] = floor($item['base_price'] * (1 - $merchantDiscount / 100));
        $item['discount_amount'] = $item['base_price'] - $item['final_price'];
    }
}

// Load user preferences
$preferences = loadUserPreferences($pdo, $userId);

// Page-specific variables
$pageTitle = 'XRPG - Shop';
$currentPage = 'shop';
$headerTitle = 'XRPG - Item Shop';
$footerInfo = 'Shopping • ' . $username . ' • ' . number_format($characterData['gold']) . ' Gold';

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>
<link rel="stylesheet" href="/players/css/shop.css">

<!-- Main Content -->
<main class="main-content">
    <!-- Shop Header -->
    <div class="shop-header">
        <div class="shop-title">
            <h1>🏪 Item Shop</h1>
            <p>Welcome to the finest shop in the realm! Browse our collection of weapons, armor, and magical items.</p>
        </div>
        <div class="player-gold">
            <div class="gold-display">
                <span class="gold-icon">💰</span>
                <span class="gold-amount"><?= number_format($characterData['gold']) ?></span>
                <span class="gold-label">Gold</span>
            </div>
            <?php if ($merchantDiscount > 0): ?>
                <div class="merchant-discount">
                    <span class="discount-icon">🛒</span>
                    <span class="discount-text"><?= $merchantDiscount ?>% Merchant Discount Active!</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Shop Categories -->
    <div class="shop-categories">
        <button class="category-tab active" onclick="filterCategory('all')">
            <span class="category-icon">🎯</span>
            <span class="category-name">All Items</span>
        </button>
        <?php foreach ($shopCategories as $categoryId => $category): ?>
            <button class="category-tab" onclick="filterCategory('<?= $categoryId ?>')">
                <span class="category-icon"><?= $category['icon'] ?></span>
                <span class="category-name"><?= $category['name'] ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Shop Grid -->
    <div class="shop-grid" id="shop-grid">
        <?php if (empty($shopItems)): ?>
            <div class="no-items">
                <h3>🏪 Shop is Currently Empty</h3>
                <p>The merchant is restocking. Please check back later!</p>
            </div>
        <?php else: ?>
            <?php foreach ($shopItems as $item): ?>
                <div class="shop-item <?= !$item['in_stock'] ? 'out-of-stock' : '' ?>" 
                     data-category="<?= $item['category'] ?>" 
                     data-item-id="<?= $item['id'] ?>"
                     onclick="showItemDetails(<?= $item['id'] ?>)">
                    
                    <!-- Item Header -->
                    <div class="item-header">
                        <div class="item-icon">
                            <?php
                            $icons = [
                                'weapon' => '⚔️', 'armor' => '🛡️', 'consumable' => '🧪',
                                'material' => '🔧', 'house' => '🏠'
                            ];
                            echo $icons[$item['item_type']] ?? '📦';
                            ?>
                        </div>
                        <div class="item-rarity" style="color: <?= $item['rarity_color'] ?>">
                            <?= $item['rarity_emoji'] ?> <?= $item['rarity'] ?>
                        </div>
                    </div>

                    <!-- Item Info -->
                    <div class="item-info">
                        <h3 class="item-name" style="color: <?= $item['rarity_color'] ?>">
                            <?= htmlspecialchars($item['name']) ?>
                        </h3>
                        <p class="item-description"><?= htmlspecialchars($item['description']) ?></p>
                        
                        <!-- Level Requirement -->
                        <div class="level-requirement">
                            <span class="level-icon">⭐</span>
                            <span>Level <?= $item['level_req'] ?> Required</span>
                        </div>
                    </div>

                    <!-- Item Stats Preview -->
                    <?php if (!empty($item['stats'])): ?>
                        <div class="item-stats-preview">
                            <?php $statCount = 0; ?>
                            <?php foreach ($item['stats'] as $statName => $statValue): ?>
                                <?php if ($statCount < 2): ?>
                                    <div class="stat-preview">
                                        <span class="stat-name"><?= $statName ?>:</span>
                                        <span class="stat-value"><?= $statValue ?></span>
                                    </div>
                                    <?php $statCount++; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (count($item['stats']) > 2): ?>
                                <div class="more-stats">+<?= count($item['stats']) - 2 ?> more stats...</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Price and Purchase -->
                    <div class="item-footer">
                        <div class="price-section">
                            <?php if (isset($item['discount_amount']) && $item['discount_amount'] > 0): ?>
                                <div class="original-price"><?= number_format($item['base_price']) ?></div>
                                <div class="discounted-price">
                                    <span class="price-amount"><?= number_format($item['final_price']) ?></span>
                                    <span class="price-currency">💰</span>
                                </div>
                                <div class="savings">Save <?= number_format($item['discount_amount']) ?>!</div>
                            <?php else: ?>
                                <div class="item-price">
                                    <span class="price-amount"><?= number_format($item['final_price']) ?></span>
                                    <span class="price-currency">💰</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$item['in_stock']): ?>
                            <div class="out-of-stock-badge">Out of Stock</div>
                        <?php elseif ($characterData['level'] < $item['level_req']): ?>
                            <div class="level-locked-badge">Level Locked</div>
                        <?php elseif ($characterData['gold'] < $item['final_price']): ?>
                            <div class="insufficient-gold-badge">Insufficient Gold</div>
                        <?php else: ?>
                            <button class="buy-button" onclick="event.stopPropagation(); purchaseItem(<?= $item['id'] ?>)">
                                🛒 Buy Now
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
        
        <div class="modal-actions" id="modal-item-actions">
            <!-- Actions will be inserted here -->
        </div>
    </div>
</div>
<?php
  // directly emit the PHP array as JS
  $shopItems = $shopItems; // already populated above
?>
<script>
  const shopItems      = <?= json_encode($shopItems, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK) ?>;
  const shopCategories = <?= json_encode($shopCategories, JSON_UNESCAPED_UNICODE) ?>;
  const playerData     = {
    gold: <?= (int)$characterData['gold'] ?>,
    level: <?= (int)$characterData['level'] ?>,
    merchantDiscount: <?= (int)$merchantDiscount ?>
  };
</script>


<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/shop.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>
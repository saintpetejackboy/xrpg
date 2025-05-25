<?php
// /players/character.php - Detailed character information page (REFACTORED VERSION)

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

// Get comprehensive character information
try {
    $stmt = $pdo->prepare('
        SELECT uc.*, r.name as race_name, r.description as race_description,
               r.strength_mod, r.vitality_mod, r.agility_mod, r.intelligence_mod, r.wisdom_mod, r.luck_mod,
               c.name as class_name, c.description as class_description, c.tier as class_tier,
               c.strength_bonus as class_str, c.vitality_bonus as class_vit, c.agility_bonus as class_agi,
               c.intelligence_bonus as class_int, c.wisdom_bonus as class_wis, c.luck_bonus as class_lck,
               j.name as job_name, j.description as job_description, j.category as job_category,
               j.strength_bonus as job_str, j.vitality_bonus as job_vit, j.agility_bonus as job_agi,
               j.intelligence_bonus as job_int, j.wisdom_bonus as job_wis, j.luck_bonus as job_lck,
               j.idle_gold_rate, j.merchant_discount,
               us.level, us.experience, us.gold, us.health, us.max_health,
               us.strength, us.vitality, us.agility, us.intelligence, us.wisdom, us.luck,
               us.class_experience, us.class_level, us.job_experience, us.job_level,
               us.last_idle_update
        FROM user_characters uc
        LEFT JOIN races r ON uc.race_id = r.id
        LEFT JOIN classes c ON uc.class_id = c.id  
        LEFT JOIN jobs j ON uc.job_id = j.id
        LEFT JOIN user_stats us ON uc.user_id = us.user_id
        WHERE uc.user_id = ? AND uc.is_character_complete = 1
    ');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character) {
        header('Location: /players/character-creation.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error loading character details: " . $e->getMessage());
    header('Location: /players/');
    exit;
}

// Calculate base stats breakdown
$baseStats = 10;
$racialMods = [
    'strength' => $character['strength_mod'],
    'vitality' => $character['vitality_mod'],
    'agility' => $character['agility_mod'],
    'intelligence' => $character['intelligence_mod'],
    'wisdom' => $character['wisdom_mod'],
    'luck' => $character['luck_mod']
];

$classBonuses = [
    'strength' => $character['class_str'],
    'vitality' => $character['class_vit'],
    'agility' => $character['class_agi'],
    'intelligence' => $character['class_int'],
    'wisdom' => $character['class_wis'],
    'luck' => $character['class_lck']
];

$jobBonuses = [
    'strength' => $character['job_str'],
    'vitality' => $character['job_vit'],
    'agility' => $character['job_agi'],
    'intelligence' => $character['job_int'],
    'wisdom' => $character['job_wis'],
    'luck' => $character['job_lck']
];

// Check change cooldowns
$classSelectedTime = strtotime($character['class_selected_at']);
$jobSelectedTime = strtotime($character['job_selected_at']);
$lastClassChange = $character['last_class_change'] ? strtotime($character['last_class_change']) : $classSelectedTime;
$lastJobChange = $character['last_job_change'] ? strtotime($character['last_job_change']) : $jobSelectedTime;

$threeDaysAgo = time() - (3 * 24 * 60 * 60);
$canChangeClass = $lastClassChange <= $threeDaysAgo;
$canChangeJob = $lastJobChange <= $threeDaysAgo;

$daysUntilClassChange = $canChangeClass ? 0 : ceil(($lastClassChange + (3 * 24 * 60 * 60) - time()) / (24 * 60 * 60));
$daysUntilJobChange = $canChangeJob ? 0 : ceil(($lastJobChange + (3 * 24 * 60 * 60) - time()) / (24 * 60 * 60));

// Load user preferences
$preferences = loadUserPreferences($pdo, $userId);

// Page-specific variables for common components
$pageTitle = 'XRPG - Character Details';
$currentPage = 'character';
$headerTitle = 'XRPG - Character Details';
$footerInfo = 'XRPG Character • ' . $username . ' • Level ' . $character['level'] . ' ' . htmlspecialchars($character['race_name']) . ' ' . htmlspecialchars($character['class_name']);

// Custom header actions for this page
$headerActions = '
    <button class="button" onclick="XRPGPlayer.goToDashboard()" title="Back to Dashboard">
        <span style="margin-right: 0.5rem;">🏠</span>Dashboard
    </button>
    <button class="button" onclick="XRPGPlayer.logout()" title="Logout">
        <span style="margin-right: 0.5rem;">🚪</span>Logout
    </button>
';

// Additional CSS for this page
$additionalCSS = '
.character-header {
    text-align: center;
    padding: 2rem;
    background: var(--gradient-accent);
    color: white;
    margin-bottom: 2rem;
    border-radius: var(--user-radius);
}

.character-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-breakdown {
    padding: 1rem;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.5);
}

.stat-breakdown h4 {
    margin: 0 0 0.5rem 0;
    color: var(--color-accent);
    text-align: center;
}

.stat-component {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    font-size: 0.875rem;
}

.stat-component:last-child {
    border-top: 1px solid var(--color-border);
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    font-weight: bold;
    color: var(--color-accent);
}

.stat-value {
    font-weight: bold;
}

.stat-value.positive {
    color: #4caf50;
}

.stat-value.negative {
    color: #f44336;
}

.stat-value.neutral {
    color: var(--color-text);
}

.progress-bar {
    width: 100%;
    height: 12px;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.25);
    overflow: hidden;
    margin: 0.5rem 0;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.health-bar {
    background: linear-gradient(90deg, #ff4444, #ffaa00, #44ff44);
}

.exp-bar {
    background: var(--gradient-accent);
}

.class-exp-bar {
    background: linear-gradient(90deg, #ff9800, #ffc107);
}

.job-exp-bar {
    background: linear-gradient(90deg, #9c27b0, #e91e63);
}

.character-summary {
    background: var(--color-surface-alt);
    padding: 1.5rem;
    border-radius: var(--user-radius);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--color-border);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    font-weight: bold;
    color: var(--color-text);
}

.summary-value {
    color: var(--color-accent);
    font-weight: bold;
}

.cooldown-info {
    color: var(--color-muted);
    font-size: 0.75rem;
    font-style: italic;
}

.level-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.level-card {
    text-align: center;
    padding: 1rem;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.5);
}

.level-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-accent);
}

.level-type {
    color: var(--color-muted);
    font-size: 0.875rem;
}

.abilities-section {
    margin-top: 1rem;
}

.ability-tag {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: rgba(var(--user-accent), 0.2);
    border-radius: calc(var(--user-radius) * 0.5);
    margin: 0.25rem;
    color: var(--color-accent);
    font-size: 0.875rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.tier-badge {
    background: var(--gradient-accent);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: calc(var(--user-radius) * 0.25);
    font-size: 0.75rem;
}
';

// Additional global JS variables
$additionalGlobalJS = '
window.canChangeClass = ' . ($canChangeClass ? 'true' : 'false') . ';
window.canChangeJob = ' . ($canChangeJob ? 'true' : 'false') . ';
window.characterData = ' . json_encode($character) . ';
';

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>

<!-- Main Content -->
<main class="main-content">
    <!-- Character Header -->
    <div class="character-header">
        <h1 style="margin: 0 0 0.5rem 0;">⚔️ <?= $username ?></h1>
        <p style="margin: 0; opacity: 0.9;">
            Level <?= $character['level'] ?> <?= htmlspecialchars($character['race_name']) ?> 
            <?= htmlspecialchars($character['class_name']) ?>
            <?php if ($character['class_tier'] > 1): ?>
                <span class="tier-badge">Tier <?= $character['class_tier'] ?></span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Character Grid -->
    <div class="character-grid">
        <!-- Character Summary -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">📋 Character Information</h3>
            
            <div class="character-summary">
                <div class="summary-row">
                    <span class="summary-label">Race:</span>
                    <span class="summary-value"><?= htmlspecialchars($character['race_name']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Class:</span>
                    <span class="summary-value">
                        <?= htmlspecialchars($character['class_name']) ?>
                        <?php if (!$canChangeClass): ?>
                            <div class="cooldown-info">Can change in <?= $daysUntilClassChange ?> day(s)</div>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Job:</span>
                    <span class="summary-value">
                        <?= htmlspecialchars($character['job_name']) ?> (<?= ucfirst($character['job_category']) ?>)
                        <?php if (!$canChangeJob): ?>
                            <div class="cooldown-info">Can change in <?= $daysUntilJobChange ?> day(s)</div>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Gold:</span>
                    <span class="summary-value"><?= number_format($character['gold']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Created:</span>
                    <span class="summary-value"><?= date('M j, Y', strtotime($character['character_created_at'])) ?></span>
                </div>
            </div>
            
            <!-- Level Information -->
            <div class="level-info">
                <div class="level-card">
                    <div class="level-number"><?= $character['level'] ?></div>
                    <div class="level-type">Character Level</div>
                </div>
                <div class="level-card">
                    <div class="level-number"><?= $character['class_level'] ?></div>
                    <div class="level-type">Class Level</div>
                </div>
                <div class="level-card">
                    <div class="level-number"><?= $character['job_level'] ?></div>
                    <div class="level-type">Job Level</div>
                </div>
            </div>
        </div>

        <!-- Health & Experience -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">💖 Health & Experience</h3>
            
            <!-- Health -->
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-weight: bold;">Health</span>
                    <span><?= $character['health'] ?>/<?= $character['max_health'] ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill health-bar" style="width: <?= ($character['health'] / $character['max_health']) * 100 ?>%;"></div>
                </div>
            </div>
            
            <!-- Character Experience -->
            <div style="margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-weight: bold;">Character Experience</span>
                    <span><?= number_format($character['experience']) ?>/<?= number_format($character['level'] * 1000) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill exp-bar" style="width: <?= min(($character['experience'] / ($character['level'] * 1000)) * 100, 100) ?>%;"></div>
                </div>
            </div>
            
            <!-- Class Experience -->
            <div style="margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-weight: bold;">Class Experience</span>
                    <span><?= number_format($character['class_experience']) ?>/<?= number_format($character['class_level'] * 500) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill class-exp-bar" style="width: <?= min(($character['class_experience'] / ($character['class_level'] * 500)) * 100, 100) ?>%;"></div>
                </div>
            </div>
            
            <!-- Job Experience -->
            <div style="margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-weight: bold;">Job Experience</span>
                    <span><?= number_format($character['job_experience']) ?>/<?= number_format($character['job_level'] * 300) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill job-exp-bar" style="width: <?= min(($character['job_experience'] / ($character['job_level'] * 300)) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Job Benefits -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">💼 Job Benefits</h3>
            
            <div class="abilities-section">
                <div class="ability-tag">
                    💰 <?= number_format($character['idle_gold_rate'], 2) ?>x Idle Gold
                </div>
                
                <?php if ($character['merchant_discount'] > 0): ?>
                    <div class="ability-tag">
                        🛒 <?= $character['merchant_discount'] ?>% Merchant Discount
                    </div>
                <?php endif; ?>
                
                <div class="ability-tag">
                    📂 <?= ucfirst($character['job_category']) ?> Category
                </div>
            </div>
            
            <p style="margin-top: 1rem; color: var(--color-text-secondary); font-style: italic;">
                "<?= htmlspecialchars($character['job_description']) ?>"
            </p>
        </div>
    </div>

    <!-- Stat Breakdown -->
    <div class="card" style="padding: 2rem;">
        <h3 style="margin-top: 0;">📊 Detailed Stat Breakdown</h3>
        <p style="color: var(--color-text-secondary);">See how your race, class, and job contribute to your final stats.</p>
        
        <div class="stat-breakdown-grid">
            <?php
            $statNames = [
                'strength' => 'Strength',
                'vitality' => 'Vitality', 
                'agility' => 'Agility',
                'intelligence' => 'Intelligence',
                'wisdom' => 'Wisdom',
                'luck' => 'Luck'
            ];
            
            foreach ($statNames as $stat => $displayName):
                $currentValue = $character[$stat];
                $racialMod = $racialMods[$stat];
                $classBonus = $classBonuses[$stat];
                $jobBonus = $jobBonuses[$stat];
            ?>
                <div class="stat-breakdown">
                    <h4><?= $displayName ?></h4>
                    
                    <div class="stat-component">
                        <span>Base:</span>
                        <span class="stat-value neutral"><?= $baseStats ?></span>
                    </div>
                    
                    <div class="stat-component">
                        <span>Race (<?= htmlspecialchars($character['race_name']) ?>):</span>
                        <span class="stat-value <?= $racialMod > 0 ? 'positive' : ($racialMod < 0 ? 'negative' : 'neutral') ?>">
                            <?= $racialMod > 0 ? '+' : '' ?><?= $racialMod ?>
                        </span>
                    </div>
                    
                    <div class="stat-component">
                        <span>Class (<?= htmlspecialchars($character['class_name']) ?>):</span>
                        <span class="stat-value <?= $classBonus > 0 ? 'positive' : 'neutral' ?>">
                            <?= $classBonus > 0 ? '+' : '' ?><?= $classBonus ?>
                        </span>
                    </div>
                    
                    <div class="stat-component">
                        <span>Job (<?= htmlspecialchars($character['job_name']) ?>):</span>
                        <span class="stat-value <?= $jobBonus > 0 ? 'positive' : 'neutral' ?>">
                            <?= $jobBonus > 0 ? '+' : '' ?><?= $jobBonus ?>
                        </span>
                    </div>
                    
                    <div class="stat-component">
                        <span>Total:</span>
                        <span class="stat-value"><?= $currentValue ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="button" onclick="XRPGPlayer.goToDashboard()">
            🏠 Dashboard
        </button>
        
        <?php if ($canChangeClass || $canChangeJob): ?>
            <button class="button" onclick="window.location.href='/players/change-class-job.php'" style="background: rgba(255, 193, 7, 0.2); border-color: rgba(255, 193, 7, 0.4);">
                🔄 Change Class/Job
            </button>
        <?php endif; ?>
        
        <button class="button" onclick="XRPGPlayer.goToSettings()">
            ⚙️ Settings
        </button>
    </div>

<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/character.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>
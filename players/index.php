<?php
// /players/index.php - Main game dashboard (REFACTORED VERSION)

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

// Check if character creation is complete
try {
    $stmt = $pdo->prepare('SELECT * FROM user_characters WHERE user_id = ?');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character || !$character['is_character_complete']) {
        header('Location: /players/character-creation.php');
        exit;
    }
    
    // Get character details for display
    $stmt = $pdo->prepare('
        SELECT uc.*, r.name as race_name, c.name as class_name, j.name as job_name,
               r.description as race_description, c.description as class_description, j.description as job_description
        FROM user_characters uc
        LEFT JOIN races r ON uc.race_id = r.id
        LEFT JOIN classes c ON uc.class_id = c.id  
        LEFT JOIN jobs j ON uc.job_id = j.id
        WHERE uc.user_id = ?
    ');
    $stmt->execute([$userId]);
    $characterDetails = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Failed to load character: " . $e->getMessage());
    header('Location: /players/character-creation.php');
    exit;
}

// Get user stats
try {
    $stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        header('Location: /players/character-creation.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Failed to load user stats: " . $e->getMessage());
    header('Location: /players/character-creation.php');
    exit;
}

// Load user preferences
$preferences = loadUserPreferences($pdo, $userId);

// Get recent activity
try {
    $stmt = $pdo->prepare('SELECT * FROM auth_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$userId]);
    $recentActivity = $stmt->fetchAll();
} catch (Exception $e) {
    $recentActivity = [];
}

// Check if class/job changes are allowed
$canChangeClass = true;
$canChangeJob = true;
$daysUntilClassChange = 0;
$daysUntilJobChange = 0;

if ($characterDetails) {
    $classSelectedTime = strtotime($characterDetails['class_selected_at']);
    $jobSelectedTime = strtotime($characterDetails['job_selected_at']);
    $lastClassChange = $characterDetails['last_class_change'] ? strtotime($characterDetails['last_class_change']) : $classSelectedTime;
    $lastJobChange = $characterDetails['last_job_change'] ? strtotime($characterDetails['last_job_change']) : $jobSelectedTime;
    
    $threeDaysAgo = time() - (3 * 24 * 60 * 60);
    
    if ($lastClassChange > $threeDaysAgo) {
        $canChangeClass = false;
        $daysUntilClassChange = ceil(($lastClassChange + (3 * 24 * 60 * 60) - time()) / (24 * 60 * 60));
    }
    
    if ($lastJobChange > $threeDaysAgo) {
        $canChangeJob = false;
        $daysUntilJobChange = ceil(($lastJobChange + (3 * 24 * 60 * 60) - time()) / (24 * 60 * 60));
    }
}

// Page-specific variables for common components
$pageTitle = 'XRPG - Dashboard';
$currentPage = 'dashboard';
$headerTitle = 'XRPG - Dashboard';
$footerInfo = 'XRPG Dashboard • Character: ' . $username . ' • Level ' . $stats['level'] . ' ' . htmlspecialchars($characterDetails['race_name']) . ' ' . htmlspecialchars($characterDetails['class_name']);

// Additional CSS for this page
$additionalCSS = '
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
}

.stat-card {
    text-align: center;
    padding: 1rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-accent);
}

.stat-label {
    color: var(--color-muted);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.25);
    overflow: hidden;
    margin: 0.5rem 0;
}

.progress-fill {
    height: 100%;
    background: var(--gradient-accent);
    transition: width 0.3s ease;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.5);
}

.activity-time {
    color: var(--color-muted);
    font-size: 0.75rem;
}

.health-bar {
    background: linear-gradient(90deg, #ff4444, #ffaa00, #44ff44);
}

.exp-bar {
    background: var(--gradient-accent);
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.quick-action {
    text-align: center;
    padding: 1.5rem 1rem;
    font-size: 0.875rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.quick-action .icon {
    font-size: 2rem;
}

.welcome-banner {
    background: var(--gradient-accent);
    color: white;
    text-align: center;
    padding: 2rem;
    border-radius: var(--user-radius);
    margin-bottom: 2rem;
}

.character-info {
    background: var(--color-surface-alt);
    padding: 1.5rem;
    border-radius: var(--user-radius);
    margin-bottom: 2rem;
}

.character-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--color-border);
}

.character-detail:last-child {
    border-bottom: none;
}

.character-label {
    font-weight: 600;
    color: var(--color-text);
}

.character-value {
    color: var(--color-accent);
    font-weight: bold;
}

.logout-section {
    border-top: 1px solid var(--color-border);
    padding-top: 1rem;
    margin-top: 1rem;
}

.change-cooldown {
    color: var(--color-muted);
    font-size: 0.75rem;
    font-style: italic;
}

.enhanced-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.enhanced-stat {
    text-align: center;
    padding: 0.75rem;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.5);
}

.enhanced-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--color-accent);
}

.enhanced-stat-name {
    color: var(--color-muted);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}
';

// Additional global JS variables
$additionalGlobalJS = '
window.canChangeClass = ' . ($canChangeClass ? 'true' : 'false') . ';
window.canChangeJob = ' . ($canChangeJob ? 'true' : 'false') . ';
';

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>

<!-- Main Content -->
<main class="main-content">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <h1 style="margin: 0 0 0.5rem 0;">Welcome back, <?= $username ?>! 🎮</h1>
        <p style="margin: 0; opacity: 0.9;">Level <?= $stats['level'] ?> <?= htmlspecialchars($characterDetails['race_name']) ?> <?= htmlspecialchars($characterDetails['class_name']) ?></p>
    </div>

    <!-- Character Information -->
    <div class="character-info">
        <h3 style="margin-top: 0; color: var(--color-accent);">🧬 Character Information</h3>
        <div class="character-detail">
            <span class="character-label">Race:</span>
            <span class="character-value"><?= htmlspecialchars($characterDetails['race_name']) ?></span>
        </div>
        <div class="character-detail">
            <span class="character-label">Class:</span>
            <span class="character-value">
                <?= htmlspecialchars($characterDetails['class_name']) ?> (Level <?= $stats['class_level'] ?>)
                <?php if (!$canChangeClass): ?>
                    <div class="change-cooldown">Can change in <?= $daysUntilClassChange ?> day(s)</div>
                <?php endif; ?>
            </span>
        </div>
        <div class="character-detail">
            <span class="character-label">Job:</span>
            <span class="character-value">
                <?= htmlspecialchars($characterDetails['job_name']) ?> (Level <?= $stats['job_level'] ?>)
                <?php if (!$canChangeJob): ?>
                    <div class="change-cooldown">Can change in <?= $daysUntilJobChange ?> day(s)</div>
                <?php endif; ?>
            </span>
        </div>
        <div class="character-detail">
            <span class="character-label">Gold Rate:</span>
            <span class="character-value"><?= number_format($stats['idle_gold_rate'], 2) ?>x per hour</span>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Character Stats -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">📊 Character Stats</h3>
            
            <div class="stats-grid">
                <div class="stat-card surface">
                    <div class="stat-value"><?= htmlspecialchars($stats['level']) ?></div>
                    <div class="stat-label">Level</div>
                </div>
                <div class="stat-card surface">
                    <div class="stat-value"><?= number_format($stats['gold']) ?></div>
                    <div class="stat-label">Gold</div>
                </div>
            </div>
            
            <!-- Enhanced Stats -->
            <div class="enhanced-stats-grid">
                <div class="enhanced-stat">
                    <div class="enhanced-stat-value"><?= $stats['strength'] ?></div>
                    <div class="enhanced-stat-name">Strength</div>
                </div>
                <div class="enhanced-stat">
                    <div class="enhanced-stat-value"><?= $stats['vitality'] ?></div>
                    <div class="enhanced-stat-name">Vitality</div>
                </div>
                <div class="enhanced-stat">
                    <div class="enhanced-stat-value"><?= $stats['agility'] ?></div>
                    <div class="enhanced-stat-name">Agility</div>
                </div>
                <div class="enhanced-stat">
                    <div class="enhanced-stat-value"><?= $stats['intelligence'] ?></div>
                    <div class="enhanced-stat-name">Intelligence</div>
                </div>
                <div class="enhanced-stat">
                    <div class="enhanced-stat-value"><?= $stats['wisdom'] ?></div>
                    <div class="enhanced-stat-name">Wisdom</div>
                </div>
                <div class="enhanced-stat">
                    <div class="enhanced-stat-value"><?= $stats['luck'] ?></div>
                    <div class="enhanced-stat-name">Luck</div>
                </div>
            </div>
            
            <!-- Health Bar -->
            <div style="margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.875rem;">Health</span>
                    <span style="font-size: 0.875rem;"><?= $stats['health'] ?>/<?= $stats['max_health'] ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill health-bar" style="width: <?= ($stats['health'] / $stats['max_health']) * 100 ?>%;"></div>
                </div>
            </div>
            
            <!-- Experience Bar -->
            <div style="margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.875rem;">Experience</span>
                    <span style="font-size: 0.875rem;"><?= number_format($stats['experience']) ?>/<?= number_format($stats['level'] * 1000) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill exp-bar" style="width: <?= min(($stats['experience'] / ($stats['level'] * 1000)) * 100, 100) ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">⚡ Quick Actions</h3>
            
            <div class="action-grid">
                <button class="button quick-action" onclick="XRPGPlayer.goToDungeon()">
                    <span class="icon">🏰</span>
                    <span>Enter Dungeon</span>
                </button>
                
                <button class="button quick-action" onclick="XRPGPlayer.goToCharacter()">
                    <span class="icon">⚔️</span>
                    <span>Character</span>
                </button>
                
                <button class="button quick-action" onclick="XRPGPlayer.goToInventory()">
                    <span class="icon">🎒</span>
                    <span>Inventory</span>
                </button>
                
                <button class="button quick-action" onclick="XRPGPlayer.goToSettings()">
                    <span class="icon">⚙️</span>
                    <span>Settings</span>
                </button>
                
                <button class="button quick-action" onclick="openMap()">
                    <span class="icon">🗺️</span>
                    <span>World Map</span>
                </button>
                
                <?php if ($canChangeClass || $canChangeJob): ?>
                    <button class="button quick-action" onclick="changeClassJob()" style="background: rgba(255, 193, 7, 0.2); border-color: rgba(255, 193, 7, 0.4);">
                        <span class="icon">🔄</span>
                        <span>Change Class/Job</span>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="logout-section">
                <button class="button" onclick="XRPGPlayer.logout()" style="width: 100%; background: rgba(255, 100, 100, 0.2); border-color: rgba(255, 100, 100, 0.4);">
                    <span style="margin-right: 0.5rem;">🚪</span>Logout Safely
                </button>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">📋 Recent Activity</h3>
            
            <?php if (empty($recentActivity)): ?>
                <div class="activity-item">
                    <span>🎉 Character created!</span>
                    <span class="activity-time">Just now</span>
                </div>
                <div class="activity-item">
                    <span>⚔️ Ready for adventure</span>
                    <span class="activity-time">Now</span>
                </div>
            <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <span>
                            <?php
                            $icon = match($activity['event_type']) {
                                'login' => '🔑',
                                'passkey_register' => '🆕',
                                'logout' => '🚪',
                                'other' => '⚙️',
                                default => '📝'
                            };
                            echo $icon . ' ' . htmlspecialchars($activity['description']);
                            ?>
                        </span>
                        <span class="activity-time"><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Game News/Updates -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="margin-top: 0;">📰 Game Updates</h3>
            <div id="dashboard-updates">
                <div class="activity-item">
                    <span>⏳ Loading latest updates...</span>
                    <span class="activity-time">Now</span>
                </div>
            </div>
        </div>
    </div>

<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/dashboard.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>
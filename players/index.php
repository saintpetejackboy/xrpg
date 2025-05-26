<?php
// /players/index.php - Main game dashboard (REFACTORED VERSION)


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

    // pull the last 10 mixed events from user_activity + auth_log in real time order
$sql = "
    SELECT created_at, event_type, description
    FROM (
        SELECT created_at, event_type, description
          FROM user_activity
         WHERE user_id = :uid

        UNION ALL

        SELECT created_at, event_type, description
          FROM auth_log
         WHERE user_id = :uid
    ) AS combined
    ORDER BY created_at DESC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->execute();
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
$footerInfo =  $username . ' • Level ' . $stats['level'] . ' ' . htmlspecialchars($characterDetails['race_name']) . ' ' . htmlspecialchars($characterDetails['class_name']);

// Additional global JS variables
$additionalGlobalJS = '
window.canChangeClass = ' . ($canChangeClass ? 'true' : 'false') . ';
window.canChangeJob = ' . ($canChangeJob ? 'true' : 'false') . ';
';

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>
<link rel="stylesheet" href="players/css/dashboard.css">
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
                
                <button class="button quick-action" onclick="XRPGPlayer.goToSettings()">
                    <span class="icon">⚙️</span>
                    <span>Settings</span>
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
                    <div class="activity-item" style="max-height: 40vh; overflow-y: scroll;">
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
            <div id="dashboard-updates" style="max-height: 40vh; overflow-y: scroll;">
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
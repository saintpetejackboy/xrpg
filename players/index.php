<?php
// /players/index.php - Main game dashboard with character creation flow

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    header('Location: /');
    exit;
}

$user = $_SESSION['user'];
$username = htmlspecialchars($user['username']);
$userId = $user['id'];

// Connect to database for user stats and preferences
require_once __DIR__ . '/../config/db.php';

// Check if character creation is complete
try {
    $stmt = $pdo->prepare('SELECT * FROM user_characters WHERE user_id = ?');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if (!$character || !$character['is_character_complete']) {
        // Character creation not complete, redirect to character creation
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
    // Redirect to character creation if there's an issue
    header('Location: /players/character-creation.php');
    exit;
}

// Get user stats (now with enhanced stats)
try {
    $stmt = $pdo->prepare('SELECT * FROM user_stats WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        // This shouldn't happen if character creation worked, but just in case
        header('Location: /players/character-creation.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Failed to load user stats: " . $e->getMessage());
    header('Location: /players/character-creation.php');
    exit;
}

// Get user preferences (create if not exists)
try {
    $stmt = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ?');
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch();
    
    if (!$preferences) {
        // Create default preferences for new user
        $stmt = $pdo->prepare('
            INSERT INTO user_preferences (user_id, theme_mode, accent_color, accent_secondary, border_radius, shadow_intensity, ui_opacity, font_family) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, 'dark', '#5299e0', '#81aaff', 18, 0.36, 0.96, 'sans']);
        
        // Fetch the newly created record
        $stmt = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ?');
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch();
    }
} catch (Exception $e) {
    error_log("Failed to load user preferences: " . $e->getMessage());
    // Use defaults if database fails
    $preferences = [
        'theme_mode' => 'dark',
        'accent_color' => '#5299e0',
        'accent_secondary' => '#81aaff',
        'border_radius' => 18,
        'shadow_intensity' => 0.36,
        'ui_opacity' => 0.96,
        'font_family' => 'sans'
    ];
}

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
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($preferences['theme_mode']) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XRPG - Dashboard</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/ico/favicon-16x16.png">
    <link rel="shortcut icon" href="/assets/ico/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <style>
        :root {
            --user-accent: <?= htmlspecialchars($preferences['accent_color']) ?>;
            --user-accent2: <?= htmlspecialchars($preferences['accent_secondary']) ?>;
            --user-radius: <?= intval($preferences['border_radius']) ?>px;
            --user-shadow-intensity: <?= floatval($preferences['shadow_intensity']) ?>;
            --user-opacity: <?= floatval($preferences['ui_opacity']) ?>;
            --user-font: var(--font-<?= htmlspecialchars($preferences['font_family']) ?>);
        }
        
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
    </style>
</head>
<body class="authenticated">
    <!-- Fixed Theme Toggle -->
    <button id="theme-toggle" class="theme-toggle-fixed" title="Toggle light/dark mode">
        <?= $preferences['theme_mode'] === 'dark' ? '🌞' : '🌙' ?>
    </button>

    <!-- Side Navigation -->
    <nav class="side-nav">
        <button class="side-nav-toggle" title="Toggle menu">☰</button>
        <div class="side-nav-items">
            <a href="/players/" class="side-nav-item active" title="Dashboard">
                <span class="side-nav-icon">🏠</span>
                <span class="side-nav-text">Dashboard</span>
            </a>
            <a href="/players/character.php" class="side-nav-item" title="Character">
                <span class="side-nav-icon">⚔️</span>
                <span class="side-nav-text">Character</span>
            </a>
            <a href="/players/inventory.php" class="side-nav-item" title="Inventory">
                <span class="side-nav-icon">🎒</span>
                <span class="side-nav-text">Inventory</span>
            </a>
            <a href="/players/dungeon.php" class="side-nav-item" title="Dungeons">
                <span class="side-nav-icon">🏰</span>
                <span class="side-nav-text">Dungeons</span>
            </a>
            <a href="/players/guild.php" class="side-nav-item" title="Guild">
                <span class="side-nav-icon">👥</span>
                <span class="side-nav-text">Guild</span>
            </a>
            <a href="/players/settings.php" class="side-nav-item" title="Settings">
                <span class="side-nav-icon">⚙️</span>
                <span class="side-nav-text">Settings</span>
            </a>
        </div>
    </nav>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-title">XRPG - Dashboard</div>
        <div class="header-actions">
            <span style="margin-right: 1rem; color: var(--color-muted);">Welcome, <?= $username ?>!</span>
            <button class="button" onclick="logout()" title="Logout">
                <span style="margin-right: 0.5rem;">🚪</span>Logout
            </button>
        </div>
    </header>

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
                    <button class="button quick-action" onclick="quickDungeon()">
                        <span class="icon">🏰</span>
                        <span>Enter Dungeon</span>
                    </button>
                    
                    <button class="button quick-action" onclick="openCharacter()">
                        <span class="icon">⚔️</span>
                        <span>Character</span>
                    </button>
                    
                    <button class="button quick-action" onclick="openInventory()">
                        <span class="icon">🎒</span>
                        <span>Inventory</span>
                    </button>
                    
                    <button class="button quick-action" onclick="visitSettings()">
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
                    <button class="button" onclick="logout()" style="width: 100%; background: rgba(255, 100, 100, 0.2); border-color: rgba(255, 100, 100, 0.4);">
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

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-links">
                <a href="/players/help.php">Help & Guide</a>
                <a href="/players/support.php">Support</a>
                <a href="/" onclick="logout(); return false;">Logout</a>
            </div>
            <div class="footer-info">
                <p>XRPG Dashboard • Character: <?= $username ?> • Level <?= $stats['level'] ?> <?= htmlspecialchars($characterDetails['race_name']) ?> <?= htmlspecialchars($characterDetails['class_name']) ?></p>
                <p>&copy; 2025 XRPG. All rights reserved.</p>
            </div>
        </footer>
    </main>

    <script>
        // Pass user preferences to JavaScript for theme system
        const userPreferences = <?= json_encode($preferences) ?>;
        const canChangeClass = <?= $canChangeClass ? 'true' : 'false' ?>;
        const canChangeJob = <?= $canChangeJob ? 'true' : 'false' ?>;
    </script>
    <script src="/assets/js/theme.js?v=4"></script>
    <script>
        // Dashboard-specific JavaScript
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('/auth/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(() => {
                    window.location.href = '/';
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    // Force redirect even if logout request fails
                    window.location.href = '/';
                });
            }
        }
        
        function quickDungeon() {
            window.location.href = '/players/dungeon.php';
        }
        
        function openCharacter() {
            window.location.href = '/players/character.php';
        }
        
        function openInventory() {
            window.location.href = '/players/inventory.php';
        }
        
        function visitSettings() {
            window.location.href = '/players/settings.php';
        }
        
        function openMap() {
            window.location.href = '/players/map.php';
        }
        
        function changeClassJob() {
            if (canChangeClass || canChangeJob) {
                window.location.href = '/players/change-class-job.php';
            } else {
                alert('You cannot change your class or job at this time. Please wait for the cooldown period to end.');
            }
        }
        
        // Load dashboard updates
        async function loadDashboardUpdates() {
            try {
                const response = await fetch('/api/updates.php');
                if (response.ok) {
                    const data = await response.json();
                    const container = document.getElementById('dashboard-updates');
                    
                    if (data.updates && data.updates.length > 0) {
                        container.innerHTML = data.updates.slice(0, 3).map(update => `
                            <div class="activity-item">
                                <span>${update.emoji} ${update.message}</span>
                                <span class="activity-time">${update.timeAgo}</span>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="activity-item">
                                <span>🎮 Welcome to XRPG!</span>
                                <span class="activity-time">Now</span>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Failed to load updates:', error);
                // Fallback content
                const container = document.getElementById('dashboard-updates');
                if (container) {
                    container.innerHTML = `
                        <div class="activity-item">
                            <span>🎮 Welcome to XRPG!</span>
                            <span class="activity-time">Now</span>
                        </div>
                        <div class="activity-item">
                            <span>⚙️ Customize your theme in Settings</span>
                            <span class="activity-time">Tip</span>
                        </div>
                    `;
                }
            }
        }
        
        // Load updates on page load
        loadDashboardUpdates();
        
        // Auto-refresh stats every 30 seconds (in a real game)
        setInterval(() => {
            // Could refresh user stats here
            console.log('Stats refresh would happen here');
        }, 30000);
    </script>
</body>
</html>
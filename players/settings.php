<?php
// /players/settings.php - Player settings and theme customization (FIXED VERSION)

session_start();
require_once __DIR__ . '/../config/environment.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    header('Location: /');
    exit;
}

// Check if user is a player (not admin)
$user = $_SESSION['user'];
if (is_array($user) && isset($user['type']) && $user['type'] !== 'player') {
    header('Location: /');
    exit;
}

// Connect to database and load user preferences
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/load-preferences.php';

$userInfo = getUserInfo($user);
$username = htmlspecialchars($userInfo['username']);
$userId = $userInfo['id'];

// Load user preferences using the helper function
$preferences = loadUserPreferences($pdo, $userId);

// Page-specific variables for common components
$pageTitle = 'XRPG - Settings';
$currentPage = 'settings';
$headerTitle = 'XRPG - Settings';
$footerInfo = 'XRPG Settings • Player: ' . $username;

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
.settings-header {
    text-align: center;
    padding: 2rem;
    background: var(--gradient-accent);
    color: white;
    margin-bottom: 2rem;
    border-radius: var(--user-radius);
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.settings-section {
    padding: 2rem;
}

.settings-section h3 {
    margin-top: 0;
    color: var(--color-accent);
    border-bottom: 2px solid var(--color-border);
    padding-bottom: 0.5rem;
}

.control-group {
    margin-bottom: 1.5rem;
}

.control-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--color-text);
}

.range-value {
    color: var(--color-accent);
    font-weight: bold;
    float: right;
}

.contrast-warning {
    background: rgba(255, 100, 100, 0.1);
    border: 1px solid rgba(255, 100, 100, 0.3);
    color: #ff6464;
    padding: 0.75rem;
    border-radius: calc(var(--user-radius) * 0.5);
    margin-top: 1rem;
    font-size: 0.875rem;
    display: none;
}

.preview-section {
    padding: 2rem;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.preview-box {
    padding: 1.5rem;
}

.save-section {
    background: var(--color-surface);
    padding: 2rem;
    border-radius: var(--user-radius);
    text-align: center;
    margin-top: 2rem;
}

.theme-toggle-demo {
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.5);
    margin: 1rem 0;
}

.theme-toggle-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid rgba(128, 128, 128, 0.2);
    background: var(--color-surface);
    color: var(--color-text);
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.theme-toggle-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.theme-toggle-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.theme-label {
    font-weight: 500;
}
';

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>

<!-- Main Content -->
<main class="main-content">
    <!-- Settings Header -->
    <div class="settings-header">
        <h1 style="margin: 0 0 0.5rem 0;">⚙️ Settings & Customization</h1>
        <p style="margin: 0; opacity: 0.9;">Personalize your XRPG experience, <?= $username ?>!</p>
    </div>

    <!-- Settings Grid -->
    <div class="settings-grid">
        <!-- Theme & Colors -->
        <div class="card settings-section">
            <h3>🎨 Theme & Colors</h3>
            
            <div class="control-group">
                <label class="control-label">Theme Mode</label>
                <div class="theme-toggle-demo">
                    <span class="theme-label">🌙 Dark</span>
                    
                    <!-- Hidden checkbox for maintaining state -->
                    <input type="checkbox" id="theme-mode-toggle" style="display: none;" <?= $preferences['theme_mode'] === 'light' ? 'checked' : '' ?>>
                    
                    <!-- Visible button that syncs with checkbox -->
                    <button id="demo-theme-btn" class="theme-toggle-btn" title="Toggle light/dark mode">
                        <?= $preferences['theme_mode'] === 'dark' ? '🌞' : '🌙' ?>
                    </button>
                    
                    <span class="theme-label">🌞 Light</span>
                </div>
            </div>
            
            <div class="control-group">
                <label class="control-label" for="accent-primary">Primary Accent Color</label>
                <input type="color" id="accent-primary" value="<?= htmlspecialchars($preferences['accent_color']) ?>" style="width: 100%; height: 50px;">
            </div>
            
            <div class="control-group">
                <label class="control-label" for="accent-secondary">Secondary Accent Color</label>
                <input type="color" id="accent-secondary" value="<?= htmlspecialchars($preferences['accent_secondary']) ?>" style="width: 100%; height: 50px;">
            </div>
            
            <div class="control-group">
                <label class="control-label" for="font-select">Font Style</label>
                <select id="font-select" style="width: 100%;">
                    <option value="sans" <?= $preferences['font_family'] === 'sans' ? 'selected' : '' ?>>Clean & Modern (Sans-serif)</option>
                    <option value="mono" <?= $preferences['font_family'] === 'mono' ? 'selected' : '' ?>>Technical (Monospace)</option>
                    <option value="game" <?= $preferences['font_family'] === 'game' ? 'selected' : '' ?>>Classic RPG (Serif)</option>
                    <option value="display" <?= $preferences['font_family'] === 'display' ? 'selected' : '' ?>>Bold & Impactful</option>
                </select>
            </div>
            
            <div id="contrast-warning" class="contrast-warning">
                ⚠️ Low contrast detected. This color combination might be hard to read.
            </div>
        </div>

        <!-- Visual Effects -->
        <div class="card settings-section">
            <h3>✨ Visual Effects</h3>
            
            <div class="control-group">
                <label class="control-label">
                    Border Radius
                    <span class="range-value" id="radius-value"><?= $preferences['border_radius'] ?>px</span>
                </label>
                <input type="range" id="radius-slider" min="9" max="40" value="<?= $preferences['border_radius'] ?>">
            </div>
            
            <div class="control-group">
                <label class="control-label">
                    Shadow Intensity
                    <span class="range-value" id="shadow-value"><?= $preferences['shadow_intensity'] ?></span>
                </label>
                <input type="range" id="shadow-slider" min="0.05" max="0.5" step="0.01" value="<?= $preferences['shadow_intensity'] ?>">
            </div>
            
            <div class="control-group">
                <label class="control-label">
                    UI Opacity
                    <span class="range-value" id="opacity-value"><?= $preferences['ui_opacity'] ?></span>
                </label>
                <input type="range" id="opacity-slider" min="0.8" max="1" step="0.01" value="<?= $preferences['ui_opacity'] ?>">
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="card preview-section">
        <h3>🎮 Live Preview</h3>
        <p class="text-muted">See how your theme affects different UI elements in real-time</p>
        
        <div class="preview-grid">
            <!-- Buttons -->
            <div class="surface preview-box">
                <h4>Buttons</h4>
                <div style="margin-bottom: 1rem;">
                    <button class="button">Primary Action</button>
                    <button class="button" disabled style="margin-left: 0.5rem;">Disabled</button>
                </div>
                <button class="button" style="width: 100%;">Full Width Button</button>
            </div>
            
            <!-- Input Fields -->
            <div class="surface preview-box">
                <h4>Input Fields</h4>
                <div style="margin-bottom: 1rem;">
                    <label>Character Name</label>
                    <input type="text" placeholder="Enter your hero name..." style="width: 100%;">
                </div>
                <div>
                    <label>Class Selection</label>
                    <select style="width: 100%;">
                        <option>⚔️ Warrior</option>
                        <option>🧙‍♂️ Mage</option>
                        <option>🏹 Ranger</option>
                    </select>
                </div>
            </div>
            
            <!-- Item Cards -->
            <div class="surface preview-box">
                <h4>Item Cards</h4>
                <div class="surface" style="padding: 1rem; margin-bottom: 1rem;">
                    <div class="text-accent" style="font-weight: bold;">🗡️ Flaming Sword</div>
                    <div class="text-muted" style="font-size: 0.875rem;">Legendary Weapon</div>
                    <div style="margin-top: 0.5rem;">+25 Attack | +10 Fire Damage</div>
                </div>
                <div class="surface" style="padding: 1rem;">
                    <div class="text-accent" style="font-weight: bold;">🧪 Health Potion</div>
                    <div class="text-muted" style="font-size: 0.875rem;">Consumable</div>
                    <div style="margin-top: 0.5rem;">Restores 50 HP</div>
                </div>
            </div>
            
            <!-- Game Stats -->
            <div class="surface preview-box">
                <h4>Game Statistics</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="text-align: center;">
                        <div class="text-muted">Level</div>
                        <div class="text-accent" style="font-size: 2rem; font-weight: bold;">42</div>
                    </div>
                    <div style="text-align: center;">
                        <div class="text-muted">Gold</div>
                        <div class="text-accent" style="font-size: 2rem; font-weight: bold;">2,847</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Section -->
    <div class="save-section">
        <h3 style="margin-top: 0;">💾 Save Your Settings</h3>
        <p class="text-muted">Your preferences will be automatically synced across all your devices</p>
        
        <div style="display: flex; gap: 1rem; justify-content: center; align-items: center; flex-wrap: wrap;">
            <button id="save-btn" class="button">
                <span style="margin-right: 0.5rem;">💾</span>Save Settings
            </button>
            <button class="button" onclick="resetToDefaults()" style="background: rgba(255, 100, 100, 0.2); border-color: rgba(255, 100, 100, 0.4);">
                <span style="margin-right: 0.5rem;">🔄</span>Reset to Defaults
            </button>
        </div>
        
        <p style="font-size: 0.875rem; color: var(--color-muted); margin-top: 1rem;">
            <em>Settings are saved to your account and will persist across sessions</em>
        </p>
    </div>

<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/settings.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>
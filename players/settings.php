<?php
// /players/settings.php - Player settings and theme customization

// Initialize session and environment
session_start();
require_once __DIR__ . '/../config/environment.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    header('Location: /');
    exit;
}

// Check if user is a player (not admin)
if ($_SESSION['user']['type'] !== 'player') {
    header('Location: /');
    exit;
}

$user = $_SESSION['user'];
$username = htmlspecialchars($user['username']);
$userId = $user['id'];

// Connect to database
require_once __DIR__ . '/../config/db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XRPG - Settings</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/ico/favicon-16x16.png">
    <link rel="shortcut icon" href="/assets/ico/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <style>
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
        
        .save-status {
            margin: 1rem 0;
            padding: 0.75rem;
            border-radius: calc(var(--user-radius) * 0.5);
            display: none;
        }
        
        .save-status.success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4caf50;
        }
        
        .save-status.error {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
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
    </style>
</head>
<body>
    <!-- Fixed Theme Toggle -->
    <button id="theme-toggle" class="theme-toggle-fixed" title="Toggle light/dark mode">🌞</button>

    <!-- Side Navigation -->
    <nav class="side-nav">
        <button class="side-nav-toggle" title="Toggle menu">☰</button>
        <div class="side-nav-items">
            <a href="/players/" class="side-nav-item" title="Dashboard">
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
            <a href="/players/settings.php" class="side-nav-item active" title="Settings">
                <span class="side-nav-icon">⚙️</span>
                <span class="side-nav-text">Settings</span>
            </a>
        </div>
    </nav>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-title">XRPG - Settings</div>
        <div class="header-actions">
            <button class="button" onclick="window.location.href='/players/'" title="Back to Dashboard">
                <span style="margin-right: 0.5rem;">🏠</span>Dashboard
            </button>
            <button class="button" onclick="logout()" title="Logout">
                <span style="margin-right: 0.5rem;">🚪</span>Logout
            </button>
        </div>
    </header>

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
    
    <!-- Keep the original checkbox (hidden) to maintain all bindings -->
    <input type="checkbox" id="theme-mode-toggle" style="display: none;" <?= $preferences['theme_mode'] === 'light' ? 'checked' : '' ?>>
    
    <!-- New button that looks like the fixed toggle -->
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
            
            <div id="save-status" class="save-status"></div>
            
            <div style="display: flex; gap: 1rem; justify-content: center; align-items: center; flex-wrap: wrap;">
                <button id="save-btn" class="button" onclick="saveSettings()">
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

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-links">
                <a href="/players/">Dashboard</a>
                <a href="/players/help.php">Help & Guide</a>
                <a href="/players/support.php">Support</a>
            </div>
            <div class="footer-info">
                <p>XRPG Settings • Player: <?= $username ?></p>
                <p>&copy; 2025 XRPG. All rights reserved.</p>
            </div>
        </footer>
    </main>

    <script>
        // Initialize settings from PHP data
        const userPreferences = <?= json_encode($preferences) ?>;
        
        // Load theme settings from database
        function loadSettingsFromDB() {
            // Apply theme mode
            setTheme(userPreferences.theme_mode);
            
            // Apply colors
            setAccentColors(userPreferences.accent_color, userPreferences.accent_secondary);
            
            // Apply font
            setFont(userPreferences.font_family);
            
            // Apply visual effects
            setRadius(userPreferences.border_radius);
            setShadowIntensity(userPreferences.shadow_intensity);
            setOpacity(userPreferences.ui_opacity);
            
            // Update UI controls
            updateUIControls();
        }
        
        function updateUIControls() {
            // Theme toggle
            const themeToggle = document.getElementById('theme-mode-toggle');
            if (themeToggle) {
                themeToggle.checked = userPreferences.theme_mode === 'light';
            }
            
            // Update theme button
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) themeBtn.textContent = userPreferences.theme_mode === 'dark' ? '🌞' : '🌙';
            
            // Color pickers
            const primaryPicker = document.getElementById('accent-primary');
            if (primaryPicker) primaryPicker.value = userPreferences.accent_color;
            
            const secondaryPicker = document.getElementById('accent-secondary');
            if (secondaryPicker) secondaryPicker.value = userPreferences.accent_secondary;
            
            // Font selector
            const fontSelect = document.getElementById('font-select');
            if (fontSelect) fontSelect.value = userPreferences.font_family;
            
            // Sliders
            const radiusSlider = document.getElementById('radius-slider');
            if (radiusSlider) {
                radiusSlider.value = userPreferences.border_radius;
                document.getElementById('radius-value').textContent = userPreferences.border_radius + 'px';
            }
            
            const shadowSlider = document.getElementById('shadow-slider');
            if (shadowSlider) {
                shadowSlider.value = userPreferences.shadow_intensity;
                document.getElementById('shadow-value').textContent = userPreferences.shadow_intensity;
            }
            
            const opacitySlider = document.getElementById('opacity-slider');
            if (opacitySlider) {
                opacitySlider.value = userPreferences.ui_opacity;
                document.getElementById('opacity-value').textContent = userPreferences.ui_opacity;
            }
        }
        
        // Save settings to database
        async function saveSettings() {
            const saveBtn = document.getElementById('save-btn');
            const saveStatus = document.getElementById('save-status');
            
            // Disable save button and show loading
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span style="margin-right: 0.5rem;">⏳</span>Saving...';
            
            try {
                const settings = {
                    theme_mode: document.getElementById('theme-mode-toggle').checked ? 'light' : 'dark',
                    accent_color: document.getElementById('accent-primary').value,
                    accent_secondary: document.getElementById('accent-secondary').value,
                    font_family: document.getElementById('font-select').value,
                    border_radius: parseInt(document.getElementById('radius-slider').value),
                    shadow_intensity: parseFloat(document.getElementById('shadow-slider').value),
                    ui_opacity: parseFloat(document.getElementById('opacity-slider').value)
                };
                
                const response = await fetch('/players/save-settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settings),
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update local preferences
                    Object.assign(userPreferences, settings);
                    
                    // Show success message
                    saveStatus.className = 'save-status success';
                    saveStatus.textContent = '✅ Settings saved successfully!';
                    saveStatus.style.display = 'block';
                    
                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        saveStatus.style.display = 'none';
                    }, 3000);
                } else {
                    throw new Error(result.message || 'Failed to save settings');
                }
            } catch (error) {
                console.error('Save error:', error);
                saveStatus.className = 'save-status error';
                saveStatus.textContent = '❌ Failed to save settings: ' + error.message;
                saveStatus.style.display = 'block';
            } finally {
                // Re-enable save button
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span style="margin-right: 0.5rem;">💾</span>Save Settings';
            }
        }
        
        // Reset to default settings
        function resetToDefaults() {
            if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
                return;
            }
            
            // Reset to defaults
            document.getElementById('theme-mode-toggle').checked = false;
            document.getElementById('accent-primary').value = '#5299e0';
            document.getElementById('accent-secondary').value = '#81aaff';
            document.getElementById('font-select').value = 'sans';
            document.getElementById('radius-slider').value = 18;
            document.getElementById('shadow-slider').value = 0.36;
            document.getElementById('opacity-slider').value = 0.96;
            
            // Apply the defaults
            applyCurrentSettings();
            
            // Save to database
            saveSettings();
        }
        
        // Apply current settings from form
        function applyCurrentSettings() {
            const themeMode = document.getElementById('theme-mode-toggle').checked ? 'light' : 'dark';
            const primary = document.getElementById('accent-primary').value;
            const secondary = document.getElementById('accent-secondary').value;
            const font = document.getElementById('font-select').value;
            const radius = document.getElementById('radius-slider').value;
            const shadow = document.getElementById('shadow-slider').value;
            const opacity = document.getElementById('opacity-slider').value;
            
            setTheme(themeMode);
            setAccentColors(primary, secondary);
            setFont(font);
            setRadius(radius);
            setShadowIntensity(shadow);
            setOpacity(opacity);
            
            // Update value displays
            document.getElementById('radius-value').textContent = radius + 'px';
            document.getElementById('shadow-value').textContent = shadow;
            document.getElementById('opacity-value').textContent = opacity;
            
            // Update theme toggle button
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) themeBtn.textContent = themeMode === 'dark' ? '🌞' : '🌙';
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
			
			 const checkbox = document.getElementById('theme-mode-toggle');
    const toggleBtn = document.getElementById('demo-theme-btn');
    
    // If we have both elements, wire up the button
    if (checkbox && toggleBtn) {
        // Initial state
        toggleBtn.textContent = checkbox.checked ? '🌙' : '🌞';
        
        // Wire up the button click to toggle the hidden checkbox
        toggleBtn.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            
            // Update button icon
            toggleBtn.textContent = checkbox.checked ? '🌙' : '🌞';
            
            // Trigger the change event on the checkbox
            // This ensures all existing event handlers run
            const event = new Event('change');
            checkbox.dispatchEvent(event);
        });
        
        // Also listen to checkbox changes to update button
        checkbox.addEventListener('change', () => {
            toggleBtn.textContent = checkbox.checked ? '🌙' : '🌞';
        });
    }
    
    // Hide the fixed theme toggle since we're using the demo one
    const fixedToggle = document.getElementById('theme-toggle');
    if (fixedToggle) {
        fixedToggle.style.display = 'none';
    }
            // Load settings from database
            loadSettingsFromDB();
            
            // Add event listeners for real-time preview
            document.getElementById('theme-mode-toggle').addEventListener('change', applyCurrentSettings);
            document.getElementById('accent-primary').addEventListener('input', applyCurrentSettings);
            document.getElementById('accent-secondary').addEventListener('input', applyCurrentSettings);
            document.getElementById('font-select').addEventListener('change', applyCurrentSettings);
            document.getElementById('radius-slider').addEventListener('input', applyCurrentSettings);
            document.getElementById('shadow-slider').addEventListener('input', applyCurrentSettings);
            document.getElementById('opacity-slider').addEventListener('input', applyCurrentSettings);
            
            // Fixed theme toggle button
            document.getElementById('theme-toggle').addEventListener('click', function() {
                const toggle = document.getElementById('theme-mode-toggle');
                toggle.checked = !toggle.checked;
                applyCurrentSettings();
            });
        });
        
        // Logout function
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
                    window.location.href = '/';
                });
            }
        }
    </script>
    <script src="/assets/js/theme.js"></script>
	
	// Add this JavaScript to the bottom of settings.php before closing the </body> tag
// Replace the existing <script> block with this fixed version

<script>
    // Initialize settings from PHP data
    const userPreferences = <?= json_encode($preferences) ?>;
    
    // Load theme settings from database
    function loadSettingsFromDB() {
        // Apply theme mode (FIXED: Set data-theme attribute first)
        document.documentElement.setAttribute('data-theme', userPreferences.theme_mode);
        setTheme(userPreferences.theme_mode);
        
        // Apply colors
        setAccentColors(userPreferences.accent_color, userPreferences.accent_secondary);
        
        // Apply font
        setFont(userPreferences.font_family);
        
        // Apply visual effects (FIXED: Apply in the correct order)
        setRadius(userPreferences.border_radius);
        setShadowIntensity(userPreferences.shadow_intensity);
        setOpacity(userPreferences.ui_opacity);
        
        // Update UI controls
        updateUIControls();
    }
    
    function updateUIControls() {
        // Theme toggle (FIXED: Correct the checked state logic)
        const themeToggle = document.getElementById('theme-mode-toggle');
        if (themeToggle) {
            themeToggle.checked = userPreferences.theme_mode === 'light';
        }
        
        // Update theme button
        const themeBtn = document.getElementById('theme-toggle');
        if (themeBtn) themeBtn.textContent = userPreferences.theme_mode === 'dark' ? '🌞' : '🌙';
        
        // Color pickers
        const primaryPicker = document.getElementById('accent-primary');
        if (primaryPicker) primaryPicker.value = userPreferences.accent_color;
        
        const secondaryPicker = document.getElementById('accent-secondary');
        if (secondaryPicker) secondaryPicker.value = userPreferences.accent_secondary;
        
        // Font selector
        const fontSelect = document.getElementById('font-select');
        if (fontSelect) fontSelect.value = userPreferences.font_family;
        
        // Sliders
        const radiusSlider = document.getElementById('radius-slider');
        if (radiusSlider) {
            radiusSlider.value = userPreferences.border_radius;
            document.getElementById('radius-value').textContent = userPreferences.border_radius + 'px';
        }
        
        const shadowSlider = document.getElementById('shadow-slider');
        if (shadowSlider) {
            shadowSlider.value = userPreferences.shadow_intensity;
            document.getElementById('shadow-value').textContent = userPreferences.shadow_intensity;
        }
        
        const opacitySlider = document.getElementById('opacity-slider');
        if (opacitySlider) {
            opacitySlider.value = userPreferences.ui_opacity;
            document.getElementById('opacity-value').textContent = userPreferences.ui_opacity;
        }
        
        // Check for contrast issues
        checkContrast(userPreferences.accent_color);
    }
    
    // Save settings to database
    async function saveSettings() {
        const saveBtn = document.getElementById('save-btn');
        const saveStatus = document.getElementById('save-status');
        
        // Disable save button and show loading
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span style="margin-right: 0.5rem;">⏳</span>Saving...';
        
        try {
            const settings = {
                theme_mode: document.getElementById('theme-mode-toggle').checked ? 'light' : 'dark',
                accent_color: document.getElementById('accent-primary').value,
                accent_secondary: document.getElementById('accent-secondary').value,
                font_family: document.getElementById('font-select').value,
                border_radius: parseInt(document.getElementById('radius-slider').value),
                shadow_intensity: parseFloat(document.getElementById('shadow-slider').value),
                ui_opacity: parseFloat(document.getElementById('opacity-slider').value)
            };
            
            const response = await fetch('/players/save-settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(settings),
                credentials: 'same-origin'
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update local preferences
                Object.assign(userPreferences, settings);
                
                // Show success message
                saveStatus.className = 'save-status success';
                saveStatus.textContent = '✅ Settings saved successfully!';
                saveStatus.style.display = 'block';
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    saveStatus.style.display = 'none';
                }, 3000);
            } else {
                throw new Error(result.message || 'Failed to save settings');
            }
        } catch (error) {
            console.error('Save error:', error);
            saveStatus.className = 'save-status error';
            saveStatus.textContent = '❌ Failed to save settings: ' + error.message;
            saveStatus.style.display = 'block';
        } finally {
            // Re-enable save button
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span style="margin-right: 0.5rem;">💾</span>Save Settings';
        }
    }
    
    // Reset to default settings
    function resetToDefaults() {
        if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
            return;
        }
        
        // Reset to defaults
        document.getElementById('theme-mode-toggle').checked = false;
        document.getElementById('accent-primary').value = '#5299e0';
        document.getElementById('accent-secondary').value = '#81aaff';
        document.getElementById('font-select').value = 'sans';
        document.getElementById('radius-slider').value = 18;
        document.getElementById('shadow-slider').value = 0.36;
        document.getElementById('opacity-slider').value = 0.96;
        
        // Apply the defaults
        applyCurrentSettings();
        
        // Save to database
        saveSettings();
    }
    
    // Apply current settings from form
    function applyCurrentSettings() {
        const themeMode = document.getElementById('theme-mode-toggle').checked ? 'light' : 'dark';
        const primary = document.getElementById('accent-primary').value;
        const secondary = document.getElementById('accent-secondary').value;
        const font = document.getElementById('font-select').value;
        const radius = document.getElementById('radius-slider').value;
        const shadow = document.getElementById('shadow-slider').value;
        const opacity = document.getElementById('opacity-slider').value;
        
        // FIXED: Set data-theme attribute first to ensure correct theme state
        document.documentElement.setAttribute('data-theme', themeMode);
        
        // Apply settings in the proper order
        setTheme(themeMode);
        setAccentColors(primary, secondary);
        setFont(font);
        setRadius(radius);
        setShadowIntensity(shadow);
        setOpacity(opacity);
        
        // Update value displays
        document.getElementById('radius-value').textContent = radius + 'px';
        document.getElementById('shadow-value').textContent = shadow;
        document.getElementById('opacity-value').textContent = opacity;
        
        // Update theme toggle button
        const themeBtn = document.getElementById('theme-toggle');
        if (themeBtn) themeBtn.textContent = themeMode === 'dark' ? '🌞' : '🌙';
        
        // Check for contrast issues
        checkContrast(primary);
    }
    
    // ADDED: Check color contrast for accessibility
    function checkContrast(color) {
        const hexToRgb = (hex) => {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        };
        
        const rgb = hexToRgb(color);
        if (!rgb) return;
        
        // Calculate luminance
        const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
        const contrastWarning = document.getElementById('contrast-warning');
        
        const currentTheme = document.getElementById('theme-mode-toggle').checked ? 'light' : 'dark';
        
        // Check contrast based on theme
        if ((currentTheme === 'light' && luminance > 0.7) || 
            (currentTheme === 'dark' && luminance < 0.3)) {
            contrastWarning.style.display = 'block';
        } else {
            contrastWarning.style.display = 'none';
        }
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', () => {
        // Load settings from database
        loadSettingsFromDB();
        
        // Add event listeners for real-time preview
        document.getElementById('theme-mode-toggle').addEventListener('change', function() {
            // FIXED: Ensure the theme toggle button state is synced correctly
            const themeMode = this.checked ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', themeMode);
            applyCurrentSettings();
        });
        
        document.getElementById('accent-primary').addEventListener('input', applyCurrentSettings);
        document.getElementById('accent-secondary').addEventListener('input', applyCurrentSettings);
        document.getElementById('font-select').addEventListener('change', applyCurrentSettings);
        document.getElementById('radius-slider').addEventListener('input', applyCurrentSettings);
        document.getElementById('shadow-slider').addEventListener('input', applyCurrentSettings);
        document.getElementById('opacity-slider').addEventListener('input', applyCurrentSettings);
        
        // Fixed theme toggle button
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const toggle = document.getElementById('theme-mode-toggle');
            toggle.checked = !toggle.checked;
            
            // FIXED: Update data-theme attribute
            document.documentElement.setAttribute('data-theme', toggle.checked ? 'light' : 'dark');
            
            applyCurrentSettings();
        });
    });
    
    // Logout function
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
                window.location.href = '/';
            });
        }
    }
</script>
</body>
</html>
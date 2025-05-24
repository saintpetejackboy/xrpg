// XRPG Enhanced Theme System with Database Support - FIXED VERSION

// In-memory fallback store for guests when localStorage isn't available
const themeMemoryStore = {
    theme: 'dark',
    font: 'sans',
    accent_primary: '#5299e0',
    accent_secondary: '#81aaff',
    radius: '18',
    shadow_intensity: '0.36',
    opacity: '0.96',
    nav_expanded: false
};

// Safe storage accessor functions to handle environments where localStorage isn't available
function getStoredValue(key, defaultValue) {
    if (isAuthenticated()) {
        return null; // Authenticated users use database values
    }
    
    try {
        const value = localStorage.getItem(key);
        return value !== null ? value : defaultValue;
    } catch (e) {
        // Fallback to memory store if localStorage is unavailable
        return themeMemoryStore[key] !== undefined ? themeMemoryStore[key] : defaultValue;
    }
}

function setStoredValue(key, value) {
    if (isAuthenticated()) {
        return; // Authenticated users use database
    }
    
    try {
        localStorage.setItem(key, value);
    } catch (e) {
        // Fallback to memory store if localStorage is unavailable
        themeMemoryStore[key] = value;
    }
}

// Check if user is authenticated (look for user data in window or DOM)
function isAuthenticated() {
    // This will be true if we're on a player page with user data
    return typeof userPreferences !== 'undefined' || document.body.classList.contains('authenticated');
}

// Apply theme mode (light/dark)
function setTheme(theme) {
    // Set the data-theme attribute (critical for CSS selectors)
    document.documentElement.setAttribute('data-theme', theme);
    
    // Store theme setting for guests
    setStoredValue('theme', theme);
    
    // Update theme-dependent CSS variables
    applyThemeDependentStyles(theme);
    
    // Update theme toggle button if it exists
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.textContent = theme === 'dark' ? '🌞' : '🌙';
    }
    
    // Re-apply accent colors to ensure proper contrast
    const primary = document.getElementById('accent-primary')?.value || 
                    (isAuthenticated() && userPreferences ? userPreferences.accent_color : getStoredValue('accent_primary', '#5299e0'));
    const secondary = document.getElementById('accent-secondary')?.value || 
                      (isAuthenticated() && userPreferences ? userPreferences.accent_secondary : getStoredValue('accent_secondary', '#81aaff'));
    
    if (primary && secondary) {
        setAccentColors(primary, secondary);
    }
}

// Apply theme-dependent CSS variables
function applyThemeDependentStyles(theme) {
    // Define color values for each theme
    const themeColors = {
        dark: {
            '--color-background': '#121418',
            '--color-surface': '#1e2128',
            '--color-surface-alt': '#282c34',
            '--color-text': '#e9ecef',
            '--color-text-secondary': '#adb5bd',
            '--color-text-muted': '#868e96',
            '--color-border': '#343a40'
        },
        light: {
            '--color-background': '#f8f9fa',
            '--color-surface': '#ffffff',
            '--color-surface-alt': '#f1f3f5',
            '--color-text': '#212529',
            '--color-text-secondary': '#495057',
            '--color-text-muted': '#6c757d',
            '--color-border': '#dee2e6'
        }
    };
    
    // Apply the colors for the selected theme
    const colors = themeColors[theme] || themeColors.dark;
    for (const [prop, value] of Object.entries(colors)) {
        updateCSSVariable(prop, value);
    }
}

// Update CSS variable with better error handling
function updateCSSVariable(varName, value) {
    try {
        document.documentElement.style.setProperty(varName, value);
    } catch (e) {
        console.error(`Failed to set CSS variable ${varName}:`, e);
    }
}

// Set font family
function setFont(fontKey) {
    updateCSSVariable('--user-font', `var(--font-${fontKey})`);
    setStoredValue('font', fontKey);
}

// Color contrast checking
function getLuminance(hex) {
    // Handle missing or invalid hex colors
    if (!hex || !hex.startsWith('#') || hex.length !== 7) {
        console.warn('Invalid hex color:', hex);
        return 0.5; // Return middle luminance as fallback
    }
    
    try {
        const rgb = parseInt(hex.slice(1), 16);
        const r = (rgb >> 16) & 0xff;
        const g = (rgb >> 8) & 0xff;
        const b = (rgb >> 0) & 0xff;
        
        const sRGB = [r, g, b].map(val => {
            val = val / 255;
            return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
        });
        
        return 0.2126 * sRGB[0] + 0.7152 * sRGB[1] + 0.0722 * sRGB[2];
    } catch (e) {
        console.error('Error calculating luminance:', e);
        return 0.5; // Return middle luminance as fallback
    }
}

function getContrastRatio(color1, color2) {
    const l1 = getLuminance(color1);
    const l2 = getLuminance(color2);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
}

function ensureContrast(accentColor) {
    // Skip if invalid color or no color provided
    if (!accentColor || !accentColor.startsWith('#')) {
        return;
    }
    
    const theme = document.documentElement.getAttribute('data-theme') || 'dark';
    const bgColor = theme === 'dark' ? '#191c22' : '#f5f7fa';
    const textColor = theme === 'dark' ? '#f5f7fa' : '#1d2435';
    
    // Check contrast with background
    const bgContrast = getContrastRatio(accentColor, bgColor);
    const textContrast = getContrastRatio(accentColor, textColor);
    
    // Warn if contrast is too low
    const warningEl = document.getElementById('contrast-warning');
    if (warningEl) {
        if (bgContrast < 3 || textContrast < 2) {
            warningEl.style.display = 'block';
        } else {
            warningEl.style.display = 'none';
        }
    }
}

// Set accent colors and update related variables
function setAccentColors(primary, secondary) {
    // Validate inputs - use defaults if invalid
    if (!primary || !primary.startsWith('#')) {
        primary = '#5299e0';
    }
    if (!secondary || !secondary.startsWith('#')) {
        secondary = '#81aaff';
    }
    
    updateCSSVariable('--user-accent', primary);
    updateCSSVariable('--color-accent', primary); // Added for compatibility
    updateCSSVariable('--user-accent2', secondary);
    updateCSSVariable('--color-accent-secondary', secondary); // Added for compatibility
    
    // Update gradient
    updateCSSVariable('--gradient-accent', `linear-gradient(135deg, ${primary}, ${secondary})`);
    
    // Calculate and set glow with opacity
    try {
        const r = parseInt(primary.slice(1,3), 16);
        const g = parseInt(primary.slice(3,5), 16);
        const b = parseInt(primary.slice(5,7), 16);
        const shadowIntensity = getComputedStyle(document.documentElement).getPropertyValue('--user-shadow-intensity').trim() || 
                               document.documentElement.style.getPropertyValue('--user-shadow-intensity') || 
                               '0.36';
        
        updateCSSVariable('--calc-accent-glow', `rgba(${r}, ${g}, ${b}, ${shadowIntensity})`);
        updateCSSVariable('--shadow-glow', `0 0 20px rgba(${r}, ${g}, ${b}, ${shadowIntensity})`);
    } catch (e) {
        console.error('Error calculating glow:', e);
    }
    
    // Check contrast
    ensureContrast(primary);
    
    // Store for guests
    setStoredValue('accent_primary', primary);
    setStoredValue('accent_secondary', secondary);
}

// Set border radius
function setRadius(value) {
    updateCSSVariable('--user-radius', value + 'px');
    setStoredValue('radius', value);
}

// Set shadow intensity and update related shadows
function setShadowIntensity(value) {
    updateCSSVariable('--user-shadow-intensity', value);
    
    // Update default shadow
    updateCSSVariable('--shadow-default', `0 4px 8px rgba(0, 0, 0, ${value})`);
    
    // Re-apply accent colors to update glow
    const primary = document.getElementById('accent-primary')?.value || 
                   (isAuthenticated() && userPreferences ? userPreferences.accent_color : getStoredValue('accent_primary', '#5299e0'));
    const secondary = document.getElementById('accent-secondary')?.value || 
                      (isAuthenticated() && userPreferences ? userPreferences.accent_secondary : getStoredValue('accent_secondary', '#81aaff'));
    
    if (primary && secondary) {
        setAccentColors(primary, secondary);
    }
    
    setStoredValue('shadow_intensity', value);
}

// Set UI opacity without breaking theme
function setOpacity(value) {
    updateCSSVariable('--user-opacity', value);
    setStoredValue('opacity', value);
    
    // IMPORTANT: Make sure theme-specific colors aren't affected
    // Re-apply the current theme to ensure consistent appearance
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    applyThemeDependentStyles(currentTheme);
}

// Load theme settings for guests (from localStorage or memory fallback)
function loadGuestThemeSettings() {
    // Load theme first to ensure proper color context
    const theme = getStoredValue('theme', 'dark');
    setTheme(theme);
    
    // Load font
    const font = getStoredValue('font', 'sans');
    setFont(font);
    
    // Load colors
    const primaryAccent = getStoredValue('accent_primary', '#5299e0');
    const secondaryAccent = getStoredValue('accent_secondary', '#81aaff');
    setAccentColors(primaryAccent, secondaryAccent);
    
    // Load other settings
    const radius = getStoredValue('radius', '18');
    setRadius(radius);
    
    const shadowIntensity = getStoredValue('shadow_intensity', '0.36');
    setShadowIntensity(shadowIntensity);
    
    const opacity = getStoredValue('opacity', '0.96');
    setOpacity(opacity);
    
    // Update UI controls if they exist
    updateUIControls(theme, primaryAccent, secondaryAccent, radius, shadowIntensity, opacity, font);
}

// Load theme settings for authenticated users (from database via PHP)
function loadAuthenticatedThemeSettings() {
    if (typeof userPreferences === 'undefined') {
        console.warn('User preferences not found, falling back to guest settings');
        loadGuestThemeSettings();
        return;
    }
    
    // Apply theme mode first
    setTheme(userPreferences.theme_mode);
    
    // Apply font
    setFont(userPreferences.font_family);
    
    // Apply colors
    setAccentColors(userPreferences.accent_color, userPreferences.accent_secondary);
    
    // Apply visual effects in the correct order
    setRadius(userPreferences.border_radius);
    setShadowIntensity(userPreferences.shadow_intensity);
    setOpacity(userPreferences.ui_opacity);
    
    // Update UI controls
    updateUIControls(
        userPreferences.theme_mode, 
        userPreferences.accent_color, 
        userPreferences.accent_secondary, 
        userPreferences.border_radius, 
        userPreferences.shadow_intensity, 
        userPreferences.ui_opacity, 
        userPreferences.font_family
    );
}

// Update UI controls with current theme settings
function updateUIControls(theme, primary, secondary, radius, shadow, opacity, font) {
    // Theme toggle button
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) themeBtn.textContent = theme === 'dark' ? '🌞' : '🌙';
    
    // Theme toggle checkbox
    const themeToggle = document.getElementById('theme-mode-toggle');
    if (themeToggle) themeToggle.checked = theme === 'light';
    
    // Color pickers
    const primaryPicker = document.getElementById('accent-primary');
    if (primaryPicker) primaryPicker.value = primary;
    
    const secondaryPicker = document.getElementById('accent-secondary');
    if (secondaryPicker) secondaryPicker.value = secondary;
    
    // Sliders
    const radiusSlider = document.getElementById('radius-slider');
    if (radiusSlider) {
        radiusSlider.value = radius;
        const radiusValue = document.getElementById('radius-value');
        if (radiusValue) radiusValue.textContent = radius + 'px';
    }
    
    const shadowSlider = document.getElementById('shadow-slider');
    if (shadowSlider) {
        shadowSlider.value = shadow;
        const shadowValue = document.getElementById('shadow-value');
        if (shadowValue) shadowValue.textContent = shadow;
    }
    
    const opacitySlider = document.getElementById('opacity-slider');
    if (opacitySlider) {
        opacitySlider.value = opacity;
        const opacityValue = document.getElementById('opacity-value');
        if (opacityValue) opacityValue.textContent = opacity;
    }
    
    // Font selector
    const fontSelect = document.getElementById('font-select');
    if (fontSelect) fontSelect.value = font;
}

// Modal system
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.showModal();
        // Close on backdrop click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.close();
            }
        });
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.close();
}

// Navigation menu
function toggleNav() {
    const nav = document.querySelector('.side-nav');
    if (nav) {
        nav.classList.toggle('expanded');
        setStoredValue('nav_expanded', nav.classList.contains('expanded'));
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    // Add switch styles for theme toggle if not already present
    addSwitchStyles();
    
    // Load theme settings based on authentication status
    if (isAuthenticated()) {
        loadAuthenticatedThemeSettings();
    } else {
        loadGuestThemeSettings();
    }
    
    // Check if nav should be expanded
    const navExpanded = getStoredValue('nav_expanded', 'false') === 'true';
    if (navExpanded) {
        document.querySelector('.side-nav')?.classList.add('expanded');
    }
    
    // Theme toggle
    document.getElementById('theme-toggle')?.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Update theme
        setTheme(newTheme);
        
        // Update theme toggle checkbox if it exists
        const themeToggle = document.getElementById('theme-mode-toggle');
        if (themeToggle) {
            themeToggle.checked = newTheme === 'light';
        }
        
        // Update user preferences if authenticated
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.theme_mode = newTheme;
        }
    });
    
    // Theme toggle checkbox
    document.getElementById('theme-mode-toggle')?.addEventListener('change', function() {
        const newTheme = this.checked ? 'light' : 'dark';
        
        // Update theme
        setTheme(newTheme);
        
        // Update user preferences if authenticated
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.theme_mode = newTheme;
        }
    });
    
    // Font selector
    document.getElementById('font-select')?.addEventListener('change', function() {
        setFont(this.value);
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.font_family = this.value;
        }
    });
    
    // Color pickers
    document.getElementById('accent-primary')?.addEventListener('input', function() {
        const secondary = document.getElementById('accent-secondary')?.value || 
                         (isAuthenticated() && userPreferences ? userPreferences.accent_secondary : getStoredValue('accent_secondary', '#81aaff'));
        setAccentColors(this.value, secondary);
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.accent_color = this.value;
        }
    });
    
    document.getElementById('accent-secondary')?.addEventListener('input', function() {
        const primary = document.getElementById('accent-primary')?.value || 
                       (isAuthenticated() && userPreferences ? userPreferences.accent_color : getStoredValue('accent_primary', '#5299e0'));
        setAccentColors(primary, this.value);
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.accent_secondary = this.value;
        }
    });
    
    // Sliders with live value display
    document.getElementById('radius-slider')?.addEventListener('input', function() {
        setRadius(this.value);
        const valueEl = document.getElementById('radius-value');
        if (valueEl) valueEl.textContent = this.value + 'px';
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.border_radius = parseInt(this.value);
        }
    });
    
    document.getElementById('shadow-slider')?.addEventListener('input', function() {
        setShadowIntensity(this.value);
        const valueEl = document.getElementById('shadow-value');
        if (valueEl) valueEl.textContent = this.value;
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.shadow_intensity = parseFloat(this.value);
        }
    });
    
    document.getElementById('opacity-slider')?.addEventListener('input', function() {
        setOpacity(this.value);
        const valueEl = document.getElementById('opacity-value');
        if (valueEl) valueEl.textContent = this.value;
        if (isAuthenticated() && typeof userPreferences !== 'undefined') {
            userPreferences.ui_opacity = parseFloat(this.value);
        }
    });
    
    // Navigation toggle
    document.querySelector('.side-nav-toggle')?.addEventListener('click', toggleNav);
    
    // Modal close buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('dialog');
            if (modal) modal.close();
        });
    });
    
    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('dialog[open]').forEach(modal => modal.close());
        }
    });
});

// Add CSS for toggle switches
function addSwitchStyles() {
    if (document.getElementById('switch-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'switch-styles';
    style.textContent = `
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: var(--color-accent);
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px var(--color-accent);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
    `;
    document.head.appendChild(style);
}

// Load updates from log file (for landing page)
async function loadUpdates() {
    try {
        const response = await fetch('updates.log');
        const text = await response.text();
        const lines = text.trim().split('\n').slice(-5).reverse(); // Last 5 updates
        
        const container = document.getElementById('updates-container');
        if (!container) return;
        
        if (lines.length === 0 || lines[0] === '') {
            container.innerHTML = `
                <div class="update-entry">
                    <span class="update-time">Now</span>
                    <span class="update-emoji">🎮</span>
                    <span class="update-blurb">Welcome to XRPG! Create an account to get started.</span>
                </div>
            `;
            return;
        }
        
        container.innerHTML = lines.map(line => {
            const parts = line.split('|');
            if (parts.length >= 3) {
                const [timestamp, emoji, message] = parts;
                const date = new Date(timestamp);
                const timeStr = date.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });
                
                return `
                    <div class="update-entry">
                        <span class="update-time">${timeStr}</span>
                        <span class="update-emoji">${emoji}</span>
                        <span class="update-blurb">${message}</span>
                    </div>
                `;
            } else {
                return `
                    <div class="update-entry">
                        <span class="update-time">Recent</span>
                        <span class="update-emoji">📝</span>
                        <span class="update-blurb">${line}</span>
                    </div>
                `;
            }
        }).join('');
    } catch (e) {
        console.error('Could not load updates:', e);
        const container = document.getElementById('updates-container');
        if (container) {
            container.innerHTML = `
                <div class="update-entry">
                    <span class="update-time">Now</span>
                    <span class="update-emoji">🎮</span>
                    <span class="update-blurb">Welcome to XRPG! Your adventure awaits.</span>
                </div>
            `;
        }
    }
}
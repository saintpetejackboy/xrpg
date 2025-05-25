// /players/js/settings.js - Settings page specific functionality

(function() {
    'use strict';
    
    // Ensure XRPGPlayer is available
    if (typeof XRPGPlayer === 'undefined') {
        console.error('XRPGPlayer not loaded - settings functionality may not work properly');
        return;
    }
    
    let currentSettings = { ...XRPGPlayer.preferences };
    
    // Initialize settings page
    function initializeSettings() {
        setupEventListeners();
        setupThemeToggleSync();
        hideFixedThemeToggle();
        applyCurrentSettings();
    }
    
    function setupEventListeners() {
        // Real-time preview event listeners
        const controls = {
            'theme-mode-toggle': applyCurrentSettings,
            'accent-primary': applyCurrentSettings,
            'accent-secondary': applyCurrentSettings,
            'font-select': applyCurrentSettings,
            'radius-slider': applyCurrentSettings,
            'shadow-slider': applyCurrentSettings,
            'opacity-slider': applyCurrentSettings
        };
        
        Object.entries(controls).forEach(([id, handler]) => {
            const element = document.getElementById(id);
            if (element) {
                const eventType = element.type === 'range' || element.type === 'color' ? 'input' : 'change';
                element.addEventListener(eventType, handler);
            }
        });
        
        // Save button
        const saveBtn = document.getElementById('save-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', saveSettings);
        }
    }
    
    function setupThemeToggleSync() {
        const checkbox = document.getElementById('theme-mode-toggle');
        const toggleBtn = document.getElementById('demo-theme-btn');
        
        if (checkbox && toggleBtn) {
            // Initial state
            updateThemeButtonIcon();
            
            // Wire up button click to toggle the hidden checkbox
            toggleBtn.addEventListener('click', () => {
                checkbox.checked = !checkbox.checked;
                updateThemeButtonIcon();
                
                // Trigger change event on checkbox
                const event = new Event('change');
                checkbox.dispatchEvent(event);
            });
            
            // Also listen to checkbox changes to update button
            checkbox.addEventListener('change', updateThemeButtonIcon);
        }
    }
    
    function updateThemeButtonIcon() {
        const checkbox = document.getElementById('theme-mode-toggle');
        const toggleBtn = document.getElementById('demo-theme-btn');
        
        if (checkbox && toggleBtn) {
            toggleBtn.textContent = checkbox.checked ? '🌙' : '🌞';
        }
    }
    
    function hideFixedThemeToggle() {
        // Hide the fixed theme toggle since we're using the demo one
        const fixedToggle = document.getElementById('theme-toggle');
        if (fixedToggle) {
            fixedToggle.style.display = 'none';
        }
    }
    
    function applyCurrentSettings() {
        const themeMode = document.getElementById('theme-mode-toggle').checked ? 'light' : 'dark';
        const primary = document.getElementById('accent-primary').value;
        const secondary = document.getElementById('accent-secondary').value;
        const font = document.getElementById('font-select').value;
        const radius = document.getElementById('radius-slider').value;
        const shadow = document.getElementById('shadow-slider').value;
        const opacity = document.getElementById('opacity-slider').value;
        
        // Update current settings
        currentSettings = {
            theme_mode: themeMode,
            accent_color: primary,
            accent_secondary: secondary,
            font_family: font,
            border_radius: parseInt(radius),
            shadow_intensity: parseFloat(shadow),
            ui_opacity: parseFloat(opacity)
        };
        
        // Apply theme changes if functions are available
        if (typeof setTheme === 'function') {
            document.documentElement.setAttribute('data-theme', themeMode);
            setTheme(themeMode);
        }
        if (typeof setAccentColors === 'function') {
            setAccentColors(primary, secondary);
        }
        if (typeof setFont === 'function') {
            setFont(font);
        }
        if (typeof setRadius === 'function') {
            setRadius(radius);
        }
        if (typeof setShadowIntensity === 'function') {
            setShadowIntensity(shadow);
        }
        if (typeof setOpacity === 'function') {
            setOpacity(opacity);
        }
        
        // Update value displays
        updateValueDisplays();
        
        // Check contrast
        checkContrast(primary, themeMode);
    }
    
    function updateValueDisplays() {
        const updates = [
            ['radius-value', currentSettings.border_radius + 'px'],
            ['shadow-value', currentSettings.shadow_intensity],
            ['opacity-value', currentSettings.ui_opacity]
        ];
        
        updates.forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }
    
    function checkContrast(color, theme) {
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
        
        if (!contrastWarning) return;
        
        // Check contrast based on theme
        if ((theme === 'light' && luminance > 0.7) || 
            (theme === 'dark' && luminance < 0.3)) {
            contrastWarning.style.display = 'block';
        } else {
            contrastWarning.style.display = 'none';
        }
    }
    
    async function saveSettings() {
        const saveBtn = document.getElementById('save-btn');
        if (!saveBtn) return;
        
        // Disable save button and show loading
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span style="margin-right: 0.5rem;">⏳</span>Saving...';
        
        try {
            const response = await XRPGPlayer.apiRequest('/players/save-settings.php', {
                method: 'POST',
                body: JSON.stringify(currentSettings)
            });
            
            if (response.success) {
                // Update global preferences
                Object.assign(XRPGPlayer.preferences, currentSettings);
                
                // Show success message
                XRPGPlayer.showStatus('✅ Settings saved successfully!', 'success');
            } else {
                throw new Error(response.message || 'Failed to save settings');
            }
        } catch (error) {
            console.error('Save error:', error);
            XRPGPlayer.showStatus('❌ Failed to save settings: ' + error.message, 'error');
        } finally {
            // Re-enable save button
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span style="margin-right: 0.5rem;">💾</span>Save Settings';
        }
    }
    
    function resetToDefaults() {
        if (!confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
            return;
        }
        
        // Reset to defaults
        const defaults = {
            theme_mode: 'dark',
            accent_color: '#5299e0',
            accent_secondary: '#81aaff',
            font_family: 'sans',
            border_radius: 18,
            shadow_intensity: 0.36,
            ui_opacity: 0.96
        };
        
        // Update form controls
        document.getElementById('theme-mode-toggle').checked = defaults.theme_mode === 'light';
        document.getElementById('accent-primary').value = defaults.accent_color;
        document.getElementById('accent-secondary').value = defaults.accent_secondary;
        document.getElementById('font-select').value = defaults.font_family;
        document.getElementById('radius-slider').value = defaults.border_radius;
        document.getElementById('shadow-slider').value = defaults.shadow_intensity;
        document.getElementById('opacity-slider').value = defaults.ui_opacity;
        
        // Apply the defaults
        applyCurrentSettings();
        
        // Save to database
        saveSettings();
    }
    
    // Make resetToDefaults available globally for the button onclick
    window.resetToDefaults = resetToDefaults;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeSettings);
})();

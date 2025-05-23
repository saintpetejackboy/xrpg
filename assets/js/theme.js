// XRPG Enhanced Theme System

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

function updateCSSVariable(varName, value) {
    document.documentElement.style.setProperty(varName, value);
}

function setFont(fontKey) {
    updateCSSVariable('--user-font', `var(--font-${fontKey})`);
    localStorage.setItem('font', fontKey);
}

// Color contrast checking
function getLuminance(hex) {
    const rgb = parseInt(hex.slice(1), 16);
    const r = (rgb >> 16) & 0xff;
    const g = (rgb >> 8) & 0xff;
    const b = (rgb >> 0) & 0xff;
    
    const sRGB = [r, g, b].map(val => {
        val = val / 255;
        return val <= 0.03928 ? val / 12.92 : Math.pow((val + 0.055) / 1.055, 2.4);
    });
    
    return 0.2126 * sRGB[0] + 0.7152 * sRGB[1] + 0.0722 * sRGB[2];
}

function getContrastRatio(color1, color2) {
    const l1 = getLuminance(color1);
    const l2 = getLuminance(color2);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
}

function ensureContrast(accentColor) {
    const theme = document.documentElement.getAttribute('data-theme') || 'dark';
    const bgColor = theme === 'dark' ? '#191c22' : '#f5f7fa';
    const textColor = theme === 'dark' ? '#f5f7fa' : '#1d2435';
    
    // Check contrast with background
    const bgContrast = getContrastRatio(accentColor, bgColor);
    const textContrast = getContrastRatio(accentColor, textColor);
    
    // Warn if contrast is too low
    if (bgContrast < 3 || textContrast < 2) {
        document.getElementById('contrast-warning')?.classList.remove('hidden');
    } else {
        document.getElementById('contrast-warning')?.classList.add('hidden');
    }
}

function setAccentColors(primary, secondary) {
    updateCSSVariable('--user-accent', primary);
    updateCSSVariable('--user-accent2', secondary);
    
    // Calculate and set glow with opacity
    const r = parseInt(primary.slice(1,3), 16);
    const g = parseInt(primary.slice(3,5), 16);
    const b = parseInt(primary.slice(5,7), 16);
    updateCSSVariable('--calc-accent-glow', `rgba(${r}, ${g}, ${b}, var(--user-shadow-intensity))`);
    
    // Check contrast
    ensureContrast(primary);
    
    localStorage.setItem('accent_primary', primary);
    localStorage.setItem('accent_secondary', secondary);
}

function setRadius(value) {
    updateCSSVariable('--user-radius', value + 'px');
    localStorage.setItem('radius', value);
}

function setShadowIntensity(value) {
    updateCSSVariable('--user-shadow-intensity', value);
    localStorage.setItem('shadow_intensity', value);
}

function setOpacity(value) {
    updateCSSVariable('--user-opacity', value);
    localStorage.setItem('opacity', value);
}

function loadThemeSettings() {
    // Load theme
    const theme = localStorage.getItem('theme') || 'dark';
    setTheme(theme);
    
    // Load font
    const font = localStorage.getItem('font') || 'sans';
    setFont(font);
    
    // Load colors
    const primaryAccent = localStorage.getItem('accent_primary') || '#5299e0';
    const secondaryAccent = localStorage.getItem('accent_secondary') || '#81aaff';
    setAccentColors(primaryAccent, secondaryAccent);
    
    // Load other settings
    const radius = localStorage.getItem('radius') || '18';
    setRadius(radius);
    
    const shadowIntensity = localStorage.getItem('shadow_intensity') || '0.36';
    setShadowIntensity(shadowIntensity);
    
    const opacity = localStorage.getItem('opacity') || '0.96';
    setOpacity(opacity);
    
    // Update UI controls if they exist
    updateUIControls(theme, primaryAccent, secondaryAccent, radius, shadowIntensity, opacity, font);
}

function updateUIControls(theme, primary, secondary, radius, shadow, opacity, font) {
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) themeBtn.textContent = theme === 'dark' ? '🌞' : '🌙';
    
    const primaryPicker = document.getElementById('accent-primary');
    if (primaryPicker) primaryPicker.value = primary;
    
    const secondaryPicker = document.getElementById('accent-secondary');
    if (secondaryPicker) secondaryPicker.value = secondary;
    
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
        localStorage.setItem('nav_expanded', nav.classList.contains('expanded'));
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    loadThemeSettings();
    
    // Check if nav should be expanded
    const navExpanded = localStorage.getItem('nav_expanded') === 'true';
    if (navExpanded) {
        document.querySelector('.side-nav')?.classList.add('expanded');
    }
    
    // Theme toggle
    document.getElementById('theme-toggle')?.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
        this.textContent = newTheme === 'dark' ? '🌞' : '🌙';
        // Re-check contrast with new theme
        const primary = document.getElementById('accent-primary')?.value || '#5299e0';
        ensureContrast(primary);
    });
    
    // Font selector
    document.getElementById('font-select')?.addEventListener('change', function() {
        setFont(this.value);
    });
    
    // Color pickers
    document.getElementById('accent-primary')?.addEventListener('input', function() {
        const secondary = document.getElementById('accent-secondary').value;
        setAccentColors(this.value, secondary);
    });
    
    document.getElementById('accent-secondary')?.addEventListener('input', function() {
        const primary = document.getElementById('accent-primary').value;
        setAccentColors(primary, this.value);
    });
    
    // Sliders with live value display
    document.getElementById('radius-slider')?.addEventListener('input', function() {
        setRadius(this.value);
        document.getElementById('radius-value').textContent = this.value + 'px';
    });
    
    document.getElementById('shadow-slider')?.addEventListener('input', function() {
        setShadowIntensity(this.value);
        document.getElementById('shadow-value').textContent = this.value;
    });
    
    document.getElementById('opacity-slider')?.addEventListener('input', function() {
        setOpacity(this.value);
        document.getElementById('opacity-value').textContent = this.value;
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

// Load updates from log file
async function loadUpdates() {
    try {
        const response = await fetch('/updates.log');
        const text = await response.text();
        const lines = text.trim().split('\n').slice(-5).reverse(); // Last 5 updates
        
        const container = document.getElementById('updates-container');
        if (!container) return;
        
        container.innerHTML = lines.map(line => {
            const [timestamp, emoji, message] = line.split('|');
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
        }).join('');
    } catch (e) {
        console.error('Could not load updates:', e);
    }
}
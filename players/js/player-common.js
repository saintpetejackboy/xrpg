// /players/js/player-common.js
// Common utilities and functions for all player pages

window.XRPGPlayer = (function() {
    'use strict';
    
    // Ensure user preferences are available
    const preferences = window.userPreferences || {
        theme_mode: 'dark',
        accent_color: '#5299e0',
        accent_secondary: '#81aaff',
        border_radius: 18,
        shadow_intensity: 0.36,
        ui_opacity: 0.96,
        font_family: 'sans'
    };
    
    // Initialize theme on page load
    function initializeTheme() {
        // Make sure data-theme attribute is set correctly
        document.documentElement.setAttribute('data-theme', preferences.theme_mode);
        
        // Apply theme settings if theme functions are available
        if (typeof setTheme === 'function') {
            setTheme(preferences.theme_mode);
        }
        if (typeof setAccentColors === 'function') {
            setAccentColors(preferences.accent_color, preferences.accent_secondary);
        }
        if (typeof setFont === 'function') {
            setFont(preferences.font_family);
        }
        if (typeof setRadius === 'function') {
            setRadius(preferences.border_radius);
        }
        if (typeof setShadowIntensity === 'function') {
            setShadowIntensity(preferences.shadow_intensity);
        }
        if (typeof setOpacity === 'function') {
            setOpacity(preferences.ui_opacity);
        }
        
        // Update theme toggle button
        const themeBtn = document.getElementById('theme-toggle');
        if (themeBtn) {
            themeBtn.textContent = preferences.theme_mode === 'dark' ? '🌞' : '🌙';
        }
    }
    
    // Logout function
    async function logout() {
        if (!confirm('Are you sure you want to logout?')) {
            return;
        }
        
        try {
            const response = await fetch('/auth/logout.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            
            // Always redirect to home, even if logout request fails
            window.location.href = '/';
        } catch (error) {
            console.error('Logout error:', error);
            // Force redirect even if logout request fails
            window.location.href = '/';
        }
    }
    
    // Show loading overlay
    function showLoading(message = 'Loading...') {
        const existingOverlay = document.getElementById('xrpg-loading-overlay');
        if (existingOverlay) {
            existingOverlay.remove();
        }
        
        const overlay = document.createElement('div');
        overlay.id = 'xrpg-loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        `;
        
        overlay.innerHTML = `
            <div style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚡</div>
                <div style="font-size: 1.5rem;">${message}</div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        return overlay;
    }
    
    // Hide loading overlay
    function hideLoading() {
        const overlay = document.getElementById('xrpg-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    // Generic API request helper
    async function apiRequest(url, options = {}) {
        const defaultOptions = {
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
    
    // Show status message
    function showStatus(message, type = 'info', duration = 3000) {
        const existingStatus = document.getElementById('xrpg-status-message');
        if (existingStatus) {
            existingStatus.remove();
        }
        
        const statusEl = document.createElement('div');
        statusEl.id = 'xrpg-status-message';
        
        const colors = {
            success: { bg: 'rgba(76, 175, 80, 0.3)', border: 'rgba(76, 175, 80, 0.3)', text: '#4caf50' },
            error: { bg: 'rgba(244, 67, 54, 0.3)', border: 'rgba(244, 67, 54, 0.3)', text: '#f44336' },
            warning: { bg: 'rgba(255, 193, 7, 0.3)', border: 'rgba(255, 193, 7, 0.3)', text: '#ffc107' },
            info: { bg: 'rgba(33, 150, 243, 0.3)', border: 'rgba(33, 150, 243, 0.3)', text: '#2196f3' }
        };
        
        const color = colors[type] || colors.info;
        
        statusEl.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${color.bg};
            border: 1px solid ${color.border};
            color: ${color.text};
            padding: 1rem;
            border-radius: calc(var(--user-radius, 8px) * 0.5);
            z-index: 1000;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
        `;
        
        statusEl.textContent = message;
        document.body.appendChild(statusEl);
        
        if (duration > 0) {
            setTimeout(() => {
                if (statusEl.parentNode) {
                    statusEl.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => {
                        if (statusEl.parentNode) {
                            statusEl.remove();
                        }
                    }, 300);
                }
            }, duration);
        }
        
        return statusEl;
    }
    
    // Quick navigation functions
    function goToDashboard() {
        window.location.href = '/players/';
    }
    
    function goToCharacter() {
        window.location.href = '/players/character.php';
    }
    
    function goToInventory() {
        window.location.href = '/players/inventory.php';
    }
    
    function goToDungeon() {
        window.location.href = '/players/dungeon.php';
    }
    
    function goToSettings() {
        window.location.href = '/players/settings.php';
    }
    
    function goToAccount() {
        window.location.href = '/players/account.php';
    }
    
    // Initialize on DOM content loaded
    document.addEventListener('DOMContentLoaded', function() {
        initializeTheme();
        
        // Set up theme toggle button if it exists
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle && typeof window.toggleTheme === 'function') {
            themeToggle.addEventListener('click', window.toggleTheme);
        }
    });
    
    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Public API
    return {
        logout,
        showLoading,
        hideLoading,
        apiRequest,
        showStatus,
        goToDashboard,
        goToCharacter,
        goToInventory,
        goToDungeon,
        goToSettings,
        goToAccount,
        initializeTheme,
        preferences
    };
})();

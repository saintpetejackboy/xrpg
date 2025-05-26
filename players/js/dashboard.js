// /players/js/dashboard.js - Dashboard page specific functionality

(function() {
    'use strict';
    
    // Ensure XRPGPlayer is available
    if (typeof XRPGPlayer === 'undefined') {
        console.error('XRPGPlayer not loaded - dashboard functionality may not work properly');
        return;
    }
    
    // Dashboard-specific functions
    function openMap() {
        window.location.href = '/players/map.php';
    }
    
    function changeClassJob() {
        if (window.canChangeClass || window.canChangeJob) {
            window.location.href = '/players/change-class-job.php';
        } else {
            XRPGPlayer.showStatus('You cannot change your class or job at this time. Please wait for the cooldown period to end.', 'warning', 5000);
        }
    }
    
    // Load dashboard updates
async function loadDashboardUpdates() {
    const container = document.getElementById('dashboard-updates');
    try {
        const response = await fetch('/api/updates.php');

        if (!response.ok) {
            // log status + full text of the 500 response
            const text = await response.text();
            console.error(`API error (${response.status}):`, text);
            throw new Error(`Server returned ${response.status}`);
        }

        const data = await response.json();
        if (data.updates && data.updates.length > 0) {
            container.innerHTML = data.updates
                .slice(0, 50)
                .map(u => `
                    <div class="activity-item">
                        <span>${u.emoji} ${u.message}</span>
                        <span class="activity-time">${u.timeAgo}</span>
                    </div>`)
                .join('');
        } else {
            // no updates → welcome
            container.innerHTML = `
                <div class="activity-item">
                    <span>🎮 Welcome to XRPG!</span>
                    <span class="activity-time">Now</span>
                </div>`;
        }

    } catch (err) {
        console.error('Failed to load updates:', err);
        // fallback UI
        if (container) {
            container.innerHTML = `
                <div class="activity-item">
                    <span>🎮 Welcome to XRPG!</span>
                    <span class="activity-time">Now</span>
                </div>
                <div class="activity-item">
                    <span>⚙️ Customize your theme in Settings</span>
                    <span class="activity-time">Tip</span>
                </div>`;
        }
    }

    }
    
    // Auto-refresh stats periodically (placeholder for future implementation)
    function startStatsRefresh() {
        setInterval(() => {
            // In a real game, this would refresh user stats
            console.log('Stats refresh would happen here');
        }, 30000); // 30 seconds
    }
    
    // Initialize dashboard functionality
    function initializeDashboard() {
        loadDashboardUpdates();
        startStatsRefresh();
    }
    
    // Make functions available globally for onclick handlers
    window.openMap = openMap;
    window.changeClassJob = changeClassJob;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeDashboard);
})();

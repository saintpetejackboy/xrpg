// /players/js/character.js - Character page specific functionality

(function() {
    'use strict';
    
    // Ensure XRPGPlayer is available
    if (typeof XRPGPlayer === 'undefined') {
        console.error('XRPGPlayer not loaded - character functionality may not work properly');
        return;
    }
    
    // Character-specific functions
    function showStatTooltip(element, statName) {
        // Future feature: show detailed stat tooltip
        console.log('Stat tooltip for:', statName);
    }
    
    function hideStatTooltip(element) {
        // Future feature: hide stat tooltip
    }
    
    function animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-fill');
        
        progressBars.forEach((bar, index) => {
            // Animate each progress bar with a slight delay
            setTimeout(() => {
                const targetWidth = bar.style.width;
                bar.style.width = '0%';
                bar.style.transition = 'width 1s ease-out';
                
                // Use requestAnimationFrame to ensure the transition applies
                requestAnimationFrame(() => {
                    bar.style.width = targetWidth;
                });
            }, index * 200);
        });
    }
    
    function highlightStatBreakdown() {
        const statBreakdowns = document.querySelectorAll('.stat-breakdown');
        
        statBreakdowns.forEach(breakdown => {
            breakdown.addEventListener('mouseenter', () => {
                breakdown.style.transform = 'translateY(-2px)';
                breakdown.style.boxShadow = 'var(--shadow-glow)';
            });
            
            breakdown.addEventListener('mouseleave', () => {
                breakdown.style.transform = 'translateY(0)';
                breakdown.style.boxShadow = '';
            });
        });
    }
    
    function setupLevelCardAnimations() {
        const levelCards = document.querySelectorAll('.level-card');
        
        levelCards.forEach((card, index) => {
            // Add a subtle hover effect
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'scale(1.05)';
                card.style.transition = 'transform 0.2s ease';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'scale(1)';
            });
            
            // Animate the level numbers on page load
            const levelNumber = card.querySelector('.level-number');
            if (levelNumber) {
                const targetValue = parseInt(levelNumber.textContent);
                levelNumber.textContent = '0';
                
                setTimeout(() => {
                    animateNumber(levelNumber, 0, targetValue, 1000);
                }, index * 300);
            }
        });
    }
    
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Use easing function for smooth animation
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const currentValue = Math.floor(start + (end - start) * easeOut);
            
            element.textContent = currentValue;
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = end; // Ensure exact final value
            }
        }
        
        requestAnimationFrame(updateNumber);
    }
    
    function setupAbilityTagInteractions() {
        const abilityTags = document.querySelectorAll('.ability-tag');
        
        abilityTags.forEach(tag => {
            tag.addEventListener('mouseenter', () => {
                tag.style.transform = 'translateY(-1px)';
                tag.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                tag.style.transition = 'all 0.2s ease';
            });
            
            tag.addEventListener('mouseleave', () => {
                tag.style.transform = 'translateY(0)';
                tag.style.boxShadow = '';
            });
        });
    }
    
    function checkForLevelUps() {
        // Future feature: check if character has gained levels since last visit
        if (window.characterData) {
            const character = window.characterData;
            
            // Check if close to level up
            const expNeeded = (character.level * 1000) - character.experience;
            const classExpNeeded = (character.class_level * 500) - character.class_experience;
            const jobExpNeeded = (character.job_level * 300) - character.job_experience;
            
            if (expNeeded <= 100) {
                XRPGPlayer.showStatus(`You're close to leveling up! Only ${expNeeded} XP needed!`, 'info', 5000);
            }
        }
    }
    
    function setupStatComparison() {
        // Future feature: allow comparing stats with other players or classes
        console.log('Stat comparison feature placeholder');
    }
    
    function initializeCharacterPage() {
        // Run all initialization functions
        animateProgressBars();
        highlightStatBreakdown();
        setupLevelCardAnimations();
        setupAbilityTagInteractions();
        checkForLevelUps();
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            if (event.ctrlKey || event.metaKey) {
                switch (event.key) {
                    case 'd':
                        event.preventDefault();
                        XRPGPlayer.goToDashboard();
                        break;
                    case 's':
                        event.preventDefault();
                        XRPGPlayer.goToSettings();
                        break;
                    case 'i':
                        event.preventDefault();
                        XRPGPlayer.goToInventory();
                        break;
                }
            }
        });
        
        // Show keyboard shortcuts hint
        setTimeout(() => {
            if (localStorage.getItem('character-shortcuts-shown') !== 'true') {
                XRPGPlayer.showStatus('💡 Tip: Use Ctrl+D for Dashboard, Ctrl+S for Settings, Ctrl+I for Inventory', 'info', 7000);
                localStorage.setItem('character-shortcuts-shown', 'true');
            }
        }, 2000);
    }
    
    // Add CSS for enhanced animations
    const style = document.createElement('style');
    style.textContent = `
        .stat-breakdown {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .level-card {
            transition: transform 0.2s ease;
        }
        
        .ability-tag {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .progress-fill {
            transition: width 1s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .level-number {
            animation: pulse 2s ease-in-out infinite;
        }
    `;
    document.head.appendChild(style);
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeCharacterPage);
})();

// /players/js/character-creation.js - Character creation page functionality

(function() {
    'use strict';
    
    // Ensure XRPGPlayer is available
    if (typeof XRPGPlayer === 'undefined') {
        console.error('XRPGPlayer not loaded - character creation functionality may not work properly');
        return;
    }
    
    // Character creation state
    let selectedRace = null;
    let selectedClass = null;
    let selectedJob = null;
    
    // Get data from global variables
    const racesData = window.racesData || [];
    const classesData = window.classesData || [];
    const jobsData = window.jobsData || [];
    
    // Selection handlers
    function selectCard(type, id, name) {
        // Remove previous selection
        document.querySelectorAll(`[data-type="${type}"]`).forEach(card => {
            card.classList.remove('selected');
        });
        
        // Select new card
        const card = document.querySelector(`[data-type="${type}"][data-id="${id}"]`);
        if (card) {
            card.classList.add('selected');
        }
        
        // Update state and enable continue button
        switch(type) {
            case 'race':
                selectedRace = {id: parseInt(id), name};
                const raceContinue = document.getElementById('race-continue');
                if (raceContinue) {
                    raceContinue.disabled = false;
                }
                break;
            case 'class':
                selectedClass = {id: parseInt(id), name};
                const classContinue = document.getElementById('class-continue');
                if (classContinue) {
                    classContinue.disabled = false;
                }
                break;
            case 'job':
                selectedJob = {id: parseInt(id), name};
                const jobContinue = document.getElementById('job-continue');
                if (jobContinue) {
                    jobContinue.disabled = false;
                }
                break;
        }
        
        // Add visual feedback
        animateCardSelection(card);
    }
    
    function animateCardSelection(card) {
        card.style.transform = 'scale(1.02)';
        setTimeout(() => {
            card.style.transform = '';
        }, 200);
    }
    
    // Navigation functions
    function continueToClass() {
        if (!selectedRace) {
            XRPGPlayer.showStatus('Please select a race first!', 'warning');
            return;
        }
        updateStepIndicator('class');
        showSection('section-class');
    }
    
    function continueToJob() {
        if (!selectedClass) {
            XRPGPlayer.showStatus('Please select a class first!', 'warning');
            return;
        }
        updateStepIndicator('job');
        showSection('section-job');
    }
    
    function continueToConfirm() {
        if (!selectedJob) {
            XRPGPlayer.showStatus('Please select a job first!', 'warning');
            return;
        }
        updateStepIndicator('confirm');
        showSection('section-confirm');
        updateCharacterSummary();
        calculateFinalStats();
    }
    
    function goBackToRace() {
        updateStepIndicator('race');
        showSection('section-race');
    }
    
    function goBackToClass() {
        updateStepIndicator('class');
        showSection('section-class');
    }
    
    function goBackToJob() {
        updateStepIndicator('job');
        showSection('section-job');
    }
    
    function updateStepIndicator(activeStep) {
        const steps = ['race', 'class', 'job', 'confirm'];
        const stepElements = ['step-race', 'step-class', 'step-job', 'step-confirm'];
        
        stepElements.forEach((stepId, index) => {
            const stepEl = document.getElementById(stepId);
            const stepName = steps[index];
            
            if (!stepEl) return;
            
            stepEl.classList.remove('active', 'completed');
            
            if (stepName === activeStep) {
                stepEl.classList.add('active');
            } else if (steps.indexOf(stepName) < steps.indexOf(activeStep)) {
                stepEl.classList.add('completed');
                const numberEl = stepEl.querySelector('.step-number');
                if (numberEl) {
                    numberEl.textContent = '✓';
                }
            } else {
                const numberEl = stepEl.querySelector('.step-number');
                if (numberEl) {
                    numberEl.textContent = index + 1;
                }
            }
        });
    }
    
    function showSection(sectionId) {
        document.querySelectorAll('.creation-section').forEach(section => {
            section.classList.add('section-hidden');
        });
        
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.remove('section-hidden');
            
            // Smooth scroll to top of section
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    
    function updateCharacterSummary() {
        const elements = {
            'selected-race': selectedRace ? selectedRace.name : '-',
            'selected-class': selectedClass ? selectedClass.name : '-',
            'selected-job': selectedJob ? selectedJob.name : '-'
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }
    
    function calculateFinalStats() {
        if (!selectedRace || !selectedClass || !selectedJob) return;
        
        // Base stats
        const baseStats = {
            strength: 10,
            vitality: 10,
            agility: 10,
            intelligence: 10,
            wisdom: 10,
            luck: 10
        };
        
        // Find selected data
        const race = racesData.find(r => r.id == selectedRace.id);
        const playerClass = classesData.find(c => c.id == selectedClass.id);
        const job = jobsData.find(j => j.id == selectedJob.id);
        
        if (!race || !playerClass || !job) {
            console.error('Missing data for stat calculation');
            return;
        }
        
        // Calculate final stats
        const finalStats = {};
        for (const stat in baseStats) {
            finalStats[stat] = baseStats[stat] + 
                (race[stat + '_mod'] || 0) +
                (playerClass[stat + '_bonus'] || 0) +
                (job[stat + '_bonus'] || 0);
        }
        
        // Display stats with animation
        displayFinalStats(finalStats);
    }
    
    function displayFinalStats(stats) {
        const statsContainer = document.getElementById('final-stats');
        if (!statsContainer) return;
        
        const statNames = {
            strength: 'Strength',
            vitality: 'Vitality',
            agility: 'Agility',
            intelligence: 'Intelligence',
            wisdom: 'Wisdom',
            luck: 'Luck'
        };
        
        statsContainer.innerHTML = Object.entries(stats).map(([stat, value]) => `
            <div class="final-stat">
                <div class="final-stat-value" data-target="${value}">0</div>
                <div class="final-stat-name">${statNames[stat]}</div>
            </div>
        `).join('');
        
        // Animate the numbers
        setTimeout(() => {
            animateStatNumbers();
        }, 100);
    }
    
    function animateStatNumbers() {
        const statElements = document.querySelectorAll('.final-stat-value[data-target]');
        
        statElements.forEach((element, index) => {
            const target = parseInt(element.dataset.target);
            
            setTimeout(() => {
                animateNumber(element, 0, target, 1000);
            }, index * 100);
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
    
    async function confirmCharacter() {
        if (!selectedRace || !selectedClass || !selectedJob) {
            XRPGPlayer.showStatus('Please make all selections first!', 'error');
            return;
        }
        
        // Show confirmation dialog
        if (!confirm('Are you sure you want to create this character? Your race choice will be permanent and class/job cannot be changed for 3 days.')) {
            return;
        }
        
        const loadingOverlay = XRPGPlayer.showLoading('Creating your character...');
        
        try {
            const response = await XRPGPlayer.apiRequest('/players/create-character.php', {
                method: 'POST',
                body: JSON.stringify({
                    race_id: selectedRace.id,
                    class_id: selectedClass.id,
                    job_id: selectedJob.id
                })
            });
            
            if (response.success) {
                XRPGPlayer.showStatus('🎉 Character created successfully! Welcome to XRPG!', 'success', 3000);
                
                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = '/players/';
                }, 2000);
            } else {
                throw new Error(response.message || 'Failed to create character');
            }
        } catch (error) {
            console.error('Error creating character:', error);
            XRPGPlayer.showStatus('❌ Failed to create character: ' + error.message, 'error');
        } finally {
            XRPGPlayer.hideLoading();
        }
    }
    
    function setupEventListeners() {
        // Selection card clicks
        document.querySelectorAll('.selection-card').forEach(card => {
            card.addEventListener('click', () => {
                const type = card.dataset.type;
                const id = card.dataset.id;
                const name = card.dataset.name;
                
                if (type && id && name) {
                    selectCard(type, id, name);
                }
            });
        });
        
        // Navigation buttons
        const buttons = {
            'race-continue': continueToClass,
            'class-continue': continueToJob,
            'job-continue': continueToConfirm,
            'class-back': goBackToRace,
            'job-back': goBackToClass,
            'confirm-back': goBackToJob,
            'confirm-character': confirmCharacter
        };
        
        Object.entries(buttons).forEach(([id, handler]) => {
            const button = document.getElementById(id);
            if (button) {
                button.addEventListener('click', handler);
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                // Go back one step
                const activeSection = document.querySelector('.creation-section:not(.section-hidden)');
                if (activeSection) {
                    const sectionId = activeSection.id;
                    switch (sectionId) {
                        case 'section-class':
                            goBackToRace();
                            break;
                        case 'section-job':
                            goBackToClass();
                            break;
                        case 'section-confirm':
                            goBackToJob();
                            break;
                    }
                }
            }
        });
    }
    
    function addSelectionAnimations() {
        // Add hover effects to selection cards
        document.querySelectorAll('.selection-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                if (!card.classList.contains('selected')) {
                    card.style.transform = 'translateY(-2px)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                if (!card.classList.contains('selected')) {
                    card.style.transform = '';
                }
            });
        });
    }
    
    function initializeCharacterCreation() {
        // Check if we have the required data
        if (racesData.length === 0 || classesData.length === 0 || jobsData.length === 0) {
            XRPGPlayer.showStatus('Error loading character creation data. Please refresh the page.', 'error');
            return;
        }
        
        setupEventListeners();
        addSelectionAnimations();
        
        // Show helpful tip
        setTimeout(() => {
            XRPGPlayer.showStatus('💡 Tip: Click on a card to select it. Use ESC to go back a step.', 'info', 5000);
        }, 1000);
    }
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeCharacterCreation);
})();

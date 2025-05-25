// /players/js/account.js - Account management page functionality

(function() {
    'use strict';
    
    // Ensure XRPGPlayer is available
    if (typeof XRPGPlayer === 'undefined') {
        console.error('XRPGPlayer not loaded - account functionality may not work properly');
        return;
    }
    
    // Utility functions for passkey operations
    function toBuf(b64url) {
        let s = b64url.replace(/-/g, '+').replace(/_/g, '/');
        while (s.length % 4) s += '=';
        let bin = atob(s), arr = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
        return arr.buffer;
    }
    
    function b64(buf) {
        let s = '', a = new Uint8Array(buf);
        for (let b of a) s += String.fromCharCode(b);
        return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }
    
    // Fallback passphrase management
    function showFallbackForm() {
        const form = document.getElementById('fallback-form');
        const showBtn = document.getElementById('show-fallback-form');
        
        if (form && showBtn) {
            form.style.display = 'block';
            showBtn.style.display = 'none';
            
            // Focus on first input
            const firstInput = form.querySelector('#fb1');
            if (firstInput) {
                firstInput.focus();
            }
        }
    }
    
    function hideFallbackForm() {
        const form = document.getElementById('fallback-form');
        const showBtn = document.getElementById('show-fallback-form');
        const feedback = document.getElementById('fb-feedback');
        
        if (form) {
            form.style.display = 'none';
            form.reset();
        }
        
        if (showBtn) {
            showBtn.style.display = 'inline-block';
        }
        
        if (feedback) {
            feedback.style.display = 'none';
        }
    }
    
    function showFeedback(elementId, message, type = 'error') {
        const feedback = document.getElementById(elementId);
        if (feedback) {
            feedback.textContent = message;
            feedback.className = `feedback ${type}`;
            feedback.style.display = 'block';
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    feedback.style.display = 'none';
                }, 5000);
            }
        }
    }
    
    async function handleFallbackSubmit(event) {
        event.preventDefault();
        
        const fb1 = document.getElementById('fb1');
        const fb2 = document.getElementById('fb2');
        const feedback = document.getElementById('fb-feedback');
        
        if (!fb1 || !fb2) return;
        
        const p1 = fb1.value;
        const p2 = fb2.value;
        
        // Clear previous feedback
        if (feedback) {
            feedback.style.display = 'none';
        }
        
        // Validate passphrases match
        if (p1 !== p2) {
            showFeedback('fb-feedback', 'Passphrases do not match');
            return;
        }
        
        // Validate passphrase strength
        if (p1.length < 6) {
            showFeedback('fb-feedback', 'Passphrase must be at least 6 characters long');
            return;
        }
        
        try {
            const response = await XRPGPlayer.apiRequest('/auth/fallback.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'set',
                    passphrase: p1
                })
            });
            
            if (response.ok) {
                XRPGPlayer.showStatus('Fallback passphrase set successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showFeedback('fb-feedback', response.error || 'Failed to set passphrase');
            }
        } catch (error) {
            showFeedback('fb-feedback', 'Network error: ' + error.message);
        }
    }
    
    async function removeFallback() {
        if (!confirm('Are you sure you want to remove your fallback passphrase? This cannot be undone.')) {
            return;
        }
        
        try {
            const response = await XRPGPlayer.apiRequest('/auth/fallback.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'remove'
                })
            });
            
            if (response.ok) {
                XRPGPlayer.showStatus('Fallback passphrase removed successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                XRPGPlayer.showStatus('Failed to remove passphrase: ' + (response.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            XRPGPlayer.showStatus('Network error: ' + error.message, 'error');
        }
    }
    
    // Passkey management
    async function addPasskey() {
        const feedback = document.getElementById('pk-feedback');
        
        try {
            showFeedback('pk-feedback', 'Starting passkey registration...', 'success');
            
            // Get registration options from server
            const optionsResponse = await fetch('/auth/passkey-manage.php', {
                method: 'POST',
                credentials: 'same-origin'
            });
            
            if (!optionsResponse.ok) {
                throw new Error('Failed to get registration options');
            }
            
            let opts = await optionsResponse.json();
            
            if (!opts || opts.error) {
                throw new Error(opts.error || 'Invalid registration options');
            }
            
            // Convert base64url to ArrayBuffer
            opts.challenge = toBuf(opts.challenge);
            opts.user.id = toBuf(opts.user.id);
            
            if (opts.excludeCredentials) {
                opts.excludeCredentials = opts.excludeCredentials.map(c => ({
                    type: c.type,
                    id: toBuf(c.id)
                }));
            }
            
            showFeedback('pk-feedback', 'Please interact with your authenticator...', 'success');
            
            // Create credential
            let cred = await navigator.credentials.create({ publicKey: opts });
            
            if (!cred) {
                throw new Error('Failed to create credential');
            }
            
            // Prepare credential data for server
            let data = {
                id: cred.id,
                rawId: b64(cred.rawId),
                type: cred.type,
                response: {
                    attestationObject: b64(cred.response.attestationObject),
                    clientDataJSON: b64(cred.response.clientDataJSON)
                }
            };
            
            showFeedback('pk-feedback', 'Saving passkey...', 'success');
            
            // Save credential to server
            const saveResponse = await fetch('/auth/passkey-manage.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            });
            
            const saveResult = await saveResponse.json();
            
            if (saveResult.ok) {
                XRPGPlayer.showStatus('Passkey added successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                throw new Error(saveResult.error || 'Failed to save passkey');
            }
            
        } catch (error) {
            console.error('Passkey registration error:', error);
            
            let errorMessage = 'Failed to add passkey';
            
            if (error.name === 'NotSupportedError') {
                errorMessage = 'Passkeys are not supported on this device or browser';
            } else if (error.name === 'NotAllowedError') {
                errorMessage = 'Passkey registration was cancelled or not allowed';
            } else if (error.name === 'SecurityError') {
                errorMessage = 'Security error occurred during passkey registration';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            showFeedback('pk-feedback', errorMessage);
        }
    }
    
    async function removePasskey(passkeyId) {
        if (!confirm('Are you sure you want to remove this passkey? You will no longer be able to use this device to log in.')) {
            return;
        }
        
        try {
            const response = await XRPGPlayer.apiRequest('/auth/passkey-manage.php', {
                method: 'DELETE',
                body: JSON.stringify({ id: passkeyId })
            });
            
            if (response.ok) {
                XRPGPlayer.showStatus('Passkey removed successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                XRPGPlayer.showStatus('Failed to remove passkey: ' + (response.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            XRPGPlayer.showStatus('Network error: ' + error.message, 'error');
        }
    }
    
    function setupEventListeners() {
        // Fallback passphrase events
        const showFallbackBtn = document.getElementById('show-fallback-form');
        const removeFallbackBtn = document.getElementById('remove-fallback');
        const cancelFallbackBtn = document.getElementById('cancel-fallback');
        const fallbackForm = document.getElementById('fallback-form');
        
        if (showFallbackBtn) {
            showFallbackBtn.addEventListener('click', showFallbackForm);
        }
        
        if (removeFallbackBtn) {
            removeFallbackBtn.addEventListener('click', removeFallback);
        }
        
        if (cancelFallbackBtn) {
            cancelFallbackBtn.addEventListener('click', hideFallbackForm);
        }
        
        if (fallbackForm) {
            fallbackForm.addEventListener('submit', handleFallbackSubmit);
        }
        
        // Passkey events
        const addPasskeyBtn = document.getElementById('add-passkey');
        if (addPasskeyBtn) {
            addPasskeyBtn.addEventListener('click', addPasskey);
        }
        
        // Remove passkey buttons
        document.querySelectorAll('.remove-passkey').forEach(btn => {
            btn.addEventListener('click', () => {
                const passkeyId = btn.dataset.id;
                if (passkeyId) {
                    removePasskey(passkeyId);
                }
            });
        });
    }
    
    function initializeAccountPage() {
        setupEventListeners();
        
        // Check for passkey support
        if (!window.PublicKeyCredential) {
            const addBtn = document.getElementById('add-passkey');
            if (addBtn) {
                addBtn.disabled = true;
                addBtn.textContent = 'Passkeys Not Supported';
                addBtn.title = 'Your browser does not support passkeys';
            }
        }
    }
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', initializeAccountPage);
})();

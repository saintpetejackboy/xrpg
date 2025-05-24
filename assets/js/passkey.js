/**
 * Modern Passkey Authentication System
 * Clean, efficient, and user-friendly
 */

class PasskeyAuth {
    constructor() {
        this.currentMode = 'login';
        this.isProcessing = false;
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }

    setupEventListeners() {
        const modal = document.getElementById('auth-modal');
        if (!modal) return;

        const usernameInput = modal.querySelector('#username');
        const actionButton = modal.querySelector('#auth-action-btn');
        const switchButton = modal.querySelector('#auth-switch-btn');
        const closeButton = modal.querySelector('#auth-close-btn');

        // Setup button handlers
        if (actionButton) {
            actionButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleAuth();
            });
        }

        if (switchButton) {
            switchButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchMode();
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', () => this.closeModal());
        }

        // Enter key support
        if (usernameInput) {
           

            // Clear errors when user types
            usernameInput.addEventListener('input', () => {
                this.clearStatus();
            });
        }

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal();
            }
        });

        // ESC key support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.open) {
                this.closeModal();
            }
        });
    }

    async handleAuth() {
        if (this.isProcessing) return;

        const username = this.getUsername();
        if (!this.validateUsername(username)) return;

        this.isProcessing = true;
        this.setButtonLoading(true);
        this.clearStatus();

        try {
            if (this.currentMode === 'register') {
                await this.register(username);
            } else {
                await this.login(username);
            }
        } catch (error) {
            console.error('Auth error:', error);
            this.showStatus('error', error.message || 'Authentication failed');
        } finally {
            this.isProcessing = false;
            this.setButtonLoading(false);
        }
    }

    async register(username) {
        this.showStatus('info', 'Starting registration...');

        // Step 1: Get registration options
        const optionsResponse = await this.fetchWithTimeout('/auth/register.php', {
            method: 'POST',
            body: new URLSearchParams({ username })
        });

        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            throw new Error(error.error || 'Registration failed');
        }

        const options = await optionsResponse.json();
        this.prepareCredentialOptions(options);

        this.showStatus('info', 'Please complete the security key or biometric prompt...');

        // Step 2: Create credential
        const credential = await navigator.credentials.create({ publicKey: options });
        if (!credential) {
            throw new Error('No credential created');
        }

        this.showStatus('info', 'Saving registration...');

        // Step 3: Save credential
        const credentialData = this.formatCredentialForTransmission(credential);
        const saveResponse = await this.fetchWithTimeout('/auth/register.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(credentialData)
        });

        if (!saveResponse.ok) {
            const error = await saveResponse.json();
            throw new Error(error.error || 'Failed to save registration');
        }

        const result = await saveResponse.json();
        this.showStatus('success', `✅ Account created successfully! Welcome, ${result.username}!`);
        
        // Auto-switch to login mode after successful registration
        setTimeout(() => {
            this.currentMode = 'login';
            this.updateUI();
            this.clearStatus();
        }, 2000);
    }

    async login(username) {
        this.showStatus('info', 'Starting login...');

        // Step 1: Get authentication options
        const optionsResponse = await this.fetchWithTimeout('/auth/login.php', {
            method: 'POST',
            body: new URLSearchParams({ username })
        });

        if (!optionsResponse.ok) {
            const error = await optionsResponse.json();
            if (optionsResponse.status === 404) {
                this.showStatus('error', 'Username not found. Would you like to create an account?');
                // Auto-suggest switching to register mode
                setTimeout(() => {
                    this.currentMode = 'register';
                    this.updateUI();
                    this.showStatus('info', 'Switched to account creation mode');
                }, 3000);
                return;
            }
            throw new Error(error.error || 'Login failed');
        }

        const options = await optionsResponse.json();
        this.prepareCredentialOptions(options);

        this.showStatus('info', 'Please complete the security key or biometric prompt...');

        // Step 2: Get assertion
        const assertion = await navigator.credentials.get({ publicKey: options });
        if (!assertion) {
            throw new Error('No assertion created');
        }

        this.showStatus('info', 'Verifying login...');

        // Step 3: Verify assertion
        const assertionData = this.formatAssertionForTransmission(assertion);
        const verifyResponse = await this.fetchWithTimeout('/auth/login.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(assertionData)
        });

        if (!verifyResponse.ok) {
            const error = await verifyResponse.json();
            throw new Error(error.error || 'Login verification failed');
        }

        const result = await verifyResponse.json();
        this.showStatus('success', '✅ Login successful! Redirecting...');
        
        // Redirect after successful login
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    // Helper methods
    getUsername() {
        const input = document.querySelector('#username');
        return input ? input.value.trim() : '';
    }

    validateUsername(username) {
        if (!username) {
            this.showStatus('error', 'Please enter a username');
            this.focusUsername();
            return false;
        }
        if (username.length < 3) {
            this.showStatus('error', 'Username must be at least 3 characters');
            this.focusUsername();
            return false;
        }
        if (username.length > 50) {
            this.showStatus('error', 'Username must be less than 50 characters');
            this.focusUsername();
            return false;
        }
        return true;
    }

    focusUsername() {
        const input = document.querySelector('#username');
        if (input) input.focus();
    }

    prepareCredentialOptions(options) {
        // Convert base64url to Uint8Array for browser API
        if (options.challenge) {
            options.challenge = this.base64urlToUint8Array(options.challenge);
        }
        if (options.user?.id) {
            options.user.id = this.base64urlToUint8Array(options.user.id);
        }
        if (options.allowCredentials) {
            options.allowCredentials = options.allowCredentials.map(cred => ({
                ...cred,
                id: this.base64urlToUint8Array(cred.id)
            }));
        }
    }

    formatCredentialForTransmission(credential) {
        return {
            id: credential.id,
            rawId: this.arrayBufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: this.arrayBufferToBase64url(credential.response.attestationObject),
                clientDataJSON: this.arrayBufferToBase64url(credential.response.clientDataJSON)
            }
        };
    }

    formatAssertionForTransmission(assertion) {
        return {
            id: assertion.id,
            rawId: this.arrayBufferToBase64url(assertion.rawId),
            type: assertion.type,
            response: {
                authenticatorData: this.arrayBufferToBase64url(assertion.response.authenticatorData),
                clientDataJSON: this.arrayBufferToBase64url(assertion.response.clientDataJSON),
                signature: this.arrayBufferToBase64url(assertion.response.signature),
                userHandle: assertion.response.userHandle ? 
                    this.arrayBufferToBase64url(assertion.response.userHandle) : null
            }
        };
    }

    // Encoding utilities
    base64urlToUint8Array(str) {
        if (!str) return new Uint8Array();
        // Convert base64url to base64
        str = str.replace(/-/g, '+').replace(/_/g, '/');
        // Add padding
        while (str.length % 4) str += '=';
        
        const binary = atob(str);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    arrayBufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    // UI Management
    switchMode() {
        this.currentMode = this.currentMode === 'login' ? 'register' : 'login';
        this.updateUI();
        this.clearStatus();
        this.focusUsername();
    }

    updateUI() {
        const title = document.querySelector('#auth-title');
        const subtitle = document.querySelector('#auth-subtitle');
        const actionButton = document.querySelector('#auth-action-btn');
        const switchButton = document.querySelector('#auth-switch-btn');

        if (this.currentMode === 'register') {
            if (title) title.textContent = 'Create Account';
            if (subtitle) subtitle.textContent = 'Join XRPG with secure passkey authentication';
            if (actionButton) {
                actionButton.innerHTML = '<span class="passkey-icon">🆕</span>Create Account';
            }
            if (switchButton) switchButton.textContent = 'Already have an account? Sign in';
        } else {
            if (title) title.textContent = 'Welcome Back';
            if (subtitle) subtitle.textContent = 'Sign in to continue your adventure';
            if (actionButton) {
                actionButton.innerHTML = '<span class="passkey-icon">🔑</span>Sign In';
            }
            if (switchButton) switchButton.textContent = "Don't have an account? Create one";
        }
    }

    setButtonLoading(loading) {
        const button = document.querySelector('#auth-action-btn');
        if (!button) return;

        if (loading) {
            button.classList.add('auth-button-loading');
            button.disabled = true;
        } else {
            button.classList.remove('auth-button-loading');
            button.disabled = false;
        }
    }

    showStatus(type, message) {
        let statusEl = document.querySelector('#auth-status');
        
        if (!statusEl) {
            statusEl = document.createElement('div');
            statusEl.id = 'auth-status';
            statusEl.className = 'auth-status';
            
         const form = modal.querySelector('#auth-form');

			if (form) {
			  form.addEventListener('submit', e => {
				e.preventDefault();         // ⬅️ stop the dialog from "submitting"
				if (!this.isProcessing) {
				  this.handleAuth();        // ⬅️ run your authentication
				}
			  });
			}
        }

        statusEl.className = `auth-status auth-status-${type}`;
        statusEl.textContent = message;
        statusEl.style.display = 'block';

        // Auto-clear success messages
        if (type === 'success') {
            setTimeout(() => this.clearStatus(), 5000);
        }

        console.log(`Auth ${type.toUpperCase()}: ${message}`);
    }

    clearStatus() {
        const statusEl = document.querySelector('#auth-status');
        if (statusEl) {
            statusEl.style.display = 'none';
        }
    }

    closeModal() {
        const modal = document.getElementById('auth-modal');
        if (modal && modal.close) {
            modal.close();
        }
        this.clearStatus();
        this.isProcessing = false;
        this.setButtonLoading(false);
    }

    async fetchWithTimeout(url, options, timeout = 10000) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Request timed out');
            }
            throw error;
        }
    }
}

// Initialize when DOM is ready
const passkeyAuth = new PasskeyAuth();

// Expose global functions for backward compatibility
window.registerPasskey = (username) => passkeyAuth.register(username);
window.loginPasskey = (username) => passkeyAuth.login(username);

// Helper to convert URL-safe base64 to standard base64
function urlBase64ToBase64(str) {
    if (!str || typeof str !== 'string') {
        console.error('urlBase64ToBase64: Invalid input:', str);
        return '';
    }
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    // Pad with = to make length a multiple of 4
    while (str.length % 4) str += '=';
    return str;
}

// Helper to convert ArrayBuffer to base64url
function arrayBufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

// Enhanced message display with better visibility
function showMessage(message, isError = false) {
    // Remove any existing message
    const existingMessage = document.getElementById('auth-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Create new message element
    const messageEl = document.createElement('div');
    messageEl.id = 'auth-message';
    messageEl.className = 'auth-message-toast';
    messageEl.textContent = message;
    
    // Apply enhanced styles
    messageEl.style.cssText = `
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
        z-index: 999999 !important;
        padding: 16px 24px !important;
        border-radius: 12px !important;
        color: white !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        max-width: 350px !important;
        min-width: 200px !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.4) !important;
        border: 2px solid rgba(255,255,255,0.2) !important;
        backdrop-filter: blur(10px) !important;
        transform: translateX(400px) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        font-family: system-ui, -apple-system, sans-serif !important;
        line-height: 1.4 !important;
        word-wrap: break-word !important;
        pointer-events: auto !important;
        opacity: 0.95 !important;
    `;
    
    // Set background color based on message type
    messageEl.style.backgroundColor = isError ? '#dc3545 !important' : '#28a745 !important';
    
    // Add to document
    document.body.appendChild(messageEl);
    
    // Trigger animation
    requestAnimationFrame(() => {
        messageEl.style.transform = 'translateX(0) !important';
        messageEl.style.opacity = '1 !important';
    });
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageEl.style.transform = 'translateX(400px) !important';
        messageEl.style.opacity = '0 !important';
        setTimeout(() => {
            if (messageEl.parentNode) {
                messageEl.remove();
            }
        }, 300);
    }, 5000);
    
    console.log(`Auth message (${isError ? 'ERROR' : 'INFO'}): ${message}`);
}

// Registration
async function registerPasskey(username) {
    if (!username || username.trim().length < 3) {
        showMessage('Username must be at least 3 characters long', true);
        return false;
    }

    try {
        showMessage('Starting registration...');

        // 1. Request registration options from backend
        const res = await fetch('/auth/register.php', {
            method: 'POST',
            body: new URLSearchParams({username: username.trim()})
        });

        let responseData;
        try {
            responseData = await res.json();
        } catch (jsonError) {
            console.error('Failed to parse registration response as JSON:', jsonError);
            showMessage('Server error: Invalid response format', true);
            return false;
        }

        if (!res.ok) {
            showMessage(`Registration failed: ${responseData.error || 'Unknown error'}`, true);
            if (responseData.details) {
                console.error('Registration error details:', responseData.details);
            }
            return false;
        }

        const options = responseData;
        console.log('Registration options received:', options);

        // 2. Convert base64url encoded data to Uint8Array for WebAuthn API
        if (!options.challenge) {
            throw new Error('No challenge received from server');
        }
        
        options.challenge = Uint8Array.from(
            atob(urlBase64ToBase64(options.challenge)), 
            c => c.charCodeAt(0)
        );

        if (!options.user || !options.user.id) {
            throw new Error('No user ID received from server');
        }

        options.user.id = Uint8Array.from(
            atob(urlBase64ToBase64(options.user.id)), 
            c => c.charCodeAt(0)
        );

        showMessage('Please complete the security key/biometric prompt...');

        // 3. Call browser WebAuthn API
        const credential = await navigator.credentials.create({publicKey: options});
        
        if (!credential) {
            throw new Error('No credential returned from authenticator');
        }

        console.log('Credential created:', credential);

        // 4. Convert ArrayBuffer data to base64url for JSON transmission
        const credentialData = {
            id: credential.id,
            rawId: arrayBufferToBase64Url(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: arrayBufferToBase64Url(credential.response.attestationObject),
                clientDataJSON: arrayBufferToBase64Url(credential.response.clientDataJSON)
            }
        };

        showMessage('Saving registration...');

        // 5. Send result to backend
        const res2 = await fetch('/auth/register.php', {
            method: 'PUT',
            body: JSON.stringify(credentialData),
            headers: {'Content-Type': 'application/json'}
        });

        let result;
        try {
            result = await res2.json();
        } catch (jsonError) {
            console.error('Failed to parse registration save response as JSON:', jsonError);
            showMessage('Server error: Invalid response format', true);
            return false;
        }

        if (res2.ok && result.ok) {
            showMessage(`✅ Registration successful! Welcome, ${result.username}!`);
            return true;
        } else {
            showMessage(`Registration failed: ${result.error || 'Unknown error'}`, true);
            if (result.details) {
                console.error('Registration error details:', result.details);
            }
            return false;
        }

    } catch (error) {
        console.error('Registration error:', error);
        if (error.name === 'NotAllowedError') {
            showMessage('Registration was cancelled or timed out', true);
        } else if (error.name === 'NotSupportedError') {
            showMessage('WebAuthn is not supported by this browser', true);
        } else {
            showMessage(`Registration failed: ${error.message}`, true);
        }
        return false;
    }
}

// Authentication (Login)
async function loginPasskey(username) {
    if (!username || username.trim().length === 0) {
        showMessage('Please enter a username', true);
        return false;
    }

    try {
        showMessage('Starting login...');

        // 1. Request authentication options from backend
        const res = await fetch('/auth/login.php', {
            method: 'POST',
            body: new URLSearchParams({username: username.trim()})
        });

        let responseData;
        try {
            responseData = await res.json();
        } catch (jsonError) {
            console.error('Failed to parse login response as JSON:', jsonError);
            console.error('Response status:', res.status);
            console.error('Response headers:', res.headers);
            
            // Try to get the actual response text for debugging
            const responseText = await res.clone().text();
            console.error('Raw response text:', responseText);
            
            showMessage('Server error: Invalid response format. Check console for details.', true);
            return false;
        }

        if (!res.ok) {
            showMessage(`Login failed: ${responseData.error || 'Unknown error'}`, true);
            if (responseData.details) {
                console.error('Login error details:', responseData.details);
            }
            return false;
        }

        const options = responseData;
        console.log('Authentication options received:', options);

        // 2. Convert base64url encoded data to Uint8Array for WebAuthn API
        if (!options.challenge) {
            throw new Error('No challenge received from server');
        }

        options.challenge = Uint8Array.from(
            atob(urlBase64ToBase64(options.challenge)), 
            c => c.charCodeAt(0)
        );

        if (options.allowCredentials && Array.isArray(options.allowCredentials)) {
            options.allowCredentials = options.allowCredentials.map(cred => {
                if (!cred.id) {
                    throw new Error('Invalid credential ID from server');
                }
                return {
                    ...cred,
                    id: Uint8Array.from(
                        atob(urlBase64ToBase64(cred.id)), 
                        c => c.charCodeAt(0)
                    )
                };
            });
        }

        showMessage('Please complete the security key/biometric prompt...');

        // 3. Call browser WebAuthn API
        const assertion = await navigator.credentials.get({publicKey: options});
        
        if (!assertion) {
            throw new Error('No assertion returned from authenticator');
        }

        console.log('Assertion created:', assertion);

        // 4. Convert ArrayBuffer data to base64url for JSON transmission
        const assertionData = {
            id: assertion.id,
            rawId: arrayBufferToBase64Url(assertion.rawId),
            type: assertion.type,
            response: {
                authenticatorData: arrayBufferToBase64Url(assertion.response.authenticatorData),
                clientDataJSON: arrayBufferToBase64Url(assertion.response.clientDataJSON),
                signature: arrayBufferToBase64Url(assertion.response.signature),
                userHandle: assertion.response.userHandle ? 
                    arrayBufferToBase64Url(assertion.response.userHandle) : null
            }
        };

        showMessage('Verifying login...');

        // 5. Send result to backend
        const res2 = await fetch('/auth/login.php', {
            method: 'PUT',
            body: JSON.stringify(assertionData),
            headers: {'Content-Type': 'application/json'}
        });

        let result;
        try {
            result = await res2.json();
        } catch (jsonError) {
            console.error('Failed to parse login verification response as JSON:', jsonError);
            console.error('Response status:', res2.status);
            
            // Try to get the actual response text for debugging
            const responseText = await res2.clone().text();
            console.error('Raw response text:', responseText);
            
            showMessage('Server error: Invalid response format. Check console for details.', true);
            return false;
        }

        if (res2.ok && result.ok) {
            showMessage('✅ Login successful! Redirecting...');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            return true;
        } else {
            showMessage(`Login failed: ${result.error || 'Unknown error'}`, true);
            if (result.details) {
                console.error('Login error details:', result.details);
            }
            return false;
        }

    } catch (error) {
        console.error('Login error:', error);
        if (error.name === 'NotAllowedError') {
            showMessage('Login was cancelled or timed out', true);
        } else if (error.name === 'NotSupportedError') {
            showMessage('WebAuthn is not supported by this browser', true);
        } else {
            showMessage(`Login failed: ${error.message}`, true);
        }
        return false;
    }
}
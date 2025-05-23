<?php
// XRPG Landing Page with Theme Customizer and Updates
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XRPG - Customize Your Adventure</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        /* Page-specific layout styles */
        .hidden { display: none !important; }
        
        .hero-section {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .updates-section {
            max-width: 600px;
            margin: 0 auto 3rem;
            padding: 0 2rem;
        }
        
        .theme-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            padding: 0 2rem;
            max-width: 1200px;
            margin: 0 auto 3rem;
        }
        
        .demo-section {
            padding: 3rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .demo-box {
            padding: 2rem;
        }
        
        .demo-box h4 {
            margin-top: 0;
            color: var(--color-accent);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .contrast-warning {
            background: rgba(255, 100, 100, 0.1);
            border: 1px solid rgba(255, 100, 100, 0.3);
            color: #ff6464;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        
    </style>
</head>
<body>
    <!-- Fixed Theme Toggle -->
    <button id="theme-toggle" class="theme-toggle-fixed" title="Toggle light/dark mode">🌞</button>

    <!-- Side Navigation -->
    <nav class="side-nav">
        <button class="side-nav-toggle" title="Toggle menu">☰</button>
        <div class="side-nav-items">
            <a href="#" class="side-nav-item active" title="Home">
                <span class="side-nav-icon">🏠</span>
                <span class="side-nav-text">Home</span>
            </a>
            <a href="#" class="side-nav-item" title="Login" onclick="openModal('auth-modal'); return false;">
                <span class="side-nav-icon">🔑</span>
                <span class="side-nav-text">Login</span>
            </a>
            <a href="#" class="side-nav-item" title="Guide">
                <span class="side-nav-icon">📖</span>
                <span class="side-nav-text">Guide</span>
            </a>
            <!-- These would be shown when logged in:
            <a href="#" class="side-nav-item" title="Profile">
                <span class="side-nav-icon">👤</span>
                <span class="side-nav-text">Profile</span>
            </a>
            <a href="#" class="side-nav-item" title="Dungeons">
                <span class="side-nav-icon">🏰</span>
                <span class="side-nav-text">Dungeons</span>
            </a>
            <a href="#" class="side-nav-item" title="Shop">
                <span class="side-nav-icon">🛍️</span>
                <span class="side-nav-text">Shop</span>
            </a>
            <a href="#" class="side-nav-item" title="Equipment">
                <span class="side-nav-icon">⚔️</span>
                <span class="side-nav-text">Equipment</span>
            </a>
            <a href="#" class="side-nav-item" title="House">
                <span class="side-nav-icon">🏡</span>
                <span class="side-nav-text">House</span>
            </a>
            <a href="#" class="side-nav-item" title="Leaderboard">
                <span class="side-nav-icon">🏆</span>
                <span class="side-nav-text">Leaderboard</span>
            </a>
            <a href="#" class="side-nav-item" title="Stats">
                <span class="side-nav-icon">📊</span>
                <span class="side-nav-text">Stats</span>
            </a>
            -->
        </div>
    </nav>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-title">XRPG</div>
        <div class="header-actions">
            <button class="button" onclick="openModal('auth-modal')">Start Playing</button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1>Welcome to XRPG</h1>
            <p class="text-muted">Your adventure begins with making it yours</p>
            <div class="theme-preview-circle"></div>
        </div>

        <!-- Updates Section -->
        <div class="updates-section">
            <div class="card updates-area" style="padding: 1.5rem;">
                <h3 style="margin-top: 0;">🚀 Recent Updates</h3>
                <div id="updates-container">
                    <div class="update-entry">
                        <span class="update-emoji">⏳</span>
                        <span class="update-blurb">Loading updates...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme Customizer -->
        <div class="theme-section">
            <div class="card" style="padding: 2rem;">
                <h3 style="margin-top: 0;">🎨 Theme Colors</h3>
                
                <div class="control-group">
                    <label class="control-label">Primary Accent</label>
                    <input type="color" id="accent-primary" value="#5299e0">
                </div>
                
                <div class="control-group">
                    <label class="control-label">Secondary Accent</label>
                    <input type="color" id="accent-secondary" value="#81aaff">
                </div>
                
                <div class="control-group">
                    <label class="control-label">Font Style</label>
                    <select id="font-select" style="width: 100%;">
                        <option value="sans">Clean & Modern (Sans-serif)</option>
                        <option value="mono">Technical (Monospace)</option>
                        <option value="game">Classic RPG (Serif)</option>
                        <option value="display">Bold & Impactful</option>
                    </select>
                </div>
                
                <div id="contrast-warning" class="contrast-warning hidden">
                    ⚠️ Low contrast detected. This color combination might be hard to read.
                </div>
            </div>

            <div class="card" style="padding: 2rem;">
                <h3 style="margin-top: 0;">✨ Visual Effects</h3>
                
                <div class="control-group">
                    <label class="control-label">
                        Border Radius
                        <span class="range-value" id="radius-value">18px</span>
                    </label>
                    <input type="range" id="radius-slider" min="9" max="40" value="18">
                </div>
                
                <div class="control-group">
                    <label class="control-label">
                        Shadow Intensity
                        <span class="range-value" id="shadow-value">0.36</span>
                    </label>
                    <input type="range" id="shadow-slider" min="0.05" max="0.5" step="0.01" value="0.36">
                </div>
                
                <div class="control-group">
                    <label class="control-label">
                        UI Opacity
                        <span class="range-value" id="opacity-value">0.96</span>
                    </label>
                    <input type="range" id="opacity-slider" min="0.8" max="1" step="0.01" value="0.96">
                </div>
            </div>
        </div>

        <!-- Demo Section -->
        <div class="demo-section">
            <h2 style="text-align: center;">🎮 UI Component Showcase</h2>
            <p class="text-muted" style="text-align: center;">See how your theme affects different UI elements</p>
            
            <div class="demo-grid">
                <!-- Buttons -->
                <div class="card demo-box">
                    <h4>Buttons</h4>
                    <div class="form-group">
                        <button class="button">Primary Action</button>
                        <button class="button" disabled style="margin-left: 0.5rem;">Disabled</button>
                    </div>
                    <div class="form-group">
                        <button class="button" style="width: 100%;">Full Width Button</button>
                    </div>
                </div>
                
                <!-- Text Inputs -->
                <div class="card demo-box">
                    <h4>Input Fields</h4>
                    <div class="form-group">
                        <label for="demo-text">Character Name</label>
                        <input type="text" id="demo-text" placeholder="Enter your hero name..." style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label for="demo-select">Class Selection</label>
                        <select id="demo-select" style="width: 100%;">
                            <option>Choose your class...</option>
                            <option>⚔️ Warrior</option>
                            <option>🧙‍♂️ Mage</option>
                            <option>🏹 Ranger</option>
                            <option>🗡️ Rogue</option>
                        </select>
                    </div>
                </div>
                
                <!-- Checkboxes and Radio -->
                <div class="card demo-box">
                    <h4>Options & Settings</h4>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" checked> Enable sound effects
                        </label>
                        <label>
                            <input type="checkbox" checked> Show damage numbers
                        </label>
                        <label>
                            <input type="checkbox"> Auto-loot items
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Difficulty:</label>
                        <label>
                            <input type="radio" name="difficulty" checked> Normal
                        </label>
                        <label>
                            <input type="radio" name="difficulty"> Hard
                        </label>
                        <label>
                            <input type="radio" name="difficulty"> Legendary
                        </label>
                    </div>
                </div>
                
                <!-- Text Areas -->
                <div class="card demo-box">
                    <h4>Text Areas</h4>
                    <div class="form-group">
                        <label for="demo-bio">Character Biography</label>
                        <textarea id="demo-bio" placeholder="Tell your character's story..." rows="4" style="width: 100%;">A brave adventurer from distant lands, seeking glory and treasure in the dangerous dungeons of XRPG...</textarea>
                    </div>
                </div>
                
                <!-- Cards and Surfaces -->
                <div class="card demo-box">
                    <h4>Item Cards</h4>
                    <div class="surface" style="padding: 1rem; margin-bottom: 1rem;">
                        <div class="text-accent" style="font-weight: bold;">🗡️ Flaming Sword</div>
                        <div class="text-muted" style="font-size: 0.875rem;">Legendary Weapon</div>
                        <div style="margin-top: 0.5rem;">+25 Attack | +10 Fire Damage</div>
                    </div>
                    <div class="surface" style="padding: 1rem;">
                        <div class="text-accent" style="font-weight: bold;">🧪 Health Potion</div>
                        <div class="text-muted" style="font-size: 0.875rem;">Consumable</div>
                        <div style="margin-top: 0.5rem;">Restores 50 HP</div>
                    </div>
                </div>
                
                <!-- Modal Demo -->
                <div class="card demo-box">
                    <h4>Modals & Dialogs</h4>
                    <button class="button" onclick="openModal('demo-modal')">Open Demo Modal</button>
                    <button class="button" onclick="openModal('loot-modal')" style="margin-left: 0.5rem;">Loot Received!</button>
                </div>
            </div>
            
            <!-- Accessibility Demo -->
            <div role="region" aria-label="Game Statistics" class="card" style="margin-top: 2rem; padding: 2rem;">
                <h4>ARIA Region Example</h4>
                <p>This panel demonstrates how ARIA regions automatically inherit theme styles for better accessibility.</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div class="surface" style="padding: 1rem; text-align: center;">
                        <div class="text-muted">Level</div>
                        <div class="text-accent" style="font-size: 2rem; font-weight: bold;">42</div>
                    </div>
                    <div class="surface" style="padding: 1rem; text-align: center;">
                        <div class="text-muted">Gold</div>
                        <div class="text-accent" style="font-size: 2rem; font-weight: bold;">2,847</div>
                    </div>
                    <div class="surface" style="padding: 1rem; text-align: center;">
                        <div class="text-muted">XP to Next</div>
                        <div class="text-accent" style="font-size: 2rem; font-weight: bold;">1,253</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-links">
                <a href="#" onclick="openModal('privacy-modal'); return false;">Privacy Policy</a>
                <a href="#" onclick="openModal('terms-modal'); return false;">Terms of Service</a>
                <a href="#" onclick="openModal('about-modal'); return false;">About XRPG</a>
                <a href="#" onclick="openModal('contact-modal'); return false;">Contact</a>
            </div>
            <div class="footer-info">
                <p>XRPG v1.0.0 • Made with ❤️ for adventurers everywhere</p>
                <p>&copy; 2025 XRPG. All rights reserved.</p>
            </div>
        </footer>
    </main>

    <!-- Auth Modal -->
    <dialog id="auth-modal" class="modal">
        <div class="modal-header">
            <h2>Begin Your Adventure</h2>
            <button data-close-modal style="background: none; border: none; margin: 20px; font-size: 1rem; cursor: pointer; color: var(--color-muted);" class="close">×</button>
        </div>
        <div class="modal-body">
            <form>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" placeholder="Enter your hero name..." style="width: 100%;">
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="button" class="button" style="width: 100%;">
                        🔑 Sign in with Passkey
                    </button>
                </div>
                
                <div style="margin-top: 1rem;">
                    <button type="button" class="button" disabled style="width: 100%; opacity: 0.5;">
                        🔒 Sign in with Password
                    </button>
                </div>
                
                <div style="margin-top: 2rem; text-align: center; color: var(--color-muted);">
                    New hero? <a href="#" style="color: var(--color-accent);">Create Account</a>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Demo Modal -->
    <dialog id="demo-modal" class="modal">
        <div class="modal-header">
            <h2>Demo Modal</h2>
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">×</button>
        </div>
        <div class="modal-body">
            <p>This is a demonstration of the modal system. Modals can contain any content and will always center on the screen.</p>
            <p>Click outside the modal or press ESC to close it.</p>
        </div>
        <div class="modal-footer">
            <button class="button" data-close-modal>Close</button>
            <button class="button">Save Changes</button>
        </div>
    </dialog>

    <!-- Loot Modal -->
    <dialog id="loot-modal" class="modal" style="max-width: 400px;">
        <div class="modal-body" style="text-align: center; padding: 2rem;">
            <h2 style="margin-top: 0;">🎉 Victory!</h2>
            <p>You received:</p>
            <div class="surface" style="padding: 1rem; margin: 1rem 0;">
                <div style="font-size: 2rem;">💎</div>
                <div class="text-accent" style="font-weight: bold;">Crystal of Power</div>
                <div class="text-muted">Rare Material</div>
            </div>
            <button class="button" data-close-modal style="width: 100%;">Awesome!</button>
        </div>
    </dialog>

    <!-- Footer Modals -->
    <dialog id="privacy-modal" class="modal">
        <div class="modal-header">
            <h2>Privacy Policy</h2>
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">×</button>
        </div>
        <div class="modal-body">
            <h3>Your Privacy Matters</h3>
            <p>At XRPG, we take your privacy seriously. This policy outlines how we collect, use, and protect your information.</p>
            
            <h4>Information We Collect</h4>
            <ul>
                <li>Account information (username, email)</li>
                <li>Game progress and statistics</li>
                <li>Theme preferences and settings</li>
            </ul>
            
            <h4>How We Use Your Information</h4>
            <p>We use your information solely to provide and improve the XRPG gaming experience. We never sell your data to third parties.</p>
            
            <p><em>Last updated: January 2025</em></p>
        </div>
        <div class="modal-footer">
            <button class="button" data-close-modal>Close</button>
        </div>
    </dialog>

    <dialog id="terms-modal" class="modal">
        <div class="modal-header">
            <h2>Terms of Service</h2>
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">×</button>
        </div>
        <div class="modal-body">
            <h3>Terms and Conditions</h3>
            <p>By using XRPG, you agree to these terms:</p>
            
            <h4>1. Fair Play</h4>
            <p>Players must not use cheats, exploits, or automation tools. Play fair, have fun!</p>
            
            <h4>2. Respectful Community</h4>
            <p>Be respectful to other players. Harassment, hate speech, or toxic behavior will result in account suspension.</p>
            
            <h4>3. Account Security</h4>
            <p>You are responsible for keeping your account secure. Don't share your login credentials.</p>
            
            <h4>4. Virtual Items</h4>
            <p>Virtual items have no real-world value and cannot be traded for real money.</p>
            
            <p><em>Last updated: January 2025</em></p>
        </div>
        <div class="modal-footer">
            <button class="button" data-close-modal>I Agree</button>
        </div>
    </dialog>

    <dialog id="about-modal" class="modal">
        <div class="modal-header">
            <h2>About XRPG</h2>
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">×</button>
        </div>
        <div class="modal-body">
            <h3>Welcome to XRPG!</h3>
            <p>XRPG is a next-generation role-playing game that puts customization and player experience first.</p>
            
            <h4>Features</h4>
            <ul>
                <li>🎨 Fully customizable UI themes</li>
                <li>⚔️ Epic battles and dungeons</li>
                <li>🏰 Build your own stronghold</li>
                <li>👥 Join guilds and make friends</li>
                <li>📈 Detailed stats and progression</li>
            </ul>
            
            <h4>Our Mission</h4>
            <p>We believe games should adapt to players, not the other way around. That's why every aspect of XRPG can be customized to match your style.</p>
            
            <p><strong>Version:</strong> 1.0.0<br>
            <strong>Released:</strong> January 2025</p>
        </div>
        <div class="modal-footer">
            <button class="button" data-close-modal>Cool!</button>
        </div>
    </dialog>

    <dialog id="contact-modal" class="modal">
        <div class="modal-header">
            <h2>Contact Us</h2>
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">×</button>
        </div>
        <div class="modal-body">
            <h3>Get in Touch</h3>
            <p>We'd love to hear from you! <br> We typically respond to emails within 48 hours.</p>
            
            <div class="form-group">
                <label for="contact-email">Your Email</label>
                <input type="email" id="contact-email" placeholder="hero@example.com" style="width: 100%;">
            </div>
            
            <div class="form-group">
                <label for="contact-subject">Subject</label>
                <select id="contact-subject" style="width: 100%;">
                    <option>General Inquiry</option>
                    <option>Bug Report</option>
                    <option>Feature Request</option>
                    <option>Account Issue</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="contact-message">Message</label>
                <textarea id="contact-message" placeholder="Tell us what's on your mind..." rows="4" style="width: 100%;"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="button" data-close-modal>Cancel</button>
            <button class="button">Send Message</button>
        </div>
    </dialog>

    <script src="/assets/js/theme.js"></script>
    <script>
        // Load updates when page loads
        loadUpdates();
        
        // Refresh updates every 30 seconds
        setInterval(loadUpdates, 30000);
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>XRPG - Customize Your Adventure</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/ico/favicon-16x16.png">
    <link rel="shortcut icon" href="/assets/ico/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <style>
        .hero-section {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--gradient-accent);
            color: white;
            margin-bottom: 3rem;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            margin: 0 0 1rem 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .hero-section p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin: 0 0 2rem 0;
        }
        
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: var(--user-radius);
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .hero-cta:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .features-section {
            max-width: 1200px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            text-align: center;
            padding: 2rem;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .feature-card h3 {
            margin: 0 0 1rem 0;
            color: var(--color-accent);
        }
        
        .updates-section {
            max-width: 600px;
            margin: 0 auto 4rem;
            padding: 0 2rem;
        }
        
        .getting-started {
            background: var(--color-surface);
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .step-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .step {
            text-align: center;
        }
        
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: var(--gradient-accent);
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 1rem;
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
            <a href="#features" class="side-nav-item" title="Features">
                <span class="side-nav-icon">⭐</span>
                <span class="side-nav-text">Features</span>
            </a>
            <a href="#getting-started" class="side-nav-item" title="Guide">
                <span class="side-nav-icon">📖</span>
                <span class="side-nav-text">Guide</span>
            </a>
        </div>
    </nav>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-title">XRPG</div>
        <div class="header-actions">
            <button class="button" onclick="openModal('auth-modal')">⚔️ Start Playing 🛡️</button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1>Welcome to XRPG</h1>
            <p>The next-generation RPG where your adventure begins with total customization</p>
            <a href="#" class="hero-cta" onclick="openModal('auth-modal'); return false;">
                <span>⚔️</span>
                Begin Your Quest
                <span>🛡️</span>
            </a>
        </div>

        <!-- Features Section -->
        <section id="features" class="features-section">
            <h2 style="text-align: center; margin-bottom: 1rem;">Why Choose XRPG?</h2>
            <p style="text-align: center; color: var(--color-muted); margin-bottom: 2rem;">
                Experience gaming like never before with features designed for the modern adventurer
            </p>
            
            <div class="features-grid">
                <div class="card feature-card">
                    <span class="feature-icon">🎨</span>
                    <h3>Total Customization</h3>
                    <p>Personalize every aspect of your interface with our advanced theming system. Dark mode, custom colors, fonts, and effects - make it truly yours.</p>
                </div>
                
                <div class="card feature-card">
                    <span class="feature-icon">🔒</span>
                    <h3>Ultra-Secure Login</h3>
                    <p>Say goodbye to passwords forever. Our passkey-only authentication provides military-grade security with the convenience of biometric login.</p>
                </div>
                
                <div class="card feature-card">
                    <span class="feature-icon">⚔️</span>
                    <h3>Epic Adventures</h3>
                    <p>Explore vast dungeons, battle fearsome monsters, and collect legendary loot. Every quest is procedurally generated for endless replayability.</p>
                </div>
                
                <div class="card feature-card">
                    <span class="feature-icon">👥</span>
                    <h3>Social Gaming</h3>
                    <p>Join guilds, team up with friends, and participate in massive raids. The adventure is always better when shared with others.</p>
                </div>
                
                <div class="card feature-card">
                    <span class="feature-icon">📈</span>
                    <h3>Deep Progression</h3>
                    <p>Level up your character with meaningful choices. Skill trees, stat allocation, and equipment crafting give you complete control over your build.</p>
                </div>
                
                <div class="card feature-card">
                    <span class="feature-icon">🌍</span>
                    <h3>Living World</h3>
                    <p>The world of XRPG evolves constantly with regular updates, seasonal events, and community-driven content that keeps the experience fresh.</p>
                </div>
            </div>
        </section>

        <!-- Getting Started Section -->
        <section id="getting-started" class="getting-started">
            <h2>Ready to Begin?</h2>
            <p style="color: var(--color-muted); margin-bottom: 0;">Getting started is easier than ever with our streamlined onboarding process</p>
            
            <div class="step-list">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Create Account</h4>
                    <p>Sign up with just a username - no email or password required thanks to passkey technology.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Customize Theme</h4>
                    <p>Make the interface your own with our powerful theme customization tools.</p>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Start Playing</h4>
                    <p>Jump right into the action with your first character and begin your epic journey!</p>
                </div>
            </div>
        </section>

        <!-- Updates Section -->
        <div class="updates-section">
            <div class="card updates-area" style="padding: 1.5rem;">
                <h3 style="margin-top: 0;">🚀 Latest Updates</h3>
                <div id="updates-container">
                    <div class="update-entry">
                        <span class="update-emoji">⏳</span>
                        <span class="update-blurb">Loading updates...</span>
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

    <!-- Modern Auth Modal -->
    <dialog id="auth-modal" class="auth-modal">
        <div class="auth-header">
            <button id="auth-close-btn" class="auth-close" aria-label="Close">&times;</button>
            <h2 id="auth-title" class="auth-title">Welcome Back</h2>
            <p id="auth-subtitle" class="auth-subtitle">Sign in to continue your adventure</p>
        </div>
        <div class="auth-body">
            <form id="auth-form" class="auth-form">
                <div class="auth-field">
                    <label for="username" class="auth-label">Username</label>
                    <input type="text" id="username" class="auth-input" placeholder="Enter your hero name..." autocomplete="username" required>
                </div>
                
                <button type="submit" id="auth-action-btn" class="auth-button auth-button-primary">
                    <span class="passkey-icon">🔑</span>Sign In
                </button>
            </form>
            
            <div class="auth-switcher">
                <div class="auth-switcher-text">
                    <button type="button" id="auth-switch-btn" class="auth-button-primary">
                        Don't have an account? Create one
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Footer Modals -->
    <dialog id="privacy-modal" class="modal">
        <div class="modal-header">
            <h2>Privacy Policy</h2>
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">&times;</button>
        </div>
        <div class="modal-body">
            <h3>Your Privacy Matters</h3>
            <p>At XRPG, we take your privacy seriously. This policy outlines how we collect, use, and protect your information.</p>
            <h4>Information We Collect</h4>
            <ul>
                <li>Account information (username, passkey data)</li>
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
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">&times;</button>
        </div>
        <div class="modal-body">
            <h3>Terms and Conditions</h3>
            <p>By using XRPG, you agree to these terms:</p>
            <h4>1. Fair Play</h4>
            <p>Players must not use cheats, exploits, or automation tools. Play fair, have fun!</p>
            <h4>2. Respectful Community</h4>
            <p>Be respectful to other players. Harassment, hate speech, or toxic behavior will result in account suspension.</p>
            <h4>3. Account Security</h4>
            <p>You are responsible for keeping your account secure. Passkeys provide the highest level of security.</p>
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
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--color-muted);">&times;</button>
        </div>
        <div class="modal-body">
            <h3>Welcome to XRPG!</h3>
            <p>XRPG is a next-generation role-playing game that puts customization and player experience first.</p>
            <h4>Features</h4>
            <ul>
                <li>🎨 Fully customizable UI themes</li>
                <li>🔑 Secure passkey-only authentication</li>
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
            <button data-close-modal style="background: none; border: none; font-size: 1.5rem; cursor: properly:">&times;</button>
        </div>
        <div class="modal-body">
            <h3>Get in Touch</h3>
            <p>We'd love to hear from you! We typically respond to emails within 48 hours.</p>
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
    <script src="/assets/js/passkey.js?v=2"></script>
    <script>
        // Load updates when page loads
        loadUpdates();
        setInterval(loadUpdates, 30000);
    </script>
</body>
</html>
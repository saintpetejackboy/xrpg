<?php
// /players/character-creation.php - Character Creation Interface (REFACTORED VERSION)

session_start();
require_once __DIR__ . '/../config/environment.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || !$_SESSION['user']) {
    header('Location: /');
    exit;
}

// Connect to database and load utilities
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/load-preferences.php';

$userInfo = getUserInfo($_SESSION['user']);
$username = htmlspecialchars($userInfo['username']);
$userId = $userInfo['id'];

// Check if character already exists and is complete
try {
    $stmt = $pdo->prepare('SELECT * FROM user_characters WHERE user_id = ?');
    $stmt->execute([$userId]);
    $character = $stmt->fetch();
    
    if ($character && $character['is_character_complete']) {
        // Character is already complete, redirect to dashboard
        header('Location: /players/');
        exit;
    }
} catch (Exception $e) {
    error_log("Error checking character: " . $e->getMessage());
}

// Get available races
try {
    $stmt = $pdo->prepare('SELECT * FROM races WHERE is_active = 1 ORDER BY sort_order, name');
    $stmt->execute();
    $races = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading races: " . $e->getMessage());
    $races = [];
}

// Get available classes (basic tier 1 classes)
try {
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE is_active = 1 AND tier = 1 ORDER BY sort_order, name');
    $stmt->execute();
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading classes: " . $e->getMessage());
    $classes = [];
}

// Get available jobs
try {
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE is_active = 1 ORDER BY sort_order, name');
    $stmt->execute();
    $jobs = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error loading jobs: " . $e->getMessage());
    $jobs = [];
}

// Load user preferences
$preferences = loadUserPreferences($pdo, $userId);

// Page-specific variables for common components
$pageTitle = 'XRPG - Character Creation';
$currentPage = '';  // No active menu item during character creation
$headerTitle = 'XRPG - Character Creation';
$footerInfo = 'XRPG Character Creation • Welcome, ' . $username;

// Custom header actions for this page
$headerActions = '
    <button class="button" onclick="XRPGPlayer.logout()" title="Logout">
        <span style="margin-right: 0.5rem;">🚪</span>Logout
    </button>
';

// Additional CSS for this page
$additionalCSS = '
.creation-header {
    text-align: center;
    padding: 2rem;
    background: var(--gradient-accent);
    color: white;
    margin-bottom: 2rem;
    border-radius: var(--user-radius);
}

.step-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: calc(var(--user-radius) * 0.5);
    background: var(--color-surface);
    border: 2px solid var(--color-border);
    transition: all 0.3s ease;
}

.step.active {
    background: var(--gradient-accent);
    color: white;
    border-color: var(--color-accent);
}

.step.completed {
    background: rgba(76, 175, 80, 0.2);
    border-color: #4caf50;
    color: #4caf50;
}

.step-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    font-weight: bold;
    font-size: 0.875rem;
}

.step.completed .step-number {
    background: #4caf50;
    color: white;
}

.creation-section {
    margin-bottom: 3rem;
}

.section-hidden {
    display: none;
}

.selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.selection-card {
    position: relative;
    padding: 1.5rem;
    border: 2px solid var(--color-border);
    border-radius: var(--user-radius);
    background: var(--color-surface);
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.selection-card:hover {
    border-color: var(--color-accent);
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow);
}

.selection-card.selected {
    border-color: var(--color-accent);
    background: linear-gradient(135deg, var(--color-surface), rgba(var(--user-accent), 0.1));
    box-shadow: var(--shadow-glow);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.card-icon {
    font-size: 2rem;
    min-width: 2rem;
}

.card-title {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--color-accent);
}

.card-description {
    color: var(--color-text-secondary);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.stat-bonuses {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-border);
}

.stat-bonus {
    text-align: center;
    font-size: 0.875rem;
}

.stat-name {
    color: var(--color-muted);
    font-size: 0.75rem;
}

.stat-value {
    font-weight: bold;
    color: var(--color-accent);
}

.stat-value.positive {
    color: #4caf50;
}

.stat-value.negative {
    color: #f44336;
}

.special-abilities {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-border);
}

.ability-tag {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: rgba(var(--user-accent), 0.2);
    border-radius: calc(var(--user-radius) * 0.25);
    font-size: 0.75rem;
    margin: 0.25rem;
    color: var(--color-accent);
}

.warning-box {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffc107;
    padding: 1rem;
    border-radius: calc(var(--user-radius) * 0.5);
    margin: 1rem 0;
}

.info-box {
    background: rgba(33, 150, 243, 0.1);
    border: 1px solid rgba(33, 150, 243, 0.3);
    color: #2196f3;
    padding: 1rem;
    border-radius: calc(var(--user-radius) * 0.5);
    margin: 1rem 0;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.final-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.final-stat {
    text-align: center;
    padding: 1rem;
    background: var(--color-surface-alt);
    border-radius: calc(var(--user-radius) * 0.5);
}

.final-stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-accent);
}

.final-stat-name {
    color: var(--color-muted);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.character-summary {
    background: var(--color-surface-alt);
    padding: 2rem;
    border-radius: var(--user-radius);
    margin: 2rem 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--color-border);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-label {
    font-weight: bold;
    color: var(--color-text);
}

.summary-value {
    color: var(--color-accent);
    font-weight: bold;
}

@media (max-width: 768px) {
    .step-indicator {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .step {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons .button {
        width: 100%;
        max-width: 300px;
    }
}
';

// Additional global JS variables
$additionalGlobalJS = '
window.racesData = ' . json_encode($races) . ';
window.classesData = ' . json_encode($classes) . ';
window.jobsData = ' . json_encode($jobs) . ';
';

// Include common header (but without navigation since character creation is special)
include __DIR__ . '/../includes/header.php';
?>

    <!-- Simple header for character creation -->
    <header class="main-header">
        <div class="header-title"><?= $headerTitle ?></div>
        <div class="header-actions">
            <?= $headerActions ?>
        </div>
    </header>

<!-- Main Content -->
<main class="main-content">
    <!-- Creation Header -->
    <div class="creation-header">
        <h1 style="margin: 0 0 0.5rem 0;">⚔️ Create Your Character</h1>
        <p style="margin: 0; opacity: 0.9;">Welcome, <?= $username ?>! Let's build your legend.</p>
    </div>

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" id="step-race">
            <div class="step-number">1</div>
            <span>Choose Race</span>
        </div>
        <div class="step" id="step-class">
            <div class="step-number">2</div>
            <span>Select Class</span>
        </div>
        <div class="step" id="step-job">
            <div class="step-number">3</div>
            <span>Pick Job</span>
        </div>
        <div class="step" id="step-confirm">
            <div class="step-number">4</div>
            <span>Confirm</span>
        </div>
    </div>

    <!-- Race Selection -->
    <div class="creation-section" id="section-race">
        <div class="card" style="padding: 2rem;">
            <h2 style="margin-top: 0; color: var(--color-accent);">🧬 Choose Your Race</h2>
            
            <div class="warning-box">
                <strong>⚠️ Permanent Decision!</strong><br>
                Your race choice is permanent and cannot be changed. Choose wisely!
            </div>
            
            <div class="info-box">
                <strong>ℹ️ About Races:</strong><br>
                Each race provides permanent stat bonuses and penalties that will affect your character throughout their entire journey.
            </div>

            <div class="selection-grid">
                <?php foreach ($races as $race): ?>
                    <div class="selection-card" data-type="race" data-id="<?= $race['id'] ?>" data-name="<?= htmlspecialchars($race['name']) ?>">
                        <div class="card-header">
                            <div class="card-icon">
                                <?php
                                $icons = ['Human' => '👤', 'Elf' => '🧝', 'Dwarf' => '🧔', 'Orc' => '👹', 'Halfling' => '🧙'];
                                echo $icons[$race['name']] ?? '👤';
                                ?>
                            </div>
                            <div class="card-title"><?= htmlspecialchars($race['name']) ?></div>
                        </div>
                        
                        <div class="card-description">
                            <?= htmlspecialchars($race['description']) ?>
                        </div>
                        
                        <div class="stat-bonuses">
                            <?php
                            $stats = ['strength' => 'STR', 'vitality' => 'VIT', 'agility' => 'AGI', 'intelligence' => 'INT', 'wisdom' => 'WIS', 'luck' => 'LCK'];
                            foreach ($stats as $stat => $abbr):
                                $value = $race[$stat . '_mod'];
                                if ($value != 0):
                            ?>
                                <div class="stat-bonus">
                                    <div class="stat-value <?= $value > 0 ? 'positive' : 'negative' ?>">
                                        <?= $value > 0 ? '+' : '' ?><?= $value ?>
                                    </div>
                                    <div class="stat-name"><?= $abbr ?></div>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="action-buttons">
                <button id="race-continue" class="button" disabled>
                    Continue to Class Selection →
                </button>
            </div>
        </div>
    </div>

    <!-- Class Selection -->
    <div class="creation-section section-hidden" id="section-class">
        <div class="card" style="padding: 2rem;">
            <h2 style="margin-top: 0; color: var(--color-accent);">⚔️ Select Your Class</h2>
            
            <div class="info-box">
                <strong>ℹ️ About Classes:</strong><br>
                Classes determine your combat role and provide stat bonuses. You can change classes later, but not for 3 days after character creation.
            </div>

            <div class="selection-grid">
                <?php foreach ($classes as $class): ?>
                    <div class="selection-card" data-type="class" data-id="<?= $class['id'] ?>" data-name="<?= htmlspecialchars($class['name']) ?>">
                        <div class="card-header">
                            <div class="card-icon">
                                <?php
                                $icons = ['Fighter' => '⚔️', 'Mage' => '🧙‍♂️', 'Rogue' => '🗡️', 'Cleric' => '✨', 'Ranger' => '🏹'];
                                echo $icons[$class['name']] ?? '⚔️';
                                ?>
                            </div>
                            <div class="card-title"><?= htmlspecialchars($class['name']) ?></div>
                        </div>
                        
                        <div class="card-description">
                            <?= htmlspecialchars($class['description']) ?>
                        </div>
                        
                        <div class="stat-bonuses">
                            <?php
                            $stats = ['strength' => 'STR', 'vitality' => 'VIT', 'agility' => 'AGI', 'intelligence' => 'INT', 'wisdom' => 'WIS', 'luck' => 'LCK'];
                            foreach ($stats as $stat => $abbr):
                                $value = $class[$stat . '_bonus'];
                                if ($value != 0):
                            ?>
                                <div class="stat-bonus">
                                    <div class="stat-value positive">+<?= $value ?></div>
                                    <div class="stat-name"><?= $abbr ?></div>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="action-buttons">
                <button class="button" id="class-back">← Back to Race</button>
                <button id="class-continue" class="button" disabled>
                    Continue to Job Selection →
                </button>
            </div>
        </div>
    </div>

    <!-- Job Selection -->
    <div class="creation-section section-hidden" id="section-job">
        <div class="card" style="padding: 2rem;">
            <h2 style="margin-top: 0; color: var(--color-accent);">💼 Pick Your Job</h2>
            
            <div class="info-box">
                <strong>ℹ️ About Jobs:</strong><br>
                Jobs provide economic benefits and small stat bonuses. They affect your idle gold income and merchant prices. You can change jobs later, but not for 3 days after character creation.
            </div>

            <div class="selection-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="selection-card" data-type="job" data-id="<?= $job['id'] ?>" data-name="<?= htmlspecialchars($job['name']) ?>">
                        <div class="card-header">
                            <div class="card-icon">
                                <?php
                                $icons = [
                                    'Merchant' => '💰', 'Blacksmith' => '🔨', 'Scholar' => '📚',
                                    'Gambler' => '🎲', 'Farmer' => '🌾', 'Adventurer' => '🗺️'
                                ];
                                echo $icons[$job['name']] ?? '💼';
                                ?>
                            </div>
                            <div class="card-title"><?= htmlspecialchars($job['name']) ?></div>
                        </div>
                        
                        <div class="card-description">
                            <?= htmlspecialchars($job['description']) ?>
                        </div>
                        
                        <div class="special-abilities">
                            <?php if ($job['idle_gold_rate'] != 1.0): ?>
                                <div class="ability-tag">
                                    💰 <?= $job['idle_gold_rate'] ?>x Gold Rate
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($job['merchant_discount'] > 0): ?>
                                <div class="ability-tag">
                                    🛒 <?= $job['merchant_discount'] ?>% Discount
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="stat-bonuses">
                            <?php
                            $stats = ['strength' => 'STR', 'vitality' => 'VIT', 'agility' => 'AGI', 'intelligence' => 'INT', 'wisdom' => 'WIS', 'luck' => 'LCK'];
                            foreach ($stats as $stat => $abbr):
                                $value = $job[$stat . '_bonus'];
                                if ($value != 0):
                            ?>
                                <div class="stat-bonus">
                                    <div class="stat-value positive">+<?= $value ?></div>
                                    <div class="stat-name"><?= $abbr ?></div>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="action-buttons">
                <button class="button" id="job-back">← Back to Class</button>
                <button id="job-continue" class="button" disabled>
                    Continue to Confirmation →
                </button>
            </div>
        </div>
    </div>

    <!-- Final Confirmation -->
    <div class="creation-section section-hidden" id="section-confirm">
        <div class="card" style="padding: 2rem;">
            <h2 style="margin-top: 0; color: var(--color-accent);">✅ Confirm Your Character</h2>
            
            <div class="character-summary">
                <h3 style="margin-top: 0;">Character Summary</h3>
                <div class="summary-row">
                    <span class="summary-label">Race:</span>
                    <span class="summary-value" id="selected-race">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Class:</span>
                    <span class="summary-value" id="selected-class">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Job:</span>
                    <span class="summary-value" id="selected-job">-</span>
                </div>
            </div>

            <h3 style="color: var(--color-accent);">📊 Final Character Stats</h3>
            <div class="final-stats" id="final-stats">
                <!-- Stats will be calculated and displayed here -->
            </div>

            <div class="warning-box">
                <strong>⚠️ Final Warning!</strong><br>
                Once you confirm your character, your race choice is permanent and your class/job cannot be changed for 3 days. Are you sure you want to proceed?
            </div>

            <div class="action-buttons">
                <button class="button" id="confirm-back">← Back to Job Selection</button>
                <button id="confirm-character" class="button">
                    🎮 Create My Character!
                </button>
            </div>
        </div>
    </div>

<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/character-creation.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>
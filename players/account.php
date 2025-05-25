<?php
// /players/account.php - Account management page (REFACTORED VERSION)

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/load-preferences.php';

// Check if user is logged in
if (empty($_SESSION['user'])) {
    header('Location:/');
    exit;
}

// Get user information
$userInfo = getUserInfo($_SESSION['user']);
$sessionUser = $_SESSION['user'];

// Determine current user record
if (is_string($sessionUser)) {
    // older flow stored just the username
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$sessionUser]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
} elseif (is_array($sessionUser) && isset($sessionUser['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$sessionUser['id']]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
} else {
    header('Location:/');
    exit;
}

if (!$user) {
    header('Location:/');
    exit;
}

// Load user preferences
$preferences = loadUserPreferences($pdo, $user['id']);

// Fallback status
$stmt = $pdo->prepare('SELECT fallback_password_hash FROM users WHERE id=?');
$stmt->execute([$user['id']]);
$hasFb = (bool)$stmt->fetchColumn();

// Passkey list
$stmt = $pdo->prepare(
    'SELECT id, device_name, created_at, last_used FROM user_passkeys WHERE user_id=?'
);
$stmt->execute([$user['id']]);
$pks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// Page-specific variables for common components
$pageTitle = 'XRPG - Account';
$currentPage = 'account';
$headerTitle = 'XRPG - Account Management';
$footerInfo = 'XRPG Account • Player: ' . htmlspecialchars($user['username']);

// Custom header actions for this page
$headerActions = '
    <button class="button" onclick="XRPGPlayer.goToDashboard()" title="Back to Dashboard">
        <span style="margin-right: 0.5rem;">🏠</span>Dashboard
    </button>
    <button class="button" onclick="XRPGPlayer.logout()" title="Logout">
        <span style="margin-right: 0.5rem;">🚪</span>Logout
    </button>
';

// Additional CSS for this page
$additionalCSS = '
.account {
    max-width: 650px;
    margin: 2rem auto;
    padding: 2rem;
    background: var(--color-surface);
    border-radius: var(--user-radius);
}

.account h3 {
    color: var(--color-accent);
    margin-bottom: 1rem;
    border-bottom: 2px solid var(--color-border);
    padding-bottom: 0.5rem;
}

.account-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

th, td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

th {
    background: var(--color-surface-alt);
    font-weight: 600;
    color: var(--color-text);
}

tr:nth-child(even) {
    background: var(--color-surface-alt);
}

tr:hover {
    background: rgba(var(--user-accent), 0.1);
}

.btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: calc(var(--user-radius) * 0.5);
    font-size: 0.875rem;
    font-weight: 500;
}

.status-set {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid rgba(76, 175, 80, 0.3);
    color: #4caf50;
}

.status-none {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    color: #ffc107;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--color-text);
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--color-border);
    border-radius: calc(var(--user-radius) * 0.5);
    background: var(--color-surface);
    color: var(--color-text);
    font-family: var(--user-font);
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--color-accent);
    box-shadow: 0 0 0 3px rgba(var(--user-accent), 0.1);
}

.feedback {
    margin-top: 0.5rem;
    padding: 0.5rem;
    border-radius: calc(var(--user-radius) * 0.25);
    font-size: 0.875rem;
}

.feedback.error {
    background: rgba(244, 67, 54, 0.1);
    border: 1px solid rgba(244, 67, 54, 0.3);
    color: #f44336;
}

.feedback.success {
    background: rgba(76, 175, 80, 0.1);
    border: 1px solid rgba(76, 175, 80, 0.3);
    color: #4caf50;
}

.device-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .device-actions {
        flex-direction: column;
    }
}
';

// Include common header
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navigation.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="account">
        <h2 style="margin-top: 0; color: var(--color-accent);">👤 Account Management</h2>

        <!-- Fallback Passphrase Section -->
        <section class="card account-section">
            <h3>🔑 Fallback Passphrase</h3>
            <p style="color: var(--color-text-secondary); margin-bottom: 1rem;">
                A fallback passphrase provides an alternative way to access your account if you lose access to all your passkeys.
            </p>
            
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div class="status-indicator <?= $hasFb ? 'status-set' : 'status-none' ?>">
                    <span><?= $hasFb ? '✅' : '⚠️' ?></span>
                    <span><?= $hasFb ? 'Passphrase Set' : 'No Passphrase Set' ?></span>
                </div>
                
                <?php if ($hasFb): ?>
                    <button id="remove-fallback" class="button" style="background: rgba(255, 100, 100, 0.2); border-color: rgba(255, 100, 100, 0.4);">
                        Remove Passphrase
                    </button>
                <?php else: ?>
                    <button id="show-fallback-form" class="button">Set Passphrase</button>
                <?php endif; ?>
            </div>
            
            <form id="fallback-form" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fb1">New Passphrase</label>
                        <input type="password" id="fb1" placeholder="Enter a secure passphrase..." minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="fb2">Confirm Passphrase</label>
                        <input type="password" id="fb2" placeholder="Repeat your passphrase..." minlength="6" required>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="button">Save Passphrase</button>
                    <button type="button" id="cancel-fallback" class="button" style="background: var(--color-surface-alt);">Cancel</button>
                </div>
                <div id="fb-feedback" class="feedback" style="display: none;"></div>
            </form>
        </section>

        <!-- Passkeys Section -->
        <section class="card account-section">
            <h3>🗝️ Your Devices / Passkeys</h3>
            <p style="color: var(--color-text-secondary); margin-bottom: 1rem;">
                Passkeys provide secure, passwordless authentication using your device's built-in security features.
            </p>
            
            <?php if (empty($pks)): ?>
                <div style="text-align: center; padding: 2rem; background: var(--color-surface-alt); border-radius: calc(var(--user-radius) * 0.5); margin-bottom: 1rem;">
                    <p style="color: var(--color-muted); margin: 0;">No passkeys registered yet</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Registered</th>
                                <th>Last Used</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pks as $pk): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($pk['device_name'] ?: 'Device') ?></strong>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($pk['created_at'])) ?></td>
                                    <td>
                                        <?= $pk['last_used'] ? date('M j, Y H:i', strtotime($pk['last_used'])) : 'Never' ?>
                                    </td>
                                    <td>
                                        <div class="device-actions">
                                            <button class="remove-passkey button" data-id="<?= $pk['id'] ?>" style="background: rgba(255, 100, 100, 0.2); border-color: rgba(255, 100, 100, 0.4);">
                                                Remove
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 1rem;">
                <button id="add-passkey" class="button">
                    <span style="margin-right: 0.5rem;">➕</span>Add New Device
                </button>
            </div>
            <div id="pk-feedback" class="feedback" style="display: none;"></div>
        </section>
    </div>

<?php
// Page-specific JavaScript
$additionalScripts = ['/players/js/account.js'];

// Include common footer
include __DIR__ . '/../includes/footer.php';
?>	
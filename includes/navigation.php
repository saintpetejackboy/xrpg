<?php
// /includes/navigation.php - Common navigation component
// Set $currentPage variable before including this file to highlight the active page

if (!isset($currentPage)) {
    $currentPage = '';
}

if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}



?>

<!-- Side Navigation -->
<nav class="side-nav">
    <button class="side-nav-toggle" title="Toggle menu">☰</button>
    <div class="side-nav-items">
        <a href="/players/" class="side-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
            <span class="side-nav-icon">🏠</span>
            <span class="side-nav-text">Dashboard</span>
        </a>
        <a href="/players/character.php" class="side-nav-item <?= $currentPage === 'character' ? 'active' : '' ?>" title="Character">
            <span class="side-nav-icon">⚔️</span>
            <span class="side-nav-text">Character</span>
        </a>
        <a href="/players/inventory.php" class="side-nav-item <?= $currentPage === 'inventory' ? 'active' : '' ?>" title="Inventory">
            <span class="side-nav-icon">🎒</span>
            <span class="side-nav-text">Inventory</span>
        </a>
        <a href="/players/dungeon.php" class="side-nav-item <?= $currentPage === 'dungeon' ? 'active' : '' ?>" title="Dungeons">
            <span class="side-nav-icon">🏰</span>
            <span class="side-nav-text">Dungeons</span>
        </a>
        <a href="/players/account.php" class="side-nav-item <?= $currentPage === 'account' ? 'active' : '' ?>" title="Account">
            <span class="side-nav-icon">👤</span>
            <span class="side-nav-text">Account</span>
        </a>
        <a href="/players/settings.php" class="side-nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>" title="Settings">
            <span class="side-nav-icon">⚙️</span>
            <span class="side-nav-text">Settings</span>
        </a>
    </div>
</nav>

<!-- Main Header -->
<header class="main-header">
    <div class="header-title"><?= isset($headerTitle) ? htmlspecialchars($headerTitle) : 'XRPG' ?></div>
    <div class="header-actions">
        <?php if (isset($headerActions)): ?>
            <?= $headerActions ?>
        <?php else: ?>
            <span style="margin-right: 1rem; color: var(--color-muted);">Welcome, <?= $username ?>!</span>

            <button class="button" onclick="XRPGPlayer.logout()" title="Logout">
                <span style="margin-right: 0.5rem;">🚪</span>Logout
            </button>
        <?php endif; ?>
    </div>
</header>

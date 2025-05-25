<?php
// /includes/footer.php - Common footer component

if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}
if (is_array($user) && isset($user['username'])) {
    $username = htmlspecialchars($user['username']);
} elseif (is_string($user)) {
    $username = htmlspecialchars($user);
} else {
    $username = 'Guest';
}

$footerInfo = $footerInfo ?? 'XRPG Dashboard';
?>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-links">
                <a href="/players/">Dashboard</a>
                <a href="/players/help.php">Help & Guide</a>
                <a href="/players/support.php">Support</a>
                <?php if (isset($additionalFooterLinks)): ?>
                    <?= $additionalFooterLinks ?>
                <?php endif; ?>
            </div>
            <div class="footer-info">
                <p><?= htmlspecialchars($footerInfo) ?> • Player: <?= $username ?></p>
                <p>&copy; 2025 XRPG. All rights reserved.</p>
            </div>
        </footer>
    </main>

    <!-- Include theme preferences for JavaScript -->
    <script>
        // Global theme preferences available to all pages
        window.userPreferences = <?= json_encode($preferences ?? []) ?>;
        <?php if (isset($additionalGlobalJS)): ?>
        <?= $additionalGlobalJS ?>
        <?php endif; ?>
    </script>
    
    <!-- Core theme system -->
    <script src="/assets/js/theme.js"></script>
    
    <!-- Common player utilities -->
    <script src="/players/js/player-common.js"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineJS)): ?>
        <script><?= $inlineJS ?></script>
    <?php endif; ?>
</body>
</html>

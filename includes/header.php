<?php
// /includes/header.php - Common header component for all pages
// This file should be included after setting $pageTitle and $preferences variables

if (!isset($pageTitle)) {
    $pageTitle = 'XRPG';
}

if (!isset($preferences)) {
    // Default preferences if not set
    $preferences = [
        'theme_mode' => 'dark',
        'accent_color' => '#5299e0',
        'accent_secondary' => '#81aaff',
        'border_radius' => 18,
        'shadow_intensity' => 0.36,
        'ui_opacity' => 0.96,
        'font_family' => 'sans'
    ];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($preferences['theme_mode']) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/ico/favicon-16x16.png">
    <link rel="shortcut icon" href="/assets/ico/favicon.ico">
    <meta name="theme-color" content="#ffffff">
    <style>
        :root {
            --user-accent: <?= htmlspecialchars($preferences['accent_color']) ?>;
            --user-accent2: <?= htmlspecialchars($preferences['accent_secondary']) ?>;
            --user-radius: <?= intval($preferences['border_radius']) ?>px;
            --user-shadow-intensity: <?= floatval($preferences['shadow_intensity']) ?>;
            --user-opacity: <?= floatval($preferences['ui_opacity']) ?>;
            --user-font: var(--font-<?= htmlspecialchars($preferences['font_family']) ?>);
        }
        
        <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
        <?php endif; ?>
    </style>
    <?php if (isset($additionalHead)): ?>
    <?= $additionalHead ?>
    <?php endif; ?>
</head>
<body class="authenticated">
    <!-- Fixed Theme Toggle -->
    <button id="theme-toggle" class="theme-toggle-fixed" title="Toggle light/dark mode">
        <?= $preferences['theme_mode'] === 'dark' ? '🌞' : '🌙' ?>
    </button>

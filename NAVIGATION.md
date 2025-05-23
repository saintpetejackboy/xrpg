# XRPG Navigation System Documentation

## Overview
The XRPG navigation system provides a collapsible side menu that adapts based on user authentication status. The menu uses emoji icons for a compact view and can expand to show full text labels.

## Structure

### HTML Structure
```html
<nav class="side-nav">
    <button class="side-nav-toggle" title="Toggle menu">☰</button>
    <div class="side-nav-items">
        <a href="#" class="side-nav-item" title="[Tooltip]">
            <span class="side-nav-icon">[Emoji]</span>
            <span class="side-nav-text">[Label]</span>
        </a>
    </div>
</nav>
```

### CSS Classes
- `.side-nav` - Main navigation container
- `.side-nav.expanded` - Expanded state showing text labels
- `.side-nav-toggle` - Hamburger menu toggle button
- `.side-nav-items` - Container for navigation links
- `.side-nav-item` - Individual navigation link
- `.side-nav-item.active` - Currently active page
- `.side-nav-icon` - Emoji icon container
- `.side-nav-text` - Text label (hidden when collapsed)

## User States

### Guest (Not Logged In)
```php
// Minimal navigation for guests
$guestNav = [
    ['icon' => '🏠', 'text' => 'Home', 'href' => '/', 'title' => 'Home'],
    ['icon' => '🔑', 'text' => 'Login', 'href' => '#', 'onclick' => 'openModal("auth-modal")', 'title' => 'Login'],
    ['icon' => '📖', 'text' => 'Guide', 'href' => '/guide', 'title' => 'Game Guide']
];
```

### Player (Logged In)
```php
// Full player navigation
$playerNav = [
    ['icon' => '🏠', 'text' => 'Home', 'href' => '/player', 'title' => 'Dashboard'],
    ['icon' => '👤', 'text' => 'Profile', 'href' => '/player/profile', 'title' => 'My Profile'],
    ['icon' => '🏰', 'text' => 'Dungeons', 'href' => '/player/dungeons', 'title' => 'Enter Dungeons'],
    ['icon' => '🛍️', 'text' => 'Shop', 'href' => '/player/shop', 'title' => 'Item Shop'],
    ['icon' => '⚔️', 'text' => 'Equipment', 'href' => '/player/equipment', 'title' => 'My Equipment'],
    ['icon' => '🏡', 'text' => 'House', 'href' => '/player/house', 'title' => 'My House'],
    ['icon' => '🏆', 'text' => 'Leaderboard', 'href' => '/leaderboard', 'title' => 'Rankings'],
    ['icon' => '📊', 'text' => 'Stats', 'href' => '/player/stats', 'title' => 'My Statistics'],
    ['icon' => '📖', 'text' => 'Guide', 'href' => '/guide', 'title' => 'Game Guide'],
    ['icon' => '🚪', 'text' => 'Logout', 'href' => '/logout', 'title' => 'Sign Out']
];
```

### Admin
```php
// Admin navigation with additional tools
$adminNav = [
    ['icon' => '🏠', 'text' => 'Dashboard', 'href' => '/admin', 'title' => 'Admin Dashboard'],
    ['icon' => '👥', 'text' => 'Users', 'href' => '/admin/users', 'title' => 'Manage Users'],
    ['icon' => '📊', 'text' => 'Analytics', 'href' => '/admin/analytics', 'title' => 'Site Analytics'],
    ['icon' => '⚙️', 'text' => 'Settings', 'href' => '/admin/settings', 'title' => 'System Settings'],
    ['icon' => '🛡️', 'text' => 'Security', 'href' => '/admin/security', 'title' => 'Security Settings'],
    ['icon' => '📝', 'text' => 'Content', 'href' => '/admin/content', 'title' => 'Manage Content'],
    ['icon' => '🎮', 'text' => 'Play', 'href' => '/player', 'title' => 'Switch to Player'],
    ['icon' => '🚪', 'text' => 'Logout', 'href' => '/logout', 'title' => 'Sign Out']
];
```

## Implementation Example

```php
<?php
// In your PHP page
function renderNavigation($userType = 'guest') {
    // Define navigation items based on user type
    switch($userType) {
        case 'admin':
            $navItems = $adminNav;
            break;
        case 'player':
            $navItems = $playerNav;
            break;
        default:
            $navItems = $guestNav;
    }
    
    // Get current page for active state
    $currentPage = $_SERVER['REQUEST_URI'];
    ?>
    <nav class="side-nav">
        <button class="side-nav-toggle" title="Toggle menu">☰</button>
        <div class="side-nav-items">
            <?php foreach($navItems as $item): ?>
                <a href="<?= $item['href'] ?>" 
                   class="side-nav-item <?= $currentPage === $item['href'] ? 'active' : '' ?>"
                   title="<?= $item['title'] ?>"
                   <?= isset($item['onclick']) ? 'onclick="'.$item['onclick'].'; return false;"' : '' ?>>
                    <span class="side-nav-icon"><?= $item['icon'] ?></span>
                    <span class="side-nav-text"><?= $item['text'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php
}
?>
```

## JavaScript Functions

The navigation state (expanded/collapsed) is persisted in localStorage:

```javascript
// Toggle navigation
function toggleNav() {
    const nav = document.querySelector('.side-nav');
    if (nav) {
        nav.classList.toggle('expanded');
        localStorage.setItem('nav_expanded', nav.classList.contains('expanded'));
    }
}

// Load saved state on page load
document.addEventListener('DOMContentLoaded', () => {
    const navExpanded = localStorage.getItem('nav_expanded') === 'true';
    if (navExpanded) {
        document.querySelector('.side-nav')?.classList.add('expanded');
    }
});
```

## Adding New Menu Items

To add a new menu item:

1. **Choose an appropriate emoji** that represents the feature
2. **Add to the appropriate array** (guest/player/admin)
3. **Ensure the href points to the correct route**
4. **Add a descriptive title attribute** for tooltips

Example:
```php
// Adding a guild feature for players
['icon' => '⚔️', 'text' => 'Guild', 'href' => '/player/guild', 'title' => 'My Guild']
```

## Responsive Behavior

- **Desktop**: Side navigation is always visible
- **Mobile**: Consider hiding the navigation by default and showing on toggle
- **Collapsed state**: Only shows emoji icons (4rem width)
- **Expanded state**: Shows full text labels (16rem width)

## Accessibility

- All links have `title` attributes for screen readers
- Focus states are clearly visible
- Keyboard navigation works properly
- ARIA attributes can be added as needed

## Styling Customization

The navigation inherits all theme variables:
- Background uses `var(--gradient-surface)`
- Hover states use `var(--color-surface-alt)`
- Active items show `var(--color-accent)`
- All transitions respect user preferences

## Best Practices

1. **Keep emoji choices consistent** - Use similar style emojis
2. **Limit menu items** - Too many items make navigation confusing
3. **Group related items** - Consider sub-sections for complex menus
4. **Test on mobile** - Ensure touch targets are large enough
5. **Provide tooltips** - Always include title attributes

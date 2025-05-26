# XRPG AI Contribution Guide

This document explains the XRPG project structure and provides rules for AI (or automated tools) to add new pages and features in a consistent, maintainable way.

---

## Project Structure Overview

```
xrpg/
├── admin/          # Admin tools and panel
├── api/            # API endpoints (AJAX/REST)
├── assets/
│   ├── css/        # All CSS stylesheets (theme, components, overrides)
│   ├── js/         # All global JavaScript (theme, passkey, etc.)
│   ├── ico/ img/   # Icons and images
├── auth/           # Authentication scripts (login, register, logout, passkey)
├── config/         # Configuration (db, environment)
├── db/             # Database schema SQL files
├── includes/       # Shared PHP components (header, footer, navigation)
├── pages/          # Public-facing pages (landing, demo, etc.)
├── players/        # Player area (dashboard, character, settings, etc.)
│   ├── js/         # Player-specific JS
├── thirdparty/     # Composer dependencies
├── utils/          # Utility scripts
├── index.php       # Main router (all requests go through here)
├── .env            # Environment config
├── .htaccess       # Apache rewrite rules
└── README.md       # Main project documentation
```

---

## How Routing Works

- All requests are routed through [`index.php`](index.php).
- The router checks authentication and user type, then serves the correct page:
    - **Unauthenticated:** [`pages/landing.php`](pages/landing.php)
    - **Player:** `/players/` pages (dashboard, character, etc.)
    - **Admin:** `/admin/` pages
    - **API:** `/api/` endpoints
    - **Auth:** `/auth/` endpoints

**To add a new page:**
- For public pages: add to [`pages/`](pages/)
- For player-only pages: add to [`players/`](players/)
- For admin-only pages: add to [`admin/`](admin/)

**Routing rules:**
- Use `.php` extension for all PHP pages.
- The router will automatically resolve `/players/foo` to `/players/foo.php` if the extension is omitted.
- For new API endpoints, add a PHP file to [`api/`](api/) and update the API routing switch in [`index.php`](index.php).

---

## Where to Put CSS and JS

- **Global CSS:** [`assets/css/`](assets/css/)
    - Use `theme.css` for theme variables and global styles.
    - Use `components.css` for UI components (buttons, cards, modals, etc.).
    - Use `overrides.css` for browser-specific fixes.
- **Page-specific CSS:** Inline in the page or add a new file in [`assets/css/`](assets/css/) and link it in the page `<head>`.
- **Global JS:** [`assets/js/`](assets/js/)
    - Use `theme.js` for theming, modals, and global UI logic.
    - Use `passkey.js` for authentication logic.
- **Player-specific JS:** [`players/js/`](players/js/)
    - For dashboard, character, or settings logic.
- **Admin-specific JS:** Add to `admin/` or `assets/js/` as needed.

---

## Navigation

- The main navigation is in [`includes/navigation.php`](includes/navigation.php).
    - Set the `$currentPage` variable before including to highlight the active page.
    - To add a new navigation link, edit this file and follow the existing structure.
- The navigation is included in player and admin pages via PHP `include`.

---

## Rules for Adding Pages

1. **File Placement**
    - Place new public pages in [`pages/`](pages/).
    - Place new player pages in [`players/`](players/).
    - Place new admin pages in [`admin/`](admin/).
2. **Naming**
    - Use lowercase, hyphen-separated filenames (e.g., `my-feature.php`).
3. **Structure**
    - Include [`includes/header.php`](includes/header.php) at the top and [`includes/footer.php`](includes/footer.php) at the bottom for consistent layout and theming.
    - Use the navigation include if the page is part of the main app.
4. **CSS/JS**
    - Use CSS variables for all colors, spacing, etc.
    - Add new CSS to `assets/css/components.css` or a new file if component-specific.
    - Add new JS to `assets/js/theme.js` (global) or `players/js/` (player area).
    - Link new CSS/JS in the page `<head>` or via the `$additionalScripts` variable.
5. **Accessibility**
    - Use semantic HTML and ARIA attributes as needed.
    - Ensure focus states and keyboard navigation.
6. **Theming**
    - Use only CSS variables defined in `variables.css` and `theme.css`.
    - Test with both light and dark modes.
7. **Responsiveness**
    - Use grid/flex utilities and media queries for mobile compatibility.
8. **Updates**
    - If adding a new feature, log it in `updates.log` using the CLI tool [`admin/tools/add-update.php`](admin/tools/add-update.php).

---

## Example: Adding a New Player Page

1. Create `players/my-feature.php`:
    ```php
    <?php
    $pageTitle = 'My Feature';
    $currentPage = 'my-feature';
    include '../includes/header.php';
    include '../includes/navigation.php';
    ?>
    <main>
        <!-- Page content here -->
    </main>
    <?php include '../includes/footer.php'; ?>
    ```

2. Add navigation link in [`includes/navigation.php`](includes/navigation.php):
    ```php
    <a href="/players/my-feature.php" class="side-nav-item <?= $currentPage === 'my-feature' ? 'active' : '' ?>">
        <span class="side-nav-icon">✨</span>
        <span class="side-nav-text">My Feature</span>
    </a>
    ```

3. Add any CSS to [`assets/css/components.css`](assets/css/components.css) or a new file.

4. Add any JS to [`players/js/`](players/js/) and include via `$additionalScripts` if needed.

---

## References

- [index.php](index.php) — Main router
- [includes/navigation.php](includes/navigation.php) — Navigation component
- [assets/css/theme.css](assets/css/theme.css) — Main theme CSS
- [assets/js/theme.js](assets/js/theme.js) — Main JS for theming and modals

---

**Follow these guidelines to keep the codebase clean, consistent, and maintainable!**
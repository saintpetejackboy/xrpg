# XRPG: The Customizable Web Adventure Game

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-Specify%20License-blue.svg)](LICENSE.md) XRPG is a dynamic, web-based role-playing game built with PHP and JavaScript. It features secure passkey-only authentication, an in-depth character creation and progression system, and a highly customizable user interface theme engine, allowing players to tailor their adventure.

## Table of Contents

1.  [Features](#features)
2.  [Technology Stack](#technology-stack)
3.  [Directory Structure Overview](#directory-structure-overview)
4.  [Prerequisites](#prerequisites)
5.  [Installation & Setup](#installation--setup)
6.  [Configuration](#configuration)
7.  [Usage](#usage)
8.  [Key Components Overview](#key-components-overview)
    * [Authentication System](#authentication-system)
    * [Theming Engine](#theming-engine)
    * [Character System](#character-system)
    * [Routing](#routing)
9.  [API Endpoints](#api-endpoints)
10. [Database Schema](#database-schema)
11. [Admin Tools](#admin-tools)
12. [Contributing](#contributing)
13. [License](#license)
14. [Further Development](#further-development--to-do)

## Features

* 🔐 **Secure Passkey Authentication:** Modern, passwordless login and registration using WebAuthn standards for enhanced security. Includes rate limiting and suspicious activity detection.
* 🎨 **Advanced UI Theming Engine:**
    * Light and Dark mode support.
    * User-configurable accent colors (primary and secondary).
    * Adjustable border radius, shadow intensity, and UI opacity.
    * Choice of multiple font families (Sans-serif, Monospace, Classic RPG, Display).
    * Theme preferences saved per user in the database (`localStorage` fallback for guests).
    * Live preview of theme changes in settings.
* ⚔️ **In-Depth Character Creation & Progression:**
    * Choose from multiple races (e.g., Human, Elf, Dwarf), each with unique stat modifiers.
    * Select a starting class (e.g., Fighter, Mage, Rogue) with distinct base stats and growth rates.
    * Pick a job (e.g., Merchant, Blacksmith, Scholar) offering economic and minor stat bonuses.
    * Support for advanced classes with prerequisites (e.g., Knight, Archmage).
    * Detailed stat system (Strength, Vitality, Agility, Intelligence, Wisdom, Luck) influenced by choices.
    * Character, class, and job leveling system with experience points.
* 🖥️ **Player Dashboard & Management:**
    * Centralized player dashboard (`/players/index.php`).
    * Detailed character information page (`/players/character.php`) with stat breakdown.
    * Ability to change class/job with a cooldown period.
* 📢 **Game World & Updates:**
    * API endpoint (`/api/updates.php`) to fetch latest game news and announcements.
    * CLI tool (`admin/tools/add-update.php`) for developers/AI to add new update entries.
* 📱 **Responsive Design:** UI components and layouts designed to adapt to various screen sizes.
* 🛠️ **Admin Utilities:** Basic tools for site/game management.

## Technology Stack

* **Backend:** PHP (Primarily PHP 8.1+ based on `composer.lock` dependencies)
* **Frontend:** HTML5, CSS3 (with CSS Variables for theming), JavaScript (ES6+)
* **Database:** MySQL / MariaDB
* **Web Server:** Apache (configured via `.htaccess` for routing and security)
* **Authentication:** WebAuthn (Passkeys) via `web-auth/webauthn-lib`
* **Dependency Management:** Composer (for PHP)
* **Environment Configuration:** `vlucas/phpdotenv`
* **Key Libraries:**
    * `web-auth/webauthn-lib`: Core library for passkey implementation.
    * `spomky-labs/cbor-php`, `web-auth/cose-lib`, `spomky-labs/pki-framework`: Dependencies for WebAuthn.
    * `psr/*`: Standard PHP interfaces (logging, HTTP client/factory, clock, event-dispatcher).
    * `symfony/*`: Polyfills (ctype, mbstring, php80, uuid) and UID components.

## Directory Structure Overview

```
xrpg/
├── admin/            # Admin-specific tools and (future) panel
│   └── tools/        # CLI tools for admin tasks (add-update.php, ban-user.php)
├── api/              # API endpoints (e.g., api/updates.php)
├── assets/           # Frontend assets (CSS, JavaScript, images/icons)
│   ├── css/          # Styling (theme.css, components.css, variables.css, etc.)
│   └── js/           # Client-side scripts (theme.js, passkey.js)
├── auth/             # Authentication logic (login.php, register.php, logout.php, RateLimiter.php)
├── config/           # Configuration files (db.php, environment.php)
├── db/               # Database schema (.sql files for users, stats, etc.)
├── docs/             # Project documentation files
├── pages/            # Public-facing pages (landing.php, demo-components.php)
├── players/          # Player-specific authenticated area (dashboard, character, settings)
├── thirdparty/       # Composer dependencies (managed by composer.json)
│   └── vendor/       # Installed Composer packages
├── utils/            # Utility scripts (e.g., setup-preferences.php)
├── .env.example      # Example environment file for local setup
├── .env              # Actual environment file (gitignored)
├── .gitignore        # Specifies intentionally untracked files
├── .htaccess         # Apache configuration for URL rewriting and security
├── .repomixignore    # Files to ignore for Repomix (if used)
├── index.php         # Main entry point and router for the application
├── README.md         # This file
└── site.webmanifest  # Web application manifest for PWA capabilities
```

## Prerequisites

Before you begin, ensure you have the following installed:
* PHP >= 8.1 (with extensions: `pdo_mysql`, `openssl`, `mbstring`, `json`, `ctype`, `iconv`)
* Web Server: Apache with `mod_rewrite` and `mod_headers` enabled is recommended.
* Database Server: MySQL or MariaDB.
* Composer: For managing PHP dependencies.
* Git: For cloning the repository.

## Installation & Setup

1.  **Clone the Repository:**
    ```bash
    git clone <your-repository-url> xrpg
    cd xrpg
    ```

2.  **Install PHP Dependencies:**
    This project manages dependencies locally within the `thirdparty/` directory.
    ```bash
    cd thirdparty
    composer install
    cd ..
    ```

3.  **Setup Database:**
    * Create a new database (e.g., `xrpg`) in your MySQL/MariaDB server.
    * Import the database schemas. You'll need to import both `db/xrpg.sql` (core tables) and `db/stats.sql` (character-related tables):
        ```bash
        mysql -u your_db_user -p your_db_name < db/xrpg.sql
        mysql -u your_db_user -p your_db_name < db/stats.sql
        ```
        Replace `your_db_user` and `your_db_name` with your actual database credentials and name.

4.  **Configure Environment Variables:**
    * Copy the example environment file:
        ```bash
        cp .env.example .env
        ```
    * Edit the `.env` file with your local development or production settings. Refer to the [Configuration](#configuration) section below for details on each variable.

5.  **Web Server Configuration:**
    * Configure your web server (e.g., Apache) to point its document root to the root directory of the `xrpg` project.
    * Ensure that `mod_rewrite` is enabled and `.htaccess` files are processed by your Apache configuration (typically `AllowOverride All` for the project directory).

6.  **File Permissions:**
    * Ensure your web server has the necessary permissions for directories if any runtime file writes are expected (e.g., log directories if not using standard PHP error logging). The `admin/tools/add-update.php` script writes to `updates.log` in the project root when run via CLI.

7.  **(Optional) Setup User Preferences for Existing Users:**
    If you have users from a previous setup without theme preferences, run the utility script:
    ```bash
    php utils/setup-preferences.php
    ```
    This can also be run via a browser with `?allow_web=1` (e.g., `http://localhost/xrpg/utils/setup-preferences.php?allow_web=1`) for testing, but **this web access should be disabled in production.**

## Configuration

The application uses a `.env` file in the project root for environment-specific settings. Copy `.env.example` to `.env` and update the values:

* `DB_HOST`: Database host (e.g., `localhost`, `127.0.0.1`).
* `DB_NAME`: Your database name (e.g., `xrpg`).
* `DB_USER`: Database username.
* `DB_PASS`: Database password.
* `DOMAIN_URL`: The full base URL of your application, including the subdirectory if applicable (e.g., `http://localhost/xrpg` or `https://yourgame.com`).
* `WEBAUTHN_ORIGIN`: The origin for WebAuthn (e.g., `http://localhost` or `https://yourgame.com`). This should **not** include subdirectories and must match `window.location.origin` in the browser.
* `RP_ID`: Relying Party ID for WebAuthn. This is typically your domain name (e.g., `localhost` for local development, or `yourgame.com` for production).
* `RP_NAME`: A human-readable name for your Relying Party (e.g., "XRPG Game").

## Usage

* **Accessing the Game:** Navigate to the `DOMAIN_URL` configured in your `.env` file.
* **Registration:** New users can create an account via the "Create Account" option on the landing page, using a username and a passkey (biometric, security key, etc.).
* **Login:** Existing users can log in using their registered passkey.
* **Player Area (`/players/`):** After successful login, players are directed to their dashboard.
    * If character creation is not complete, they will be redirected to `/players/character-creation.php`.
    * View character details at `/players/character.php`.
    * Customize UI theme and other settings at `/players/settings.php`.
* **Admin Area (`/admin/`):** Admin users (if implemented and logged in as such) will be routed to the admin panel.

## Key Components Overview

### Authentication System

* **Location:** `auth/`, `assets/js/passkey.js`
* **Description:** Implements passkey-only (WebAuthn) registration and login, providing a secure, passwordless experience.
* **Backend:** PHP scripts in `auth/` handle cryptographic challenges, credential storage in the `users` table, and verification using the `web-auth/webauthn-lib`.
* **Frontend:** `assets/js/passkey.js` manages browser interaction with the WebAuthn API and communicates with the backend endpoints.
* **Security:** Includes `RateLimiter.php` for robust protection against brute-force attacks and abuse. Session management is enhanced with IP and User-Agent hash checks.

### Theming Engine

* **Location:** `assets/css/`, `assets/js/theme.js`, `players/settings.php`
* **Description:** A powerful and flexible system allowing users to personalize the game's appearance.
* **Features:**
    * Dynamic light/dark mode.
    * User-selectable primary and secondary accent colors.
    * Adjustable border radius, shadow intensity, and overall UI opacity.
    * Multiple font family choices.
* **Implementation:** Uses CSS Variables extensively (`variables.css`, `theme.css`, `components.css`). `theme.js` applies these settings dynamically, persisting them in the `user_preferences` database table for authenticated users or `localStorage` for guests. The settings page (`players/settings.php`) provides controls and a live preview.

### Character System

* **Location:** `players/`, `db/stats.sql`
* **Description:** Manages character creation, stats, and progression.
* **Creation (`character-creation.php`):** A multi-step process:
    1.  **Race Selection:** Permanent choice impacting base stats (e.g., Human, Elf).
    2.  **Class Selection:** Defines combat role and stat bonuses (e.g., Fighter, Mage); changeable with a cooldown.
    3.  **Job Selection:** Provides economic/minor stat bonuses (e.g., Merchant, Blacksmith); changeable with a cooldown.
    4.  **Confirmation:** Finalizes choices and creates the character.
* **Stats & Progression:** Detailed stats (Strength, Vitality, etc.) are calculated from base values plus racial, class, and job modifiers. Characters level up through experience, and class/job levels also progress.
* **Backend:** `create-character.php` handles initial creation, and `apply-class-job-change.php` manages subsequent modifications. Data is stored in `user_characters` and `user_stats` tables.

### Routing

* **Location:** `index.php`, `.htaccess`
* **Description:** `index.php` serves as the central front controller. All non-static asset requests are rewritten by `.htaccess` to `index.php`.
* **Logic:** Routes requests based on the URI and user authentication status:
    * Unauthenticated users are generally shown `pages/landing.php`.
    * Authenticated 'player' users are routed to the `/players/` section.
    * Authenticated 'admin' users are routed to the `/admin/` section.
    * API calls to `/api/` are handled directly.
    * Auth actions (`/auth/`) are routed to their respective scripts.

## API Endpoints

* **`GET /api/updates.php`**:
    * Fetches the latest game updates or announcements.
    * **Query Parameters:**
        * `limit` (optional): Number of updates to return. Default: 5, Max: 20.
    * **Response:** JSON array of update objects, each containing:
        * `id`: Update ID.
        * `emoji`: Emoji for the update.
        * `message`: Update text.
        * `timestamp`: Timestamp of the update.
        * `type`: Type of update (e.g., 'feature', 'security', 'announcement').
        * `timeAgo`: Relative time string (e.g., "Just now", "2h ago").

## Database Schema

* **Location:** `db/xrpg.sql`, `db/stats.sql`
* **Overview:** The database stores all critical game data.
    * `xrpg.sql`: Contains core tables such as:
        * `users`: User accounts, passkey credentials.
        * `auth_log`: Authentication attempt logs.
        * `rate_limits`, `creation_limits`, `security_events`: For security and anti-abuse.
        * `user_preferences`: User-specific theme settings.
    * `stats.sql`: Contains character and game progression tables:
        * `user_stats`: Detailed RPG stats for characters (level, HP, STR, VIT, etc.).
        * `races`, `classes`, `jobs`: Definitions and attributes for character choices.
        * `user_characters`: Links users to their chosen race, class, and job.
        * `prerequisites`: Defines requirements for unlocking advanced classes/jobs.
* **Setup:** Both SQL files must be imported into your database during installation.

## Admin Tools

* **Location:** `admin/tools/`
* **`add-update.php`**:
    * **Type:** Command-Line Interface (CLI) tool.
    * **Usage:** `php admin/tools/add-update.php "Your update message here"`
    * **Functionality:** Adds a new entry to the `updates.log` file (which is then read by `assets/js/theme.js` for the landing page updates).
* **`ban-user.php`**:
    * Currently a placeholder file containing only "Test". Intended for future user banning functionality.
* **`admin-panel.php`** (Inferred):
    * The main entry point for admin users, as referenced in `index.php`. The actual content of this panel is not provided in the file list.

## Contributing

Contributions are welcome! You can also fork and steal this basics!

## License

This project is licensed under the NO LICENSE License - DO NOT see the `LICENSE.md` file for details, because it does not exist!

## Further Development / To-Do

This section outlines potential areas for future enhancements based on the current project state:

* **Core Gameplay:** Implement actual game mechanics such as combat, dungeons (`players/dungeon.php`), inventory management (`players/inventory.php`), guilds (`players/guild.php`), and world exploration (`players/map.php`).
* **Admin Panel:** Fully develop the `admin/admin-panel.php` with user management, content management, and game monitoring tools.
* **`ban-user.php`:** Implement the user banning functionality.
* **`change-class-job.php`:** Complete and test the UI and backend for changing class/job after the cooldown. The file `players/change-class-job.php` appears incomplete.
* **Updates System:** Transition `api/updates.php` from its in-memory array to fetch updates from the database (potentially populated by `admin/tools/add-update.php` or a new admin interface).
* **Help & Support Pages:** Create content for `players/help.php` and `players/support.php`.
* **Error Handling & Logging:** Enhance application-wide error handling and logging for better diagnostics.
* **Security Hardening:** Conduct security audits and implement further hardening measures as features are added.
* **Automated Testing:** Introduce unit and integration tests for backend and frontend components.
* **Internationalization (i18n) / Localization (l10n):** Plan for multi-language support if intended.

---


# XRPG Project Structure & Environment Notes

## Hosting, Localhost, and Virtual Host

- On **local development**, your web server/vhost points **directly to `/var/www/xrpg`**. So `http://localhost/` serves as the root of the XRPG project (e.g., `localhost/index.php`, `localhost/pages/landing.php`, etc).
- **In production** (e.g., `xrpg.win`), ensure your domain root points to this same directory so all routing and asset paths work the same way.
- Some documentation or scripts may show `/xrpg/` as a base folder. **REMOVE this prefix when using your vhost as described above**.

---

## Core Architectural Decisions

- `index.php` is the central router. It:
   - Checks if user is logged in (session/cookie)
   - Sends unauthenticated users to the landing page (`pages/landing.php`)
   - Sends authenticated users to either their dashboard/profile, or admin panel
- All endpoints (pages, assets, etc.) are assumed relative to `/` unless otherwise stated.
- **All environment/configuration values** (hostnames, WebAuthn origins, etc) are defined in `/config/environment.php` for easy migration between local/production.

---

## Style and UI System

- Sitewide theming (dark/light, accent, roundness, shadow) is driven by CSS variables in `/assets/theme.css` and persisted locally or from user profile settings.
- Theme/appearance settings must be available on every page for a consistent, player-friendly experience.

---

_Written May 2025. Update this file whenever project assumptions or root folder setup changes!_


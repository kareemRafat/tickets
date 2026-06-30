# CRV Ticket System — Agent Guide

## Tech Stack
- **Backend**: PHP Native (no framework), MySQL via PDO
- **Frontend**: Tailwind CSS v3, Flowbite v4, vanilla JS
- **DB name**: `tickets` (charset utf8mb4, collation utf8mb4_unicode_ci)

## CSS Build (Tailwind CLI)
```sh
npm run dev    # watch mode, output: assets/css/styles.css
npm run build  # minified production build
```
Input: `assets/css/input.css` (just `@tailwind` directives).

## Three Sections, Three Routes
| Section | Route | Auth guard |
|---------|-------|------------|
| Admin   | `/admin/` | `require_admin()` |
| Support | `/support/` | `require_employee()` |
| Student | `/students/` | `require_student()` |

All guards in `middleware/auth.php`. Student auth: enter 14-digit national ID → verified against `all_students` table → session created.

## File Structure Patterns
- **Entrypoints**: direct `.php` files per section (no router/front controller)
- **Bootstrap**: `bootstrap.php` (includes config, database, security, auth, rate_limiter — all pages require it)
- **Shared config**: `config/config.php` (session setup, constants, CSRF token)
- **Shared DB**: `config/database.php` (`getDBConnection()` singleton via PDO)
- **Shared includes**: `includes/header.php`, `sidebar.php`, `footer.php`
- **Security**: `helpers/security.php` — `xss_clean()`, `generate_csrf_token()`, `verify_csrf_token()`, password helpers
- **Audit**: `helpers/audit.php` — `log_audit_action()` (required separately on CRUD pages only)
- **Rate limiting**: `middleware/rate_limiter.php` — `is_login_allowed()` (5 failures / 15 min), `log_login_attempt()`
- Each page calls `set_security_headers()` from `rate_limiter.php` at the top

## Every Page Pattern
1. `require_once __DIR__ . '/../bootstrap.php';` (or `'/bootstrap.php'` from root)
2. On CRUD pages, additionally `require_once __DIR__ . '/../helpers/audit.php';`
3. Call `set_security_headers()`
4. Call the relevant `require_*()` guard
5. Set `$pageTitle`
6. `require_once` header + sidebar
7. HTML content
8. `require_once` footer

## Coding Conventions
- **DB**: PDO prepared statements with named params (`:param`), `FETCH_ASSOC` mode
- **CSRF**: hidden `<input>` with `generate_csrf_token()`, verify with `verify_csrf_token()` on POST
- **XSS**: `xss_clean()` on all user input (wraps `htmlspecialchars`)
- **Passwords**: `security_hash_password()` / `security_verify_password()` wrappers around `password_hash`
- **Sessions**: `session_regenerate_id(true)` after login, CSRF token regenerated
- **Audit logging**: `log_audit_action($action, $table_name, $record_id, $old_values, $new_values)` on all CRUD
- **Session flash messages**: `$_SESSION['success']` / `$_SESSION['error']` — displayed + unset in footer
- **Dark mode**: `localStorage` key `color-theme`, toggled via Flowbite button, stored as `dark` class on `<html>`

## Database Schema (from `database/schema.sql`)
- **Core tables**: `branches`, `employees` (role: admin/employee), `categories` (with `type`: student/support), `all_students`, `support_tickets`, `support_ticket_replies`, `student_tickets`, `student_ticket_replies`, `audit_logs`, `login_attempts`, `system_settings`
- **Ticket statuses**: `open`, `in_progress`, `closed`
- **Ticket priorities**: `low`, `medium`, `high`
- **Ticket number format**: `CRV-` + random 6 digits (unique)
- **Seed data**: 3 branches (Cairo/Giza/Alexandria), 1 admin (`admin` / `Admin@123456`), 8 categories (4 support + 4 student), 3 test students

## Default Admin
| Username | Password |
|----------|----------|
| `admin` | `Admin@123456` |

## Arabic RTL Only
- All user-facing text **must** be Arabic. English is never used in the UI.
- `<html lang="ar" dir="rtl">` — sidebar right-aligned (`right-0`, `translate-x-full`, `sm:mr-64`)
- Cairo Google Font
- All forms, alerts, tables, nav, placeholders, validation messages in Arabic

## UI Components — Flowbite Only
- Every UI component (tables, toasts, modals, dropdowns, badges, buttons, cards, navbars, sidebars, pagination, datepickers) must use Flowbite v4 markup and JS.
- Flowbite JS loaded via `<script src="<?php echo BASE_URL; ?>node_modules/flowbite/dist/flowbite.min.js">` in `includes/footer.php`.
- Dark mode toggle uses Flowbite's `data-drawer-toggle`, `data-dropdown-toggle` etc.
- Do **not** write custom CSS for layout or interactive components; use Tailwind utility classes + Flowbite data attributes.

## Key File Locations
- Schema: `database/schema.sql` (run against MySQL to bootstrap)
- Config: `config/config.php`, `config/database.php`
- Helpers: `helpers/security.php`, `helpers/audit.php`
- Middleware: `middleware/auth.php`, `middleware/rate_limiter.php`
- PRD: `prd.md` (detailed product requirements)

## Communication
- All summaries, explanations, Code comments, and responses to the user **must** be in English.
-  variables, and user-facing UI text remain in Arabic as specified above.
- When the user asks "what did we do so far", provide a summary in English.

## Verification Flow
1. Run `database/schema.sql` against MySQL
2. `npm install && npm run dev` for CSS
3. Serve via any PHP-enabled web server pointing at project root

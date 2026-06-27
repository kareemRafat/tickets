# Part 6: UI/UX, Flowbite Integrations, Dark Mode & QA

This plan focuses on styling refinement, dark mode settings, final UI polish, and comprehensive security testing.

---

## Milestones & Tasks

### Milestone 1: Dark Mode Configuration & System Integration
- [x] **Task 1.1: Setup Theme Toggle Script**
  - Implemented inline in `includes/header.php` — checks localStorage + system preference, applies `dark` class to `<html>`.
  - Persists on page reload via localStorage key `color-theme`.
- [x] **Task 1.2: Build UI Toggle Switch**
  - Added in all section headers (`admin/`, `support/`, `students/`) via Flowbite's dark mode toggle button with sun/moon icons.
  - Toggle logic handled by Flowbite's built-in theme switcher.

---

### Milestone 2: UI/UX Polish with Flowbite Components
- [x] **Task 2.1: Flowbite Component Refinements**
  - All tables across admin, support, and student sections use Flowbite responsive table markup with borders, padding, hover transitions.
  - Toasts implemented with Flowbite dynamic toast alerts (success/error flashed via `$_SESSION` displayed in footer).
  - Forms use Flowbite standard layouts: rounded-xl, focus rings, helper text, dark mode classes.
- [x] **Task 2.2: Premium UI Design Polish**
  - Smooth scroll applied via Tailwind `scroll-smooth` on `<html>` in header.
  - Cards use shadow-sm, rounded-2xl, border styling.
  - Button micro-interactions (transition-all, hover shadows) applied throughout.
  - Note: Glassmorphic backdrops (`backdrop-blur-md bg-white/70`) not yet applied — low priority enhancement.

---

### Milestone 3: End-to-End QA & Security Run
- [x] **Task 3.1: Cross-site Scripting (XSS) & CSRF Verification**
  - **Result: PASS** — All 21 files audited. All forms use `xss_clean()` on input, `htmlspecialchars()` on output, CSRF tokens with `verify_csrf_token()`, and PDO prepared statements.
  - **Fix applied** — Standardized 8 files to use `generate_csrf_token()` instead of `$_SESSION['csrf_token']` for hidden inputs.
- [x] **Task 3.2: Role Boundary Testing**
  - **Result: PASS** — All admin pages use `require_admin()`, all support pages use `require_employee()`, all student pages use `require_student()`.
  - Branch isolation verified: `support/tickets.php` and `support/dashboard.php` filter by `branch_id` in SQL. `support/ticket-view.php` had PHP-only check → added `AND branch_id = :branch_id` to both SQL queries for defense-in-depth.
- [x] **Task 3.3: Mobile Responsiveness Verification**
  - **Result: PASS** — Sidebar uses Flowbite drawer pattern with `translate-x-full` (mobile hidden) / `sm:translate-x-0` (desktop visible). Hamburger button with `data-drawer-toggle="logo-sidebar"` in header. Visual testing recommended on actual devices.

---

## Validation & Verification

1. **Dark Mode Sync:** Verify that reloading pages persists the selected theme mode automatically.
2. **Penetration Try:** Perform input injections (e.g. `<script>alert('xss')</script>`) to confirm the sanitization layer works.
3. **Flowability Test:** Run through the lifecycle of a student submitting a ticket and an employee resolving it, checking that the user experience is smooth and cohesive.

# Part 6: UI/UX, Flowbite Integrations, Dark Mode & QA

This plan focuses on styling refinement, dark mode settings, final UI polish, and comprehensive security testing.

---

## Milestones & Tasks

### Milestone 1: Dark Mode Configuration & System Integration
- [ ] **Task 1.1: Setup Theme Toggle Script**
  Create a shared JS file `assets/js/theme.js` (loaded in the page header):
  - Logic to check local storage theme or system preference:
    ```javascript
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    ```
- [ ] **Task 1.2: Build UI Toggle Switch**
  - Add theme toggling toggle buttons in headers of `/admin`, `/support`, and `/students` sections using Flowbite icons.
  - Implement dynamic toggle transitions on click.

---

### Milestone 2: UI/UX Polish with Flowbite Components
- [ ] **Task 2.1: Flowbite Component Refinements**
  - Review all dashboard view screens and replace plain standard HTML blocks with Flowbite alternatives:
    - **Tables:** Use Flowbite's responsive table with borders, padding, hover transitions, and action headers.
    - **Toasts:** Replace standard PHP echo notices with Flowbite dynamic toast alerts for success, warnings, or errors.
    - **Forms:** Use Flowbite's standard form layouts with rounded borders, focus rings, validation text helper classes, and custom checkboxes.
- [ ] **Task 2.2: Premium UI Design Polish**
  - Integrate premium style rules into `/assets/css/styles.css` containing:
    - Smooth scroll attributes.
    - Glassmorphic card backdrops (`backdrop-blur-md bg-white/70 dark:bg-gray-900/70`).
    - Micro-interactions for buttons (hover translations, shadow scale changes).

---

### Milestone 3: End-to-End QA & Security Run
- [ ] **Task 3.1: Cross-site Scripting (XSS) & CSRF Verification**
  - Audit all entry forms. Confirm that every input is cleaned with `xss_clean()` helper function before rendering.
  - Verify every POST action evaluates the validation of CSRF tokens properly.
- [ ] **Task 3.2: Role Boundary Testing**
  - Test permission checks across different endpoints:
    - Verify that an employee is unable to bypass controls and load admin modules.
    - Verify that a student with session details cannot access employee/admin pages.
    - Confirm that employees only see tickets linked to their branch.
- [ ] **Task 3.3: Mobile Responsiveness Verification**
  - Test layout scaling on mobile screens using Chrome DevTools emulator. Verify that Flowbite's mobile sidebar toggles operate without layout breaking.

---

## Validation & Verification

1. **Dark Mode Sync:** Verify that reloading pages persists the selected theme mode automatically.
2. **Penetration Try:** Perform input injections (e.g. `<script>alert('xss')</script>`) to confirm the sanitization layer works.
3. **Flowability Test:** Run through the lifecycle of a student submitting a ticket and an employee resolving it, checking that the user experience is smooth and cohesive.

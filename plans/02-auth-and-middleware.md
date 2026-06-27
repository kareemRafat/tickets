# Part 2: Security Middleware & Authentication System

This plan details the implementation of employee and admin authentication, student verification based on National ID, and security layers.

---

## Milestones & Tasks

### Milestone 1: Security Helpers & Middleware
- [ ] **Task 1.1: Create Security Utilities Manager**
  Create [helpers/security.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/helpers/security.php):
  - `generate_csrf_token()`: Generate cryptographically secure token.
  - `verify_csrf_token($token)`: Compare token inside session.
  - `xss_clean($data)`: Recursively sanitize inputs to prevent XSS (utilizing `htmlspecialchars`).
  - Password verify/hash functions.
- [ ] **Task 1.2: Build Core Authentication Gatekeeper**
  Create [middleware/auth.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/middleware/auth.php):
  - `require_admin()`: Verifies if the authenticated session belongs to an admin. Redirects to `/admin/login.php` on failure.
  - `require_employee()`: Verifies if the session belongs to a valid employee. Redirects to `/support/login.php` on failure.
  - `require_student()`: Verifies if session has valid National ID registration. Redirects to `/students/index.php` on failure.
- [ ] **Task 1.3: Rate Limiting & Secure Headers**
  Create [middleware/rate_limiter.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/middleware/rate_limiter.php):
  - Implement request limits on authentication portals based on IP address to prevent brute-force attempts.
  - Set security headers (X-Frame-Options, X-Content-Type-Options, Content-Security-Policy).

---

### Milestone 2: Employee & Admin Authentication
- [ ] **Task 2.1: Unified or Separate Login Portals**
  - Create `/support/login.php` for Employee login.
  - Create `/admin/login.php` (or redirect `/admin` to unified portal) for Admin login.
  - Use Flowbite cards, floating labels inputs, and validation states for form layout.
- [ ] **Task 2.2: Login Controller Logic**
  - Implement secure login authentication querying `employees` or `admins`.
  - Perform session regeneration via `session_regenerate_id(true)` to prevent session fixation.
  - Set secure session cookies properties.

---

### Milestone 3: Student verification flow (National ID validation)
- [ ] **Task 3.1: Student Gatekeeper Screen**
  Create [students/index.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/students/index.php):
  - Entry form for National ID with strict pattern matching.
  - UI styled with a high-fidelity Flowbite authentication box.
- [ ] **Task 3.2: Verify National ID Logic**
  - Query `all_students` table for matching ID.
  - If match is found:
    - Create session storing Student Name, National ID, and their Branch ID.
    - Redirect to `/students/dashboard.php`.
  - If match is not found:
    - Return error indicating invalid National ID registration.

---

## Validation & Verification

1. **CSRF Enforcement:** Attempt to POST forms without a CSRF token to verify it returns a 403 Forbidden status.
2. **Session Hijacking Safeguard:** Verify session identifier (`PHPSESSID`) changes upon successful login.
3. **Student ID Validity Check:** Test entering a valid and an invalid National ID to confirm access redirection limits.

# Part 3: Admin Section `/admin`

This plan covers administrative features including branches, employee management, ticket classification categories, system configuration, and audit logs.

---

## Milestones & Tasks

### Milestone 1: Admin Layout & Dashboard Landing Page
- [ ] **Task 1.1: Build Admin Dashboard Template**
  Create `/admin/dashboard.php`:
  - Utilize Flowbite Sidebar layout with toggles for collapsible menus.
  - Integrate stat cards for:
    - Total tickets across all branches.
    - Total employees registered.
    - Active tickets count.
  - Implement a list of "Recent System Activity" and "Audit Log Snapshots" using Flowbite timeline UI components.

---

### Milestone 2: Branch & Employee CRUD Management
- [ ] **Task 2.1: Branch CRUD Panels**
  Create `/admin/branches.php`:
  - Table displaying all branches.
  - "Add Branch" form inside a Flowbite Modal block.
  - Edit/Delete functionality with modal warning overlays.
- [ ] **Task 2.2: Employee CRUD Panels**
  Create `/admin/employees.php`:
  - Interactive table displaying Employee Name, Email, assigned Branch (joined from `branches` table), status, and action controls.
  - Create `/admin/employee-add.php` and `/admin/employee-edit.php` forms using Flowbite dropdown selectors for branch selection.
  - Securely hash employee passwords using PHP's `password_hash()` with `PASSWORD_DEFAULT`.

---

### Milestone 3: Category Management & Audit Logs
- [ ] **Task 3.1: Categories CRUD (Support vs Student)**
  Create `/admin/categories.php`:
  - Admin controls categories.
  - Table displaying categories along with badges specifying type: `support` (Employee) vs `student` (Student).
  - Add/Edit modals for creating category designations.
- [ ] **Task 3.2: Audit Log Viewer**
  Create `/admin/logs.php`:
  - Read actions from `audit_logs`.
  - Display log date, user context, action performed, old values vs new values, and remote IP address.
  - Add filtering options by date range, action type, and user ID.

---

## Validation & Verification

1. **Role Access Control:** Attempt to access `/admin/dashboard.php` as an employee or student to confirm immediate redirect.
2. **Database Constraints check:** Attempt to delete a branch containing employees to ensure foreign key constraint triggers alerts instead of crashing.
3. **Audit Log verification:** Perform an action (e.g. edit an employee's status) and check `/admin/logs.php` to verify the audit log row matches the operation details.

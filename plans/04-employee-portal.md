# Part 4: Employee Section `/support`

This plan covers the Employee support portal where support staff create internal tickets, handle customer service issues, and interact with student tickets for their branch.

---

## Milestones & Tasks

### Milestone 1: Employee Portal & Dashboard Setup
- [ ] **Task 1.1: Build Employee Layout Dashboard**
  Create `/support/dashboard.php`:
  - Main landing layout restricting data viewing to employee's assigned `branch_id`.
  - Visual summary cards powered by Flowbite:
    - Branch Open Tickets
    - Branch In Progress Tickets
    - Branch Closed Tickets
  - Latest Tickets section showing recently added tickets.

---

### Milestone 2: Internal Ticket Management & Search/Filters
- [ ] **Task 2.1: Internal Ticket Creator**
  Create `/support/ticket-create.php`:
  - Form fields: Subject, Category (filtered by type='support'), Priority (Low, Medium, High), Description.
  - Submission logic:
    - Generate a unique, random 6-digit number formatted as `CRV-[6-digit]` (e.g. `CRV-834729`).
    - Capture Employee ID, Branch ID, Date.
    - Validate fields and handle database insertion.
- [ ] **Task 2.2: Ticket Listing Directory**
  Create `/support/tickets.php`:
  - Responsive Flowbite Table containing:
    - Ticket Number, Subject, Category, Priority, Status, Branch, Created By, Last Reply By, Last Reply Date, Created Date.
  - Implement sorting to display the latest replied tickets first.
  - Add filtering panels (Flowbite dropdowns) for Status, Category, Priority, and Date Range.
  - Implement a search bar query filtering by Ticket Number, Student Name, National ID, or Phone Number.
  - Employee can view both Internal Tickets AND Student Tickets related to their branch (with separate tabs or flag toggles).

---

### Milestone 3: Ticket View, Replies & Workflows
- [ ] **Task 3.1: Detailed Ticket Hub**
  Create `/support/ticket-view.php`:
  - Display full ticket details, priority badges, and status indicator.
  - Form to submit replies.
  - Dropdown controls to update the Ticket Status (`Open`, `In Progress`, `Closed`).
  - Dropdown controller to update the Ticket Category.
  - Workflow to allow reopening closed tickets.
- [ ] **Task 3.2: Thread System & Logging**
  - Display replies in chronological timeline order (Flowbite Timeline Component).
  - Insert records to replies tables (`employee_ticket_replies` or `student_ticket_replies`).
  - Write transaction audits to `audit_logs` table upon category or status changes.

---

## Validation & Verification

1. **Branch Separation Boundary:** Log in as Employee A (Branch: Cairo) and try to access details of a ticket from Branch B (Alexandria) via URL manipulation; verify that access is blocked and returns a 403 error.
2. **Ticket Generation Check:** Create multiple tickets to verify that ticket numbers are always random 6-digit values and never conflict.
3. **Reply & Status Updates:** Send a reply and change the ticket status to 'Closed'; reload to verify the status badges update and that it is logged in the audit trail.

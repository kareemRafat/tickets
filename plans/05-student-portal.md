# Part 5: Student Section `/students`

This plan covers the Student portal layout including ticket submission, tracking, and view-only ticket replies.

---

## Milestones & Tasks

### Milestone 1: Verification Screen & Student Dashboard
- [x] **Task 1.1: Verification Gate**
  - Verify that students must enter their National ID on the portal index.
  - Set student variables in the session.
- [x] **Task 1.2: Student Portal Dashboard**
  Create `/students/dashboard.php`:
  - Provide a landing page with two prominent cards/buttons:
    1. **Create New Ticket / Complaint**
    2. **Track Existing Tickets**
  - Show a list of their current active tickets on the dashboard summary panel.

---

### Milestone 2: Student Ticket Submission
- [x] **Task 2.1: Student Create Ticket Page**
  Create `/students/ticket-create.php`:
  - Form fields: Subject, Category (filtered by type='student'), Phone Number, Priority, Description.
  - Automatically load from session:
    - Student Name (fetched from `all_students`)
    - National ID
    - Branch ID (assigned to student in `all_students`)
  - Submission logic:
    - Generate unique random 6-digit ticket number `CRV-[6-digit]`.
    - Create ticket row in `student_tickets`.
    - Log ticket creation.
    - Redirect to tracking screen.

---

### Milestone 3: Student Ticket Tracking & Detail View
- [x] **Task 3.1: Tracking Dashboard**
  Create `/students/track.php`:
  - Displays a clean table of all tickets submitted by this student's National ID.
  - Add search filter by ticket number.
  - Add quick status filter tabs (All, Open, In Progress, Closed).
- [x] **Task 3.2: View Ticket Details**
  Create `/students/ticket-view.php`:
  - Display the student's ticket details and status timeline.
  - Display replies thread from admin/employee in chronological order using a Flowbite Timeline component.
  - Ensure the student **cannot reply** and has no inputs on this screen (Read-Only access).

---

## Validation & Verification

1. **Isolation Checks:** Log in with Student ID A. Try to load Student ID B's ticket ID in the browser URL query; verify that the system blocks the request and redirects them to the tracking dashboard.
2. **Read-Only Verification:** Confirm that there are no form elements or script endpoints that allow students to post reply content to `student_ticket_replies`.
3. **Database Population:** Submit a ticket and verify it is successfully associated with the correct branch ID from the student's database record.

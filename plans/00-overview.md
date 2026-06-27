# CRV Ticket System — Implementation Plans Overview

This directory contains the step-by-step implementation plans for building the **CRV Ticket System**, a customer service and student ticket management platform.

The system will be built using a secure PHP Native backend, JavaScript frontend interactions, MySQL for data persistence, and **TailwindCSS v3 with Flowbite** for a highly polished, responsive interface supporting both Light and Dark modes.

---

## 📅 Chronological Execution Order

Click on each plan file to view detailed tasks and milestones:

| Step | Plan File | Focus | Milestones | Key Flowbite Components |
| :--- | :--- | :--- | :--- | :--- |
| **00** | [00-overview.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/00-overview.md) | Plans Index & Architectural Guidance | - Overview | - None |
| **01** | [01-setup-and-db.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/01-setup-and-db.md) | Project Setup, Database Schema & Flowbite Integration | - Project Init & Assets Setup<br>- DB Schema Design & Seed Data<br>- DB Connection & Config Modules | - Tailwind CLI / Flowbite npm build configuration |
| **02** | [02-auth-and-middleware.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/02-auth-and-middleware.md) | Security Middleware & Authentication System | - Security Helpers & Core Protections<br>- Employee / Admin Auth System<br>- Student ID Verification Session Flow | - Login forms, Alert banners, Toast messages |
| **03** | [03-admin-dashboard.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/03-admin-dashboard.md) | Admin Panel `/admin` | - Admin Layout & Dashboard Widgets<br>- Branch & Employee CRUD Management<br>- Categories Management & Audit Log Viewer | - Admin Sidebar, Tables, Modal dialogues, Cards |
| **04** | [04-employee-portal.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/04-employee-portal.md) | Employee support dashboard & Ticket operations `/support` | - Employee Portal Layout & Dashboard<br>- Internal Ticket Creation & Ticket Directory<br>- Ticket Reply System & Status Workflows | - Sidebar, Tables, Badge statuses, Form components |
| **05** | [05-student-portal.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/05-student-portal.md) | Student Ticket Portal `/students` | - Verification Check & Dashboard<br>- Student Ticket Creation Portal<br>- Ticket Tracking Dashboard & Details View | - Carousel details, Steppers, Timeline view, Alerts |
| **06** | [06-ui-ux-flowbite-darkmode.md](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/plans/06-ui-ux-flowbite-darkmode.md) | Fine-tuning UI, Dark Mode Toggle & QA Audit | - Flowbite UI Polish & Transitions<br>- Dark Mode LocalStorage/Cookie Sync<br>- Code Security Verification & QA Testing | - Theme toggle buttons, Responsive menus, Toasts |

---

## 🎨 Visual Concept & Core Styles (Flowbite)

We will utilize Flowbite's components to achieve a premium UI aesthetic (similar to Zendesk). Specifically, we will use:
- **Navigation/Sidebar:** A fixed sidebar collapsible on mobile devices.
- **Data Tables:** Searchable and filterable tables with rounded borders, hover effects, and clean typography.
- **Modals:** For confirmations (e.g., closing tickets, deleting users, or category edits).
- **Badges:** Using Flowbite's status badges (`indigo` / `blue` for Open, `yellow` / `orange` for In Progress, and `green` / `gray` for Closed).
- **Forms:** Float labels or clean, modern input states, toggle switches for theme changes, and secure CSRF hidden inputs.

---

## 🛠️ Architectural Guidelines

1. **Prepared Statements:** ALWAYS use PDO with parameter binding for queries. Do NOT inject variables into SQL strings.
2. **HTML Sanitization:** Avoid raw output of user content; use `htmlspecialchars()` or a purifier helper function.
3. **Session Safety:** Use session regeneration upon login. Validate CSRF tokens on all POST requests.
4. **Separation of Concerns:** Keep PHP controller logic separate from templates where possible.

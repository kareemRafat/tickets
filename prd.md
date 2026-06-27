# PRD — CRV Ticket System

## Customer Service & Student Ticket Management System

---

# 1. Project Overview

## Project Name

CRV Ticket System

## Purpose

A web-based internal ticket management system built using:

* PHP Native
* JavaScript
* TailwindCSS v3
* MySQL

The system is divided into two separate sections:

1. Employee / Customer Service Ticket System
2. Student Ticket System

The system allows:

* Employees to create and manage internal tickets
* Students to submit complaints/tickets and track their status
* Admins to manage employees, branches, categories, and logs

---

# 2. Technologies

| Technology         | Usage                 |
| ------------------ | --------------------- |
| PHP Native         | Backend               |
| JavaScript         | Frontend interactions |
| TailwindCSS v3     | UI Design             |
| MySQL              | Database              |
| PDO                | Database connection   |
| Sessions & Cookies | Authentication        |

---

# 3. System Structure

## Main Sections

### A) Employee System

Route:
`/support`

### B) Student System

Route:
`/students`

### C) Admin Dashboard

Route:
`/admin`

---

# 4. User Roles

## 1. Admin

Permissions:

* Manage employees
* Manage branches
* Manage categories
* View all tickets from all branches
* View audit logs
* Manage system settings

---

## 2. Employee

Permissions:

* Login to dashboard
* Create internal tickets
* View tickets related to their branch only
* Reply to tickets
* Change ticket status
* Reopen closed tickets
* Change category
* View student tickets related to their branch

Restrictions:

* Cannot access other branches
* Cannot manage employees
* Cannot access admin settings

---

## 3. Student

Permissions:

* Access student portal
* Enter national ID
* Open ticket/complaint
* Track own tickets only
* View ticket replies and status

Restrictions:

* No login system
* Cannot reply to tickets
* Cannot access other students' tickets

---

# 5. Authentication System

## Employee Authentication

Features:

* Login with username/email + password
* Password hashing
* Session regeneration
* CSRF protection
* Rate limiting
* Secure cookies

---

## Student Authentication

Flow:

1. Student enters national ID
2. System checks database table "all_students"
3. If found:

   * Create session
   * Store national ID in session/cookie
4. Student can access:

   * Create ticket
   * Track tickets

---

# 6. Branch System

## Multi-Branch Support

Each employee belongs to one branch.

Employees can only view tickets related to their branch.

Admins can access all branches.

Student tickets are linked to a branch during ticket creation.

---

# 7. Ticket Types

## A) Internal Employee Tickets

Examples:

* IT
* HR
* Finance
* Operations

---

## B) Student Tickets

Examples:

* Financial complaint
* Academic issue
* Technical issue
* Complaint

---

# 8. Ticket Priorities

Supported priorities:

* Low
* Medium
* High

---

# 9. Ticket Statuses

Supported statuses:

* Open
* In Progress
* Closed

---

# 10. Ticket Number Format

Example:

`CRV-834729`

Requirements:

* Unique
* Random 6-digit number
* Auto-generated

---

# 11. Employee Ticket Workflow

## Create Ticket

Employee fills:

* Subject
* Description
* Category
* Priority

Automatically stored:

* Employee ID
* Branch ID
* Date
* Ticket Number

---

## Ticket Replies

Employees can:

* Add multiple replies
* Change ticket status while replying
* Reopen closed tickets

---

## Ticket Listing Page

Features:

* Table view
* Latest replied tickets first
* Filters
* Search
* Status badges
* Priority badges

Columns:

* Ticket Number
* Subject
* Category
* Priority
* Status
* Branch
* Created By
* Last Reply By
* Last Reply Date
* Created Date

---

# 12. Student Ticket Workflow

## Step 1 — National ID Verification

Student enters national ID.

System checks:

* Student exists
* Fetch student data

---

## Step 2 — Student Portal

Student sees:

1. Create Ticket
2. Track Tickets

---

## Step 3 — Create Ticket

Fields:

* Subject
* Description
* Category
* Phone Number
* Priority

Automatically stored:

* Student Name
* National ID
* Branch
* Ticket Number
* Created Date

---

## Step 4 — Ticket Tracking

Student can:

* View all own tickets
* Filter by status
* Search by ticket number
* View replies
* View ticket status
* View reply dates

Student cannot reply.

---

# 13. Dashboard

## Employee Dashboard

Widgets:

* Total Tickets
* Open Tickets
* In Progress Tickets
* Closed Tickets
* Latest Tickets
* Most Used Categories

---

## Admin Dashboard

Widgets:

* All Branch Statistics
* Employee Count
* Ticket Statistics
* Recent Activity
* Audit Logs

---

# 14. Search & Filters

## Search Fields

Supported search by:

* Ticket Number
* Student Name
* National ID
* Phone Number

---

## Filters

Supported filters:

* Status
* Category
* Priority
* Branch
* Date Range

---

# 15. Audit Log System

The system records:

* Ticket creation
* Status changes
* Category changes
* Replies
* Reopening tickets
* Employee actions

Stored data:

* User ID
* Action
* Old value
* New value
* IP Address
* Timestamp

---

# 16. Dark Mode

Supported themes:

* Light Mode
* Dark Mode

Preference stored in:

* LocalStorage
  or
* Cookie

---

# 17. Suggested Database Tables

## Core Tables

### employees

Stores employee accounts

### admins

Stores admin accounts

### branches

Stores system branches

### categories

Stores categories

### employee_tickets

Internal tickets

### employee_ticket_replies

Internal ticket replies

### student_tickets

Student tickets

### student_ticket_replies

Replies for student tickets

### all_students

Imported student database

### audit_logs

System activity logs

---

# 18. Categories Table Structure

Single categories table with type column.

Example:

| id | name           | type    |
| -- | -------------- | ------- |
| 1  | IT             | support |
| 2  | HR             | support |
| 3  | Academic Issue | student |

---

# 19. Security Requirements

Mandatory protections:

* PDO Prepared Statements
* CSRF Tokens
* XSS Sanitization
* Password Hashing
* Session Regeneration
* Login Rate Limiting
* Secure Cookies
* Input Validation

---

# 20. Suggested Folder Structure

```plaintext
/project-root
│
├── admin/
├── support/
├── students/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
├── database/
├── includes/
├── middleware/
├── helpers/
├── uploads/
│
├── templates/
├── logs/
└── index.php
```

---

# 21. Suggested UI Style

UI inspiration:

* Zendesk
* Modern dashboard layout
* Clean tables
* Responsive design
* Sidebar navigation
* Status badges
* Priority colors

---

# 22. Future Improvements

Future scalable features:

* Email notifications
* SMS notifications
* Attachments
* Real-time updates
* Employee assignments
* SLA system
* API integration
* Mobile application

---

# 23. Recommended Architecture Notes

Although the project uses PHP Native, recommended structure:

* Reusable includes
* Shared helpers
* Modular pages
* Centralized DB connection
* Reusable validation layer

Avoid writing all logic directly inside pages.

---

# 24. Final Notes

This system is designed as:

* Internal company support system
* Student complaint management platform
* Multi-branch scalable architecture
* Secure PHP Native implementation
* Modern UI using TailwindCSS v3

The structure is scalable and allows future migration to frameworks such as Laravel if needed.

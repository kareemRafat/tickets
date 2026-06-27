# Part 1: Project Setup, Database Schema & Flowbite Integration

This plan focuses on setting up the initial workspace structure, database schema, and configuring TailwindCSS and Flowbite via **npm / Tailwind CLI** for optimal production performance.

---

## Milestones & Tasks

### Milestone 1: Directory Setup & Tailwind / Flowbite npm Integration
- [x] **Task 1.1: Create Project Folder Structure**
  Create the following folders under the project root:
  - `admin/` (pages: `dashboard.php`, `employees.php`, `branches.php`, `categories.php`, `logs.php`)
  - `support/` (pages: `dashboard.php`, `tickets.php`, `ticket-view.php`, `ticket-create.php`, `login.php`)
  - `students/` (pages: `index.php`, `dashboard.php`, `ticket-create.php`, `track.php`, `ticket-view.php`)
  - `assets/css/`, `assets/js/`, `assets/images/`
  - `config/`, `database/`, `includes/`, `middleware/`, `helpers/`, `uploads/`, `templates/`, `logs/`

- [x] **Task 1.2: Initialize npm & Install Tailwind and Flowbite**
  Initialize npm and install the development dependencies:
  - Run `npm init -y` to generate a `package.json` file.
  - Install dependencies: `npm install -D tailwindcss postcss autoprefixer`
  - Install Flowbite: `npm install flowbite`
  - Generate Tailwind configuration: `npx tailwindcss init`

- [x] **Task 1.3: Configure tailwind.config.js**
  Update the generated `tailwind.config.js` to scan our PHP views and include Flowbite templates:
  ```javascript
  /** @type {import('tailwindcss').Config} */
  module.exports = {
    content: [
      "./admin/**/*.php",
      "./support/**/*.php",
      "./students/**/*.php",
      "./includes/**/*.php",
      "./node_modules/flowbite/**/*.js"
    ],
    theme: {
      extend: {},
    },
    plugins: [
      require('flowbite/plugin')
    ],
    darkMode: 'class',
  }
  ```

- [x] **Task 1.4: Set up CSS Build Pipeline**
  - Create [assets/css/input.css](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/assets/css/input.css) to import Tailwind:
    ```css
    @tailwind base;
    @tailwind components;
    @tailwind utilities;
    ```
  - Add execution scripts in `package.json`:
    ```json
    "scripts": {
      "dev": "tailwindcss -i ./assets/css/input.css -o ./assets/css/styles.css --watch",
      "build": "tailwindcss -i ./assets/css/input.css -o ./assets/css/styles.css --minify"
    }
    ```
  - Run `npm run dev` to start building styles incrementally.

- [x] **Task 1.5: Create Shared Template Includes**
  Create layout templates in the `includes/` folder referencing compiled styles and Flowbite script dependencies:
  - [includes/header.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/includes/header.php): Page head, CSS link to `/assets/css/styles.css` (compiled output), CSRF meta-tags, theme-detection logic, topbar with Dark Mode toggle.
  - [includes/footer.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/includes/footer.php): Closing HTML tags, Flowbite JS script reference `/node_modules/flowbite/dist/flowbite.min.js`, custom notifications script.
  - [includes/sidebar.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/includes/sidebar.php): Collapsible sidebar navigation powered by Flowbite.

---

### Milestone 2: Database Schema Design & Migration
- [x] **Task 2.1: Write Schema Definition SQL Script**
  Create [database/schema.sql](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/database/schema.sql) containing structures for:
  - `branches` (id, name, created_at)
  - `employees` (id, name, email, password_hash, branch_id, status, created_at)
  - `admins` (id, name, email, password_hash, created_at)
  - `categories` (id, name, type ENUM('support', 'student'), created_at)
  - `all_students` (id, national_id VARCHAR(50) UNIQUE, full_name, email, phone, branch_id, created_at)
  - `employee_tickets` (id, ticket_number VARCHAR(12) UNIQUE, subject, description, category_id, priority ENUM('low', 'medium', 'high'), status ENUM('open', 'in progress', 'closed'), branch_id, employee_id, created_at, updated_at)
  - `employee_ticket_replies` (id, ticket_id, author_id, author_type ENUM('employee', 'admin'), reply_text, created_at)
  - `student_tickets` (id, ticket_number VARCHAR(12) UNIQUE, subject, description, category_id, phone_number, priority ENUM('low', 'medium', 'high'), status ENUM('open', 'in progress', 'closed'), student_name, student_national_id, branch_id, created_at, updated_at)
  - `student_ticket_replies` (id, ticket_id, author_id, author_type ENUM('employee', 'admin'), reply_text, created_at)
  - `audit_logs` (id, user_id, user_type ENUM('employee', 'admin'), action, old_value, new_value, ip_address, created_at)

- [x] **Task 2.2: Add SQL Database Seeding script**
  Provide seed records inside `database/schema.sql`:
  - 1 Default Admin account (`admin@crv.com` / `Admin@123456`)
  - 3 Branches (e.g., Cairo, Giza, Alexandria)
  - Predefined support categories (IT, HR, Finance, Operations)
  - Predefined student categories (Financial Complaint, Academic Issue, Technical Issue, General Complaint)
  - Predefined test students in `all_students` table (with National IDs for login test)

---

### Milestone 3: Config & DB Driver Setup
- [x] **Task 3.1: Central Config Settings File**
  Create [config/config.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/config/config.php) to handle:
  - Error reporting levels
  - Session security configuration (`session.cookie_httponly`, `session.use_only_cookies`)
  - Global constant definitions (`BASE_URL`, `SYSTEM_NAME`)
- [x] **Task 3.2: PDO Connection Manager**
  Create [config/database.php](file:///C:/Users/Kareem/Desktop/Projects/tickets_system/config/database.php):
  - Return dynamic PDO connection string with configurations:
    - UTF-8 characters set
    - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
    - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`

---

## Validation & Verification

1. **Build Verification:** Execute `npm run build` to confirm output compilation runs successfully and generates `/assets/css/styles.css`.
2. **Local Test DB Run:** Import `database/schema.sql` into target MySQL server, verifying structure and absence of syntax errors.
3. **Template Validation:** Access dummy page integrating `header.php` and `footer.php` to verify Flowbite responsive layouts load properly and without console errors.

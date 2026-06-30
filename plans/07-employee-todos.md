# Part 7: Employee Todo / Task Management System

This plan covers building a todo/task management system for support employees and admins. Users can create, assign, complete, and manage daily tasks with due dates entirely through AJAX interactions (no page reloads).

---

## File Structure Changes

### New Files (6 files)

```
support/
├── todos.php                          # Main todos page (accessible by admin+employee)
├── ajax/
│   └── todos.php                      # Unified AJAX handler (list, create, toggle, delete)
└── js/
    └── todos.js                       # Vanilla JS for inline AJAX interactions

admin/
├── index.php                          # [MODIFIED] Add dashboard widget
└── ajax/
    └── dashboard-todos.php            # AJAX endpoint returning pending todo count + recent items

support/
└── index.php                          # [MODIFIED] Add dashboard widget

includes/
└── sidebar.php                        # [MODIFIED] Add "المهام" nav link for admin + employee

database/
└── schema.sql                         # [MODIFIED] Add employee_todos table definition
```

---

## Milestones & Tasks

### Milestone 1: Database & Backend Foundation

#### Task 1.1 — Create `employee_todos` table
Run the following SQL against the existing `tickets` database:

```sql
CREATE TABLE IF NOT EXISTS `employee_todos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assigned_by` INT NOT NULL,
    `assigned_to` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `due_date` DATE NULL,
    `status` ENUM('pending','done') DEFAULT 'pending',
    `completed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_todos_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_todos_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_todos_assigned_to ON employee_todos(assigned_to);
CREATE INDEX idx_todos_status ON employee_todos(status);
CREATE INDEX idx_todos_due_date ON employee_todos(due_date);
```

#### Task 1.2 — Update `database/schema.sql`
Append the `employee_todos` table definition and indexes to the schema file for fresh-install compatibility.

#### Task 1.3 — Create `support/ajax/todos.php` (unified AJAX handler)

A single PHP endpoint handling all todo CRUD operations via `?action=` parameter:

| Action | Method | Description |
|--------|--------|-------------|
| `list` | GET | Returns JSON of pending + done todos for the logged-in user |
| `create` | POST | Creates a new todo (validates CSRF, employee exists, title non-empty) |
| `toggle` | POST | Toggles todo status between pending/done (only the assigned_to user can toggle) |
| `delete` | POST | Deletes a todo (only creator or admin can delete) |

**Auth guard:** `require_employee_or_admin()`

**Response format:**
```json
{
  "success": true,
  "data": {
    "pending": [{ "id": 1, "title": "...", "due_date": null, "assigned_by_name": "Ahmed", "assigned_by_id": 2, "created_at": "..." }],
    "done": [{ "id": 2, "title": "...", "due_date": "2026-07-05", "assigned_by_name": "Ali", "assigned_by_id": 3, "completed_at": "..." }]
  }
}
```

**Permissions logic:**
- `list`: Returns todos where `assigned_to = current_user_id`
- `create`: Any admin/employee can create. `assigned_to` can be self or another employee.
- `toggle`: Only the user who is `assigned_to` can toggle their own todo. Returns error if another user tries.
- `delete`: Only the `assigned_by` creator or admin role can delete.

---

### Milestone 2: Frontend — Main Todos Page

#### Task 2.1 — Create `support/todos.php`

**Page pattern:**
```php
require_once __DIR__ . '/../bootstrap.php';
set_security_headers();
require_employee_or_admin();
// Fetch all active employees for the dropdown (for all branches)
$pageTitle = 'المهام';
require_once header + sidebar
// HTML
require_once footer
```

**Layout:**

```
┌──────────────────────────────────────────────────────────┐
│  header: 📋 المهام                        [عدد pending]  │
├──────────────────────────┬───────────────────────────────┤
│  إنشاء مهمة جديدة         │  📋 المهام الحالية (pending)   │
│  ┌────────────────────┐  │  ┌─────────────────────────┐   │
│  │ الموظف: [dropdown ▼]│  │  │ ☐ تنظيف قاعدة البيانات │   │
│  │ العنوان: [input   ]│  │  │   👤 من: أحمد محمد      │   │
│  │ التاريخ: [date    ]│  │  │   📅 2026-07-05         │   │
│  │                    │  │  └─────────────────────────┘   │
│  │ [➕ إنشاء المهمة]   │  │  ┌─────────────────────────┐   │
│  └────────────────────┘  │  │ ☐ مراجعة التقارير       │   │
│                          │  │   👤 من: علي السيد       │   │
│                          │  │   📅 بدون تاريخ          │   │
│                          │  └─────────────────────────┘   │
│                          ├───────────────────────────────┤
│                          │  ✅ المهام المُنجزة            │
│                          │  ┌─────────────────────────┐   │
│                          │  │ ☑ ~~تصليح البريد~~      │   │
│                          │  │   👤 من: أحمد محمد       │   │
│                          │  │   ✅ 2026-07-01          │   │
│                          │  └─────────────────────────┘   │
└──────────────────────────┴───────────────────────────────┘
```

**Employee dropdown** — fetched via `$db->query("SELECT id, name FROM employees WHERE status = 'active' ORDER BY name ASC")` to show all active employees across all branches. The current user is selected by default.

#### Task 2.2 — Create `support/js/todos.js`

Vanilla JS module handling:

| Function | Trigger | Behavior |
|----------|---------|----------|
| `loadTodos()` | On DOMContentLoaded + after every mutation | Fetches `/support/ajax/todos.php?action=list`, renders pending and done lists |
| `createTodo(e)` | Form submit | Serializes form, POSTs to `/support/ajax/todos.php?action=create`, reloads list |
| `toggleTodo(id)` | Click on checkbox | POSTs to `/support/ajax/todos.php?action=toggle`, animates the card and reloads list |
| `deleteTodo(id)` | Click on delete button | Shows Flowbite confirmation modal, POSTs delete, reloads list |
| `renderTodos(data)` | Called by `loadTodos()` | Builds HTML for pending/done sections, applies line-through for done items |

**Animation:** When toggling, the card fades out before being re-rendered in the new section (500ms transition to match existing pattern).

**Empty states:**
- No pending: "🎉 لا توجد مهام معلقة! كل المهام مُنجزة"
- No done: "لا توجد مهام مُنجزة بعد"

**Error states:** Toast-style error message at the top (using existing `$_SESSION['error']` or inline notification).

---

### Milestone 3: Sidebar Integration

#### Task 3.1 — Update `includes/sidebar.php`

Add "المهام" link in both `admin` and `employee` sections:

**In admin section** (after the "سجلات العمليات" item):
```php
<li>
   <a href="<?php echo BASE_URL; ?>support/todos.php" class="...">
      <svg>...</svg>
      <span class="mr-3">المهام</span>
   </a>
</li>
```

**In employee section** (after "شكاوى الطلاب"):
```php
<li>
   <a href="<?php echo BASE_URL; ?>support/todos.php" class="...">
      <svg>...</svg>
      <span class="mr-3">المهام</span>
   </a>
</li>
```

Icon: Clipboard checklist SVG from Heroicons.

Active state detection: Check if `$current_page === 'todos'`.

---

### Milestone 4: Dashboard Widgets

#### Task 4.1 — Create `admin/ajax/dashboard-todos.php`

AJAX endpoint returning:
```json
{
  "pending_count": 3,
  "recent": [
    { "id": 1, "title": "...", "assigned_by_name": "Ahmed", "due_date": "2026-07-05" },
    { "id": 2, "title": "...", "assigned_by_name": "Ali", "due_date": null }
  ]
}
```

#### Task 4.2 — Update `support/index.php`

Add a Flowbite card widget showing:
- Title: "📋 المهام"
- Pending count badge
- List of last 5 pending todos (title + assigned by)
- Link to full todos page

#### Task 4.3 — Update `admin/index.php`

Same widget as Task 4.2, using the admin dashboard layout conventions.

---

### Milestone 5: Verification & Polish

#### Task 5.1 — CSS Rebuild
Run `npm run build` to regenerate `assets/css/styles.css` including all new classes used in the todos pages.

#### Task 5.2 — Manual QA Checklist
- [ ] New table `employee_todos` created successfully
- [ ] Admin sees "المهام" in sidebar and can access the page
- [ ] Employee sees "المهام" in sidebar and can access the page
- [ ] Can create a todo assigned to self — appears in list
- [ ] Can create a todo assigned to another employee — appears in their list
- [ ] Due date is optional — todos without dates display "بدون تاريخ"
- [ ] Clicking checkbox toggles status (pending ↔ done) without page reload
- [ ] Done items have `line-through` styling and sit below pending items
- [ ] Only the assigned user can toggle their own todo
- [ ] Creator/admin can delete a todo
- [ ] Dashboard widget shows correct pending count on both admin and support dashboards
- [ ] Dark mode works correctly for all new elements
- [ ] No console errors
- [ ] Arabic text renders properly throughout

---

## Key Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Single AJAX handler | `support/ajax/todos.php` with `?action=` | Reduces file count; all todo logic in one place |
| Dashboard data | Separate AJAX call, not inline PHP | Consistent with existing dashboard pattern; avoids duplicating query logic |
| Employee list scope | All branches | Per user request |
| Toggle permission | Only `assigned_to` can toggle | Prevents others from completing someone else's task |
| Delete permission | Creator or admin can delete | Managerial control without clutter |
| Creation UX | Inline AJAX without page reload | Per user request ("like Google Keep") |
| Rendering approach | JS builds HTML strings (no frameworks) | Matches existing `track.js` pattern in the codebase |

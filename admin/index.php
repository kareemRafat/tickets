<?php
require_once __DIR__ . '/../bootstrap.php';

// Apply security headers
set_security_headers();

// Enforce admin privileges
require_admin();

try {
    $db = getDBConnection();
    
    // 1. Total Registered Employees (Support staff)
    $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE role = 'employee'");
    $employees_count = $stmt->fetchColumn();
    
    // 2. Total Branches
    $stmt = $db->query("SELECT COUNT(*) FROM branches");
    $branches_count = $stmt->fetchColumn();
    
    // 3. Ticket Counts
    $stmt = $db->query("SELECT COUNT(*) FROM support_tickets");
    $support_total = (int)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM support_tickets WHERE status != 'closed'");
    $support_active = (int)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM student_tickets");
    $student_total = (int)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM student_tickets WHERE status != 'closed'");
    $student_active = (int)$stmt->fetchColumn();
    
    $total_tickets = $support_total + $student_total;
    $active_tickets = $support_active + $student_active;
    
    // 4. Todos for admin
    $admin_id = (int)$_SESSION['user_id'];
    $stmt = $db->prepare("SELECT COUNT(*) FROM employee_todos WHERE assigned_to = :uid AND status = 'pending'");
    $stmt->execute(['uid' => $admin_id]);
    $admin_pending_todos = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT t.id, t.title, t.status, t.due_date, a.name as assigned_by_name
        FROM employee_todos t
        JOIN employees a ON t.assigned_by = a.id
        WHERE t.assigned_to = :uid
        ORDER BY t.status ASC, t.due_date ASC, t.created_at DESC
        LIMIT 5
    ");
    $stmt->execute(['uid' => $admin_id]);
    $admin_recent_todos = $stmt->fetchAll();

    // 5. Fetch Recent Audit Logs
    $stmt = $db->query("
        SELECT a.*, e.name as employee_name, e.role as employee_role 
        FROM audit_logs a 
        JOIN employees e ON a.employee_id = e.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $recent_activities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin dashboard database error: " . $e->getMessage());
    $employees_count = $branches_count = $total_tickets = $active_tickets = 0;
    $support_total = $support_active = $student_total = $student_active = 0;
    $admin_pending_todos = 0;
    $admin_recent_todos = [];
    $recent_activities = [];
}

$pageTitle = 'لوحة تحكم الإدارة العامة';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content Area -->
<main class="p-6 space-y-6 flex-1">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                لوحة التحكم الإدارية
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                متابعة إحصائيات الفروع، تذاكر الطلاب، الدعم الداخلي وسجلات العمليات المباشرة.
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="<?php echo BASE_URL; ?>admin/employees.php" class="px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800">
                إدارة الموظفين
            </a>
            <a href="<?php echo BASE_URL; ?>admin/branches.php" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 rounded-xl shadow-sm transition-all focus:ring-4 focus:ring-gray-100">
                عرض الفروع
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Stat Card 1: Active Tickets -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">التذاكر النشطة المفتوحة</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    قيد المتابعة
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $active_tickets; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">تذكرة غير مغلقة</span>
            </div>
        </div>

        <!-- Stat Card 2: Total Tickets -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي التذاكر</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    الكل
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $total_tickets; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">منذ إطلاق النظام</span>
            </div>
        </div>

        <!-- Stat Card 3: Employees -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">موظفو الدعم الفني</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    نشطين
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $employees_count; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">بكل الفروع</span>
            </div>
        </div>

        <!-- Stat Card 4: Branches -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">الفروع المفعلة</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    الفروع
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $branches_count; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">فروع معتمدة</span>
            </div>
        </div>
        <!-- Stat Card 5: Pending Todos -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المهام المعلقة</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                    مهامي
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $admin_pending_todos; ?></span>
                <a href="<?php echo BASE_URL; ?>support/todos.php" class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">عرض الكل</a>
            </div>
        </div>
    </div>

    <!-- Middle Dashboard Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Ticket Breakdown + Todos -->
        <div class="space-y-6">
        <!-- Ticket Breakdown -->
        <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">تفاصيل التذاكر الحالية</h3>
            
            <div class="space-y-4">
                <!-- Support tickets details -->
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تذاكر الدعم الداخلي المفتوحة (<?php echo $support_active; ?> / <?php echo $support_total; ?>)</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400"><?php echo $support_total > 0 ? round(($support_active / $support_total) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $support_total > 0 ? ($support_active / $support_total) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <!-- Student complaints details -->
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">شكاوى الطلاب المفتوحة (<?php echo $student_active; ?> / <?php echo $student_total; ?>)</span>
                        <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400"><?php echo $student_total > 0 ? round(($student_active / $student_total) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo $student_total > 0 ? ($student_active / $student_total) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>تذاكر الدعم المغلقة: <?php echo $support_total - $support_active; ?></span>
                <span>شكاوى الطلاب المغلقة: <?php echo $student_total - $student_active; ?></span>
            </div>
        </div>

        <!-- Todos Widget -->
        <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">المهام</h3>
                <a href="<?php echo BASE_URL; ?>support/todos.php" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">عرض الكل</a>
            </div>
            <?php if (empty($admin_recent_todos)): ?>
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد مهام بعد</p>
                </div>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($admin_recent_todos as $todo): ?>
                        <li class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <span class="shrink-0 w-2 h-2 rounded-full <?php echo $todo['status'] === 'done' ? 'bg-green-500' : 'bg-amber-400'; ?>"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate <?php echo $todo['status'] === 'done' ? 'line-through text-gray-400 dark:text-gray-500' : ''; ?>">
                                    <?php echo htmlspecialchars($todo['title']); ?>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    من: <?php echo htmlspecialchars($todo['assigned_by_name']); ?>
                                    <?php if ($todo['due_date']): ?>
                                        · <?php echo date('Y-m-d', strtotime($todo['due_date'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($todo['status'] === 'done'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-800/40 dark:text-green-300">تم</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-800/40 dark:text-amber-300">معلق</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        </div>

        <!-- Recent System Activity Timeline -->
        <div class="lg:col-span-2 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">سجل النشاط المباشر للنظام</h3>
                <a href="<?php echo BASE_URL; ?>admin/logs.php" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">عرض كل العمليات</a>
            </div>

            <?php if (empty($recent_activities)): ?>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p class="text-sm">لا توجد عمليات مسجلة حديثاً في النظام.</p>
                </div>
            <?php else: ?>
                <ol class="relative border-r border-gray-200 dark:border-gray-700 pr-4">
                    <?php foreach ($recent_activities as $log): ?>
                        <li class="mb-5 last:mb-0">
                            <!-- Bullet point aligned right -->
                            <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -right-3 ring-8 ring-white dark:ring-gray-800 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                </svg>
                            </span>
                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700/50">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($log['employee_name']); ?>
                                        <span class="text-xs font-normal text-gray-400 dark:text-gray-500">
                                            (<?php echo $log['employee_role'] === 'admin' ? 'مدير' : 'موظف دعم'; ?>)
                                        </span>
                                    </h4>
                                    <time class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                        <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                                    </time>
                                </div>
                                <p class="text-sm font-normal text-gray-600 dark:text-gray-300">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </p>
                                <?php if ($log['table_name']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 mt-2 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        الجدول: <?php echo htmlspecialchars($log['table_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

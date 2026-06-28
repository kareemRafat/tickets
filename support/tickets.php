<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_employee_or_admin();

$db = getDBConnection();
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
$branch_id = $is_admin ? 0 : (int)$_SESSION['user_branch_id'];

$type = xss_clean($_GET['type'] ?? 'support');
$status_filter = xss_clean($_GET['status'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);
$priority_filter = xss_clean($_GET['priority'] ?? '');
$search = xss_clean($_GET['search'] ?? '');
$from_date = xss_clean($_GET['from_date'] ?? '');
$to_date = xss_clean($_GET['to_date'] ?? '');

$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories WHERE type = 'support' AND status = 'active' ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch categories for filter: " . $e->getMessage());
}

$pageTitle = 'قائمة التذاكر';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                قائمة التذاكر
            </h1>
            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">متابعة وإدارة جميع تذاكر الدعم الفني وشكاوى الطلاب الخاصة بالفرع.</p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo BASE_URL; ?>support/ticket-create.php" class="inline-flex items-center px-4 py-2.5 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                إنشاء تذكرة
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 items-end" method="GET" action="">
            <input type="hidden" name="type" value="<?php echo $type; ?>">
            <div class="col-span-2 sm:col-span-1">
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">بحث</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">الحالة</label>
                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">الكل</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">التصنيف</label>
                <select name="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">الكل</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter === $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">الأولوية</label>
                <select name="priority" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">الكل</option>
                    <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>منخفضة</option>
                    <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>متوسطة</option>
                    <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>عالية</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">من تاريخ</label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">إلى تاريخ</label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="flex justify-between items-center col-span-2 sm:col-span-3 lg:col-span-6">
                <button type="submit" class="w-28 px-4 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
                <a href="<?php echo BASE_URL; ?>support/tickets.php?type=<?php echo $type; ?>" class="px-2 py-1 text-xs text-red-600 hover:text-red-800 underline dark:text-red-400 dark:hover:text-red-300 transition-all font-bold">إعادة تعيين</a>
            </div>
        </form>
    </div>

    <?php if ($type === 'support'): ?>
        <?php
        $where = [];
        $params = [];
        if (!$is_admin) {
            $where[] = "st.branch_id = :branch_id";
            $params['branch_id'] = $branch_id;
        }

        if (!empty($status_filter)) {
            $where[] = "st.status = :status";
            $params['status'] = $status_filter;
        }
        if ($category_filter > 0) {
            $where[] = "st.category_id = :category";
            $params['category'] = $category_filter;
        }
        if (!empty($priority_filter)) {
            $where[] = "st.priority = :priority";
            $params['priority'] = $priority_filter;
        }
        if (!empty($search)) {
            $where[] = "(st.ticket_number LIKE :search OR st.subject LIKE :search_subject)";
            $params['search'] = '%' . $search . '%';
            $params['search_subject'] = '%' . $search . '%';
        }
        if (!empty($from_date)) {
            $where[] = "st.created_at >= :from_date";
            $params['from_date'] = $from_date . ' 00:00:00';
        }
        if (!empty($to_date)) {
            $where[] = "st.created_at <= :to_date";
            $params['to_date'] = $to_date . ' 23:59:59';
        }

        $where_clause = $where ? implode(' AND ', $where) : '1=1';
        $tickets = [];
        try {
            $stmt = $db->prepare("
                SELECT st.*, c.name as category_name, e.name as employee_name,
                    (SELECT name FROM employees WHERE id = st.last_reply_by) as last_reply_name
                FROM support_tickets st
                JOIN categories c ON st.category_id = c.id
                JOIN employees e ON st.employee_id = e.id
                WHERE {$where_clause}
                ORDER BY COALESCE(st.last_reply_at, st.created_at) DESC
            ");
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Support tickets query error: " . $e->getMessage());
        }
        ?>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-base text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th scope="col" class="px-3 py-4 w-10">#</th>
                            <th scope="col" class="px-4 py-4">رقم التذكرة</th>
                            <th scope="col" class="px-4 py-4">الموضوع</th>
                            <th scope="col" class="px-4 py-4">التصنيف</th>
                            <th scope="col" class="px-4 py-4">الأولوية</th>
                            <th scope="col" class="px-4 py-4">الحالة</th>
                            <th scope="col" class="px-4 py-4">المنشئ</th>
                            <th scope="col" class="px-4 py-4">آخر رد</th>
                            <th scope="col" class="px-4 py-4">تاريخ الإنشاء</th>
                            <th scope="col" class="px-4 py-4 text-left">عرض</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    لا توجد تذاكر دعم فني تطابق معايير البحث.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-3 py-3 text-sm text-gray-400 dark:text-gray-500 text-center"><?php echo $i++; ?></td>
                                    <td class="px-4 py-3 font-mono text-sm font-semibold"><?php echo htmlspecialchars($t['ticket_number']); ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white max-w-xs truncate"><?php echo htmlspecialchars($t['subject']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($t['category_name']); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($t['priority'] === 'high'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">عالية</span>
                                        <?php elseif ($t['priority'] === 'medium'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">متوسطة</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">منخفضة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($t['status'] === 'open'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">مفتوحة</span>
                                        <?php elseif ($t['status'] === 'in_progress'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">قيد التنفيذ</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">مغلقة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($t['employee_name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php if ($t['last_reply_at']): ?><span class="font-medium"><?php echo htmlspecialchars($t['last_reply_name']); ?></span><br><span class="font-mono text-gray-500"><?php echo date('Y-m-d H:i', strtotime($t['last_reply_at'])); ?></span><?php else: ?><span class="text-gray-400">—</span><?php endif; ?></td>
                                    <td class="px-4 py-3 text-sm font-mono"><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                                    <td class="px-4 py-3 text-left">
                                        <a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $t['id']; ?>&type=support" class="font-medium text-blue-600 dark:text-blue-400 hover:underline text-sm">عرض</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                إجمالي <?php echo count($tickets); ?> تذكرة دعم فني
            </div>
        </div>

    <?php else: ?>
        <?php
        $where = [];
        $params = [];
        if (!$is_admin) {
            $where[] = "st.branch_id = :branch_id";
            $params['branch_id'] = $branch_id;
        }

        if (!empty($status_filter)) {
            $where[] = "st.status = :status";
            $params['status'] = $status_filter;
        }
        if ($category_filter > 0) {
            $where[] = "st.category_id = :category";
            $params['category'] = $category_filter;
        }
        if (!empty($priority_filter)) {
            $where[] = "st.priority = :priority";
            $params['priority'] = $priority_filter;
        }
        if (!empty($search)) {
            $where[] = "(st.ticket_number LIKE :search OR st.subject LIKE :search_subject OR st.student_name LIKE :search_name OR st.national_id LIKE :search_nid OR st.contact_phone LIKE :search_phone)";
            $params['search'] = '%' . $search . '%';
            $params['search_subject'] = '%' . $search . '%';
            $params['search_name'] = '%' . $search . '%';
            $params['search_nid'] = '%' . $search . '%';
            $params['search_phone'] = '%' . $search . '%';
        }
        if (!empty($from_date)) {
            $where[] = "st.created_at >= :from_date";
            $params['from_date'] = $from_date . ' 00:00:00';
        }
        if (!empty($to_date)) {
            $where[] = "st.created_at <= :to_date";
            $params['to_date'] = $to_date . ' 23:59:59';
        }

        $where_clause = $where ? implode(' AND ', $where) : '1=1';
        $tickets = [];
        try {
            $stmt = $db->prepare("
                SELECT st.*, c.name as category_name, st.student_name, st.national_id, st.student_code, st.contact_phone,
                    (SELECT name FROM employees WHERE id = st.last_reply_by) as last_reply_name
                FROM student_tickets st
                JOIN categories c ON st.category_id = c.id
                WHERE {$where_clause}
                ORDER BY COALESCE(st.last_reply_at, st.created_at) DESC
            ");
            $stmt->execute($params);
            $tickets = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Student tickets query error: " . $e->getMessage());
        }
        ?>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-base text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                        <tr>
                            <th scope="col" class="px-3 py-4 w-10">#</th>
                            <th scope="col" class="px-4 py-4">رقم التذكرة</th>
                            <th scope="col" class="px-4 py-4">الموضوع</th>
                            <th scope="col" class="px-4 py-4">الطالب</th>
                            <th scope="col" class="px-4 py-4">التصنيف</th>
                            <th scope="col" class="px-4 py-4">الأولوية</th>
                            <th scope="col" class="px-4 py-4">الحالة</th>
                            <th scope="col" class="px-4 py-4">آخر رد</th>
                            <th scope="col" class="px-4 py-4">تاريخ الإنشاء</th>
                            <th scope="col" class="px-4 py-4 text-left">عرض</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    لا توجد شكاوى طلابية تطابق معايير البحث.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-3 py-3 text-sm text-gray-400 dark:text-gray-500 text-center"><?php echo $i++; ?></td>
                                    <td class="px-4 py-3 font-mono text-sm font-semibold"><?php echo htmlspecialchars($t['ticket_number']); ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white max-w-xs truncate"><?php echo htmlspecialchars($t['subject']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($t['student_name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($t['category_name']); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($t['priority'] === 'high'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">عالية</span>
                                        <?php elseif ($t['priority'] === 'medium'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">متوسطة</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">منخفضة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($t['status'] === 'open'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">مفتوحة</span>
                                        <?php elseif ($t['status'] === 'in_progress'): ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">قيد التنفيذ</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">مغلقة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php if ($t['last_reply_at']): ?><span class="font-medium"><?php echo htmlspecialchars($t['last_reply_name']); ?></span><br><span class="font-mono text-gray-500"><?php echo date('Y-m-d H:i', strtotime($t['last_reply_at'])); ?></span><?php else: ?><span class="text-gray-400">—</span><?php endif; ?></td>
                                    <td class="px-4 py-3 text-sm font-mono"><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                                    <td class="px-4 py-3 text-left">
                                        <a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $t['id']; ?>&type=student" class="font-medium text-blue-600 dark:text-blue-400 hover:underline text-sm">عرض</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                إجمالي <?php echo count($tickets); ?> شكوى طلابية
            </div>
        </div>
    <?php endif; ?>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

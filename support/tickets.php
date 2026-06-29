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

$status_labels = ['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'closed' => 'مغلقة'];
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
                ORDER BY FIELD(st.status, 'open', 'in_progress', 'closed'), COALESCE(st.last_reply_at, st.created_at) DESC
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
                                <?php
                                $row_bg = match($t['status']) {
                                    'open' => 'bg-blue-200/50 hover:bg-blue-300/60 dark:bg-blue-900/25 dark:hover:bg-blue-900/40',
                                    'in_progress' => 'bg-yellow-200/50 hover:bg-yellow-300/60 dark:bg-yellow-900/25 dark:hover:bg-yellow-900/40',
                                    'closed' => 'bg-green-200/50 hover:bg-green-300/60 dark:bg-green-900/25 dark:hover:bg-green-900/40',
                                    default => 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'
                                };
                                $badge_bg = match($t['status']) {
                                    'open' => 'bg-blue-700 text-white dark:bg-blue-500 dark:text-white',
                                    'in_progress' => 'bg-yellow-600 text-white dark:bg-yellow-500 dark:text-white',
                                    'closed' => 'bg-green-700 text-white dark:bg-green-500 dark:text-white',
                                    default => ''
                                };
                                ?>
                                <tr class="<?php echo $row_bg; ?> transition-colors">
                                    <td class="px-3 py-3 text-sm text-gray-400 dark:text-gray-500 text-center"><?php echo $i++; ?></td>
                                    <td class="px-4 py-3 font-mono text-base font-semibold"><a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $t['id']; ?>&type=support" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($t['ticket_number']); ?></a></td>
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
                                        <span class="px-1.5 py-0.5 text-xs font-bold rounded-full inline-flex items-center gap-1 <?php echo $badge_bg; ?>">
                                            <?php if ($t['status'] === 'closed'): ?>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                                            <?php elseif ($t['status'] === 'in_progress'): ?>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                            <?php else: ?>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.5 1A4.5 4.5 0 0010 5.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5a3 3 0 016 0V7a1 1 0 102 0V5.5A4.5 4.5 0 0014.5 1z" clip-rule="evenodd"/></svg>
                                            <?php endif; ?>
                                            <?php echo $status_labels[$t['status']]; ?>
                                        </span>
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
                ORDER BY FIELD(st.status, 'open', 'in_progress', 'closed'), COALESCE(st.last_reply_at, st.created_at) DESC
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
                                <?php
                                $row_bg = match($t['status']) {
                                    'open' => 'bg-blue-200/50 hover:bg-blue-300/60 dark:bg-blue-900/25 dark:hover:bg-blue-900/40',
                                    'in_progress' => 'bg-yellow-200/50 hover:bg-yellow-300/60 dark:bg-yellow-900/25 dark:hover:bg-yellow-900/40',
                                    'closed' => 'bg-green-200/50 hover:bg-green-300/60 dark:bg-green-900/25 dark:hover:bg-green-900/40',
                                    default => 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'
                                };
                                $badge_bg = match($t['status']) {
                                    'open' => 'bg-blue-700 text-white dark:bg-blue-500 dark:text-white',
                                    'in_progress' => 'bg-yellow-600 text-white dark:bg-yellow-500 dark:text-white',
                                    'closed' => 'bg-green-700 text-white dark:bg-green-500 dark:text-white',
                                    default => ''
                                };
                                ?>
                                <tr class="<?php echo $row_bg; ?> transition-colors">
                                    <td class="px-3 py-3 text-sm text-gray-400 dark:text-gray-500 text-center"><?php echo $i++; ?></td>
                                    <td class="px-4 py-3 font-mono text-base font-semibold"><a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $t['id']; ?>&type=student" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo htmlspecialchars($t['ticket_number']); ?></a></td>
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
                                        <span class="px-1.5 py-0.5 text-xs font-bold rounded-full inline-flex items-center gap-1 <?php echo $badge_bg; ?>">
                                            <?php if ($t['status'] === 'closed'): ?>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                                            <?php elseif ($t['status'] === 'in_progress'): ?>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                            <?php else: ?>
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.5 1A4.5 4.5 0 0010 5.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5a3 3 0 016 0V7a1 1 0 102 0V5.5A4.5 4.5 0 0014.5 1z" clip-rule="evenodd"/></svg>
                                            <?php endif; ?>
                                            <?php echo $status_labels[$t['status']]; ?>
                                        </span>
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

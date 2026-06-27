<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$db = getDBConnection();
$national_id = $_SESSION['student_national_id'];

$status_filter = xss_clean($_GET['status'] ?? '');
$search = xss_clean($_GET['search'] ?? '');

$where = ["st.national_id = :national_id"];
$params = ['national_id' => $national_id];

if (!empty($status_filter)) {
    $where[] = "st.status = :status";
    $params['status'] = $status_filter;
}
if (!empty($search)) {
    $where[] = "(st.ticket_number LIKE :search OR st.subject LIKE :search_subject)";
    $params['search'] = '%' . $search . '%';
    $params['search_subject'] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where);
$tickets = [];
try {
    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name
        FROM student_tickets st
        JOIN categories c ON st.category_id = c.id
        WHERE {$where_clause}
        ORDER BY st.created_at DESC
    ");
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Student track error: " . $e->getMessage());
}

$pageTitle = 'تتبع تذاكري';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                تتبع تذاكري
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">متابعة حالة جميع التذاكر والشكاوى التي قمت بتقديمها.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            تقديم شكوى جديدة
        </a>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form class="flex flex-wrap items-end gap-4" method="GET" action="">
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">بحث برقم التذكرة</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-56 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">تصفية بالحالة</label>
                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-36 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">الكل</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
                <a href="<?php echo BASE_URL; ?>students/track.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 rounded-xl transition-all">إعادة تعيين</a>
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">رقم التذكرة</th>
                        <th scope="col" class="px-6 py-4">الموضوع</th>
                        <th scope="col" class="px-6 py-4">التصنيف</th>
                        <th scope="col" class="px-6 py-4">الأولوية</th>
                        <th scope="col" class="px-6 py-4">الحالة</th>
                        <th scope="col" class="px-6 py-4">تاريخ الإنشاء</th>
                        <th scope="col" class="px-6 py-4 text-left">عرض التفاصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا توجد تذاكر مسجلة تطابق معايير البحث.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs font-semibold"><?php echo htmlspecialchars($t['ticket_number']); ?></td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray-900 dark:text-white max-w-xs truncate"><?php echo htmlspecialchars($t['subject']); ?></td>
                                <td class="px-6 py-4 text-xs"><?php echo htmlspecialchars($t['category_name']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $t['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($t['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                                        <?php echo $t['priority'] === 'high' ? 'عالية' : ($t['priority'] === 'medium' ? 'متوسطة' : 'منخفضة'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $t['status'] === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($t['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'); ?>">
                                        <?php echo $t['status'] === 'open' ? 'مفتوحة' : ($t['status'] === 'in_progress' ? 'قيد التنفيذ' : 'مغلقة'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono"><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                                <td class="px-6 py-4 text-left">
                                    <a href="<?php echo BASE_URL; ?>students/ticket-view.php?id=<?php echo $t['id']; ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:underline text-xs">عرض</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            إجمالي <?php echo count($tickets); ?> تذكرة
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

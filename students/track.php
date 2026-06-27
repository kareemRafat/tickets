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

$hide_sidebar = true;
$pageTitle = 'تتبع تذاكري';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="sm:mr-0 pt-20 flex-1 flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">
<main class="p-4 lg:p-6 flex-1 flex flex-col space-y-4">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">تتبع تذاكري</h1>
            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">متابعة حالة جميع التذاكر والشكاوى التي قمت بتقديمها.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="inline-flex items-center px-4 py-2.5 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            تقديم شكوى جديدة
        </a>
    </div>

    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form class="flex flex-wrap items-end gap-4" method="GET" action="">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">بحث برقم التذكرة</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-56 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">تصفية بالحالة</label>
                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-36 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">الكل</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
            <a href="<?php echo BASE_URL; ?>students/track.php" class="mr-auto font-bold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors text-sm mb-2">إعادة تعيين</a>
        </form>
    </div>

    <style>
    .ticket-item.active {
        background-color: rgba(59, 130, 246, 0.08);
        border-right: 3px solid #3b82f6;
    }
    .dark .ticket-item.active {
        background-color: rgba(59, 130, 246, 0.12);
        border-right-color: #60a5fa;
    }
</style>

<div class="flex-1 grid grid-cols-1 lg:grid-cols-6 gap-4 min-h-0">
        <div id="ticket-details" data-api-url="<?php echo BASE_URL; ?>students/ajax/ticket-details.php" class="lg:col-span-4 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6 order-2 lg:order-2 min-h-[300px] lg:min-h-0 overflow-y-auto">
            <div class="flex items-center justify-center h-full py-20">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <p class="text-lg font-medium text-gray-400 dark:text-gray-500">اختر تذكرة من القائمة لعرض تفاصيلها</p>
                </div>
            </div>
        </div>

        <div id="ticket-list" class="lg:col-span-2 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden order-1 lg:order-1 flex flex-col max-h-[300px] lg:max-h-[calc(100vh-280px)]">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between shrink-0">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">التذاكر</span>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo count($tickets); ?> تذكرة</span>
            </div>
            <div class="overflow-y-auto flex-1 divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($tickets)): ?>
                    <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        لا توجد تذاكر مسجلة تطابق معايير البحث.
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <?php
                        $status_ar = $t['status'] === 'open' ? 'مفتوحة' : ($t['status'] === 'in_progress' ? 'قيد التنفيذ' : 'مغلقة');
                        $priority_ar = $t['priority'] === 'high' ? 'عالية' : ($t['priority'] === 'medium' ? 'متوسطة' : 'منخفضة');
                        $status_bg = $t['status'] === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($t['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200');
                        $priority_bg = $t['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($t['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300');
                        ?>
                        <div class="ticket-item cursor-pointer px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors" data-ticket-id="<?php echo $t['id']; ?>">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono text-base font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($t['ticket_number']); ?></span>
                                <span class="text-sm text-gray-400 dark:text-gray-500"><?php echo date('Y-m-d', strtotime($t['created_at'])); ?></span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 dark:text-gray-200 truncate mb-2"><?php echo htmlspecialchars($t['subject']); ?></p>
                            <div class="flex items-center gap-2.5">
                                <span class="px-2.5 py-0.5 text-sm font-medium rounded-full <?php echo $status_bg; ?>"><?php echo $status_ar; ?></span>
                                <span class="px-2.5 py-0.5 text-sm font-medium rounded-full <?php echo $priority_bg; ?>"><?php echo $priority_ar; ?></span>
                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-auto"><?php echo htmlspecialchars($t['category_name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</main>
<script src="<?php echo BASE_URL; ?>js/students/track.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

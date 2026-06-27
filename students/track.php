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
        ORDER BY
            CASE st.status
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'closed' THEN 3
            END,
            st.created_at DESC
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
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-56 p-2 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
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
        background-color: rgba(0, 0, 0, 0.04);
    }
    .dark .ticket-item.active {
        background-color: rgba(255, 255, 255, 0.06);
    }
</style>

<div class="flex-1 grid grid-cols-1 lg:grid-cols-6 gap-4 min-h-0">
        <div id="ticket-details" data-api-url="<?php echo BASE_URL; ?>students/ajax/ticket-details.php" class="lg:col-span-4 bg-white border border-gray-300 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6 order-2 lg:order-2 min-h-[300px] lg:min-h-0 overflow-y-auto">
            <div class="flex items-center justify-center h-full py-20">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    <p class="text-lg font-medium text-gray-400 dark:text-gray-500">اختر تذكرة من القائمة لعرض تفاصيلها</p>
                </div>
            </div>
        </div>

        <div id="ticket-list" class="lg:col-span-2 bg-white border border-gray-300 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden order-1 lg:order-1 flex flex-col max-h-[300px] lg:max-h-[calc(100vh-280px)]">
            <div class="px-4 py-3 border-b border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between shrink-0">
                <span class="text-sm text-gray-700 dark:text-gray-300 font-bold">التذاكر</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-bold"><?php echo count($tickets); ?> تذكرة</span>
            </div>
            <div class="overflow-y-auto flex-1 p-3 space-y-2">
                <?php if (empty($tickets)): ?>
                    <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        لا توجد تذاكر مسجلة تطابق معايير البحث.
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <?php
                        $status_ar = $t['status'] === 'open' ? 'مفتوحة' : ($t['status'] === 'in_progress' ? 'قيد التنفيذ' : 'مغلقة');
                        $status_bg = $t['status'] === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($t['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200');
                        $border_color = $t['status'] === 'open' ? '#22c55e' : ($t['status'] === 'in_progress' ? '#eab308' : '#ef4444');
                        $status_icon = $t['status'] === 'open'
                            ? '<svg class="w-4 h-4 inline ml-1 -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M5 11a2 2 0 00-2 2v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2H5z"/><path d="M12 14a2 2 0 100 4 2 2 0 000-4z"/><path d="M12 16v2"/><path d="M7 11V8a5 5 0 0110-2" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>'
                            : ($t['status'] === 'in_progress'
                                ? '<svg class="w-4 h-4 inline ml-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>'
                                : '<svg class="w-4 h-4 inline ml-1 -mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M5 11a2 2 0 00-2 2v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2H5z"/><path d="M12 14a2 2 0 100 4 2 2 0 000-4z"/><path d="M12 16v2"/><path d="M7 11V8a5 5 0 0110 0v3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>');
                        ?>
                        <div class="ticket-item cursor-pointer px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors" data-ticket-id="<?php echo $t['id']; ?>" style="border-right: 3px solid <?php echo $border_color; ?>">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono text-base font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($t['ticket_number']); ?></span>
                                <span class="text-sm text-gray-400 dark:text-gray-500"><?php echo date('Y-m-d', strtotime($t['created_at'])); ?></span>
                            </div>
                            <p class="text-base font-semibold text-gray-800 dark:text-gray-200 truncate mb-2"><?php echo htmlspecialchars($t['subject']); ?></p>
                            <div class="flex items-center gap-2.5">
                                <span class="px-2.5 py-0.5 text-sm font-medium rounded-full <?php echo $status_bg; ?>"><?php echo $status_icon . $status_ar; ?></span>
                                <span class="text-sm text-gray-600 dark:text-gray-400 mr-auto font-bold"><?php echo htmlspecialchars($t['category_name']); ?></span>
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

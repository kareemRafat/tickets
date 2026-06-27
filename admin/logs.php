<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_admin();

$db = getDBConnection();

$from_date = xss_clean($_GET['from_date'] ?? '');
$to_date = xss_clean($_GET['to_date'] ?? '');
$search_action = xss_clean($_GET['search_action'] ?? '');

$where = [];
$params = [];

if (!empty($from_date)) {
    $where[] = "a.created_at >= :from_date";
    $params['from_date'] = $from_date . ' 00:00:00';
}
if (!empty($to_date)) {
    $where[] = "a.created_at <= :to_date";
    $params['to_date'] = $to_date . ' 23:59:59';
}
if (!empty($search_action)) {
    $where[] = "a.action LIKE :search_action";
    $params['search_action'] = '%' . $search_action . '%';
}

$where_clause = '';
if (!empty($where)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where);
}

$logs = [];
try {
    $stmt = $db->prepare("
        SELECT a.*, e.name as employee_name, e.role as employee_role
        FROM audit_logs a
        JOIN employees e ON a.employee_id = e.id
        {$where_clause}
        ORDER BY a.created_at DESC
        LIMIT 200
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch audit logs: " . $e->getMessage());
}

$pageTitle = 'سجل العمليات والمراجعة';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                سجل العمليات
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                مراجعة جميع العمليات والإجراءات التي تمت داخل النظام مع تفاصيل المستخدم والتوقيت.
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form class="flex flex-wrap items-end gap-4" method="GET" action="">
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">من تاريخ</label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-44 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">إلى تاريخ</label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-44 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">بحث في الإجراء</label>
                <input type="text" name="search_action" value="<?php echo htmlspecialchars($search_action); ?>" placeholder="مثال: إضافة موظف" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-56 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
                <a href="<?php echo BASE_URL; ?>admin/logs.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 rounded-xl transition-all">إعادة تعيين</a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-4 py-4">التاريخ والوقت</th>
                        <th scope="col" class="px-4 py-4">المستخدم</th>
                        <th scope="col" class="px-4 py-4">الإجراء</th>
                        <th scope="col" class="px-4 py-4">الجدول</th>
                        <th scope="col" class="px-4 py-4">القيم القديمة</th>
                        <th scope="col" class="px-4 py-4">القيم الجديدة</th>
                        <th scope="col" class="px-4 py-4">IP العنوان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا توجد عمليات مسجلة تطابق معايير البحث.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-4 py-3 text-xs whitespace-nowrap font-mono">
                                    <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-semibold text-gray-900 dark:text-white text-xs"><?php echo htmlspecialchars($log['employee_name']); ?></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 block">
                                        <?php echo $log['employee_role'] === 'admin' ? 'مدير' : 'موظف'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs max-w-xs truncate">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($log['table_name']): ?>
                                        <span class="px-2 py-0.5 text-xs font-mono rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars($log['table_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs max-w-xs">
                                    <?php if ($log['old_values']): ?>
                                        <button data-modal-target="old-json-modal-<?php echo $log['id']; ?>" data-modal-toggle="old-json-modal-<?php echo $log['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            عرض
                                        </button>
                                        <div id="old-json-modal-<?php echo $log['id']; ?>" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                                            <div class="relative w-full max-w-lg max-h-full">
                                                <div class="relative bg-white rounded-2xl shadow dark:bg-gray-800 text-right">
                                                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="old-json-modal-<?php echo $log['id']; ?>">
                                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                                        </svg>
                                                        <span class="sr-only">إغلاق</span>
                                                    </button>
                                                    <div class="p-6">
                                                        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">القيم القديمة</h3>
                                                        <pre class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl text-xs text-left font-mono overflow-x-auto max-h-96 text-gray-800 dark:text-gray-200 whitespace-pre-wrap"><?php echo json_encode(json_decode($log['old_values'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs max-w-xs">
                                    <?php if ($log['new_values']): ?>
                                        <button data-modal-target="new-json-modal-<?php echo $log['id']; ?>" data-modal-toggle="new-json-modal-<?php echo $log['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            عرض
                                        </button>
                                        <div id="new-json-modal-<?php echo $log['id']; ?>" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                                            <div class="relative w-full max-w-lg max-h-full">
                                                <div class="relative bg-white rounded-2xl shadow dark:bg-gray-800 text-right">
                                                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="new-json-modal-<?php echo $log['id']; ?>">
                                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                                        </svg>
                                                        <span class="sr-only">إغلاق</span>
                                                    </button>
                                                    <div class="p-6">
                                                        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">القيم الجديدة</h3>
                                                        <pre class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl text-xs text-left font-mono overflow-x-auto max-h-96 text-gray-800 dark:text-gray-200 whitespace-pre-wrap"><?php echo json_encode(json_decode($log['new_values'], true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono">
                                    <?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
            عرض آخر <?php echo count($logs); ?> سجل من إجمالي العمليات (الحد الأقصى 200)
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

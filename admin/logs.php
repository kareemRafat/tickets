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
    <div class="p-3 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3" method="GET" action="">
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">من تاريخ</label>
                <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">إلى تاريخ</label>
                <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">بحث في الإجراء</label>
                <input type="text" name="search_action" value="<?php echo htmlspecialchars($search_action); ?>" placeholder="مثال: إضافة موظف" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
            </div>
            <div class="flex items-end justify-end">
                <a href="<?php echo BASE_URL; ?>admin/logs.php" class="text-sm font-semibold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 underline transition-all">إعادة تعيين</a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-base text-right text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-5 py-4">التاريخ والوقت</th>
                        <th scope="col" class="px-5 py-4">المستخدم</th>
                        <th scope="col" class="px-5 py-4">الإجراء</th>
                        <th scope="col" class="px-5 py-4">الجدول</th>
                        <th scope="col" class="px-5 py-4">التغييرات</th>
                        <th scope="col" class="px-5 py-4">IP العنوان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا توجد عمليات مسجلة تطابق معايير البحث.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap font-mono text-base">
                                    <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-gray-900 dark:text-white text-base"><?php echo htmlspecialchars($log['employee_name']); ?></span>
                                    <span class="text-base text-gray-400 dark:text-gray-500 block">
                                        <?php echo $log['employee_role'] === 'admin' ? 'مدير' : 'موظف'; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-base max-w-xs truncate">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ($log['table_name']): ?>
                                        <span class="px-2.5 py-0.5 text-base font-mono rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars($log['table_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-base text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-base max-w-xs">
                                    <?php
                                    $old_data = $log['old_values'] ? json_decode($log['old_values'], true) : null;
                                    $new_data = $log['new_values'] ? json_decode($log['new_values'], true) : null;
                                    ?>
                                    <?php if ($old_data || $new_data): ?>
                                        <button data-modal-target="changes-modal-<?php echo $log['id']; ?>" data-modal-toggle="changes-modal-<?php echo $log['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            عرض التغييرات
                                        </button>
                                        <div id="changes-modal-<?php echo $log['id']; ?>" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                                            <div class="relative w-full max-w-2xl max-h-full">
                                                <div class="relative bg-white rounded-2xl shadow dark:bg-gray-800 text-right">
                                                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="changes-modal-<?php echo $log['id']; ?>">
                                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                                        </svg>
                                                        <span class="sr-only">إغلاق</span>
                                                    </button>
                                                    <div class="p-6">
                                                        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">التغييرات</h3>
                                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                                                            <table class="w-full text-sm text-right">
                                                                <thead class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                                                    <tr>
                                                                        <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 w-1/4 border-l border-gray-200 dark:border-gray-700">الحقل</th>
                                                                        <th class="px-4 py-3 font-semibold text-red-600 dark:text-red-400 w-[37.5%]">القيمة القديمة</th>
                                                                        <th class="px-4 py-3 font-semibold text-green-600 dark:text-green-400 w-[37.5%]">القيمة الجديدة</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                                    <?php
                                                                    $all_keys = [];
                                                                    if ($old_data) $all_keys = array_merge($all_keys, array_keys($old_data));
                                                                    if ($new_data) $all_keys = array_merge($all_keys, array_keys($new_data));
                                                                    $all_keys = array_unique($all_keys);
                                                                    ?>
                                                                    <?php foreach ($all_keys as $key): ?>
                                                                        <?php
                                                                        $old_val = $old_data[$key] ?? null;
                                                                        $new_val = $new_data[$key] ?? null;
                                                                        $changed = $old_val !== $new_val;
                                                                        $fmt_old = $old_val !== null ? (is_array($old_val) || is_object($old_val) ? json_encode($old_val, JSON_UNESCAPED_UNICODE) : (string)$old_val) : null;
                                                                        $fmt_new = $new_val !== null ? (is_array($new_val) || is_object($new_val) ? json_encode($new_val, JSON_UNESCAPED_UNICODE) : (string)$new_val) : null;
                                                                        ?>
                                                                        <?php if ($changed): ?>
                                                                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-800/50">
                                                                            <td class="px-4 py-2.5 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap border-l border-gray-200 dark:border-gray-700"><?php echo htmlspecialchars($key); ?></td>
                                                                            <td class="px-4 py-2.5 text-red-700 dark:text-red-400 bg-red-50/50 dark:bg-red-900/10 line-through decoration-red-400/60"><?php echo htmlspecialchars($fmt_old ?? '—'); ?></td>
                                                                            <td class="px-4 py-2.5 text-green-700 dark:text-green-400 bg-green-50/50 dark:bg-green-900/10 font-semibold"><?php echo htmlspecialchars($fmt_new ?? '—'); ?></td>
                                                                        </tr>
                                                                        <?php else: ?>
                                                                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-800/50">
                                                                            <td class="px-4 py-2.5 font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap border-l border-gray-200 dark:border-gray-700"><?php echo htmlspecialchars($key); ?></td>
                                                                            <td class="px-4 py-2.5 text-gray-900 dark:text-white" colspan="2"><?php echo htmlspecialchars($fmt_old ?? '—'); ?></td>
                                                                        </tr>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 font-mono text-base">
                                    <?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
            عرض آخر <?php echo count($logs); ?> سجل من إجمالي العمليات (الحد الأقصى 200)
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

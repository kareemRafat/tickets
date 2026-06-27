<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_employee();

$db = getDBConnection();
$branch_id = (int)$_SESSION['user_branch_id'];
$employee_id = (int)$_SESSION['user_id'];

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE branch_id = :branch_id AND status = 'open'");
    $stmt->execute(['branch_id' => $branch_id]);
    $open_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE branch_id = :branch_id AND status = 'in_progress'");
    $stmt->execute(['branch_id' => $branch_id]);
    $in_progress_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE branch_id = :branch_id AND status = 'closed'");
    $stmt->execute(['branch_id' => $branch_id]);
    $closed_count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branch_id]);
    $total_support = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE branch_id = :branch_id AND status != 'closed'");
    $stmt->execute(['branch_id' => $branch_id]);
    $student_active = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branch_id]);
    $student_total = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name, e.name as employee_name,
            (SELECT name FROM employees WHERE id = st.last_reply_by) as last_reply_name
        FROM support_tickets st
        JOIN categories c ON st.category_id = c.id
        JOIN employees e ON st.employee_id = e.id
        WHERE st.branch_id = :branch_id
        ORDER BY COALESCE(st.last_reply_at, st.created_at) DESC
        LIMIT 5
    ");
    $stmt->execute(['branch_id' => $branch_id]);
    $latest_tickets = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT name FROM branches WHERE id = :branch_id");
    $stmt->execute(['branch_id' => $branch_id]);
    $branch_name = $stmt->fetchColumn() ?: '';

} catch (PDOException $e) {
    error_log("Employee dashboard error: " . $e->getMessage());
    $open_count = $in_progress_count = $closed_count = 0;
    $total_support = $student_active = $student_total = 0;
    $latest_tickets = [];
    $branch_name = '';
}

$pageTitle = 'لوحة تحكم الدعم الفني';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                لوحة تحكم الدعم الفني
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                متابعة تذاكر فرع <span class="font-semibold text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($branch_name); ?></span>
            </p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>support/ticket-create.php" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                إنشاء تذكرة جديدة
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">مفتوحة</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">مفتوحة</span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $open_count; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">تذكرة</span>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">قيد التنفيذ</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">قيد التنفيذ</span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $in_progress_count; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">تذكرة</span>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">مغلقة</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">مغلقة</span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $closed_count; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">تذكرة</span>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">شكاوى طلاب نشطة</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">نشطة</span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo $student_active; ?></span>
                <span class="text-xs text-gray-500 dark:text-gray-400">من أصل <?php echo $student_total; ?></span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">إحصائيات التذاكر</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">مفتوحة (<?php echo $open_count; ?> / <?php echo $total_support; ?>)</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400"><?php echo $total_support > 0 ? round(($open_count / $total_support) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $total_support > 0 ? ($open_count / $total_support) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">قيد التنفيذ (<?php echo $in_progress_count; ?> / <?php echo $total_support; ?>)</span>
                        <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400"><?php echo $total_support > 0 ? round(($in_progress_count / $total_support) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-yellow-400 h-2.5 rounded-full" style="width: <?php echo $total_support > 0 ? ($in_progress_count / $total_support) * 100 : 0; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">مغلقة (<?php echo $closed_count; ?> / <?php echo $total_support; ?>)</span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400"><?php echo $total_support > 0 ? round(($closed_count / $total_support) * 100, 1) : 0; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div class="bg-green-500 h-2.5 rounded-full" style="width: <?php echo $total_support > 0 ? ($closed_count / $total_support) * 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">آخر التذاكر</h3>
                <a href="<?php echo BASE_URL; ?>support/tickets.php" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">عرض الكل</a>
            </div>
            <?php if (empty($latest_tickets)): ?>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p class="text-sm">لا توجد تذاكر دعم فني مسجلة في هذا الفرع حتى الآن.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3">رقم التذكرة</th>
                                <th scope="col" class="px-4 py-3">الموضوع</th>
                                <th scope="col" class="px-4 py-3">التصنيف</th>
                                <th scope="col" class="px-4 py-3">الأولوية</th>
                                <th scope="col" class="px-4 py-3">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php foreach ($latest_tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <a href="<?php echo BASE_URL; ?>support/ticket-view.php?id=<?php echo $ticket['id']; ?>" class="font-mono text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white text-xs max-w-xs truncate"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td class="px-4 py-3 text-xs"><?php echo htmlspecialchars($ticket['category_name']); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($ticket['priority'] === 'high'): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">عالية</span>
                                        <?php elseif ($ticket['priority'] === 'medium'): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">متوسطة</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">منخفضة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($ticket['status'] === 'open'): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">مفتوحة</span>
                                        <?php elseif ($ticket['status'] === 'in_progress'): ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">قيد التنفيذ</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">مغلقة</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

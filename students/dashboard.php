<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$db = getDBConnection();
$national_id = $_SESSION['student_national_id'];
$student_name = $_SESSION['student_name'];

$tickets = [];
try {
    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name
        FROM student_tickets st
        JOIN categories c ON st.category_id = c.id
        WHERE st.national_id = :national_id
        ORDER BY st.created_at DESC
        LIMIT 5
    ");
    $stmt->execute(['national_id' => $national_id]);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Student dashboard error: " . $e->getMessage());
}

$pageTitle = 'لوحة الخدمات الطلابية';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                مرحباً بك، <?php echo htmlspecialchars($student_name); ?>
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">بوابة الخدمات الطلابية — يمكنك تقديم شكوى جديدة أو متابعة تذاكرك الحالية.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="block p-8 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700 group">
            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">تقديم شكوى / تذكرة</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">إنشاء شكوى أو تذكرة جديدة للتواصل مع فريق الدعم الفني بالفرع.</p>
        </a>
        <a href="<?php echo BASE_URL; ?>students/track.php" class="block p-8 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700 group">
            <div class="w-14 h-14 bg-indigo-100 dark:bg-indigo-900/50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">تتبع تذاكري</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">متابعة حالة التذاكر السابقة والاطلاع على الردود والتحديثات.</p>
        </a>
    </div>

    <?php if (!empty($tickets)): ?>
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">آخر تذاكري</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">رقم التذكرة</th>
                            <th scope="col" class="px-6 py-3">الموضوع</th>
                            <th scope="col" class="px-6 py-3">التصنيف</th>
                            <th scope="col" class="px-6 py-3">الأولوية</th>
                            <th scope="col" class="px-6 py-3">الحالة</th>
                            <th scope="col" class="px-6 py-3">تاريخ الإنشاء</th>
                            <th scope="col" class="px-6 py-3 text-left">عرض</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-3 font-mono text-xs font-semibold"><?php echo htmlspecialchars($t['ticket_number']); ?></td>
                                <td class="px-6 py-3 text-xs font-semibold text-gray-900 dark:text-white max-w-xs truncate"><?php echo htmlspecialchars($t['subject']); ?></td>
                                <td class="px-6 py-3 text-xs"><?php echo htmlspecialchars($t['category_name']); ?></td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $t['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($t['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                                        <?php echo $t['priority'] === 'high' ? 'عالية' : ($t['priority'] === 'medium' ? 'متوسطة' : 'منخفضة'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $t['status'] === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($t['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'); ?>">
                                        <?php echo $t['status'] === 'open' ? 'مفتوحة' : ($t['status'] === 'in_progress' ? 'قيد التنفيذ' : 'مغلقة'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs font-mono"><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                                <td class="px-6 py-3 text-left">
                                    <a href="<?php echo BASE_URL; ?>students/ticket-view.php?id=<?php echo $t['id']; ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:underline text-xs">عرض</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                <a href="<?php echo BASE_URL; ?>students/track.php" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">عرض جميع تذاكري &larr;</a>
            </div>
        </div>
    <?php endif; ?>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

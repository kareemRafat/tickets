<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../functions/pagination.php';

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
$student_name = xss_clean($_GET['student_name'] ?? '');
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
    <div class="p-4 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form method="GET" action="">
            <input type="hidden" name="type" value="<?php echo $type; ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">بحث (رقم / موضوع)</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
                </div>
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">الحالة</label>
                    <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">الكل</option>
                        <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">التصنيف</label>
                    <select name="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">الكل</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter === $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">الأولوية</label>
                    <select name="priority" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">الكل</option>
                        <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>منخفضة</option>
                        <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>متوسطة</option>
                        <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>عالية</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">اسم الطالب</label>
                    <input type="text" name="student_name" value="<?php echo htmlspecialchars($student_name); ?>" placeholder="اسم الطالب..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
                </div>
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">من تاريخ</label>
                    <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="w-full">
                    <label class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">إلى تاريخ</label>
                    <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div class="w-full flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
                    <a href="<?php echo BASE_URL; ?>support/tickets.php?type=<?php echo $type; ?>" class="px-3 py-1.5 text-sm font-bold text-red-600 hover:text-red-800 underline dark:text-red-400 dark:hover:text-red-300 transition-all whitespace-nowrap">إعادة تعيين</a>
                </div>
            </div>
        </form>
    </div>

    <?php if ($type === 'support'): ?>
        <?php include __DIR__ . '/tables/support_tickets.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/tables/student_tickets.php'; ?>
    <?php endif; ?>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

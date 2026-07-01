<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_admin();

$db = getDBConnection();

$employees = [];
try {
    $stmt = $db->query("SELECT e.id, e.name, e.role, b.name as branch_name FROM employees e LEFT JOIN branches b ON e.branch_id = b.id WHERE e.status = 'active' ORDER BY e.role != 'admin', b.name, e.name");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch employees for all-todos: " . $e->getMessage());
}

$pageTitle = 'جميع المهام';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-4 md:p-6 space-y-6 flex-1">
    <div>

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                    جميع المهام
                </h1>
                <p class="mt-1 text-base text-gray-500 dark:text-gray-400">عرض وإدارة جميع مهام الموظفين</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-300 dark:bg-amber-900/20 dark:border-amber-800/40 rounded-xl text-base font-semibold text-amber-700 dark:text-amber-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>إجمالي : <strong id="total-count">0</strong></span>
                </span>
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-400 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">العودة للوحة التحكم</a>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="w-full lg:w-4/5 mb-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div class="flex items-center gap-3">
                <label for="all-todos-date" class="text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">تاريخ الاستحقاق:</label>
                <input type="date" id="all-todos-date" value="<?php echo date('Y-m-d'); ?>" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white w-full">
            </div>
            <div class="flex items-center gap-3">
                <label for="all-todos-employee" class="text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">الموظف:</label>
                <select id="all-todos-employee" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white w-full">
                    <option value="">جميع الموظفين</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['name']); if (!empty($emp['branch_name']) && $emp['role'] !== 'admin') { echo ' ( ' . htmlspecialchars($emp['branch_name']) . ' )'; } ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <label for="all-todos-status" class="text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">الحالة:</label>
                <select id="all-todos-status" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white w-full">
                    <option value="">الكل</option>
                    <option value="pending">معلقة</option>
                    <option value="done">منتهية</option>
                </select>
            </div>
            <div class="flex items-center gap-3">
                <label for="all-todos-search" class="text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">بحث:</label>
                <input type="text" id="all-todos-search" placeholder="عنوان المهمة..." class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white w-full">
            </div>
            <div class="flex items-center justify-end sm:col-span-2">
                <button type="button" id="all-todos-reset" class="text-sm font-bold text-red-600 dark:text-red-400 hover:underline cursor-pointer">إعادة تعيين</button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-base text-right">
                    <thead class="text-xs font-bold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-center w-12">#</th>
                            <th scope="col" class="px-4 py-3">العنوان</th>
                            <th scope="col" class="px-4 py-3">مسندة إلى</th>
                            <th scope="col" class="px-4 py-3">بواسطة</th>
                            <th scope="col" class="px-4 py-3">تاريخ الاستحقاق</th>
                            <th scope="col" class="px-4 py-3">تاريخ الإنشاء</th>
                            <th scope="col" class="px-4 py-3 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="all-todos-body">
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <svg class="animate-spin h-8 w-8 mx-auto mb-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">جارٍ تحميل المهام...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <nav id="all-todos-pagination" class="hidden flex-col md:flex-row items-center justify-between gap-4 px-4 py-3 border-t border-gray-100 dark:border-gray-700" aria-label="pagination"></nav>
        </div>

    </div>
</main>

<script src="<?php echo BASE_URL; ?>admin/js/all-todos.js"></script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

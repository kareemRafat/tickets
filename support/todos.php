<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_employee_or_admin();

$db = getDBConnection();
$user_id = (int)$_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';

$employees = [];
try {
    $stmt = $db->query("SELECT e.id, e.name, e.role, b.name as branch_name FROM employees e LEFT JOIN branches b ON e.branch_id = b.id WHERE e.status = 'active' ORDER BY e.name ASC");
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch employees for todos: " . $e->getMessage());
}

$pageTitle = 'المهام';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-4 md:p-6 space-y-6 flex-1">
    <div>

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                    المهام
                </h1>
                <p class="mt-1 text-base text-gray-500 dark:text-gray-400">إدارة ومتابعة المهام اليومية المسندة إليك</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800/40 rounded-xl text-base font-semibold text-amber-700 dark:text-amber-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>المهام المعلقة: <strong id="pending-count">0</strong></span>
                </span>
                <a href="<?php echo BASE_URL; ?>support/index.php" class="px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">العودة للوحة التحكم</a>
            </div>
        </div>

        <!-- Toasts injected via JS -->

        <!-- Date Filter -->
        <div class="mb-6 flex items-center gap-3">
            <label for="todo-date-filter" class="text-sm font-semibold text-gray-700 dark:text-gray-300">تاريخ المهام:</label>
            <input type="date" id="todo-date-filter" value="<?php echo date('Y-m-d'); ?>" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        </div>

        <!-- Main Grid -->
        <div class="flex flex-col lg:flex-row gap-6">

            <!-- Left Column: Create Form -->
            <div class="lg:w-80 shrink-0">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            إنشاء مهمة جديدة
                        </h2>
                    </div>
                    <form id="todo-create-form" class="p-5 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div>
                            <label class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">الموظف</label>
                            <select name="assigned_to" required class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">اختر الموظف...</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo (int)$emp['id'] === $user_id ? 'selected' : ''; ?>>
                                        <?php
                                        echo htmlspecialchars($emp['name']);
                                        if (!empty($emp['branch_name']) && $emp['role'] !== 'admin') {
                                            echo ' ( ' . htmlspecialchars($emp['branch_name']) . ' )';
                                        }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">عنوان المهمة</label>
                            <input type="text" name="title" required maxlength="500" placeholder="اكتب عنوان المهمة..." class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">تاريخ الاستحقاق <span class="text-gray-400 dark:text-gray-500 font-normal">(اختياري)</span></label>
                            <input type="date" name="due_date" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 text-sm font-bold text-white bg-gradient-to-l from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl shadow-sm transition-all focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800">
                            ➕ إنشاء المهمة
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Todo Lists -->
            <div class="flex-1 min-w-0">
                <div id="todos-container"
                     data-list-url="<?php echo BASE_URL; ?>support/ajax/todos.php?action=list"
                     data-create-url="<?php echo BASE_URL; ?>support/ajax/todos.php"
                     data-toggle-url="<?php echo BASE_URL; ?>support/ajax/todos.php"
                     data-delete-url="<?php echo BASE_URL; ?>support/ajax/todos.php">
                    <div id="todos-list">
                        <div class="text-center py-16">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">جارٍ تحميل المهام...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>
<style>
    @keyframes slideDown {
        0% { transform: translate(-50%, -100%); opacity: 0; }
        100% { transform: translate(-50%, 0); opacity: 1; }
    }
</style>
<script src="<?php echo BASE_URL; ?>support/js/todos.js"></script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_employee_or_admin();

$db = getDBConnection();
$user_id = (int)$_SESSION['user_id'];
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';

$currentView = $_GET['view'] ?? 'assigned_to_me';
if (!in_array($currentView, ['assigned_to_me', 'created_by_me'])) {
    $currentView = 'assigned_to_me';
}

$employees = [];
try {
    $stmt = $db->query("SELECT e.id, e.name, e.role, b.name as branch_name FROM employees e LEFT JOIN branches b ON e.branch_id = b.id WHERE e.status = 'active' ORDER BY e.role != 'admin', b.name, e.name");
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
                <span class="inline-flex items-center gap-2 px-3 md:px-4 py-2 bg-amber-50 border border-amber-300 dark:bg-amber-900/20 dark:border-amber-800/40 rounded-xl text-sm md:text-base font-semibold text-amber-700 dark:text-amber-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>المهام المعلقة : <strong id="pending-count">0</strong></span>
                </span>
                <a href="<?php echo BASE_URL; ?>support/index.php" class="px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-400 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">العودة للوحة التحكم</a>
            </div>
        </div>

        <!-- Toasts injected via JS -->

        <!-- View Toggle -->
        <div class="flex mb-6">
            <div class="flex w-full md:inline-flex md:w-auto rounded-xl overflow-hidden border-2 border-gray-300 dark:border-gray-600 shadow-sm">
                <a href="?view=assigned_to_me" class="flex-1 md:flex-none px-4 md:px-8 py-2.5 text-xs md:text-sm font-bold transition-all text-center border-l border-gray-300 dark:border-gray-600 last:border-l-0 whitespace-nowrap <?php echo $currentView === 'assigned_to_me' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'; ?>">
                    <svg class="w-4 h-4 inline ml-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <span class="hidden md:inline">المهام المسندة إليّ</span>
                    <span class="md:hidden">مسندة إليّ</span>
                </a>
                <a href="?view=created_by_me" class="flex-1 md:flex-none px-4 md:px-8 py-2.5 text-xs md:text-sm font-bold transition-all text-center border-l border-gray-300 dark:border-gray-600 last:border-l-0 whitespace-nowrap <?php echo $currentView === 'created_by_me' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'; ?>">
                    <svg class="w-4 h-4 inline ml-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 16.604a1.875 1.875 0 01-1.07.603l-2.685.8.8-2.685a1.875 1.875 0 01.603-1.07L16.863 4.487zm0 0L19.5 7.125"/></svg>
                    <span class="hidden md:inline">المهام التي أنشأتها</span>
                    <span class="md:hidden">أنشأتها</span>
                </a>
            </div>
        </div>

        <!-- Filters Row -->
        <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="w-full">
                <label for="todo-date-filter" class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">تاريخ الاستحقاق</label>
                <input type="date" id="todo-date-filter" value="<?php echo date('Y-m-d'); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <?php if ($currentView === 'created_by_me'): ?>
            <div class="w-full">
                <label for="todo-assigned-to-filter" class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">الموظف</label>
                <select id="todo-assigned-to-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">الكل</option>
                    <?php foreach ($employees as $emp): ?>
                        <?php if ((int)$emp['id'] !== $user_id): ?>
                        <option value="<?php echo $emp['id']; ?>">
                            <?php echo htmlspecialchars($emp['name']); if (!empty($emp['branch_name']) && $emp['role'] !== 'admin') { echo ' ( ' . htmlspecialchars($emp['branch_name']) . ' )'; } ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-full">
                <label for="todo-search" class="block mb-1 text-xs font-medium text-gray-900 dark:text-white">بحث</label>
                <div class="flex gap-2">
                    <input type="text" id="todo-search" placeholder="ابحث عن عنوان المهمة..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
                    <button type="button" id="todo-reset-filters" class="px-3 py-1.5 text-sm font-bold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 whitespace-nowrap">إعادة تعيين</button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Grid -->
        <div class="flex flex-col lg:flex-row gap-6">

            <?php if ($currentView === 'assigned_to_me'): ?>
            <!-- Left Column: Create Form -->
            <div class="lg:w-80 shrink-0">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-300 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
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
                            <textarea name="title" required maxlength="500" rows="3" placeholder="اكتب عنوان المهمة..." class="w-full bg-white border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300">تاريخ الاستحقاق <span class="text-gray-400 dark:text-gray-500 font-normal">( اختياري )</span></label>
                            <input type="date" name="due_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <button type="submit" class="w-full px-4 py-2.5 text-sm font-bold text-white bg-gradient-to-l from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl shadow-sm transition-all focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800">
                            إنشاء المهمة
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Right Column: Todo Lists -->
            <div class="flex-1 min-w-0">
                <div id="todos-container"
                     data-current-view="<?php echo $currentView; ?>"
                     data-list-url="<?php echo BASE_URL; ?>support/ajax/todos.php?action=list&view=<?php echo $currentView; ?>"
                     data-create-url="<?php echo BASE_URL; ?>support/ajax/todos.php"
                     data-toggle-url="<?php echo BASE_URL; ?>support/ajax/todos.php"
                     data-edit-url="<?php echo BASE_URL; ?>support/ajax/todos.php">
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

<!-- Edit Todo Modal -->
<div id="edit-todo-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-900/50 dark:bg-gray-900/80">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 w-full max-w-lg mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">تعديل المهمة</h3>
            <button type="button" onclick="document.getElementById('edit-todo-modal').classList.add('hidden');document.getElementById('edit-todo-modal').classList.remove('flex');" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="edit-todo-form" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" id="edit-todo-id" name="id" value="">
            <div>
                <label class="block mb-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300">الموظف</label>
                <select id="edit-todo-assigned-to" name="assigned_to" required class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">اختر الموظف...</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>">
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
                <label class="block mb-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300">عنوان المهمة</label>
                <textarea id="edit-todo-title" name="title" required maxlength="500" rows="3" placeholder="اكتب عنوان المهمة..." class="w-full bg-white border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white resize-none"></textarea>
            </div>
            <div>
                <label class="block mb-1.5 text-sm font-semibold text-gray-700 dark:text-gray-300">تاريخ الاستحقاق <span class="text-gray-400 dark:text-gray-500 font-normal">(اختياري)</span></label>
                <input type="date" id="edit-todo-due-date" name="due_date" class="w-full bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-gradient-to-l from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl shadow-sm transition-all focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    حفظ التعديلات
                </button>
                <button type="button" onclick="document.getElementById('edit-todo-modal').classList.add('hidden');document.getElementById('edit-todo-modal').classList.remove('flex');" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes slideDown {
        0% {
            transform: translate(-50%, -100%);
            opacity: 0;
        }

        100% {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }
</style>
<script>var CURRENT_USER_ID = <?php echo (int)$_SESSION['user_id']; ?>;</script>
<script src="<?php echo BASE_URL; ?>support/js/todos.js"></script>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
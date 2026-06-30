<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';
require_once __DIR__ . '/../functions/pagination.php';

// Apply security headers
set_security_headers();

// Enforce admin privileges
require_admin();

$error_message = '';
unset($_SESSION['error']);
$db = getDBConnection();

// Process delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
    } elseif ($id === (int)$_SESSION['user_id']) {
        $error_message = 'عذراً، لا يمكنك حذف حسابك الحالي الذي تسجل الدخول به.';
    } elseif ($id <= 0) {
        $error_message = 'معرف الموظف غير صالح.';
    } else {
        try {
            // Fetch employee data before delete for audit log
            $stmt = $db->prepare("SELECT * FROM employees WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $employee = $stmt->fetch();
            
            if ($employee) {
                $delete = $db->prepare("DELETE FROM employees WHERE id = :id");
                $delete->execute(['id' => $id]);
                
                log_audit_action("حذف الموظف: {$employee['name']} (اسم المستخدم: {$employee['username']})", 'employees', $id, $employee, null);
                
                $_SESSION['success'] = 'تم حذف الموظف بنجاح من النظام.';
                header('Location: ' . BASE_URL . 'admin/employees.php');
                exit();
            } else {
                $error_message = 'الموظف المطلوب غير موجود بالأساس.';
            }
        } catch (PDOException $e) {
            $error_message = 'فشل حذف الموظف لوجود تذاكر دعم فني مرتبطة به.';
        }
    }
}

// Pagination and fetch
$pageParams = get_pagination_params(10);
$employees = [];
try {
    $countStmt = $db->query("SELECT COUNT(*) FROM employees");
    $totalEmployees = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT e.*, b.name as branch_name 
        FROM employees e 
        LEFT JOIN branches b ON e.branch_id = b.id 
        ORDER BY e.id ASC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $pageParams['perPage'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pageParams['offset'], PDO::PARAM_INT);
    $stmt->execute();
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch employees: " . $e->getMessage());
}

$pageTitle = 'إدارة الموظفين والإداريين';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content Area -->
<main class="p-6 space-y-6 flex-1">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                إدارة الموظفين
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                تسجيل وتعديل بيانات الإداريين وموظفي الدعم الفني وتوزيعهم على الفروع.
            </p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>admin/employee-add.php" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-xl shadow-sm dark:bg-blue-600 dark:hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                إضافة موظف جديد
            </a>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-base text-right text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">الاسم الكامل</th>
                        <th scope="col" class="px-6 py-4">اسم المستخدم</th>
                        <th scope="col" class="px-6 py-4">البريد الإلكتروني</th>
                        <th scope="col" class="px-6 py-4">الفرع</th>
                        <th scope="col" class="px-6 py-4">الدور الوظيفي</th>
                        <th scope="col" class="px-6 py-4">حالة التفعيل</th>
                        <th scope="col" class="px-6 py-4 text-left">خيارات التحكم</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا يوجد موظفون مسجلون بالنظام حالياً.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($employee['name']); ?></td>
                                <td class="px-6 py-4 font-mono text-sm"><?php echo htmlspecialchars($employee['username']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($employee['email'] ?? '—'); ?></td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($employee['branch_name'] ?? 'إدارة النظام '); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($employee['role'] === 'admin'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            مدير نظام
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            موظف دعم
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($employee['status'] === 'active'): ?>
                                        <span data-status-badge class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            نشط
                                        </span>
                                    <?php else: ?>
                                        <span data-status-badge class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            معطل
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-left">
                                    <?php if ($employee['id'] !== (int)$_SESSION['user_id']): ?>
                                        <div class="inline-flex rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                                            <button data-toggle-status
                                                    data-employee-id="<?php echo $employee['id']; ?>"
                                                    data-employee-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                    data-current-status="<?php echo $employee['status']; ?>"
                                                    data-csrf-token="<?php echo generate_csrf_token(); ?>"
                                                    data-url="<?php echo BASE_URL; ?>admin/functions/toggle-employee-status.php"
                                                    class="px-3 py-2 text-xs font-medium text-white <?php echo $employee['status'] === 'active' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?> transition-colors focus:z-10">
                                                <svg class="inline w-3.5 h-3.5 ml-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                <?php echo $employee['status'] === 'active' ? 'تعطيل' : 'تفعيل'; ?>
                                            </button>
                                            <a href="<?php echo BASE_URL; ?>admin/employee-edit.php?id=<?php echo $employee['id']; ?>" class="px-3 py-2 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 transition-colors border-x border-blue-400 focus:z-10">
                                                <svg class="inline w-3.5 h-3.5 ml-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                تعديل
                                            </a>
                                            <button data-modal-target="delete-modal-<?php echo $employee['id']; ?>" data-modal-toggle="delete-modal-<?php echo $employee['id']; ?>" class="px-3 py-2 text-xs font-medium text-white bg-red-500 hover:bg-red-600 transition-colors focus:z-10">
                                                <svg class="inline w-3.5 h-3.5 ml-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                حذف
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 italic">حسابك الحالي</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php render_pagination($totalEmployees, 10); ?>
    </div>

    <!-- Delete Warning Modals -->
    <?php foreach ($employees as $employee): ?>
        <?php if ($employee['id'] !== (int)$_SESSION['user_id']): ?>
            <div id="delete-modal-<?php echo $employee['id']; ?>" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative w-full max-w-md max-h-full">
                    <div class="relative bg-white rounded-2xl shadow dark:bg-gray-700 text-right">
                        <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="delete-modal-<?php echo $employee['id']; ?>">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">إغلاق</span>
                        </button>
                        <div class="p-6 text-center">
                            <svg class="mx-auto mb-4 text-red-500 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">هل أنت متأكد من رغبتك في حذف حساب الموظف <strong><?php echo htmlspecialchars($employee['name']); ?></strong>؟</h3>
                            <p class="text-sm text-red-600 dark:text-red-400 mb-6">تنبيه: لا يمكن التراجع عن هذه الخطوة، وسيتم مسح كافة سجلات الموظف وصلاحياته.</p>
                            <form action="" method="POST" class="inline-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-xl text-sm inline-flex items-center px-5 py-2.5 text-center transition-all">
                                    نعم، احذف الموظف
                                </button>
                            </form>
                            <button data-modal-hide="delete-modal-<?php echo $employee['id']; ?>" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-xl border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 transition-all">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Toggle Status Confirmation Modal -->
    <div id="toggle-status-modal" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-sm max-h-full">
            <div class="relative bg-white rounded-xl shadow dark:bg-gray-700 p-6 text-center">
                <button data-modal-close type="button" class="absolute top-3 left-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <svg class="mx-auto mb-3 w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <h3 data-modal-text class="mb-4 text-base font-semibold text-gray-800 dark:text-gray-200"></h3>
                <div class="flex justify-center gap-3">
                    <button data-modal-confirm type="button" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"></button>
                    <button data-modal-cancel type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 rounded-lg transition-colors">إلغاء</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="<?php echo BASE_URL; ?>admin/ajax/employee-status.js"></script>
<?php
if (!empty($error_message)) {
    $_SESSION['error'] = $error_message;
}
require_once __DIR__ . '/../includes/footer.php';
?>

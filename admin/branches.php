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

// Process Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
    } else {
        if ($action === 'add') {
            $name = xss_clean($_POST['name'] ?? '');
            $code = xss_clean($_POST['code'] ?? '');
            $status = xss_clean($_POST['status'] ?? 'active');
            
            if (empty($name) || empty($code)) {
                $error_message = 'يرجى تعبئة جميع الحقول الإلزامية.';
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO branches (name, code, status) VALUES (:name, :code, :status)");
                    $stmt->execute([
                        'name' => $name,
                        'code' => strtoupper($code),
                        'status' => $status
                    ]);
                    $new_id = $db->lastInsertId();
                    
                    log_audit_action("إضافة فرع جديد: {$name} ({$code})", 'branches', $new_id, null, ['name' => $name, 'code' => $code, 'status' => $status]);
                    
                    $_SESSION['success'] = 'تم إضافة الفرع الجديد بنجاح.';
                    header('Location: ' . BASE_URL . 'admin/branches.php');
                    exit();
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error_message = 'رمز الفرع (Code) مسجل بالفعل لفرع آخر.';
                    } else {
                        $error_message = 'حدث خطأ أثناء حفظ البيانات في قاعدة البيانات.';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = xss_clean($_POST['name'] ?? '');
            $code = xss_clean($_POST['code'] ?? '');
            $status = xss_clean($_POST['status'] ?? 'active');
            
            if (empty($name) || empty($code) || $id <= 0) {
                $error_message = 'يرجى تعبئة الحقول المطلوبة لتعديل الفرع.';
            } else {
                try {
                    // Fetch old state for audit trails
                    $old_stmt = $db->prepare("SELECT * FROM branches WHERE id = :id");
                    $old_stmt->execute(['id' => $id]);
                    $old_branch = $old_stmt->fetch();
                    
                    if ($old_branch) {
                        $stmt = $db->prepare("UPDATE branches SET name = :name, code = :code, status = :status WHERE id = :id");
                        $stmt->execute([
                            'name' => $name,
                            'code' => strtoupper($code),
                            'status' => $status,
                            'id' => $id
                        ]);
                        
                        log_audit_action("تعديل الفرع: {$name} ({$code})", 'branches', $id, $old_branch, ['name' => $name, 'code' => $code, 'status' => $status]);
                        
                        $_SESSION['success'] = 'تم تعديل بيانات الفرع بنجاح.';
                        header('Location: ' . BASE_URL . 'admin/branches.php');
                        exit();
                    } else {
                        $error_message = 'الفرع المستهدف غير موجود.';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error_message = 'رمز الفرع الجديد مسجل بالفعل لفرع آخر.';
                    } else {
                        $error_message = 'حدث خطأ في قاعدة البيانات أثناء تحديث الفرع.';
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error_message = 'رقم معرف الفرع غير صالح.';
            } else {
                try {
                    $old_stmt = $db->prepare("SELECT * FROM branches WHERE id = :id");
                    $old_stmt->execute(['id' => $id]);
                    $old_branch = $old_stmt->fetch();
                    
                    if ($old_branch) {
                        $stmt = $db->prepare("DELETE FROM branches WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                        
                        log_audit_action("حذف الفرع: {$old_branch['name']}", 'branches', $id, $old_branch, null);
                        
                        $_SESSION['success'] = 'تم حذف الفرع بنجاح من النظام.';
                        header('Location: ' . BASE_URL . 'admin/branches.php');
                        exit();
                    } else {
                        $error_message = 'الفرع الذي تحاول حذفه غير موجود.';
                    }
                } catch (PDOException $e) {
                    $error_message = 'لا يمكن حذف هذا الفرع لوجود سجلات موظفين أو تذاكر مرتبطة به.';
                }
            }
        }
    }
}

// Pagination and fetch
$pageParams = get_pagination_params(10);
$branches = [];
try {
    $countStmt = $db->query("SELECT COUNT(*) FROM branches");
    $totalBranches = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM branches ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $pageParams['perPage'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pageParams['offset'], PDO::PARAM_INT);
    $stmt->execute();
    $branches = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Branches list fetch error: " . $e->getMessage());
}

$pageTitle = 'إدارة الفروع المعتمدة';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content Area -->
<main class="p-6 space-y-6 flex-1">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                إدارة الفروع
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                إضافة وتعديل وحذف الفروع المؤسسية وتتبع تفعيلها بالنظام.
            </p>
        </div>
        <div>
            <!-- Add Branch Modal Toggle -->
            <button data-modal-target="add-branch-modal" data-modal-toggle="add-branch-modal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-xl shadow-sm dark:bg-blue-600 dark:hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                إضافة فرع جديد
            </button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">رقم التعريف</th>
                        <th scope="col" class="px-6 py-4">اسم الفرع</th>
                        <th scope="col" class="px-6 py-4">رمز الفرع (Code)</th>
                        <th scope="col" class="px-6 py-4">حالة التفعيل</th>
                        <th scope="col" class="px-6 py-4">تاريخ الإنشاء</th>
                        <th scope="col" class="px-6 py-4 text-left">خيارات التحكم</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($branches)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا يوجد فروع مسجلة بالنظام حتى الآن.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">#<?php echo $branch['id']; ?></td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($branch['name']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-mono font-bold rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                        <?php echo htmlspecialchars($branch['code']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($branch['status'] === 'active'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            نشط
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            غير نشط
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <?php echo date('Y-m-d H:i', strtotime($branch['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-left flex items-center justify-end gap-2">
                                    <!-- Edit Trigger Button -->
                                    <button data-modal-target="edit-modal-<?php echo $branch['id']; ?>" data-modal-toggle="edit-modal-<?php echo $branch['id']; ?>" class="font-medium text-blue-600 dark:text-blue-500 hover:underline px-3 py-1.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                        تعديل
                                    </button>
                                    
                                    <!-- Delete Trigger Button -->
                                    <button data-modal-target="delete-modal-<?php echo $branch['id']; ?>" data-modal-toggle="delete-modal-<?php echo $branch['id']; ?>" class="font-medium text-red-600 dark:text-red-500 hover:underline px-3 py-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                        حذف
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php render_pagination($totalBranches, 10); ?>
    </div>

    <!-- Modals Section -->

    <!-- Add Branch Modal -->
    <div id="add-branch-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="add-branch-modal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    <span class="sr-only">إغلاق</span>
                </button>
                <div class="px-6 py-6 lg:px-8 text-right">
                    <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">إضافة فرع جديد</h3>
                    <form class="space-y-4" action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم الفرع</label>
                            <input type="text" name="name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white" placeholder="مثال: فرع القاهرة" required>
                        </div>
                        <div>
                            <label for="code" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">رمز الفرع المختصر (Code)</label>
                            <input type="text" name="code" id="code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white font-mono uppercase" placeholder="مثال: CAI" required>
                        </div>
                        <div>
                            <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الحالة</label>
                            <select name="status" id="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">حفظ الفرع</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit & Delete Modals loops -->
    <?php foreach ($branches as $branch): ?>
        <!-- Edit Branch Modal -->
        <div id="edit-modal-<?php echo $branch['id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="edit-modal-<?php echo $branch['id']; ?>">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        <span class="sr-only">إغلاق</span>
                    </button>
                    <div class="px-6 py-6 lg:px-8 text-right">
                        <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">تعديل الفرع: <?php echo htmlspecialchars($branch['name']); ?></h3>
                        <form class="space-y-4" action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                            
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم الفرع</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($branch['name']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">رمز الفرع المختصر (Code)</label>
                                <input type="text" name="code" value="<?php echo htmlspecialchars($branch['code']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono uppercase" required>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الحالة</label>
                                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="active" <?php echo $branch['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="inactive" <?php echo $branch['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">تحديث الفرع</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Branch Warning Modal -->
        <div id="delete-modal-<?php echo $branch['id']; ?>" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-2xl shadow dark:bg-gray-700 text-right">
                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-850 dark:hover:text-white" data-modal-hide="delete-modal-<?php echo $branch['id']; ?>">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">إغلاق</span>
                    </button>
                    <div class="p-6 text-center">
                        <svg class="mx-auto mb-4 text-red-500 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">هل أنت متأكد من رغبتك في حذف فرع <strong><?php echo htmlspecialchars($branch['name']); ?></strong>؟</h3>
                        <p class="text-xs text-red-600 dark:text-red-400 mb-6">تنبيه: سيؤدي حذف هذا الفرع إلى حذف كافة السجلات التابعة للطلاب وإلغاء ارتباط الموظفين به تلقائياً.</p>
                        <form action="" method="POST" class="inline-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-xl text-sm inline-flex items-center px-5 py-2.5 text-center transition-all">
                                نعم، احذف الفرع
                            </button>
                        </form>
                        <button data-modal-hide="delete-modal-<?php echo $branch['id']; ?>" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-xl border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-650 transition-all">
                            لا، إلغاء العملية
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<?php
if (!empty($error_message)) {
    $_SESSION['error'] = $error_message;
}
require_once __DIR__ . '/../includes/footer.php';
?>

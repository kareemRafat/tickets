<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_admin();

$error_message = '';
$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
    } else {
        if ($action === 'add') {
            $name = xss_clean($_POST['name'] ?? '');
            $type = xss_clean($_POST['type'] ?? 'support');
            $status = xss_clean($_POST['status'] ?? 'active');

            if (empty($name)) {
                $error_message = 'يرجى إدخال اسم التصنيف.';
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO categories (name, type, status) VALUES (:name, :type, :status)");
                    $stmt->execute(['name' => $name, 'type' => $type, 'status' => $status]);
                    $new_id = $db->lastInsertId();

                    log_audit_action("إضافة تصنيف جديد: {$name} (النوع: {$type})", 'categories', $new_id, null, ['name' => $name, 'type' => $type, 'status' => $status]);

                    $_SESSION['success'] = 'تم إضافة التصنيف الجديد بنجاح.';
                    header('Location: ' . BASE_URL . 'admin/categories.php');
                    exit();
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ أثناء حفظ التصنيف في قاعدة البيانات.';
                    error_log("Failed to add category: " . $e->getMessage());
                }
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = xss_clean($_POST['name'] ?? '');
            $type = xss_clean($_POST['type'] ?? 'support');
            $status = xss_clean($_POST['status'] ?? 'active');

            if (empty($name) || $id <= 0) {
                $error_message = 'يرجى تعبئة الحقول المطلوبة لتعديل التصنيف.';
            } else {
                try {
                    $old_stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
                    $old_stmt->execute(['id' => $id]);
                    $old_cat = $old_stmt->fetch();

                    if ($old_cat) {
                        $stmt = $db->prepare("UPDATE categories SET name = :name, type = :type, status = :status WHERE id = :id");
                        $stmt->execute(['name' => $name, 'type' => $type, 'status' => $status, 'id' => $id]);

                        log_audit_action("تعديل التصنيف: {$name}", 'categories', $id, $old_cat, ['name' => $name, 'type' => $type, 'status' => $status]);

                        $_SESSION['success'] = 'تم تعديل التصنيف بنجاح.';
                        header('Location: ' . BASE_URL . 'admin/categories.php');
                        exit();
                    } else {
                        $error_message = 'التصنيف المستهدف غير موجود.';
                    }
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ في قاعدة البيانات أثناء تحديث التصنيف.';
                    error_log("Failed to update category: " . $e->getMessage());
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error_message = 'رقم معرف التصنيف غير صالح.';
            } else {
                try {
                    $old_stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
                    $old_stmt->execute(['id' => $id]);
                    $old_cat = $old_stmt->fetch();

                    if ($old_cat) {
                        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
                        $stmt->execute(['id' => $id]);

                        log_audit_action("حذف التصنيف: {$old_cat['name']}", 'categories', $id, $old_cat, null);

                        $_SESSION['success'] = 'تم حذف التصنيف بنجاح من النظام.';
                        header('Location: ' . BASE_URL . 'admin/categories.php');
                        exit();
                    } else {
                        $error_message = 'التصنيف الذي تحاول حذفه غير موجود.';
                    }
                } catch (PDOException $e) {
                    $error_message = 'لا يمكن حذف هذا التصنيف لوجود تذاكر مرتبطة به.';
                }
            }
        }
    }
}

$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY type ASC, id ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories list fetch error: " . $e->getMessage());
}

$pageTitle = 'إدارة التصنيفات والأقسام';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                إدارة التصنيفات
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                إضافة وتعديل وحذف تصنيفات التذاكر الخاصة بالدعم الفني والطلاب.
            </p>
        </div>
        <div>
            <button data-modal-target="add-category-modal" data-modal-toggle="add-category-modal" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-xl shadow-sm dark:bg-blue-600 dark:hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                إضافة تصنيف جديد
            </button>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-100 dark:border-red-900/50 flex items-center gap-2" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">الاسم</th>
                        <th scope="col" class="px-6 py-4">النوع</th>
                        <th scope="col" class="px-6 py-4">الحالة</th>
                        <th scope="col" class="px-6 py-4">تاريخ الإنشاء</th>
                        <th scope="col" class="px-6 py-4 text-left">خيارات التحكم</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                لا يوجد تصنيفات مسجلة بالنظام حتى الآن.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($cat['type'] === 'support'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            دعم فني
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                            طلاب
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($cat['status'] === 'active'): ?>
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
                                    <?php echo date('Y-m-d H:i', strtotime($cat['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-left flex items-center justify-end gap-2">
                                    <button data-modal-target="edit-cat-modal-<?php echo $cat['id']; ?>" data-modal-toggle="edit-cat-modal-<?php echo $cat['id']; ?>" class="font-medium text-blue-600 dark:text-blue-500 hover:underline px-3 py-1.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                        تعديل
                                    </button>
                                    <button data-modal-target="delete-cat-modal-<?php echo $cat['id']; ?>" data-modal-toggle="delete-cat-modal-<?php echo $cat['id']; ?>" class="font-medium text-red-600 dark:text-red-500 hover:underline px-3 py-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                        حذف
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="add-category-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="add-category-modal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    <span class="sr-only">إغلاق</span>
                </button>
                <div class="px-6 py-6 lg:px-8 text-right">
                    <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">إضافة تصنيف جديد</h3>
                    <form class="space-y-4" action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="add">

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم التصنيف</label>
                            <input type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white" placeholder="مثال: استفسارات مالية" required>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">نوع التصنيف</label>
                            <select name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="support">دعم فني (للموظفين)</option>
                                <option value="student">طلاب (للشكاوى الطلابية)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الحالة</label>
                            <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">حفظ التصنيف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit & Delete Modals -->
    <?php foreach ($categories as $cat): ?>
        <div id="edit-cat-modal-<?php echo $cat['id']; ?>" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-2xl shadow-xl dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="edit-cat-modal-<?php echo $cat['id']; ?>">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        <span class="sr-only">إغلاق</span>
                    </button>
                    <div class="px-6 py-6 lg:px-8 text-right">
                        <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">تعديل التصنيف: <?php echo htmlspecialchars($cat['name']); ?></h3>
                        <form class="space-y-4" action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">

                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم التصنيف</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">نوع التصنيف</label>
                                <select name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="support" <?php echo $cat['type'] === 'support' ? 'selected' : ''; ?>>دعم فني (للموظفين)</option>
                                    <option value="student" <?php echo $cat['type'] === 'student' ? 'selected' : ''; ?>>طلاب (للشكاوى الطلابية)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الحالة</label>
                                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="active" <?php echo $cat['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="inactive" <?php echo $cat['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700">تحديث التصنيف</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="delete-cat-modal-<?php echo $cat['id']; ?>" tabindex="-1" class="fixed top-0 left-0 right-0 z-50 hidden p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative bg-white rounded-2xl shadow dark:bg-gray-700 text-right">
                    <button type="button" class="absolute top-3 left-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 right-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white" data-modal-hide="delete-cat-modal-<?php echo $cat['id']; ?>">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">إغلاق</span>
                    </button>
                    <div class="p-6 text-center">
                        <svg class="mx-auto mb-4 text-red-500 w-12 h-12" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">هل أنت متأكد من رغبتك في حذف التصنيف <strong><?php echo htmlspecialchars($cat['name']); ?></strong>؟</h3>
                        <p class="text-xs text-red-600 dark:text-red-400 mb-6">تنبيه: لا يمكن التراجع عن هذه الخطوة، وقد يؤثر حذف التصنيف على التذاكر المرتبطة به.</p>
                        <form action="" method="POST" class="inline-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                            <button type="submit" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-xl text-sm inline-flex items-center px-5 py-2.5 text-center transition-all">
                                نعم، احذف التصنيف
                            </button>
                        </form>
                        <button data-modal-hide="delete-cat-modal-<?php echo $cat['id']; ?>" type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-xl border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 transition-all">
                            إلغاء
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_admin();

$error_message = '';
$db = getDBConnection();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = 'معرف الموظف غير صالح.';
    header('Location: ' . BASE_URL . 'admin/employees.php');
    exit();
}

$stmt = $db->prepare("SELECT * FROM employees WHERE id = :id");
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch();

if (!$employee) {
    $_SESSION['error'] = 'الموظف المطلوب غير موجود.';
    header('Location: ' . BASE_URL . 'admin/employees.php');
    exit();
}

$branches = [];
try {
    $stmt = $db->query("SELECT * FROM branches WHERE status = 'active' ORDER BY id ASC");
    $branches = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch active branches: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = xss_clean($_POST['name'] ?? '');
    $username = xss_clean($_POST['username'] ?? '');
    $email = xss_clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = xss_clean($_POST['phone'] ?? '');
    $branch_id = $_POST['branch_id'] ?? '';
    $role = xss_clean($_POST['role'] ?? 'employee');
    $status = xss_clean($_POST['status'] ?? 'active');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
    } elseif (empty($name) || empty($username)) {
        $error_message = 'الاسم واسم المستخدم حقول إجبارية.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error_message = 'اسم المستخدم يجب أن يحتوي على أحرف إنجليزية وأرقام وعلامة _ فقط، وأن يتراوح طوله بين 3 إلى 30 رمزاً.';
    } else {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE username = :username AND id != :id");
            $stmt->execute(['username' => $username, 'id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = 'اسم المستخدم غير متاح، يرجى اختيار اسم مستخدم آخر.';
            } elseif (!empty($email)) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE email = :email AND id != :id");
                $stmt->execute(['email' => $email, 'id' => $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = 'البريد الإلكتروني المدخل مسجل بالفعل لموظف آخر.';
                }
            }

            if (empty($error_message)) {
                $branch_value = ($branch_id === '') ? null : (int)$branch_id;
                $old_values = $employee;

                if (!empty($password)) {
                    $hashed_password = security_hash_password($password);
                    $update = $db->prepare("
                        UPDATE employees SET branch_id = :branch_id, name = :name, username = :username, email = :email, password = :password, phone = :phone, role = :role, status = :status
                        WHERE id = :id
                    ");
                    $update->execute([
                        'branch_id' => $branch_value, 'name' => $name, 'username' => $username, 'email' => empty($email) ? null : $email,
                        'password' => $hashed_password, 'phone' => empty($phone) ? null : $phone, 'role' => $role, 'status' => $status, 'id' => $id
                    ]);
                } else {
                    $update = $db->prepare("
                        UPDATE employees SET branch_id = :branch_id, name = :name, username = :username, email = :email, phone = :phone, role = :role, status = :status
                        WHERE id = :id
                    ");
                    $update->execute([
                        'branch_id' => $branch_value, 'name' => $name, 'username' => $username, 'email' => empty($email) ? null : $email,
                        'phone' => empty($phone) ? null : $phone, 'role' => $role, 'status' => $status, 'id' => $id
                    ]);
                }

                log_audit_action("تعديل بيانات الموظف: {$name}", 'employees', $id, $old_values, ['branch_id' => $branch_value, 'name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone, 'role' => $role, 'status' => $status, 'password_changed' => !empty($password)]);

                $_SESSION['success'] = 'تم تحديث بيانات الموظف بنجاح.';
                header('Location: ' . BASE_URL . 'admin/employees.php');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ في قاعدة البيانات أثناء تحديث بيانات الموظف.';
            error_log("Failed to update employee: " . $e->getMessage());
        }
    }
}

$pageTitle = 'تعديل بيانات الموظف';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                تعديل بيانات الموظف
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">تحديث بيانات وصلاحيات الحساب الإداري أو موظف الدعم الفني.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>admin/employees.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">
            عودة للقائمة
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-100 dark:border-red-900/50 flex items-center gap-2" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 max-w-2xl">
        <form class="space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الاسم الكامل</label>
                    <input type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="الاسم ثلاثي أو رباعي" required value="<?php echo htmlspecialchars($employee['name']); ?>">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم المستخدم (باللغة الإنجليزية)</label>
                    <input type="text" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="مثال: ahmad_ali" required value="<?php echo htmlspecialchars($employee['username']); ?>">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="name@example.com" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">كلمة المرور (اتركه فارغاً للإبقاء على الحالية)</label>
                    <input type="password" name="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="••••••••">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">رقم الهاتف (اختياري)</label>
                    <input type="text" name="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="01XXXXXXXXX" value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الصلاحيات والوظيفة</label>
                    <select name="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="employee" <?php echo $employee['role'] === 'employee' ? 'selected' : ''; ?>>موظف دعم فني (صلاحيات الفرع فقط)</option>
                        <option value="admin" <?php echo $employee['role'] === 'admin' ? 'selected' : ''; ?>>مدير نظام (صلاحية كاملة)</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الفرع التابع له</label>
                    <select name="branch_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">إدارة عامة (بدون فرع)</option>
                        <?php foreach ($branches as $br): ?>
                            <option value="<?php echo $br['id']; ?>" <?php echo $employee['branch_id'] == $br['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($br['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">حالة الحساب</label>
                    <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>نشط (مسموح بالدخول)</option>
                        <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>معطل (محظور من الدخول)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700">حفظ التغييرات</button>
        </form>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

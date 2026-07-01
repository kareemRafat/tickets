<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

// Apply security headers
set_security_headers();

// Enforce admin privileges
require_admin();

$error_message = '';
unset($_SESSION['error']);
$db = getDBConnection();

// Fetch branches for dropdown
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
    } elseif (empty($name) || empty($username) || empty($password)) {
        $error_message = 'الاسم واسم المستخدم وكلمة المرور حقول إجبارية.';
    } else {
        // Validate username formatting
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error_message = 'اسم المستخدم يجب أن يحتوي على أحرف إنجليزية وأرقام وعلامة _ فقط، وأن يتراوح طوله بين 3 إلى 30 رمزاً.';
        } else {
            try {
                // Check if username already exists
                $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE username = :username");
                $stmt->execute(['username' => $username]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = 'اسم المستخدم غير متاح، يرجى اختيار اسم مستخدم آخر.';
                } else {
                    // Check if email already exists (if email is provided)
                    if (!empty($email)) {
                        $stmt = $db->prepare("SELECT COUNT(*) FROM employees WHERE email = :email");
                        $stmt->execute(['email' => $email]);
                        if ($stmt->fetchColumn() > 0) {
                            $error_message = 'البريد الإلكتروني المدخل مسجل بالفعل لموظف آخر.';
                        }
                    }

                    if (empty($error_message)) {
                        // Secure password hashing
                        $hashed_password = security_hash_password($password);
                        $branch_value = ($branch_id === '') ? null : (int)$branch_id;

                        $insert = $db->prepare("
                            INSERT INTO employees (branch_id, name, username, email, password, phone, role, status) 
                            VALUES (:branch_id, :name, :username, :email, :password, :phone, :role, :status)
                        ");
                        $insert->execute([
                            'branch_id' => $branch_value,
                            'name' => $name,
                            'username' => $username,
                            'email' => empty($email) ? null : $email,
                            'password' => $hashed_password,
                            'phone' => empty($phone) ? null : $phone,
                            'role' => $role,
                            'status' => $status
                        ]);
                        $new_emp_id = $db->lastInsertId();

                        log_audit_action(
                            "إضافة موظف جديد باسم: {$name} ودور: {$role}",
                            'employees',
                            $new_emp_id,
                            null,
                            ['branch_id' => $branch_value, 'name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone, 'role' => $role, 'status' => $status]
                        );

                        $_SESSION['success'] = 'تم إضافة الموظف الجديد بنجاح.';
                        header('Location: ' . BASE_URL . 'admin/employees.php');
                        exit();
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'حدث خطأ في قاعدة البيانات أثناء تسجيل الموظف.';
                error_log("Failed to insert employee: " . $e->getMessage());
            }
        }
    }
}

$pageTitle = 'إضافة موظف جديد';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content Area -->
<main class="p-6 space-y-6 flex-1">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                إضافة موظف جديد
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">سجل حساباً جديداً لإداري أو موظف دعم فني وحدد صلاحياته.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>admin/employees.php" class="hidden sm:inline-flex px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">
            عودة للقائمة
        </a>
    </div>
    
    <!-- Fixed Back Button (Mobile) -->
    <div class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg">
        <a href="<?php echo BASE_URL; ?>admin/employees.php" class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            عودة للقائمة
        </a>
    </div>

    <!-- Form Container -->
    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 w-full">
        <form class="space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الاسم الكامل</label>
                    <input type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="الاسم ثلاثي أو رباعي" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <!-- Username -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم المستخدم (باللغة الإنجليزية)</label>
                    <input type="text" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="مثال: ahmad_ali" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <!-- Email -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-gray-900 dark:text-white">كلمة المرور الحساب</label>
                        <button type="button" class="toggle-password-btn flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-bold" data-target="password-add">
                            <span class="pw-show flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                إظهار كلمة المرور
                            </span>
                            <span class="pw-hide hidden flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                                إخفاء كلمة المرور
                            </span>
                        </button>
                    </div>
                    <input type="password" name="password" id="password-add" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="••••••••" required>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">رقم الهاتف (اختياري)</label>
                    <input type="text" name="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="01XXXXXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <!-- Role -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الصلاحيات والوظيفة</label>
                    <select name="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="employee" <?php echo (isset($_POST['role']) && $_POST['role'] === 'employee') ? 'selected' : ''; ?>>موظف دعم فني (صلاحيات الفرع فقط)</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>مدير نظام (صلاحية كاملة)</option>
                    </select>
                </div>

                <!-- Branch -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الفرع التابع له</label>
                    <select name="branch_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">إدارة عامة (بدون فرع)</option>
                        <?php foreach ($branches as $br): ?>
                            <option value="<?php echo $br['id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $br['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($br['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">حالة الحساب</label>
                    <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>نشط (مسموح بالدخول)</option>
                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>معطل (محظور من الدخول)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700">حفظ الموظف</button>
        </form>
    </div>

    <!-- Mobile bottom padding to account for fixed button -->
    <div class="sm:hidden h-16"></div>
</main>

<?php
if (!empty($error_message)) {
    $_SESSION['error'] = $error_message;
}
require_once __DIR__ . '/../includes/footer.php';
?>
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

// Apply security headers
set_security_headers();

// Enforce admin privileges
require_admin();

$error_message = '';
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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                إضافة موظف جديد
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">سجل حساباً جديداً لإداري أو موظف دعم فني وحدد صلاحياته.</p>
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

    <!-- Form Container -->
    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 max-w-2xl">
        <form class="space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الاسم الكامل</label>
                    <input type="text" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="الاسم ثلاثي أو رباعي" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>

                <!-- Username -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم المستخدم (باللغة الإنجليزية)</label>
                    <input type="text" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="مثال: ahmad_ali" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <!-- Email -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <!-- Password -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">كلمة المرور الحساب</label>
                    <input type="password" name="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="••••••••" required>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">رقم الهاتف (اختياري)</label>
                    <input type="text" name="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="01XXXXXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
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
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

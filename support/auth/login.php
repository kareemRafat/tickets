<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../functions/remember_me.php';

// Apply security headers
set_security_headers();

// If already logged in as employee, redirect to support dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'employee') {
    header('Location: ' . BASE_URL . 'support/index.php');
    exit();
}

// Check for remember-me auto-login
if (process_employee_remember_login()) {
    $_SESSION['success'] = 'مرحباً بعودتك! تم تسجيل الدخول تلقائياً.';
    header('Location: ' . BASE_URL . 'support/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = xss_clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $_SESSION['error'] = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة.';
    } elseif (empty($username) || empty($password)) {
        $_SESSION['error'] = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } elseif (!is_login_allowed($username)) {
        $_SESSION['error'] = 'تم حظر محاولات تسجيل الدخول مؤقتاً لكثرة المحاولات الخاطئة. يرجى المحاولة بعد 15 دقيقة.';
    } else {
        try {
            $db = getDBConnection();
            // Query for support employee account
            $stmt = $db->prepare("
                SELECT * FROM employees 
                WHERE (username = :username OR email = :email) 
                  AND role = 'employee' 
                  AND status = 'active'
            ");
            $stmt->execute([
                'username' => $username,
                'email' => $username
            ]);
            $user = $stmt->fetch();

            if ($user && security_verify_password($password, $user['password'])) {
                // Clear any session leftover
                session_unset();

                // Succeeded
                log_login_attempt($username, true);

                // Regenerate session to prevent fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_branch_id'] = $user['branch_id'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // New CSRF token for the authenticated session

                // Update last login
                $update = $db->prepare("UPDATE employees SET last_login_at = NOW() WHERE id = :id");
                $update->execute(['id' => $user['id']]);

                // Create remember-me token if requested
                if (!empty($_POST['remember_me'])) {
                    handle_employee_remember_login($user['id']);
                }

                $_SESSION['success'] = 'مرحباً بك! تم تسجيل الدخول بنجاح كموظف دعم فني.';
                header('Location: ' . BASE_URL . 'support/index.php');
                exit();
            } else {
                // Failed
                log_login_attempt($username, false);
                $_SESSION['error'] = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'حدث خطأ غير متوقع بالخادم. يرجى المحاولة لاحقاً.';
            error_log("Support employee login database error: " . $e->getMessage());
        }
    }
}

$hide_navbar = true;
$pageTitle = 'تسجيل دخول موظفي الدعم الفني';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Wrapper mimicking sidebar wrapper layout but centered for login -->
<div class="flex-1 flex flex-col min-h-screen justify-center items-center bg-gray-50 dark:bg-gray-900 pt-20 px-4">
    <div class="w-full max-w-md p-6 space-y-6 bg-white rounded-2xl border border-gray-100 shadow-xl dark:bg-gray-800 dark:border-gray-700">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">بوابة موظفي الدعم الفني</h2>
            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">سجل الدخول لإدارة ومتابعة تذاكر الدعم الفني للفروع</p>
        </div>

        <form class="space-y-4" action="" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <!-- Username/Email Field -->
            <div>
                <label for="username" class="block mb-2 text-base font-medium text-gray-900 dark:text-white">اسم المستخدم أو البريد الإلكتروني</label>
                <input type="text" name="username" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="employee" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <!-- Password Field -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="text-base font-medium text-gray-900 dark:text-white">كلمة المرور</label>
                    <button type="button" class="toggle-password-btn flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 font-bold" data-target="password">
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
                <input type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="••••••••" required>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="flex items-center">
                <input type="checkbox" name="remember_me" id="remember_me" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
                <label for="remember_me" class="mr-2 text-base font-medium text-gray-900 dark:text-gray-300">تذكرني</label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-base px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-all shadow-md">دخول النظام</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
<?php
require_once __DIR__ . '/../bootstrap.php';

// Apply security headers
set_security_headers();

// If already logged in as admin, redirect to admin dashboard
if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = xss_clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة.';
    } elseif (empty($username) || empty($password)) {
        $error_message = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } elseif (!is_login_allowed($username)) {
        $error_message = 'تم حظر محاولات تسجيل الدخول مؤقتاً لكثرة المحاولات الخاطئة. يرجى المحاولة بعد 15 دقيقة.';
    } else {
        try {
            $db = getDBConnection();
            // Query for administrative account
            $stmt = $db->prepare("
                SELECT * FROM employees 
                WHERE (username = :username OR email = :email) 
                  AND role = 'admin' 
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
                
                $_SESSION['success'] = 'مرحباً بك! تم تسجيل الدخول بنجاح كمدير للنظام.';
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit();
            } else {
                // Failed
                log_login_attempt($username, false);
                $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ غير متوقع بالخادم. يرجى المحاولة لاحقاً.';
            error_log("Admin login database error: " . $e->getMessage());
        }
    }
}

$pageTitle = 'تسجيل دخول الإدارة';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Wrapper mimicking sidebar wrapper layout but centered for login -->
<div class="flex-1 flex flex-col min-h-screen justify-center items-center bg-gray-50 dark:bg-gray-900 pt-20 px-4">
    <div class="w-full max-w-md p-6 space-y-6 bg-white rounded-2xl border border-gray-100 shadow-xl dark:bg-gray-800 dark:border-gray-700">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">بوابة الإدارة</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">سجل الدخول لإدارة الفروع والموظفين والتذاكر</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-700 dark:text-red-400 flex items-center gap-2" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form class="space-y-4" action="" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <!-- Username/Email Field -->
            <div>
                <label for="username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">اسم المستخدم أو البريد الإلكتروني</label>
                <input type="text" name="username" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="admin" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">كلمة المرور</label>
                <input type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="••••••••" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-all shadow-md">دخول النظام</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

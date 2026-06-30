<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../functions/remember_me.php';

// Apply security headers
set_security_headers();

// If already verified, redirect to student dashboard
if (isset($_SESSION['student_national_id'])) {
    header('Location: ' . BASE_URL . 'students/index.php');
    exit();
}

// Check for remember-me auto-login
if (process_student_remember_login()) {
    $_SESSION['success'] = 'مرحباً بعودتك! تم التحقق من الهوية تلقائياً.';
    header('Location: ' . BASE_URL . 'students/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $national_id = xss_clean($_POST['national_id'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $_SESSION['error'] = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة.';
    } elseif (empty($national_id)) {
        $_SESSION['error'] = 'يرجى إدخال الرقم القومي الخاص بك.';
    } elseif (!preg_match('/^[0-9]{14}$/', $national_id)) {
        $_SESSION['error'] = 'الرقم القومي غير صحيح، يجب أن يتكون من 14 رقماً فقط.';
    } elseif (!is_login_allowed($national_id)) {
        $_SESSION['error'] = 'تم حظر محاولات التحقق مؤقتاً لكثرة المحاولات الخاطئة. يرجى المحاولة بعد 15 دقيقة.';
    } else {
        try {
            $db = getDBConnection();
            // Verify student existence in database
            $stmt = $db->prepare("SELECT * FROM all_students WHERE national_id = :national_id");
            $stmt->execute(['national_id' => $national_id]);
            $student = $stmt->fetch();
            
            if ($student) {
                // Clear any session leftovers
                session_unset();
                
                log_login_attempt($national_id, true);
                
                // Regenerate session to protect session token
                session_regenerate_id(true);
                
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['student_national_id'] = $student['national_id'];
                $_SESSION['student_branch_id'] = $student['branch_id'];
                $_SESSION['student_email'] = $student['email'];
                $_SESSION['student_phone'] = $student['phone'];
                $_SESSION['student_code'] = $student['student_code'];
                $_SESSION['user_role'] = 'student';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Secure session CSRF token
                
                // Create remember-me token if requested
                if (!empty($_POST['remember_me'])) {
                    handle_student_remember_login($student['id']);
                }

                $_SESSION['success'] = 'تم التحقق من الهوية بنجاح، مرحباً بك في لوحة الخدمات الطلابية.';
                header('Location: ' . BASE_URL . 'students/index.php');
                exit();
            } else {
                log_login_attempt($national_id, false);
                $_SESSION['error'] = 'الرقم القومي غير مسجل لدينا بالنظام. يرجى مراجعة إدارة شؤون الطلاب بالفروع.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'حدث خطأ غير متوقع بالخادم. يرجى المحاولة لاحقاً.';
            error_log("Student verification db error: " . $e->getMessage());
        }
    }
}

$hide_navbar = true;
$pageTitle = 'بوابة الخدمات الطلابية - تحقق الهوية';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="mx-auto w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM2.01 10.313a1 1 0 00.125.922L6.115 16.5a1 1 0 00.78-.375l2.062-2.48 2.062 2.48a1 1 0 00.78.375l3.98-5.265a1 1 0 01.125-.922 1 1 0 00-1.638-1.146l-3.328 4.4L8.766 11.3a1 1 0 00-.78 0L4.658 13.565l-1.01-1.336a1 1 0 00-1.638 1.084z"/>
                </svg>
            </div>
            <h2 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">بوابة الخدمات الطلابية</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">أدخل رقمك القومي للتحقق والدخول</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <form class="space-y-4" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div>
                    <label for="national_id" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">الرقم القومي</label>
                    <input type="text" pattern="[0-9]{14}" maxlength="14" name="national_id" id="national_id"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-center tracking-widest"
                        placeholder="29000000000000" required
                        value="<?php echo isset($_POST['national_id']) ? htmlspecialchars($_POST['national_id']) : ''; ?>">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember_me" id="remember_me"
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
                    <label for="remember_me" class="mr-2 text-sm text-gray-600 dark:text-gray-400">تذكرني</label>
                </div>

                <button type="submit"
                    class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    تحقق من الهوية والدخول
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>

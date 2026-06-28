<?php
require_once __DIR__ . '/../bootstrap.php';

// Apply security headers
set_security_headers();

// If already verified, redirect to student dashboard
if (isset($_SESSION['student_national_id'])) {
    header('Location: ' . BASE_URL . 'students/index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $national_id = xss_clean($_POST['national_id'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة.';
    } elseif (empty($national_id)) {
        $error_message = 'يرجى إدخال الرقم القومي الخاص بك.';
    } elseif (!preg_match('/^[0-9]{14}$/', $national_id)) {
        $error_message = 'الرقم القومي غير صحيح، يجب أن يتكون من 14 رقماً فقط.';
    } elseif (!is_login_allowed($national_id)) {
        $error_message = 'تم حظر محاولات التحقق مؤقتاً لكثرة المحاولات الخاطئة. يرجى المحاولة بعد 15 دقيقة.';
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
                
                $_SESSION['success'] = 'تم التحقق من الهوية بنجاح، مرحباً بك في لوحة الخدمات الطلابية.';
                header('Location: ' . BASE_URL . 'students/index.php');
                exit();
            } else {
                log_login_attempt($national_id, false);
                $error_message = 'الرقم القومي غير مسجل لدينا بالنظام. يرجى مراجعة إدارة شؤون الطلاب بالفروع.';
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ غير متوقع بالخادم. يرجى المحاولة لاحقاً.';
            error_log("Student verification db error: " . $e->getMessage());
        }
    }
}

$pageTitle = 'بوابة الخدمات الطلابية - تحقق الهوية';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Wrapper mimicking sidebar layout but centered for login -->
<div class="flex-1 flex flex-col min-h-screen justify-center items-center bg-gray-50 dark:bg-gray-900 pt-20 px-4">
    <div class="w-full max-w-md p-6 space-y-6 bg-white rounded-2xl border border-gray-100 shadow-xl dark:bg-gray-800 dark:border-gray-700">
        <div class="text-center">
            <!-- Premium Graduate/Book Icon -->
            <div class="mx-auto w-12 h-12 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mb-2">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM2.01 10.313a1 1 0 00.125.922L6.115 16.5a1 1 0 00.78-.375l2.062-2.48 2.062 2.48a1 1 0 00.78.375l3.98-5.265a1 1 0 01.125-.922 1 1 0 00-1.638-1.146l-3.328 4.4L8.766 11.3a1 1 0 00-.78 0L4.658 13.565l-1.01-1.336a1 1 0 00-1.638 1.084z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">بوابة الخدمات الطلابية</h2>
            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">يرجى إدخال رقمك القومي المكون من 14 رقماً للتحقق والدخول للبوابة</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="p-4 mb-4 text-base text-red-800 rounded-lg bg-red-50 dark:bg-gray-700 dark:text-red-400 flex items-center gap-2" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form class="space-y-4" action="" method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <!-- National ID Field -->
            <div>
                <label for="national_id" class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الرقم القومي للطالب</label>
                <input type="text" pattern="[0-9]{14}" maxlength="14" name="national_id" id="national_id" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-center tracking-widest font-semibold" placeholder="29000000000000" required value="<?php echo isset($_POST['national_id']) ? htmlspecialchars($_POST['national_id']) : ''; ?>">
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-base px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition-all shadow-md">تحقق من الهوية والدخول</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_student();

$error_message = '';
unset($_SESSION['error']);
$db = getDBConnection();

$student_name = $_SESSION['student_name'];
$national_id = $_SESSION['student_national_id'];
$branch_id = (int)$_SESSION['student_branch_id'];
$student_code = $_SESSION['student_code'] ?? null;
$student_phone = $_SESSION['student_phone'] ?? '';

$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories WHERE type = 'student' AND status = 'active' ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch student categories: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = xss_clean($_POST['subject'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $priority = xss_clean($_POST['priority'] ?? 'medium');
    $phone = xss_clean($_POST['phone'] ?? $student_phone);
    $description = xss_clean($_POST['description'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة.';
    } elseif (empty($subject) || empty($description) || $category_id <= 0) {
        $error_message = 'يرجى تعبئة جميع الحقول الإلزامية.';
    } else {
        try {
            // Daily limit: max 2 tickets per student
            $dailyStmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE national_id = :national_id AND DATE(created_at) = CURDATE()");
            $dailyStmt->execute(['national_id' => $national_id]);
            if ($dailyStmt->fetchColumn() >= 2) {
                throw new Exception('لقد تجاوزت الحد الأقصى المسموح به من التذاكر اليومية (تذكرتين فقط في اليوم).');
            }

            $max_attempts = 10;
            $ticket_number = '';
            for ($i = 0; $i < $max_attempts; $i++) {
                $candidate = 'CRV-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $stmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE ticket_number = :tn");
                $stmt->execute(['tn' => $candidate]);
                if ($stmt->fetchColumn() == 0) {
                    $ticket_number = $candidate;
                    break;
                }
            }
            if (empty($ticket_number)) {
                throw new Exception('فشل في إنشاء رقم تذكرة فريد. يرجى المحاولة مرة أخرى.');
            }

            $insert = $db->prepare("
                INSERT INTO student_tickets (branch_id, category_id, ticket_number, student_name, national_id, student_code, contact_phone, subject, description, priority, status)
                VALUES (:branch_id, :category_id, :ticket_number, :student_name, :national_id, :student_code, :contact_phone, :subject, :description, :priority, 'open')
            ");
            $insert->execute([
                'branch_id' => $branch_id,
                'category_id' => $category_id,
                'ticket_number' => $ticket_number,
                'student_name' => $student_name,
                'national_id' => $national_id,
                'student_code' => $student_code,
                'contact_phone' => $phone,
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority
            ]);
            $new_ticket_id = $db->lastInsertId();

            log_audit_action("إنشاء تذكرة طلابية جديدة: {$ticket_number} بواسطة {$student_name}", 'student_tickets', $new_ticket_id, null, [
                'ticket_number' => $ticket_number, 'subject' => $subject, 'category_id' => $category_id, 'priority' => $priority
            ]);

            $_SESSION['success'] = "تم تقديم تذكرتك {$ticket_number} بنجاح. سنقوم بالرد في أقرب وقت.";
            header('Location: ' . BASE_URL . 'students/track.php');
            exit();
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("Student ticket creation error: " . $e->getMessage());
        }
    }
}

$hide_sidebar = true;
$hide_navbar = true;
$pageTitle = 'تقديم تذكرة';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 px-4 md:px-8 pb-8 md:pb-12 pt-6">
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 dark:to-transparent rounded-3xl p-6 border border-blue-100 dark:border-blue-900/30">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">تقديم تذكرة</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">يرجى تعبئة النموذج لتقديم تذكرتك أو استفسارك</p>
                    </div>
                    <!-- Mobile three-dots menu -->
                    <div class="sm:hidden relative mr-auto">
                        <button type="button" class="flex items-center justify-center w-9 h-9 text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/60 rounded-xl transition-all" data-dropdown-toggle="mobile-menu-dropdown">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        </button>
                        <div class="z-50 hidden my-2 text-base list-none bg-white divide-y divide-gray-100 rounded-2xl shadow-xl dark:bg-gray-700 dark:divide-gray-600 min-w-[200px]" id="mobile-menu-dropdown">
                            <div class="py-1" role="none">
                                <button id="mobile-theme-toggle" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-600/50 rounded-xl transition-all text-right" role="menuitem">
                                    <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                    <span id="mobile-theme-label">الوضع الليلي</span>
                                </button>
                                <a href="<?php echo BASE_URL; ?>students/auth/logout.php" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-xl transition-all" role="menuitem">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    تسجيل الخروج
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>students/index.php" class="self-start sm:self-auto inline-flex items-center px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 rounded-xl transition-all shadow-sm shrink-0 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        الرئيسية
                    </a>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <!-- Student Info Card -->
        <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-md shrink-0">
                    <?php echo mb_substr($student_name, 0, 1); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-base font-bold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($student_name); ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($student_code ?: '—'); ?> · <?php echo htmlspecialchars($national_id); ?></p>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full shrink-0">طالب</span>
            </div>
        </div>

        <!-- Form Card -->
        <div class="p-4 md:p-6 bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 dark:to-transparent rounded-2xl border border-blue-100 dark:border-blue-900/30">
            <form class="space-y-6" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div>
                    <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الموضوع</label>
                    <input type="text" name="subject" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500" placeholder="عنوان التذكرة" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">التصنيف</label>
                        <select name="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            <option value="">اختر التصنيف</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الأولوية</label>
                        <select name="priority" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>منخفضة</option>
                            <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>متوسطة</option>
                            <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>عالية</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">رقم الهاتف للتواصل</label>
                    <input type="text" name="phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500" placeholder="01XXXXXXXXX" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($student_phone); ?>">
                </div>

                <div>
                    <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الوصف</label>
                    <textarea name="description" rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500" placeholder="اكتب وصفاً تفصيلياً للتذكرة" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-base px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700">تقديم التذكرة</button>
            </form>
        </div>
    </div>

    <footer class="text-center py-5 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 mt-8">
        &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?> — جميع الحقوق محفوظة
    </footer>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mobileToggle = document.getElementById('mobile-theme-toggle');
    var mobileLabel = document.getElementById('mobile-theme-label');
    if (mobileToggle && mobileLabel) {
        var updateLabel = function () {
            mobileLabel.textContent = document.documentElement.classList.contains('dark') ? 'الوضع النهاري' : 'الوضع الليلي';
        };
        updateLabel();
        mobileToggle.addEventListener('click', function () {
            if (localStorage.getItem('color-theme')) {
                if (localStorage.getItem('color-theme') === 'light') {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                }
            } else {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('color-theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('color-theme', 'dark');
                }
            }
            updateLabel();
        });
    }
});
</script>
<?php
if (!empty($error_message)) {
    $_SESSION['error'] = $error_message;
}
require_once __DIR__ . '/../includes/footer.php';
?>

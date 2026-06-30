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

            log_audit_action("إنشاء شكوى طلابية جديدة: {$ticket_number} بواسطة {$student_name}", 'student_tickets', $new_ticket_id, null, [
                'ticket_number' => $ticket_number, 'subject' => $subject, 'category_id' => $category_id, 'priority' => $priority
            ]);

            $_SESSION['success'] = "تم تقديم شكواك {$ticket_number} بنجاح. سنقوم بالرد في أقرب وقت.";
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
$pageTitle = 'تقديم شكوى أو تذكرة';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 px-4 md:px-8 pb-4 md:pb-8 pt-6">
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 dark:to-transparent rounded-3xl p-6 border border-blue-100 dark:border-blue-900/30">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">تقديم شكوى / تذكرة</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">يرجى تعبئة النموذج لتقديم شكواك أو استفسارك</p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>students/index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all shrink-0">عودة للرئيسية</a>
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
        <div class="p-6 bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 dark:to-transparent rounded-2xl border border-blue-100 dark:border-blue-900/30">
            <form class="space-y-6" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div>
                    <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الموضوع</label>
                    <input type="text" name="subject" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500" placeholder="عنوان الشكوى" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
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
                    <textarea name="description" rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-500" placeholder="اكتب وصفاً تفصيلياً للشكوى" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-base px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700">تقديم الشكوى</button>
            </form>
        </div>
    </div>
</div>
<?php
if (!empty($error_message)) {
    $_SESSION['error'] = $error_message;
}
require_once __DIR__ . '/../includes/footer.php';
?>

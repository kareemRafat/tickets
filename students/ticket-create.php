<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_student();

$error_message = '';
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
$pageTitle = 'تقديم شكوى أو تذكرة';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="sm:mr-0 pt-20 flex-1 flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">
<main class="p-6 space-y-6 flex-1">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">تقديم شكوى / تذكرة</h1>
            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">يرجى تعبئة النموذج أدناه لتقديم شكواك أو استفسارك لفريق الدعم.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>students/index.php" class="px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">عودة للرئيسية</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="p-4 text-base text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-100 dark:border-red-900/50 flex items-center gap-2" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="mb-6 bg-gradient-to-l from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-100 dark:border-blue-800/30 p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-800/50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                    </svg>
                </div>
                <span class="text-base font-bold text-gray-700 dark:text-gray-300">معلومات الطالب</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800/50 rounded-xl p-3.5 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-50 dark:bg-blue-800/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الاسم</p>
                        <p class="text-base font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($student_name); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800/50 rounded-xl p-3.5 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-50 dark:bg-blue-800/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 0H2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2ZM6 6a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm8 10v1H6v-1a3 3 0 0 1 3-3h2a3 3 0 0 1 3 3Zm2-9a1 1 0 0 1-1 1H9a1 1 0 0 1 0-2h6a1 1 0 0 1 1 1Zm0 4a1 1 0 0 1-1 1H9a1 1 0 0 1 0-2h6a1 1 0 0 1 1 1Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الرقم القومي</p>
                        <p class="text-base font-bold text-gray-900 dark:text-white font-mono"><?php echo htmlspecialchars($national_id); ?></p>
                    </div>
                </div>
            </div>
        </div>

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
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
</div>

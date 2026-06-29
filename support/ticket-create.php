<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_employee();

$error_message = '';
unset($_SESSION['error']);
$db = getDBConnection();
$employee_id = (int)$_SESSION['user_id'];
$branch_id = (int)$_SESSION['user_branch_id'];

$categories = [];
$employee_name = '';
$branch_name = '';
try {
    $stmt = $db->query("SELECT * FROM categories WHERE type = 'support' AND status = 'active' ORDER BY name ASC");
    $categories = $stmt->fetchAll();

    $stmt_emp = $db->prepare("SELECT name FROM employees WHERE id = :id");
    $stmt_emp->execute(['id' => $employee_id]);
    $employee_name = $stmt_emp->fetchColumn() ?: '';

    $stmt_branch = $db->prepare("SELECT name FROM branches WHERE id = :id");
    $stmt_branch->execute(['id' => $branch_id]);
    $branch_name = $stmt_branch->fetchColumn() ?: '';
} catch (PDOException $e) {
    error_log("Failed to fetch support categories: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = xss_clean($_POST['subject'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $priority = xss_clean($_POST['priority'] ?? 'medium');
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
                $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE ticket_number = :tn");
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
                INSERT INTO support_tickets (branch_id, category_id, employee_id, ticket_number, subject, description, priority, status)
                VALUES (:branch_id, :category_id, :employee_id, :ticket_number, :subject, :description, :priority, 'open')
            ");
            $insert->execute([
                'branch_id' => $branch_id,
                'category_id' => $category_id,
                'employee_id' => $employee_id,
                'ticket_number' => $ticket_number,
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority
            ]);
            $new_ticket_id = $db->lastInsertId();

            log_audit_action("إنشاء تذكرة دعم فني جديدة: {$ticket_number}", 'support_tickets', $new_ticket_id, null, [
                'ticket_number' => $ticket_number, 'subject' => $subject, 'category_id' => $category_id, 'priority' => $priority
            ]);

            $_SESSION['success'] = "تم إنشاء التذكرة {$ticket_number} بنجاح.";
            header('Location: ' . BASE_URL . 'support/tickets.php');
            exit();
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            error_log("Ticket creation error: " . $e->getMessage());
        }
    }
}

$pageTitle = 'إنشاء تذكرة دعم فني جديدة';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                إنشاء تذكرة دعم فني
            </h1>
            <p class="mt-1 text-base text-gray-500 dark:text-gray-400">تقديم طلب دعم فني داخلي لفريق الفرع.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>support/tickets.php" class="px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">
            عودة للتذاكر
        </a>
    </div>

    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 w-full">
        <!-- Employee Info Card -->
        <div class="mb-6 bg-gradient-to-l from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-100 dark:border-blue-800/30 p-5">
            <div class="flex items-center gap-2.5 mb-4">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-800/50 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                    </svg>
                </div>
                <span class="text-base font-bold text-gray-700 dark:text-gray-300">معلومات مقدم الطلب</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800/50 rounded-xl p-3.5 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-50 dark:bg-blue-800/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الاسم</p>
                        <p class="text-base font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($employee_name); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800/50 rounded-xl p-3.5 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-50 dark:bg-blue-800/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 003 5.5v2.879a2.5 2.5 0 00.732 1.767l6.5 6.5a2.5 2.5 0 003.536 0l2.878-2.878a2.5 2.5 0 000-3.536l-6.5-6.5A2.5 2.5 0 008.38 3H5.5zM5 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">رقم الموظف</p>
                        <p class="text-base font-bold text-gray-900 dark:text-white font-mono">#<?php echo $employee_id; ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800/50 rounded-xl p-3.5 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-50 dark:bg-blue-800/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm1 2.25a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75zm0 3a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75zm0 3a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75zm0 3a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">الفرع</p>
                        <p class="text-base font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($branch_name); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <form class="space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <div>
                <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الموضوع</label>
                <input type="text" name="subject" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="ملخص مختصر للتذكرة" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">التصنيف</label>
                    <select name="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="">اختر التصنيف</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الأولوية</label>
                    <select name="priority" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>منخفضة</option>
                        <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>متوسطة</option>
                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>عالية</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block mb-2 text-base font-medium text-gray-900 dark:text-white">الوصف</label>
                <textarea name="description" rows="8" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white" placeholder="اكتب وصفاً تفصيلياً للتذكرة"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-base px-8 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700 transition-all">
                    إنشاء التذكرة
                </button>
                <a href="<?php echo BASE_URL; ?>support/tickets.php" class="px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl transition-all dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</main>
<?php
if (!empty($error_message)) {
    $_SESSION['error'] = $error_message;
}
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_employee();

$error_message = '';
$db = getDBConnection();
$employee_id = (int)$_SESSION['user_id'];
$branch_id = (int)$_SESSION['user_branch_id'];

$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories WHERE type = 'support' AND status = 'active' ORDER BY name ASC");
    $categories = $stmt->fetchAll();
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
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">تقديم طلب دعم فني داخلي لفريق الفرع.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>support/tickets.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">
            عودة للتذاكر
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="p-4 text-sm text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-100 dark:border-red-900/50 flex items-center gap-2" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 max-w-2xl">
        <form class="space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الموضوع</label>
                <input type="text" name="subject" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ملخص مختصر للتذكرة" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">التصنيف</label>
                    <select name="category_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="">اختر التصنيف</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الأولوية</label>
                    <select name="priority" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>منخفضة</option>
                        <option value="medium" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>متوسطة</option>
                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>عالية</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">الوصف</label>
                <textarea name="description" rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="اكتب وصفاً تفصيلياً للتذكرة"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-3 text-center dark:bg-blue-600 dark:hover:bg-blue-700">إنشاء التذكرة</button>
        </form>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

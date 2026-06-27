<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$db = getDBConnection();
$national_id = $_SESSION['student_national_id'];

$ticket_id = (int)($_GET['id'] ?? 0);
if ($ticket_id <= 0) {
    $_SESSION['error'] = 'رابط غير صالح.';
    header('Location: ' . BASE_URL . 'students/track.php');
    exit();
}

$stmt = $db->prepare("
    SELECT st.*, c.name as category_name
    FROM student_tickets st
    JOIN categories c ON st.category_id = c.id
    WHERE st.id = :id
");
$stmt->execute(['id' => $ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket || $ticket['national_id'] !== $national_id) {
    $_SESSION['error'] = 'لا يمكنك الوصول إلى هذه التذكرة.';
    header('Location: ' . BASE_URL . 'students/track.php');
    exit();
}

$replies = [];
try {
    $stmt = $db->prepare("
        SELECT r.*, e.name as employee_name
        FROM student_ticket_replies r
        JOIN employees e ON r.employee_id = e.id
        WHERE r.ticket_id = :ticket_id
        ORDER BY r.created_at ASC
    ");
    $stmt->execute(['ticket_id' => $ticket_id]);
    $replies = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch student replies: " . $e->getMessage());
}

$status_labels = ['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'closed' => 'مغلقة'];
$priority_labels = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'];

$pageTitle = "التذكرة {$ticket['ticket_number']}";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full <?php echo $ticket['status'] === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($ticket['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'); ?>">
                    <?php echo $status_labels[$ticket['status']]; ?>
                </span>
                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full <?php echo $ticket['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($ticket['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                    <?php echo $priority_labels[$ticket['priority']]; ?>
                </span>
            </div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
        </div>
        <a href="<?php echo BASE_URL; ?>students/track.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all shrink-0">
            عودة للتذاكر
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">الوصف</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($ticket['description']); ?></p>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                    تم الإنشاء: <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                </div>
            </div>

            <!-- Replies Timeline -->
            <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">الردود من فريق الدعم</h3>
                <?php if (empty($replies)): ?>
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <p class="text-sm">لا توجد ردود حتى الآن. سيتم الرد عليك في أقرب وقت ممكن.</p>
                    </div>
                <?php else: ?>
                    <ol class="relative border-r border-gray-200 dark:border-gray-700 pr-6">
                        <?php foreach ($replies as $reply): ?>
                            <li class="mb-6 last:mb-0">
                                <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -right-3 ring-8 ring-white dark:ring-gray-800 dark:bg-blue-900">
                                    <svg class="w-3 h-3 text-blue-800 dark:text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 11a1 1 0 11-2 0 1 1 0 012 0zm0-3a1 1 0 01-2 0V7a1 1 0 112 0v3z"/></svg>
                                </span>
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700/50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($reply['employee_name']); ?></span>
                                        <time class="text-xs text-gray-500 dark:text-gray-400"><?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?></time>
                                    </div>
                                    <?php if ($reply['old_status'] && $reply['new_status']): ?>
                                        <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                                            تم تغيير الحالة من <span class="font-medium"><?php echo $status_labels[$reply['old_status']]; ?></span> إلى <span class="font-medium"><?php echo $status_labels[$reply['new_status']]; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($reply['reply']); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">معلومات التذكرة</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">التصنيف</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['category_name']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">الأولوية</dt>
                        <dd><span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $ticket['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($ticket['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>"><?php echo $priority_labels[$ticket['priority']]; ?></span></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">الحالة</dt>
                        <dd><span class="px-2 py-0.5 text-xs font-medium rounded-full <?php echo $ticket['status'] === 'open' ? 'bg-blue-100 text-blue-800' : ($ticket['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>"><?php echo $status_labels[$ticket['status']]; ?></span></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء</dt>
                        <dd class="text-gray-900 dark:text-white font-mono text-xs"><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></dd>
                    </div>
                    <?php if ($ticket['closed_at']): ?>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">تاريخ الإغلاق</dt>
                            <dd class="text-gray-900 dark:text-white font-mono text-xs"><?php echo date('Y-m-d H:i', strtotime($ticket['closed_at'])); ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">بيانات الطالب</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">الاسم</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white text-xs"><?php echo htmlspecialchars($ticket['student_name']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">رقم الهاتف</dt>
                        <dd class="text-xs text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['contact_phone']); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

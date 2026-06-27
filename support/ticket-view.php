<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers/audit.php';

set_security_headers();
require_employee();

$db = getDBConnection();
$employee_id = (int)$_SESSION['user_id'];
$branch_id = (int)$_SESSION['user_branch_id'];

$ticket_id = (int)($_GET['id'] ?? 0);
$type = xss_clean($_GET['type'] ?? 'support');

if ($ticket_id <= 0 || !in_array($type, ['support', 'student'])) {
    $_SESSION['error'] = 'رابط غير صالح.';
    header('Location: ' . BASE_URL . 'support/tickets.php');
    exit();
}

// Fetch ticket
if ($type === 'student') {
    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name
        FROM student_tickets st
        JOIN categories c ON st.category_id = c.id
        WHERE st.id = :id AND st.branch_id = :branch_id
    ");
} else {
    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name, e.name as employee_name
        FROM support_tickets st
        JOIN categories c ON st.category_id = c.id
        JOIN employees e ON st.employee_id = e.id
        WHERE st.id = :id AND st.branch_id = :branch_id
    ");
}
$stmt->execute(['id' => $ticket_id, 'branch_id' => $branch_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = 'التذكرة المطلوبة غير موجودة.';
    header('Location: ' . BASE_URL . 'support/tickets.php');
    exit();
}

if ((int)$ticket['branch_id'] !== $branch_id) {
    $_SESSION['error'] = 'لا يمكنك الوصول إلى هذه التذكرة فهي تابعة لفرع آخر.';
    header('Location: ' . BASE_URL . 'support/tickets.php');
    exit();
}

// Fetch categories for category change dropdown
$categories = [];
try {
    $cat_type = $type === 'student' ? 'student' : 'support';
    $stmt = $db->prepare("SELECT * FROM categories WHERE type = :type AND status = 'active' ORDER BY name ASC");
    $stmt->execute(['type' => $cat_type]);
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch categories: " . $e->getMessage());
}

// Status labels
$status_labels = ['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'closed' => 'مغلقة'];
$priority_labels = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'];

// Handle POST actions
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $error_message = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة.';
    } else {
        if ($action === 'reply') {
            $reply_text = xss_clean($_POST['reply'] ?? '');
            $new_status = xss_clean($_POST['new_status'] ?? '');
            $old_status = $ticket['status'];

            if (empty($reply_text)) {
                $error_message = 'يرجى كتابة الرد.';
            } else {
                try {
                    $replies_table = $type === 'student' ? 'student_ticket_replies' : 'support_ticket_replies';
                    $tickets_table = $type === 'student' ? 'student_tickets' : 'support_tickets';

                    $insert = $db->prepare("
                        INSERT INTO {$replies_table} (ticket_id, employee_id, reply, old_status, new_status)
                        VALUES (:ticket_id, :employee_id, :reply, :old_status, :new_status)
                    ");
                    $insert->execute([
                        'ticket_id' => $ticket_id,
                        'employee_id' => $employee_id,
                        'reply' => $reply_text,
                        'old_status' => !empty($new_status) && $new_status !== $old_status ? $old_status : null,
                        'new_status' => !empty($new_status) && $new_status !== $old_status ? $new_status : null
                    ]);

                    $update_data = ['last_reply_by' => $employee_id, 'last_reply_at' => date('Y-m-d H:i:s')];
                    if (!empty($new_status) && $new_status !== $old_status) {
                        $update_data['status'] = $new_status;
                        if ($new_status === 'closed') {
                            $update_data['closed_at'] = date('Y-m-d H:i:s');
                        } else {
                            $update_data['closed_at'] = null;
                        }
                    }

                    $set_parts = [];
                    foreach ($update_data as $col => $val) {
                        $set_parts[] = "{$col} = :{$col}";
                    }
                    $update_data['id'] = $ticket_id;
                    $update = $db->prepare("UPDATE {$tickets_table} SET " . implode(', ', $set_parts) . " WHERE id = :id");
                    $update->execute($update_data);

                    log_audit_action(
                        "رد على التذكرة {$ticket['ticket_number']}" . (!empty($new_status) && $new_status !== $old_status ? " وتغيير الحالة من {$old_status} إلى {$new_status}" : ''),
                        $tickets_table, $ticket_id,
                        !empty($new_status) && $new_status !== $old_status ? ['status' => $old_status] : null,
                        !empty($new_status) && $new_status !== $old_status ? ['status' => $new_status] : null
                    );

                    $_SESSION['success'] = 'تم إضافة الرد بنجاح.';
                    header('Location: ' . BASE_URL . "support/ticket-view.php?id={$ticket_id}&type={$type}");
                    exit();
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ أثناء حفظ الرد.';
                    error_log("Reply error: " . $e->getMessage());
                }
            }
        } elseif ($action === 'change_category') {
            $new_category_id = (int)($_POST['category_id'] ?? 0);
            if ($new_category_id <= 0) {
                $error_message = 'يرجى اختيار تصنيف صالح.';
            } else {
                try {
                    $tickets_table = $type === 'student' ? 'student_tickets' : 'support_tickets';
                    $old_category_id = $ticket['category_id'];

                    $update = $db->prepare("UPDATE {$tickets_table} SET category_id = :category_id WHERE id = :id");
                    $update->execute(['category_id' => $new_category_id, 'id' => $ticket_id]);

                    log_audit_action("تغيير تصنيف التذكرة {$ticket['ticket_number']}", $tickets_table, $ticket_id,
                        ['category_id' => $old_category_id], ['category_id' => $new_category_id]);

                    $_SESSION['success'] = 'تم تغيير تصنيف التذكرة بنجاح.';
                    header('Location: ' . BASE_URL . "support/ticket-view.php?id={$ticket_id}&type={$type}");
                    exit();
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ أثناء تغيير التصنيف.';
                    error_log("Category change error: " . $e->getMessage());
                }
            }
        } elseif ($action === 'change_status') {
            $new_status = xss_clean($_POST['new_status'] ?? '');
            if (empty($new_status) || !in_array($new_status, ['open', 'in_progress', 'closed'])) {
                $error_message = 'حالة غير صالحة.';
            } elseif ($new_status === $ticket['status']) {
                $error_message = 'التذكرة بالفعل بهذه الحالة.';
            } else {
                $replies_table = $type === 'student' ? 'student_ticket_replies' : 'support_ticket_replies';
                $tickets_table = $type === 'student' ? 'student_tickets' : 'support_tickets';
                $stmt = $db->prepare("SELECT COUNT(*) FROM {$replies_table} WHERE ticket_id = :ticket_id");
                $stmt->execute(['ticket_id' => $ticket_id]);
                $reply_count = $stmt->fetchColumn();

                if ($new_status === 'closed' && $reply_count == 0) {
                    $error_message = 'يجب إضافة رد أولاً قبل إغلاق التذكرة.';
                } else {
                    try {
                        $old_status = $ticket['status'];
                        $update_data = ['status' => $new_status];
                        if ($new_status === 'closed') {
                            $update_data['closed_at'] = date('Y-m-d H:i:s');
                        } else {
                            $update_data['closed_at'] = null;
                        }
                        $update = $db->prepare("UPDATE {$tickets_table} SET status = :status, closed_at = :closed_at WHERE id = :id");
                        $update->execute(['status' => $new_status, 'closed_at' => $update_data['closed_at'], 'id' => $ticket_id]);

                        log_audit_action("تغيير حالة التذكرة {$ticket['ticket_number']} من {$old_status} إلى {$new_status}", $tickets_table, $ticket_id,
                            ['status' => $old_status], ['status' => $new_status]);

                        $_SESSION['success'] = "تم تغيير حالة التذكرة إلى " . $status_labels[$new_status] . " بنجاح.";
                        header('Location: ' . BASE_URL . "support/ticket-view.php?id={$ticket_id}&type={$type}");
                        exit();
                    } catch (PDOException $e) {
                        $error_message = 'حدث خطأ أثناء تغيير الحالة.';
                        error_log("Change status error: " . $e->getMessage());
                    }
                }
            }
        } elseif ($action === 'reopen') {
            if ($ticket['status'] !== 'closed') {
                $error_message = 'التذكرة ليست مغلقة ولا يمكن إعادة فتحها.';
            } else {
                try {
                    $tickets_table = $type === 'student' ? 'student_tickets' : 'support_tickets';

                    $update = $db->prepare("UPDATE {$tickets_table} SET status = 'open', closed_at = NULL WHERE id = :id");
                    $update->execute(['id' => $ticket_id]);

                    log_audit_action("إعادة فتح التذكرة {$ticket['ticket_number']}", $tickets_table, $ticket_id,
                        ['status' => 'closed'], ['status' => 'open']);

                    $_SESSION['success'] = 'تم إعادة فتح التذكرة بنجاح.';
                    header('Location: ' . BASE_URL . "support/ticket-view.php?id={$ticket_id}&type={$type}");
                    exit();
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ أثناء إعادة فتح التذكرة.';
                    error_log("Reopen error: " . $e->getMessage());
                }
            }
        }
    }
}

// Fetch replies
$replies = [];
try {
    $replies_table = $type === 'student' ? 'student_ticket_replies' : 'support_ticket_replies';
    $stmt = $db->prepare("
        SELECT r.*, e.name as employee_name
        FROM {$replies_table} r
        JOIN employees e ON r.employee_id = e.id
        WHERE r.ticket_id = :ticket_id
        ORDER BY r.created_at ASC
    ");
    $stmt->execute(['ticket_id' => $ticket_id]);
    $replies = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch replies: " . $e->getMessage());
}

$pageTitle = "التذكرة {$ticket['ticket_number']}";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<main class="p-6 space-y-6 flex-1">
    <?php if (!empty($error_message)): ?>
        <div class="p-4 text-base text-red-800 rounded-xl bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-100 dark:border-red-900/50 flex items-center gap-2" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <!-- Ticket Header -->
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
                <span class="px-2.5 py-0.5 text-sm font-medium rounded-full <?php echo $ticket['status'] === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ($ticket['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'); ?>">
                    <?php echo $status_labels[$ticket['status']]; ?>
                </span>
                <span class="px-2.5 py-0.5 text-sm font-medium rounded-full <?php echo $ticket['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ($ticket['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'); ?>">
                    <?php echo $priority_labels[$ticket['priority']]; ?>
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <?php echo $type === 'student' ? 'شكوى طالب' : 'تذكرة دعم فني'; ?>
                </span>
            </div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="<?php echo BASE_URL; ?>support/tickets.php?tab=<?php echo $type === 'student' ? 'student' : 'support'; ?>" class="px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all">
                عودة
            </a>
            <?php if ($ticket['status'] === 'closed'): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="reopen">
                    <button type="submit" class="px-4 py-2 text-base font-medium text-white bg-green-600 hover:bg-green-700 rounded-xl transition-all">
                        إعادة فتح التذكرة
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">الوصف</h3>
                <p class="text-base text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($ticket['description']); ?></p>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                    تم الإنشاء بواسطة: <strong class="text-gray-700 dark:text-gray-300"><?php echo $type === 'student' ? htmlspecialchars($ticket['student_name']) : htmlspecialchars($ticket['employee_name']); ?></strong>
                    &middot; <?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?>
                </div>
            </div>

            <!-- Replies Timeline -->
            <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">الردود</h3>
                <?php if (empty($replies)): ?>
                    <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                        <p class="text-base">لا توجد ردود حتى الآن.</p>
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
                                        <div>
                                            <span class="text-base font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($reply['employee_name']); ?></span>
                                            <?php if ($reply['old_status'] && $reply['new_status']): ?>
                                                <span class="text-sm text-gray-500 dark:text-gray-400 mr-2">
                                                    غير الحالة من <?php echo $status_labels[$reply['old_status']]; ?> إلى <?php echo $status_labels[$reply['new_status']]; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <time class="text-sm text-gray-500 dark:text-gray-400"><?php echo date('Y-m-d H:i', strtotime($reply['created_at'])); ?></time>
                                    </div>
                                    <p class="text-base text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?php echo htmlspecialchars($reply['reply']); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>

            <!-- Reply Form (only if not closed) -->
            <?php if ($ticket['status'] !== 'closed'): ?>
                <div class="p-6 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">إضافة رد</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="reply">

                        <div class="mb-4">
                            <textarea name="reply" rows="4" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="اكتب ردك هنا..." required></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex items-center gap-3 flex-1">
                                <label class="text-base font-medium text-gray-900 dark:text-white shrink-0">تغيير الحالة:</label>
                                <select name="new_status" class="w-44 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">دون تغيير</option>
                                    <?php foreach ($status_labels as $key => $label): ?>
                                        <?php if ($key !== $ticket['status']): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shrink-0">
                                إرسال الرد
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Status Actions (only if there are replies) -->
                <?php if (!empty($replies)): ?>
                    <div class="p-4 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-base font-medium text-gray-900 dark:text-white">إجراء سريع:</span>
                            <?php if ($ticket['status'] === 'open'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="new_status" value="in_progress">
                                    <button type="submit" class="px-4 py-2 text-base font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-xl transition-all">
                                        قيد التنفيذ
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($ticket['status'] !== 'closed'): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="new_status" value="closed">
                                    <button type="submit" class="px-4 py-2 text-base font-medium text-white bg-red-600 hover:bg-red-700 rounded-xl transition-all">
                                        إغلاق التذكرة
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Info Card -->
            <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">معلومات التذكرة</h3>
                <dl class="space-y-3 text-base">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">التصنيف</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['category_name']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">الأولوية</dt>
                        <dd>
                            <span class="px-2 py-0.5 text-sm font-medium rounded-full <?php echo $ticket['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($ticket['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo $priority_labels[$ticket['priority']]; ?>
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">الحالة</dt>
                        <dd>
                            <span class="px-2 py-0.5 text-sm font-medium rounded-full <?php echo $ticket['status'] === 'open' ? 'bg-blue-100 text-blue-800' : ($ticket['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo $status_labels[$ticket['status']]; ?>
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء</dt>
                        <dd class="text-gray-900 dark:text-white font-mono text-sm"><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></dd>
                    </div>
                    <?php if ($ticket['closed_at']): ?>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">تاريخ الإغلاق</dt>
                            <dd class="text-gray-900 dark:text-white font-mono text-sm"><?php echo date('Y-m-d H:i', strtotime($ticket['closed_at'])); ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($type === 'student'): ?>
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">الطالب</dt>
                                <dd class="font-semibold text-gray-900 dark:text-white text-sm"><?php echo htmlspecialchars($ticket['student_name']); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">الرقم القومي</dt>
                                <dd class="font-mono text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['national_id']); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">رقم الهاتف</dt>
                                <dd class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['contact_phone']); ?></dd>
                            </div>
                            <?php if ($ticket['student_code']): ?>
                                <div class="flex justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">كود الطالب</dt>
                                    <dd class="font-mono text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($ticket['student_code']); ?></dd>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Change Category -->
            <?php if (!empty($categories) && count($categories) > 0): ?>
                <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">تغيير التصنيف</h3>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="change_category">
                        <select name="category_id" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-xl focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white mb-3">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $ticket['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="w-full px-4 py-2 text-base font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">
                            تحديث التصنيف
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>

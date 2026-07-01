<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$db = getDBConnection();
$national_id = $_SESSION['student_national_id'];

$status_filter = xss_clean($_GET['status'] ?? '');
$search = xss_clean($_GET['search'] ?? '');

$where = ["st.national_id = :national_id"];
$params = ['national_id' => $national_id];

if (!empty($status_filter)) {
    $where[] = "st.status = :status";
    $params['status'] = $status_filter;
}
if (!empty($search)) {
    $where[] = "(st.ticket_number LIKE :search OR st.subject LIKE :search_subject)";
    $params['search'] = '%' . $search . '%';
    $params['search_subject'] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where);
$tickets = [];
try {
    $stmt = $db->prepare("
        SELECT st.*, c.name as category_name
        FROM student_tickets st
        JOIN categories c ON st.category_id = c.id
        WHERE {$where_clause}
        ORDER BY
            CASE st.status
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'closed' THEN 3
            END,
            st.created_at DESC
    ");
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Student track error: " . $e->getMessage());
}

$hide_sidebar = true;
$hide_navbar = true;
$pageTitle = 'تتبع تذاكري';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 px-4 md:px-8 pb-8 md:pb-12 pt-6">
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 dark:to-transparent rounded-3xl p-6 border border-blue-100 dark:border-blue-900/30">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">تتبع تذاكري</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">متابعة حالة جميع التذاكر التي قمت بتقديمها</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-auto flex-wrap">
                    <a href="<?php echo BASE_URL; ?>students/index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 rounded-xl transition-all shrink-0">عودة للرئيسية</a>
                    <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all shrink-0">
                        <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        تقديم تذكرة جديدة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <!-- Filter Card -->
        <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-4">
            <form class="flex flex-wrap items-end gap-3 md:gap-4" method="GET" action="">
                <div class="flex-1 min-w-[150px]">
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">بحث</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-800 dark:border-gray-600 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white">
                </div>
                <div class="w-full sm:w-auto">
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">الحالة</label>
                    <select name="status" class="bg-white border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-32 p-1.5 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                        <option value="">الكل</option>
                        <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                    </select>
                </div>
                <button type="submit" class="px-3.5 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all">بحث</button>
                <a href="<?php echo BASE_URL; ?>students/track.php" class="sm:mr-auto font-bold text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors text-xs mb-2">إعادة تعيين</a>
            </form>
        </div>

        <style>
        .ticket-item.active {
            background-color: rgba(0, 0, 0, 0.04);
            border-right: 3px solid #3b82f6;
        }
        .dark .ticket-item.active {
            background-color: rgba(255, 255, 255, 0.06);
            border-right: 3px solid #60a5fa;
        }
        @media (max-width: 767px) {
            .track-container.mobile-detail-open #ticket-list { display: none; }
            .track-container.mobile-detail-open #ticket-details { display: block; }
        }
        </style>

        <div class="track-container flex flex-col md:flex-row gap-4">
            <!-- Ticket List -->
            <div id="ticket-list" class="w-full md:w-96 md:shrink-0 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 overflow-hidden flex flex-col max-h-[calc(100vh-240px)]">
                <div class="overflow-y-auto flex-1">
                    <?php if (empty($tickets)): ?>
                        <div class="px-4 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                            لا توجد تذاكر
                        </div>
                    <?php else: ?>
                        <?php foreach ($tickets as $i => $t): ?>
                            <?php
                            $status_ar = $t['status'] === 'open' ? 'مفتوحة' : ($t['status'] === 'in_progress' ? 'قيد التنفيذ' : 'مغلقة');
                            $status_color = $t['status'] === 'open' ? 'text-green-600 bg-green-50 dark:text-green-300 dark:bg-green-800/40' : ($t['status'] === 'in_progress' ? 'text-amber-600 bg-amber-50 dark:text-amber-300 dark:bg-amber-800/40' : 'text-red-600 bg-red-50 dark:text-red-300 dark:bg-red-800/40');
                            $dot_color = $t['status'] === 'open' ? 'bg-green-500' : ($t['status'] === 'in_progress' ? 'bg-amber-500' : 'bg-red-500');
                            ?>
                            <div class="ticket-item cursor-pointer px-4 py-3.5 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors <?php echo $i === 0 ? '' : ''; ?>" data-ticket-id="<?php echo $t['id']; ?>">
                                <div class="flex items-start gap-3">
                                    <span class="w-2 h-2 rounded-full <?php echo $dot_color; ?> mt-2 shrink-0"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-base font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($t['subject']); ?></span>
                                            <span class="text-sm text-gray-400 dark:text-gray-500 shrink-0"><?php echo date('M d', strtotime($t['created_at'])); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-sm font-mono text-gray-400 dark:text-gray-500"><?php echo htmlspecialchars($t['ticket_number']); ?></span>
                                            <span class="text-sm text-gray-400 dark:text-gray-500">·</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($t['category_name']); ?></span>
                                        </div>
                                        <div class="mt-1.5">
                                            <span class="inline-block px-2.5 py-0.5 text-sm font-medium rounded <?php echo $status_color; ?>"><?php echo $status_ar; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Panel -->
            <div id="ticket-details" data-api-url="<?php echo BASE_URL; ?>students/ajax/ticket-details.php" class="flex-1 bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700 p-4 md:p-8 min-h-[300px] overflow-y-auto hidden md:block">
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        <p class="text-sm text-gray-400 dark:text-gray-500">اختر تذكرة لعرض التفاصيل</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-5 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 mt-8">
        &copy; <?php echo date('Y'); ?> <?php echo SYSTEM_NAME; ?> — جميع الحقوق محفوظة
    </footer>
</div>
<script src="<?php echo BASE_URL; ?>students/js/track.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

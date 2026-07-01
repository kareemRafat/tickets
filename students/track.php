<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$db = getDBConnection();
$national_id = $_SESSION['student_national_id'];
$student_name = $_SESSION['student_name'];
$student_code = $_SESSION['student_code'] ?? '';

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
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 px-4 md:px-8 pb-12 pt-6">
    <!-- Header Banner -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700/50 shadow-sm relative overflow-hidden">
            <!-- Decorative blur elements -->
            <div class="absolute -top-24 -left-20 w-80 h-80 bg-blue-500/5 dark:bg-blue-400/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -right-20 w-80 h-80 bg-indigo-500/5 dark:bg-indigo-400/5 rounded-full blur-3xl"></div>
            
            <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20 shrink-0">
                        <!-- Heroicons: ticket icon -->
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12h12c.621 0 1.125.504 1.125 1.125V17.625c0 .621-.504 1.125-1.125 1.125H7.5c-.621 0-1.125-.504-1.125-1.125V7.125C6.375 6.629 6.879 6.125 7.5 6.125z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">تتبع تذاكري</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">متابعة حالة جميع التذاكر التي قمت بتقديمها والردود عليها</p>
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
                
                <div class="flex items-center gap-2 self-end sm:self-auto flex-wrap">
                    <!-- Home -->
                    <a href="<?php echo BASE_URL; ?>students/index.php" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 rounded-xl transition-all shadow-sm shrink-0 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        الرئيسية
                    </a>
                    
                    <!-- Theme Toggle (desktop only) -->
                    <button id="theme-toggle" type="button" class="hidden sm:inline-flex items-center text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-xl text-sm p-2.5 transition-all bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 shadow-sm shrink-0">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05L5.75 4.343a1 1 0 10-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2h1a1 1 0 100 2H4z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>
                    
                    <!-- Create ticket -->
                    <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-500/10 hover:shadow-lg transition-all shrink-0 gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        تقديم تذكرة جديدة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <!-- Filter Card -->
        <div class="mb-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-4 relative overflow-hidden">
            <form class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 md:gap-4" method="GET" action="">
                <!-- Search Input with Icon -->
                <div class="flex-1 relative">
                    <label class="sr-only">بحث</label>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="رقم التذكرة أو الموضوع..." class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 block w-full pr-10 pl-3 py-2.5 dark:bg-gray-900 dark:border-gray-700 placeholder-gray-400 dark:placeholder-gray-500 dark:text-white transition-all">
                </div>
                
                <!-- Status Dropdown -->
                <div class="w-full sm:w-44 shrink-0">
                    <label class="sr-only">الحالة</label>
                    <select name="status" class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-900 dark:border-gray-700 dark:text-white transition-all">
                        <option value="">كل الحالات</option>
                        <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                        <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                    </select>
                </div>
                
                <!-- Search Button -->
                <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-sm shadow-blue-500/5 gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.637 10.637z" />
                    </svg>
                    بحث
                </button>
                
                <!-- Reset Button -->
                <?php if (!empty($search) || !empty($status_filter)): ?>
                <a href="<?php echo BASE_URL; ?>students/track.php" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:text-red-400 dark:hover:bg-red-900/20 rounded-xl transition-all gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    إعادة تعيين
                </a>
                <?php endif; ?>
            </form>
        </div>

        <style>
        .ticket-item.active {
            background-color: rgba(59, 130, 246, 0.05);
            border-right-color: #3b82f6 !important;
        }
        .dark .ticket-item.active {
            background-color: rgba(96, 165, 250, 0.08);
            border-right-color: #60a5fa !important;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.3);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.5);
        }
        @media (max-width: 767px) {
            .track-container.mobile-detail-open #ticket-list { display: none; }
            .track-container.mobile-detail-open #ticket-details { display: block; }
        }
        </style>

        <div class="track-container flex flex-col md:flex-row gap-6">
            <!-- Ticket List -->
            <div id="ticket-list" class="w-full md:w-96 md:shrink-0 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 overflow-hidden flex flex-col max-h-[calc(100vh-240px)] shadow-sm">
                <div class="px-4 py-3.5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex items-center justify-between shrink-0">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">قائمة التذاكر</span>
                    <span class="px-2 py-0.5 text-xs font-semibold text-blue-600 bg-blue-50 dark:text-blue-300 dark:bg-blue-900/30 rounded-full">
                        <?php echo count($tickets); ?> تذكرة
                    </span>
                </div>
                
                <div class="overflow-y-auto flex-1 divide-y divide-gray-100 dark:divide-gray-700/50 custom-scrollbar">
                    <?php if (empty($tickets)): ?>
                        <div class="px-4 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18" />
                            </svg>
                            <p class="text-sm text-gray-400 dark:text-gray-500 font-medium">لا توجد تذاكر متوفرة</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tickets as $i => $t): ?>
                            <?php
                            $status_ar = $t['status'] === 'open' ? 'مفتوحة' : ($t['status'] === 'in_progress' ? 'قيد التنفيذ' : 'مغلقة');
                            $status_color = $t['status'] === 'open' ? 'text-green-700 bg-green-50 dark:text-green-300 dark:bg-green-800/20' : ($t['status'] === 'in_progress' ? 'text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-800/20' : 'text-red-700 bg-red-50 dark:text-red-300 dark:bg-red-800/20');
                            ?>
                            <div class="ticket-item cursor-pointer p-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/20 border-r-4 border-transparent transition-all" data-ticket-id="<?php echo $t['id']; ?>">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1 flex-1 leading-snug"><?php echo htmlspecialchars($t['subject']); ?></h3>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0 font-medium"><?php echo date('Y-m-d', strtotime($t['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-1">
                                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-mono bg-gray-100 dark:bg-gray-900 px-1.5 py-0.5 rounded text-gray-600 dark:text-gray-300 font-semibold"><?php echo htmlspecialchars($t['ticket_number']); ?></span>
                                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                            <span class="truncate max-w-[120px]"><?php echo htmlspecialchars($t['category_name']); ?></span>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php echo $status_ar; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Panel -->
            <div id="ticket-details" data-api-url="<?php echo BASE_URL; ?>students/ajax/ticket-details.php" data-logo-url="<?php echo BASE_URL; ?>images/logo.webp" class="flex-1 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700 p-6 md:p-8 min-h-[400px] overflow-y-auto hidden md:block shadow-sm">
                <!-- Initial State -->
                <div class="flex flex-col items-center justify-center h-full py-16 text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-4 text-blue-600 dark:text-blue-400 animate-pulse">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">عرض تفاصيل التذكرة</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 max-w-sm">الرجاء اختيار إحدى التذاكر من القائمة الجانبية لعرض موضوع التذكرة، حالتها، والردود الواردة عليها من فريق الدعم الفني.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-5 text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 mt-12">
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
<script src="<?php echo BASE_URL; ?>students/js/track.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

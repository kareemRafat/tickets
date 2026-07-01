<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$student_name = $_SESSION['student_name'];
$national_id = $_SESSION['student_national_id'];
$student_code = $_SESSION['student_code'] ?? '';
$student_email = $_SESSION['student_email'] ?? '';

$db = getDBConnection();
$openCount = 0;
$inProgressCount = 0;
$closedCount = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE national_id = :nid AND status = 'open'");
    $stmt->execute(['nid' => $national_id]);
    $openCount = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE national_id = :nid AND status = 'in_progress'");
    $stmt->execute(['nid' => $national_id]);
    $inProgressCount = (int)$stmt->fetchColumn();
    $stmt = $db->prepare("SELECT COUNT(*) FROM student_tickets WHERE national_id = :nid AND status = 'closed'");
    $stmt->execute(['nid' => $national_id]);
    $closedCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Failed to fetch student ticket stats: " . $e->getMessage());
}

$totalTickets = $openCount + $inProgressCount + $closedCount;

$hide_sidebar = true;
$hide_navbar = true;
$pageTitle = 'بوابة الطلاب';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 px-4 md:px-8 pb-12 md:pb-16 pt-6">
    <!-- Welcome Bar -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-gradient-to-l from-blue-600/10 to-transparent dark:from-blue-400/5 dark:to-transparent rounded-3xl p-6 border border-blue-100 dark:border-blue-900/30">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-3 sm:gap-4 order-2 sm:order-1">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-lg shrink-0">
                        <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 dark:text-white truncate">مرحباً بك، <?php echo htmlspecialchars($student_name); ?></h1>
                        <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-1 font-bold">
                            <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">بوابة الطلاب .. تذاكر خدمة العملاء</span>
                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600 shrink-0 hidden sm:inline-block"></span>
                            <span class="hidden sm:inline text-xs sm:text-sm text-gray-500 dark:text-gray-400"><?php echo $totalTickets; ?> تذاكر</span>
                        </div>
                    </div>
                    <!-- Mobile three-dots -->
                    <div class="sm:hidden relative">
                        <button type="button" class="flex items-center justify-center w-9 h-9 text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/60 rounded-xl transition-all" data-dropdown-toggle="mobile-menu-dropdown">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        </button>
                        <div class="z-50 hidden my-2 text-base list-none bg-white divide-y divide-gray-100 rounded-2xl shadow-xl dark:bg-gray-700 dark:divide-gray-600 min-w-[200px]" id="mobile-menu-dropdown">
                            <div class="px-4 py-3" role="none">
                                <p class="text-sm font-bold text-gray-900 dark:text-white truncate" role="none"><?php echo htmlspecialchars($student_name); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" role="none"><?php echo htmlspecialchars($student_code ?: '—'); ?></p>
                            </div>
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
                <div class="flex items-center gap-2 self-end sm:self-auto order-1 sm:order-2">
                    <!-- Desktop: theme toggle -->
                    <button id="theme-toggle" type="button" class="hidden sm:inline-flex text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/60 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-xl text-sm p-2.5 transition-all">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05L5.75 4.343a1 1 0 10-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2h1a1 1 0 100 2H4z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>

                    <!-- Desktop: user avatar -->
                    <div class="hidden sm:flex items-center">
                        <button type="button" class="flex text-sm rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600 transition-all hover:ring-2 hover:ring-blue-300" id="student-menu-button" aria-expanded="false" data-dropdown-toggle="student-dropdown">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md">
                                <?php echo mb_substr($student_name, 0, 1); ?>
                            </div>
                        </button>
                        <div class="z-50 hidden my-3 text-base list-none bg-white divide-y divide-gray-100 rounded-2xl shadow-xl dark:bg-gray-700 dark:divide-gray-600 min-w-[220px]" id="student-dropdown">
                            <div class="px-5 py-4" role="none">
                                <p class="text-base font-bold text-gray-900 dark:text-white truncate" role="none"><?php echo htmlspecialchars($student_name); ?></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 truncate" role="none"><?php echo htmlspecialchars($student_code ?: '—'); ?></p>
                                <?php if ($student_email): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate" role="none"><?php echo htmlspecialchars($student_email); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="py-2" role="none">
                                <a href="<?php echo BASE_URL; ?>students/auth/logout.php" class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-xl transition-all mx-2" role="menuitem">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    تسجيل الخروج
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Cards -->
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- New Ticket Card -->
        <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="group bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="h-48 bg-cover bg-center relative" style="background-image: url('<?php echo BASE_URL; ?>images/add%20ticket.jfif');">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                <div class="absolute bottom-4 right-4">
                    <span class="bg-white/20 backdrop-blur-md text-white text-xs px-3 py-1 rounded-full border border-white/20">تذكرة جديدة</span>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-2xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">تقديم تذكرة</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">إنشاء تذكرة جديدة</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">للتواصل مع فريق الدعم الفني حول مشكلة أو استفسار، يمكنك تقديم تذكرة جديدة وسيتم الرد عليها في أقرب وقت.</p>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">تقديم الآن</span>
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </div>
            </div>
        </a>

        <!-- Track Tickets Card -->
        <a href="<?php echo BASE_URL; ?>students/track.php" class="group bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div class="h-48 bg-cover bg-center relative" style="background-image: url('<?php echo BASE_URL; ?>images/track%20tickets.jfif');">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                <?php if ($totalTickets > 0): ?>
                    <div class="absolute top-4 left-4">
                        <span class="bg-white/20 backdrop-blur-md text-white text-xs px-3 py-1 rounded-full border border-white/20"><?php echo $totalTickets; ?> تذاكر</span>
                    </div>
                <?php endif; ?>
                <div class="absolute bottom-4 right-4 flex gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-2xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">تتبع تذاكري</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">متابعة حالة تذاكرك</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">متابعة حالة التذاكر السابقة، الاطلاع على الردود والتحديثات من فريق الدعم الفني، والتأكد من سير العمل.</p>
                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">عرض التذاكر</span>
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Stats Bar -->
    <div class="max-w-6xl mx-auto mt-10 mb-12 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-blue-200 dark:border-blue-900/50 overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="h-1.5 bg-gradient-to-r from-blue-400 to-blue-600"></div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 rounded-full">مفتوحة</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $openCount; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-semibold">تذاكر مفتوحة تنتظر الرد</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-amber-200 dark:border-amber-900/50 overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="h-1.5 bg-gradient-to-r from-amber-400 to-amber-600"></div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2.5 py-1 rounded-full">قيد التنفيذ</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $inProgressCount; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-semibold">تذاكر قيد المعالجة حالياً</p>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-emerald-200 dark:border-emerald-900/50 overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="h-1.5 bg-gradient-to-r from-emerald-400 to-emerald-600"></div>
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-11 h-11 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 rounded-full">مغلقة</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $closedCount; ?></p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-semibold">تذاكر تم حلها وإغلاقها</p>
            </div>
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
require_once __DIR__ . '/../includes/footer.php';
?>

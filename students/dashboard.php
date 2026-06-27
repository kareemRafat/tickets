<?php
require_once __DIR__ . '/../bootstrap.php';

set_security_headers();
require_student();

$student_name = $_SESSION['student_name'];

$hide_sidebar = true;
$pageTitle = 'بوابة الطلاب';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="sm:mr-0 pt-20 flex-1 flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">
<main class="p-6 space-y-6 flex-1">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">مرحباً بك، <?php echo htmlspecialchars($student_name); ?></h1>
        <p class="mt-1 text-base text-gray-500 dark:text-gray-400">اختر إحدى الخدمات المتاحة من الأسفل.</p>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="flex-1 p-10 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700 group flex flex-col items-center text-center">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">تقديم شكوى / تذكرة</h2>
            <p class="text-base text-gray-500 dark:text-gray-400">إنشاء شكوى أو تذكرة جديدة للتواصل مع فريق الدعم الفني.</p>
        </a>
        <a href="<?php echo BASE_URL; ?>students/track.php" class="flex-1 p-10 bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700 group flex flex-col items-center text-center">
            <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">تتبع تذاكري</h2>
            <p class="text-base text-gray-500 dark:text-gray-400">متابعة حالة التذاكر السابقة والاطلاع على الردود والتحديثات.</p>
        </a>
    </div>
</main>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
</div>

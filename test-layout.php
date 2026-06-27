<?php
/**
 * Test layout and design verification page in Arabic RTL.
 * Loads includes, sidebar, dark mode controls, and a preview of premium styling.
 */
require_once __DIR__ . '/bootstrap.php';

// Define dummy user session for previewing user interface items
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'المدير العام';
$_SESSION['user_email'] = 'admin@crv.com';
$_SESSION['user_role'] = 'admin';

// Set active success toast
$_SESSION['success'] = 'مرحباً بك! تم إعداد إطار عمل Tailwind CSS v3 ومكونات Flowbite بنجاح باللغة العربية (RTL).';

$pageTitle = 'معاينة لوحة التحكم والتصميم';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Area -->
<main class="p-6 space-y-6 flex-1">
    <!-- Header banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                نظام التصميم ومعاينة المظهر (RTL)
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                معاينة للشبكة، شريط التنقل الجانبي الأيمن، دعم السمة الداكنة والمكونات التفاعلية المتناسقة.
            </p>
        </div>
        <div class="flex items-center space-x-3 space-x-reverse">
            <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-blue-300">
                إجراء رئيسي
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-gray-100">
                إجراء ثانوي
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat Card 1 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي التذاكر</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    +12.5%
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">142</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">هذا الشهر</span>
            </div>
        </div>
        <!-- Stat Card 2 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">معدل حل التذاكر</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    ممتاز
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">94.2%</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">المتوسط</span>
            </div>
        </div>
        <!-- Stat Card 3 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">متوسط وقت الاستجابة</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    1.4 ساعة
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">52 د</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">دقيقة</span>
            </div>
        </div>
        <!-- Stat Card 4 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">فريق الدعم النشط</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    8 متصلين
                </span>
            </div>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">12</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">إجمالي الموظفين</span>
            </div>
        </div>
    </div>

    <!-- Banner details -->
    <div class="p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl text-white shadow-lg">
        <h3 class="text-xl font-bold">تم إعداد الهيكل الرئيسي للوحة التحكم بنجاح</h3>
        <p class="mt-2 text-sm opacity-90 max-w-xl">
            تم إعداد المشروع لدعم اللغة العربية وتخطيط الاتجاه من اليمين لليسار (RTL) بالتوافق الكامل مع TailwindCSS v3 ومكونات مكتبة Flowbite. يمكنك تفعيل المظهر الداكن وتجربة القوائم والمنبثقات التفاعلية.
        </p>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

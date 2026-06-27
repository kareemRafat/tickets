<?php
/**
 * Test layout and design verification page.
 * Loads includes, sidebar, dark mode controls, and a preview of premium styling.
 */

// Define dummy user session for previewing user interface items
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Super Admin';
$_SESSION['user_email'] = 'admin@crv.com';
$_SESSION['user_role'] = 'admin';

// Set active success toast
$_SESSION['success'] = 'Welcome! Tailwind CSS v3 and Flowbite are successfully configured.';

$pageTitle = 'Design Layout Test';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Area -->
<main class="p-6 space-y-6 flex-1">
    <!-- Header banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                Design System & Layout Preview
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                A verification preview of the grid, sidebar navigation, dark theme integration, and interactive components.
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-blue-300">
                Primary Action
            </button>
            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-gray-100">
                Secondary Action
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat Card 1 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tickets</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    +12.5%
                </span>
            </div>
            <div class="mt-4 flex items-baseline">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">142</span>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">this month</span>
            </div>
        </div>
        <!-- Stat Card 2 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Resolved Rate</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    High
                </span>
            </div>
            <div class="mt-4 flex items-baseline">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">94.2%</span>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">average</span>
            </div>
        </div>
        <!-- Stat Card 3 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg. Response Time</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    1.4 hrs
                </span>
            </div>
            <div class="mt-4 flex items-baseline">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">52m</span>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">mins</span>
            </div>
        </div>
        <!-- Stat Card 4 -->
        <div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Staff</span>
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    8 Online
                </span>
            </div>
            <div class="mt-4 flex items-baseline">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white">12</span>
                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">total</span>
            </div>
        </div>
    </div>

    <!-- Banner details -->
    <div class="p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl text-white shadow-lg">
        <h3 class="text-xl font-bold">Premium Layout Shell Configured</h3>
        <p class="mt-2 text-sm opacity-90 max-w-xl">
            This workspace has been successfully integrated with TailwindCSS v3 and Flowbite components. Toggle dark mode in the header navbar to inspect dark/light theme adjustments.
        </p>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

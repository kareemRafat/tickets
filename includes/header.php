<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SYSTEM_NAME : SYSTEM_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    
    <!-- Tailwind compiled output -->
    <link href="<?php echo BASE_URL; ?>assets/css/styles.css" rel="stylesheet">
    
    <!-- Google Fonts: Cairo for a modern, clean, premium Arabic look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
    </style>
    
    <script>
        // Inline script to prevent screen flash (FOUC) by checking dark mode class immediately
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased flex flex-col">
    <!-- Top Navbar -->
    <nav class="fixed top-0 z-40 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 backdrop-blur-md bg-opacity-80 dark:bg-opacity-80">
        <div class="px-4 py-3 lg:px-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start gap-2">
                    <?php if (empty($hide_sidebar)): ?>
                    <!-- Toggle sidebar mobile button -->
                    <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar" type="button" class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                        <span class="sr-only">فتح القائمة الجانبية</span>
                        <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                           <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>
                    <?php endif; ?>
                    
                    <a href="<?php echo BASE_URL; ?>" class="flex">
                        <!-- Premium Gradient Text logo -->
                        <span class="self-center text-xl font-bold sm:text-2xl whitespace-nowrap bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent">
                            <?php echo SYSTEM_NAME; ?>
                        </span>
                    </a>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Dark Mode Toggle Button -->
                    <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2">
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.46 5.05L5.75 4.343a1 1 0 10-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2h1a1 1 0 100 2H4z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                    </button>

                    <?php if (isset($_SESSION['student_national_id']) && !isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>students/logout.php" class="px-3 py-1.5 text-sm text-red-600 border border-red-200 hover:bg-red-50 dark:text-red-400 dark:border-red-900/50 dark:hover:bg-red-900/20 rounded-xl transition-all">تسجيل الخروج</a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Profile Dropdown -->
                    <div class="flex items-center">
                        <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                            <span class="sr-only">فتح قائمة المستخدم</span>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'م', 0, 1)); ?>
                            </div>
                        </button>
                        <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded shadow dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
                            <div class="px-4 py-3" role="none">
                                <p class="text-sm text-gray-900 dark:text-white font-semibold" role="none">
                                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'مستخدم'); ?>
                                </p>
                                <p class="text-xs font-medium text-gray-500 truncate dark:text-gray-400" role="none">
                                    <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>
                                </p>
                                <?php 
                                $role_ar = 'زائر';
                                if (($_SESSION['user_role'] ?? '') === 'admin') {
                                    $role_ar = 'مدير النظام';
                                } elseif (($_SESSION['user_role'] ?? '') === 'employee') {
                                    $role_ar = 'موظف دعم';
                                } elseif (($_SESSION['user_role'] ?? '') === 'student') {
                                    $role_ar = 'طالب';
                                }
                                ?>
                                <span class="inline-flex items-center px-2 py-0.5 mt-2 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    <?php echo htmlspecialchars($role_ar); ?>
                                </span>
                            </div>
                            <div class="py-1" role="none">
                                <?php 
                                $logout_url = BASE_URL;
                                if (($_SESSION['user_role'] ?? '') === 'admin') {
                                    $logout_url .= 'admin/logout.php';
                                } elseif (($_SESSION['user_role'] ?? '') === 'employee') {
                                    $logout_url .= 'support/logout.php';
                                } else {
                                    $logout_url .= 'students/logout.php';
                                }
                                ?>
                                <a href="<?php echo $logout_url; ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:text-red-400 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">تسجيل الخروج</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

<?php
$current_page = $activePage ?? basename($_SERVER['SCRIPT_NAME'], '.php');
$role = $_SESSION['user_role'] ?? '';

// Map current page to the active nav item (role-aware)
$nav = '';
if ($role === 'student') {
    if ($current_page === 'index') {
        $nav = 'dashboard';
    } elseif ($current_page === 'ticket-create') {
        $nav = 'ticket-create';
    } elseif (in_array($current_page, ['track', 'ticket-view'])) {
        $nav = 'track';
    }
} else {
    // admin / employee
    if ($current_page === 'index') {
        $nav = 'dashboard';
    } elseif (in_array($current_page, ['employees', 'employee-add', 'employee-edit'])) {
        $nav = 'employees';
    } elseif ($current_page === 'branches') {
        $nav = 'branches';
    } elseif ($current_page === 'categories') {
        $nav = 'categories';
    } elseif ($current_page === 'logs') {
        $nav = 'logs';
    } elseif (($current_page === 'tickets' || $current_page === 'ticket-create' || $current_page === 'ticket-view') && (!isset($_GET['type']) || $_GET['type'] === 'support')) {
        $nav = 'support-tickets';
    } elseif (($current_page === 'tickets' || $current_page === 'ticket-create' || $current_page === 'ticket-view') && isset($_GET['type']) && $_GET['type'] === 'student') {
        $nav = 'student-tickets';
    }
}

$active_classes = 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300 font-semibold border-r-[3px] border-blue-600 dark:border-blue-400 shadow-sm';
$icon_active = 'text-blue-600 dark:text-blue-300 scale-110';
$icon_default = 'text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400';
?>

<aside id="logo-sidebar" class="fixed top-0 right-0 z-30 w-64 h-screen pt-20 transition-transform translate-x-full bg-white border-l border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar">
   <div class="h-full px-4 py-4 overflow-y-auto bg-white dark:bg-gray-800">
      <ul class="space-y-2 font-medium">
         
         <?php if ($role === 'admin'): ?>
            <div class="pt-2 pb-1 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider dark:text-gray-500">
                لوحة الإدارة
            </div>
            <li>
               <a href="<?php echo BASE_URL; ?>admin/index.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'dashboard' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'dashboard' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                     <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1z"/>
                     <path d="M12.5 10h5a1 1 0 0 0 .991-.892A7.5 7.5 0 0 0 13.52.41a1 1 0 0 0-1.02 1.018V10z"/>
                  </svg>
                  <span class="mr-3">لوحة التحكم</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>admin/employees.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'employees' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'employees' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                  <span class="mr-3">الموظفون والإداريون</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>admin/branches.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'branches' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'branches' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">الفروع</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>admin/categories.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'categories' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'categories' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm14 1a1 1 0 100-2 1 1 0 000 2zm-4-1a1 1 0 11-2 0 1 1 0 012 0zM4 11a2 2 0 00-2 2v2a2 2 0 002 2h12a2 2 0 002-2v-2a2 2 0 00-2-2H4zm10 2a1 1 0 100 2 1 1 0 000-2zm-4 1a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">التصنيفات الأقسام</span>
               </a>
            </li>
            <div class="pt-4 pb-1 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider dark:text-gray-500">
                إدارة التذاكر
            </div>
            <li>
               <a href="<?php echo BASE_URL; ?>support/tickets.php?type=support" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'support-tickets' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'support-tickets' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">تذاكر الدعم الفني</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>support/tickets.php?type=student" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'student-tickets' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'student-tickets' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">شكاوى الطلاب</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>admin/logs.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'logs' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'logs' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 2h4v2H6V6zm0 4h8v2H6v-2zm0 4h8v2H6v-2z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">سجلات العمليات</span>
               </a>
            </li>

         <?php elseif ($role === 'employee'): ?>
            <div class="pt-2 pb-1 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider dark:text-gray-500">
                لوحة الدعم الفني
            </div>
            <li>
               <a href="<?php echo BASE_URL; ?>support/index.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'dashboard' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'dashboard' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                     <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1z"/>
                     <path d="M12.5 10h5a1 1 0 0 0 .991-.892A7.5 7.5 0 0 0 13.52.41a1 1 0 0 0-1.02 1.018V10z"/>
                  </svg>
                  <span class="mr-3">لوحة التحكم</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>support/tickets.php?type=support" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'support-tickets' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'support-tickets' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">تذاكر الدعم الفني</span>
               </a>
            </li>
            <li>
               <a href="<?php echo BASE_URL; ?>support/tickets.php?type=student" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'student-tickets' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'student-tickets' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                  </svg>
                  <span class="mr-3">شكاوى الطلاب</span>
               </a>
            </li>

         <?php else: ?>
            <div class="pt-2 pb-1 px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider dark:text-gray-500">
                بوابة الطلاب
            </div>
            <li>
               <a href="<?php echo BASE_URL; ?>students/login.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $current_page === 'login' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $current_page === 'login' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                     <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                  </svg>
                  <span class="mr-3">الرئيسية للبوابة</span>
               </a>
            </li>
            
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
            <li>
               <a href="<?php echo BASE_URL; ?>students/index.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'dashboard' ? $active_classes : ''; ?>">
                  <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'dashboard' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                     <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1z"/>
                     <path d="M12.5 10h5a1 1 0 0 0 .991-.892A7.5 7.5 0 0 0 13.52.41a1 1 0 0 0-1.02 1.018V10z"/>
                  </svg>
                  <span class="mr-3">لوحة التحكم الخاصة بي</span>
               </a>
            </li>
             <li>
                <a href="<?php echo BASE_URL; ?>students/ticket-create.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'ticket-create' ? $active_classes : ''; ?>">
                   <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'ticket-create' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                   </svg>
                   <span class="mr-3">تقديم شكوى / تذكرة</span>
                </a>
             </li>
             <li>
                <a href="<?php echo BASE_URL; ?>students/track.php" class="flex items-center p-2.5 text-gray-900  dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group text-sm transition-all <?php echo $nav === 'track' ? $active_classes : ''; ?>">
                   <svg class="w-5 h-5 transition duration-75 <?php echo $nav === 'track' ? $icon_active : $icon_default; ?>" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                   </svg>
                   <span class="mr-3">تتبع تذاكري</span>
                </a>
             </li>
            <?php endif; ?>
            
         <?php endif; ?>
         
      </ul>
   </div>
</aside>

<div class="sm:mr-64 pt-20 flex-1 flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">

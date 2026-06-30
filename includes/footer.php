    <!-- Password Toggle -->
    <script src="<?php echo BASE_URL; ?>js/password-toggle.js"></script>

    <!-- Flowbite JavaScript -->
    <script src="<?php echo BASE_URL; ?>node_modules/flowbite/dist/flowbite.min.js"></script>

    <!-- Theme and Global Scripts -->
    <script>
        // Dark Mode Toggle Logic
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (themeToggleDarkIcon && themeToggleLightIcon) {
            // Adjust icons based on current state
            if (document.documentElement.classList.contains('dark')) {
                themeToggleLightIcon.classList.remove('hidden');
            } else {
                themeToggleDarkIcon.classList.remove('hidden');
            }

            const themeToggleBtn = document.getElementById('theme-toggle');

            themeToggleBtn.addEventListener('click', function() {
                // Toggle icons
                themeToggleDarkIcon.classList.toggle('hidden');
                themeToggleLightIcon.classList.toggle('hidden');

                // Toggle class and store preference
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
            });
        }
    </script>

    <!-- Toast Notification System in Arabic -->
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
        <div id="toast-notification" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 w-full max-w-md p-4 text-gray-500 bg-white rounded-lg shadow-xl dark:text-gray-400 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 animate-slide-down" role="alert">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                    </svg>
                </div>
                <div class="flex-1 text-sm font-bold"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php else: ?>
                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200">
                    <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/>
                    </svg>
                </div>
                <div class="flex-1 text-sm font-bold"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <button type="button" class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-gray-400 hover:text-gray-900 rounded-lg hover:bg-gray-100 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-700" data-dismiss-target="#toast-notification" aria-label="إغلاق">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
        <style>
            @keyframes slideDown {
                0% { transform: translate(-50%, -100%); opacity: 0; }
                100% { transform: translate(-50%, 0); opacity: 1; }
            }
            .animate-slide-down {
                animation: slideDown 0.4s ease-out forwards;
            }
        </style>
        <script>
            // Automatically dismiss toast after 5 seconds
            setTimeout(function() {
                const toast = document.getElementById('toast-notification');
                if (toast) {
                    toast.style.transition = 'all 0.4s ease-in';
                    toast.style.opacity = '0';
                    toast.style.transform = 'translate(-50%, -20px)';
                    setTimeout(function() { toast.remove(); }, 400);
                }
            }, 5000);
        </script>
    <?php endif; ?>
</body>
</html>

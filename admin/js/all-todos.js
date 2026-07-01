document.addEventListener('DOMContentLoaded', function () {
    var dateEl = document.getElementById('all-todos-date');
    var employeeEl = document.getElementById('all-todos-employee');
    var statusEl = document.getElementById('all-todos-status');
    var searchEl = document.getElementById('all-todos-search');
    var resetBtn = document.getElementById('all-todos-reset');
    var tbody = document.getElementById('all-todos-body');
    var paginationEl = document.getElementById('all-todos-pagination');
    var totalCountEl = document.getElementById('total-count');

    var baseUrl = 'ajax/all-todos.php';
    var currentPage = 1;
    var totalPages = 1;

    function loadTodos(page) {
        if (page !== undefined) currentPage = page;
        else currentPage = 1;

        var params = [];
        if (dateEl && dateEl.value) params.push('date=' + dateEl.value);
        if (employeeEl && employeeEl.value) params.push('assigned_to=' + employeeEl.value);
        if (statusEl && statusEl.value) params.push('status=' + statusEl.value);
        if (searchEl && searchEl.value.trim()) params.push('search=' + encodeURIComponent(searchEl.value.trim()));
        params.push('page=' + currentPage);

        var url = baseUrl + '?' + params.join('&');

        tbody.innerHTML =
            '<tr><td colspan="7" class="px-4 py-16 text-center">' +
            '<svg class="animate-spin h-8 w-8 mx-auto mb-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
            '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
            '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>' +
            '<p class="text-gray-500 dark:text-gray-400 text-sm">جارٍ تحميل المهام...</p></td></tr>';

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-red-500 text-sm">' + (data.message || 'حدث خطأ في تحميل المهام') + '</td></tr>';
                    if (paginationEl) paginationEl.classList.add('hidden');
                    return;
                }
                renderTodos(data.data);
                if (totalCountEl) totalCountEl.textContent = data.total;
                totalPages = data.total_pages;
                renderPagination(data.total, data.total_pages, data.current_page);
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-10 text-center text-red-500 text-sm">فشل الاتصال بالخادم</td></tr>';
                if (paginationEl) paginationEl.classList.add('hidden');
            });
    }

    function renderTodos(todos) {
        if (!todos || !todos.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-16 text-center text-gray-400 dark:text-gray-500 text-sm">لا توجد مهام للعرض</td></tr>';
            return;
        }

        var html = '';
        todos.forEach(function (t, i) {
            var statusBadge = t.status === 'done'
                ? '<span class="px-2.5 py-1 text-xs font-bold text-green-700 bg-green-100 dark:bg-green-800/40 dark:text-green-300 rounded-full">منتهية</span>'
                : '<span class="px-2.5 py-1 text-xs font-bold text-amber-700 bg-amber-100 dark:bg-amber-800/40 dark:text-amber-300 rounded-full">معلقة</span>';

            var rowClass = t.status === 'done'
                ? 'bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800/30'
                : 'bg-amber-50 dark:bg-amber-900/15 border-b border-amber-200 dark:border-amber-800/30';

            var dueDate = t.due_date ? t.due_date : '<span class="text-gray-400 dark:text-gray-500">—</span>';

            html += '<tr class="' + rowClass + ' transition-colors">' +
                '<td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 text-sm">' + (i + 1) + '</td>' +
                '<td class="px-4 py-3 font-medium text-gray-900 dark:text-white text-sm">' + t.title + '</td>' +
                '<td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">' + t.assigned_to_name + '</td>' +
                '<td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">' + t.assigned_by_name + '</td>' +
                '<td class="px-4 py-3 text-gray-700 dark:text-gray-300 text-sm">' + dueDate + '</td>' +
                '<td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">' + (t.created_at ? t.created_at.slice(0, 10) : '—') + '</td>' +
                '<td class="px-4 py-3 text-center">' + statusBadge + '</td>' +
                '</tr>';
        });

        tbody.innerHTML = html;
    }

    function renderPagination(total, totalPages, currentPage) {
        if (!paginationEl) return;
        if (totalPages <= 1) {
            paginationEl.classList.add('hidden');
            return;
        }
        paginationEl.classList.remove('hidden');
        paginationEl.style.display = 'flex';

        var range = 2;
        var start = Math.max(1, currentPage - range);
        var end = Math.min(totalPages, currentPage + range);

        var html = '';
        html += '<span class="text-sm font-medium text-gray-500 dark:text-gray-400">صفحة ' + currentPage + ' من ' + totalPages + ' (' + total + ' سجل)</span>';
        html += '<ul class="inline-flex items-center gap-1 text-sm">';

        // Prev
        if (currentPage > 1) {
            html += '<li><a href="#" data-page="' + (currentPage - 1) + '" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">' +
                    '<svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>السابق</a></li>';
        }

        // First + ellipsis
        if (start > 1) {
            html += '<li><a href="#" data-page="1" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">1</a></li>';
            if (start > 2) {
                html += '<li><span class="px-2 py-2 text-gray-400 dark:text-gray-500">...</span></li>';
            }
        }

        // Pages
        for (var i = start; i <= end; i++) {
            if (i === currentPage) {
                html += '<li><span class="flex items-center justify-center px-3 py-2 font-bold text-white bg-blue-600 border border-blue-600 rounded-lg cursor-default">' + i + '</span></li>';
            } else {
                html += '<li><a href="#" data-page="' + i + '" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">' + i + '</a></li>';
            }
        }

        // Last + ellipsis
        if (end < totalPages) {
            if (end < totalPages - 1) {
                html += '<li><span class="px-2 py-2 text-gray-400 dark:text-gray-500">...</span></li>';
            }
            html += '<li><a href="#" data-page="' + totalPages + '" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">' + totalPages + '</a></li>';
        }

        // Next
        if (currentPage < totalPages) {
            html += '<li><a href="#" data-page="' + (currentPage + 1) + '" class="flex items-center justify-center px-3 py-2 font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white transition-all">التالي' +
                    '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a></li>';
        }

        html += '</ul>';
        paginationEl.innerHTML = html;

        // Bind click events
        paginationEl.querySelectorAll('a[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                var pg = parseInt(this.getAttribute('data-page'));
                if (pg && pg !== currentPage) {
                    loadTodos(pg);
                }
            });
        });
    }

    // Filter changes reset to page 1
    function onFilterChange() {
        currentPage = 1;
        loadTodos(1);
    }

    if (dateEl) dateEl.addEventListener('change', onFilterChange);
    if (employeeEl) employeeEl.addEventListener('change', onFilterChange);
    if (statusEl) statusEl.addEventListener('change', onFilterChange);

    // Search debounce
    if (searchEl) {
        var searchTimeout;
        searchEl.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(onFilterChange, 300);
        });
    }

    // Reset
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (dateEl) dateEl.value = new Date().toISOString().split('T')[0];
            if (employeeEl) employeeEl.value = '';
            if (statusEl) statusEl.value = '';
            if (searchEl) searchEl.value = '';
            onFilterChange();
        });
    }

    // Initial load
    loadTodos(1);
});

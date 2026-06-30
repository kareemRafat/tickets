document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('todos-container');
    if (!container) return;

    const listUrl = container.dataset.listUrl;
    const createUrl = container.dataset.createUrl;
    const toggleUrl = container.dataset.toggleUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const listEl = document.getElementById('todos-list');
    const createForm = document.getElementById('todo-create-form');
    const pendingCountEl = document.getElementById('pending-count');
    const dateFilterEl = document.getElementById('todo-date-filter');

    function showToast(msg, type) {
        var isSuccess = type === 'success';
        var iconHtml = isSuccess
            ? '<div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg></div>'
            : '<div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"/></svg></div>';

        var toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 w-full max-w-md p-4 text-gray-500 bg-white rounded-lg shadow-xl dark:text-gray-400 dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
        toast.style.animation = 'slideDown 0.4s ease-out forwards';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = iconHtml +
            '<div class="flex-1 text-sm font-bold">' + msg + '</div>' +
            '<button type="button" class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-gray-400 hover:text-gray-900 rounded-lg hover:bg-gray-100 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-700" data-dismiss-target="#toast-notification" aria-label="إغلاق">' +
            '<svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg></button>';

        document.body.appendChild(toast);

        setTimeout(function () {
            toast.style.transition = 'all 0.4s ease-in';
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, -20px)';
            setTimeout(function () { toast.remove(); }, 400);
        }, 4000);
    }

    function showError(msg) { showToast(msg, 'error'); }
    function showSuccess(msg) { showToast(msg, 'success'); }

    function todayEgypt() {
        var now = new Date();
        var offset = now.getTimezoneOffset();
        var cairoOffset = -120;
        var diff = cairoOffset - offset;
        var cairo = new Date(now.getTime() + diff * 60000);
        var y = cairo.getFullYear();
        var m = String(cairo.getMonth() + 1).padStart(2, '0');
        var d = String(cairo.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function formatDate(dateStr) {
        if (!dateStr) return null;
        var d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dateStr;
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function isOverdue(dateStr) {
        if (!dateStr) return false;
        var today = todayEgypt();
        var d = dateStr.length > 10 ? dateStr.slice(0, 10) : dateStr;
        return d < today;
    }

    function renderTodos(data) {
        var pending = data.pending || [];
        var done = data.done || [];

        pendingCountEl.textContent = pending.length;

        var html = '';

        // Pending section
        html += '<div class="mb-2">';
        html += '<div class="flex items-center gap-2 mb-4">';
        html += '<h3 class="text-lg font-bold text-gray-900 dark:text-white">المهام الحالية</h3>';
        html += '<span class="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-800/40 dark:text-amber-300 rounded-full">' + pending.length + '</span>';
        html += '</div>';

        if (pending.length === 0) {
            html += '<div class="text-center py-10 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-200 dark:border-gray-700">';
            html += '<svg class="w-14 h-14 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            html += '<p class="text-base text-gray-500 dark:text-gray-400">🎉 لا توجد مهام معلقة! كل المهام مُنجزة</p>';
            html += '</div>';
        } else {
            html += '<div class="space-y-2">';
            pending.forEach(function (t) {
                html += renderTodoCard(t, false);
            });
            html += '</div>';
        }
        html += '</div>';

        // Done section
        html += '<div class="mt-8">';
        html += '<div class="flex items-center gap-2 mb-4">';
        html += '<h3 class="text-lg font-bold text-gray-500 dark:text-gray-400">المهام المُنجزة</h3>';
        html += '<span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/40 dark:text-green-300 rounded-full">' + done.length + '</span>';
        html += '</div>';

        if (done.length === 0) {
            html += '<div class="text-center py-8 bg-gray-50/50 dark:bg-gray-700/10 rounded-xl border border-gray-200/50 dark:border-gray-700/50">';
            html += '<p class="text-sm text-gray-400 dark:text-gray-500">لا توجد مهام مُنجزة بعد</p>';
            html += '</div>';
        } else {
            html += '<div class="space-y-2">';
            done.forEach(function (t) {
                html += renderTodoCard(t, true);
            });
            html += '</div>';
        }
        html += '</div>';

        listEl.innerHTML = html;
    }

    function renderTodoCard(t, isDone) {
        var overdue = !isDone && isOverdue(t.due_date);
        var dateLabel = t.due_date ? formatDate(t.due_date) : null;
        var dueHtml = '';
        if (dateLabel) {
            dueHtml = '<span class="inline-flex items-center gap-1 text-sm ' + (overdue ? 'text-red-500 dark:text-red-400 font-semibold' : 'text-gray-400 dark:text-gray-500') + '">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' +
                (overdue ? 'متأخرة - ' : '') + dateLabel +
                '</span>';
        }

        var completedHtml = '';
        if (isDone && t.completed_at) {
            completedHtml = '<span class="text-sm text-green-500 dark:text-green-400 font-medium">أُنجزت في ' + formatDate(t.completed_at) + '</span>';
        }

        return '<div class="flex items-start gap-4 p-5 bg-white rounded-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:shadow-sm transition-shadow todo-card ' + (isDone ? 'opacity-75' : '') + '" data-id="' + t.id + '">' +
            '<button class="todo-toggle shrink-0 mt-1 w-8 h-8 rounded-full border-2 flex items-center justify-center transition-all ' +
            (isDone
                ? 'bg-green-500 border-green-500 text-white hover:bg-green-600 hover:border-green-600'
                : 'border-gray-300 dark:border-gray-600 text-gray-300 dark:text-gray-600 hover:text-blue-500 hover:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400') +
            '" data-id="' + t.id + '" title="' + (isDone ? 'إعادة فتح' : 'إتمام') + '">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' +
            '</button>' +

            '<div class="flex-1 min-w-0">' +
            '<div class="flex items-start justify-between gap-2">' +
            '<p class="text-base font-semibold text-gray-900 dark:text-white ' + (isDone ? 'line-through text-gray-400 dark:text-gray-500' : '') + '">' + t.title + '</p>' +
            '</div>' +
            '<div class="flex items-center gap-3 mt-2 flex-wrap">' +
            '<span class="inline-flex items-center gap-1 text-sm text-gray-400 dark:text-gray-500">' +
            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>' +
            'من: ' + t.assigned_by_name +
            '</span>' +
            (dueHtml ? dueHtml : '') +
            (completedHtml ? completedHtml : '') +
            '</div>' +
            '</div>' +
            '</div>';
    }

    function loadTodos() {
        var dateVal = dateFilterEl ? dateFilterEl.value : '';
        var url = listUrl + '&date=' + (dateVal || todayEgypt());
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    showError(data.message || 'حدث خطأ في تحميل المهام');
                    return;
                }
                renderTodos(data.data);
            })
            .catch(function () {
                showError('فشل الاتصال بالخادم');
            });
    }

    // Toggle handler (event delegation)
    listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.todo-toggle');
        if (btn) {
            e.preventDefault();
            var id = btn.dataset.id;
            var formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            fetch(toggleUrl, { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showError(data.message || 'حدث خطأ');
                        return;
                    }
                    showSuccess(data.message);
                    loadTodos();
                })
                .catch(function () {
                    showError('فشل الاتصال بالخادم');
                });
        }

    });

    // Date filter change
    if (dateFilterEl) {
        dateFilterEl.addEventListener('change', function () {
            loadTodos();
        });
    }

    // Create form
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(createForm);
            formData.append('action', 'create');

            var btn = createForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 inline ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ الإنشاء...';

            fetch(createUrl, { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showError(data.message || 'حدث خطأ');
                        btn.disabled = false;
                        btn.innerHTML = '➕ إنشاء المهمة';
                        return;
                    }
                    showSuccess(data.message);
                    createForm.reset();
                    btn.disabled = false;
                    btn.innerHTML = '➕ إنشاء المهمة';
                    loadTodos();
                })
                .catch(function () {
                    showError('فشل الاتصال بالخادم');
                    btn.disabled = false;
                    btn.innerHTML = '➕ إنشاء المهمة';
                });
        });
    }

    // Initial load
    loadTodos();
});

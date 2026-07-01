document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('todos-container');
    if (!container) return;

    const listUrl = container.dataset.listUrl;
    const createUrl = container.dataset.createUrl;
    const toggleUrl = container.dataset.toggleUrl;
    const editUrl = container.dataset.editUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const listEl = document.getElementById('todos-list');
    const createForm = document.getElementById('todo-create-form');
    const pendingCountEl = document.getElementById('pending-count');
    const dateFilterEl = document.getElementById('todo-date-filter');
    const editModal = document.getElementById('edit-todo-modal');
    const editForm = document.getElementById('edit-todo-form');
    const editTitle = document.getElementById('edit-todo-title');
    const editAssignedTo = document.getElementById('edit-todo-assigned-to');
    const editDueDate = document.getElementById('edit-todo-due-date');
    const editIdInput = document.getElementById('edit-todo-id');
    const assignedToFilterEl = document.getElementById('todo-assigned-to-filter');

    var currentView = container.dataset.currentView || 'assigned_to_me';

    function showToast(msg, type) {
        var isSuccess = type === 'success';
        var iconHtml = isSuccess
            ? '<div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-200"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg></div>'
            : '<div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-200"><svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg></div>';

        var toast = document.createElement('div');
        toast.id = 'toast-notification';
        toast.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 w-full max-w-md p-4 text-gray-500 bg-white rounded-lg shadow-xl dark:text-gray-400 dark:bg-gray-800 border border-gray-300 dark:border-gray-700';
        toast.style.animation = 'slideDown 0.4s ease-out forwards';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = iconHtml +
            '<div class="flex-1 text-sm font-bold">' + msg + '</div>' +
            '<button type="button" class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-gray-400 hover:text-gray-900 rounded-lg hover:bg-gray-100 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-700" data-dismiss-target="#toast-notification" aria-label="إغلاق">' +
            '<svg class="w-3 h-3" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>';

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

    function isEditable(t) {
        if (t.assigned_by_id !== CURRENT_USER_ID) return false;
        if (t.status === 'done') return false;
        if (!t.created_at) return false;
        var created = new Date(t.created_at.replace(' ', 'T'));
        var now = new Date();
        var diffMs = now - created;
        var diffMin = diffMs / 60000;
        return diffMin < 30;
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
        html += (currentView === 'assigned_to_me' ? '<span class="mr-auto px-2 py-1.5 text-sm font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300 rounded inline-flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>يمكنك تعديل المهام التي قمت بإنشائها فقط</span>' : '');
        html += '</div>';

        if (pending.length === 0) {
            html += '<div class="text-center py-10 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-300 dark:border-gray-700">';
            html += '<svg class="w-14 h-14 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
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
            html += '<div class="text-center py-8 bg-gray-50/50 dark:bg-gray-700/10 rounded-xl border border-gray-300/50 dark:border-gray-700/50">';
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
            dueHtml = '<span class="inline-flex items-center gap-1 text-sm ' + (overdue ? 'text-red-500 dark:text-red-400 font-bold' : 'text-gray-400 dark:text-gray-500') + '">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>' +
                (overdue ? 'متأخرة - ' : '') + dateLabel +
                '</span>';
        }

        var completedHtml = '';
        if (isDone && t.completed_at) {
            completedHtml = '<span class="text-sm font-bold text-green-600 dark:text-green-400">أُنجزت في ' + formatDate(t.completed_at) + '</span>';
        }

        var toggleHtml = '';
        if (t.can_toggle) {
            toggleHtml = '<button class="todo-toggle shrink-0 mt-0.5 w-5 h-5 rounded border-2 flex items-center justify-center transition-all ' +
                (isDone
                    ? 'bg-green-500 border-green-500 text-white hover:bg-green-600'
                    : 'border-gray-400 dark:border-gray-500 hover:border-blue-500 dark:hover:border-blue-400 bg-gray-50 dark:bg-gray-700') +
                '" data-id="' + t.id + '" title="' + (isDone ? 'إعادة فتح' : 'إتمام') + '">' +
                (isDone
                    ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>'
                    : '') +
                '</button>';
        }

        var nameLabel = currentView === 'assigned_to_me'
            ? 'بواسطة : ' + t.assigned_by_name
            : 'إلى : ' + t.assigned_to_name;

        return '<div class="flex items-stretch gap-0 bg-white rounded-xl border border-gray-300 dark:bg-gray-800 dark:border-gray-700 hover:shadow-sm transition-shadow todo-card overflow-hidden ' + (isDone ? 'opacity-75' : '') + '" data-id="' + t.id + '">' +
            '<div class="flex items-start gap-4 p-5 flex-1 min-w-0">' +
            toggleHtml +

            '<div class="flex-1 min-w-0">' +
            '<div class="flex items-start justify-between gap-2">' +
            '<p class="text-base font-semibold text-gray-900 dark:text-white ' + (isDone ? 'line-through text-gray-400 dark:text-gray-500' : '') + '">' + t.title + '</p>' +
            '</div>' +
            '<div class="flex items-center gap-3 mt-2 flex-wrap">' +
            '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-sm font-semibold bg-pink-700 text-white rounded dark:bg-gray-700 dark:text-gray-300">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>' +
            nameLabel +
            '</span>' +
            (dueHtml ? dueHtml : '') +
            (completedHtml ? completedHtml : '') +
            '</div>' +
            '</div>' +
            '</div>' +
            (currentView === 'assigned_to_me' && t.can_edit
                ? '<button class="todo-edit flex flex-col items-center justify-center gap-1 px-5 text-xs font-bold transition-all shrink-0 ' +
                (isEditable(t)
                    ? 'text-white bg-sky-500 hover:bg-sky-600 dark:bg-sky-600 dark:hover:bg-sky-700 cursor-pointer'
                    : 'text-gray-400 bg-gray-200 dark:text-gray-500 dark:bg-gray-600 cursor-not-allowed') +
                '" data-id="' + t.id + '" ' + (isEditable(t) ? '' : 'disabled') + '>' +
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 16.604a1.875 1.875 0 01-1.07.603l-2.685.8.8-2.685a1.875 1.875 0 01.603-1.07L16.863 4.487zm0 0L19.5 7.125"/></svg>' +
                'تعديل' +
                '</button>'
                : '') +
            '</div>';
    }

    function showLoading() {
        listEl.innerHTML =
            '<div class="flex flex-col items-center justify-center py-16 text-gray-400 dark:text-gray-500">' +
            '<svg class="animate-spin h-10 w-10 mb-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>' +
            '<span class="text-base font-semibold">جارٍ تحميل المهام...</span>' +
            '</div>';
    }

    function loadTodos(showSpinner) {
        if (showSpinner === undefined) showSpinner = true;
        var dateVal = dateFilterEl ? dateFilterEl.value : '';
        var url = listUrl + '&date=' + (dateVal || todayEgypt());
        if (assignedToFilterEl) {
            url += '&assigned_to=' + assignedToFilterEl.value;
        }
        var start = Date.now();
        if (showSpinner) showLoading();
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var elapsed = Date.now() - start;
                var delay = elapsed < 300 ? 300 - elapsed : 0;
                setTimeout(function () {
                    if (!data.success) {
                        showError(data.message || 'حدث خطأ في تحميل المهام');
                        return;
                    }
                    renderTodos(data.data);
                }, delay);
            })
            .catch(function () {
                showError('فشل الاتصال بالخادم');
            });
    }

    function toggleBtnState(btn, toDone) {
        if (toDone) {
            btn.classList.remove('border-gray-400', 'dark:border-gray-500', 'hover:border-blue-500', 'dark:hover:border-blue-400', 'bg-gray-50', 'dark:bg-gray-700');
            btn.classList.add('bg-green-500', 'border-green-500', 'text-white', 'hover:bg-green-600');
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>';
            btn.title = 'إعادة فتح';
        } else {
            btn.classList.remove('bg-green-500', 'border-green-500', 'text-white', 'hover:bg-green-600');
            btn.classList.add('border-gray-400', 'dark:border-gray-500', 'hover:border-blue-500', 'dark:hover:border-blue-400', 'bg-gray-50', 'dark:bg-gray-700');
            btn.innerHTML = '';
            btn.title = 'إتمام';
        }
    }

    // Toggle handler (event delegation)
    listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.todo-toggle');
        if (btn) {
            e.preventDefault();
            var card = btn.closest('.todo-card');
            var wasDone = card.classList.contains('opacity-75');

            // Optimistic UI: immediately toggle visual state
            var toDone = !wasDone;
            card.classList.toggle('opacity-75');
            var titleEl = card.querySelector('p');
            titleEl.classList.toggle('line-through');
            titleEl.classList.toggle('text-gray-400');
            titleEl.classList.toggle('dark:text-gray-500');
            toggleBtnState(btn, toDone);

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
                        card.classList.toggle('opacity-75');
                        titleEl.classList.toggle('line-through');
                        titleEl.classList.toggle('text-gray-400');
                        titleEl.classList.toggle('dark:text-gray-500');
                        toggleBtnState(btn, wasDone);
                        return;
                    }
                    showSuccess(data.message);
                    loadTodos(false);
                })
                .catch(function () {
                    showError('فشل الاتصال بالخادم');
                    card.classList.toggle('opacity-75');
                    titleEl.classList.toggle('line-through');
                    titleEl.classList.toggle('text-gray-400');
                    titleEl.classList.toggle('dark:text-gray-500');
                    toggleBtnState(btn, wasDone);
                });
        }

        var editBtn = e.target.closest('.todo-edit');
        if (editBtn) {
            if (editBtn.disabled) {
                showError('لا يمكن تعديل المهمة بعد مرور 30 دقيقة من إنشائها أو بعد إتمامها');
                return;
            }
            e.preventDefault();
            var id = editBtn.dataset.id;
            fetch(editUrl + '?action=get&id=' + id)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showError(data.message || 'حدث خطأ');
                        return;
                    }
                    var todo = data.data;
                    editIdInput.value = todo.id;
                    editTitle.value = todo.title;
                    editDueDate.value = todo.due_date || '';
                    if (editAssignedTo) {
                        for (var i = 0; i < editAssignedTo.options.length; i++) {
                            if (parseInt(editAssignedTo.options[i].value) === todo.assigned_to) {
                                editAssignedTo.selectedIndex = i;
                                break;
                            }
                        }
                    }
                    editModal.classList.remove('hidden');
                    editModal.classList.add('flex');
                })
                .catch(function () {
                    showError('فشل الاتصال بالخادم');
                });
        }

    });

    // Close edit modal on backdrop click
    if (editModal) {
        editModal.addEventListener('click', function (e) {
            if (e.target === editModal) {
                editModal.classList.add('hidden');
                editModal.classList.remove('flex');
            }
        });
    }

    // Edit form submit
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(editForm);
            formData.append('action', 'edit');

            var btn = editForm.querySelector('button[type="submit"]');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 inline ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ الحفظ...';

            fetch(editUrl, { method: 'POST', body: formData })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showError(data.message || 'حدث خطأ');
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                        return;
                    }
                    showSuccess(data.message);
                    editModal.classList.add('hidden');
                    editModal.classList.remove('flex');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    loadTodos();
                })
                .catch(function () {
                    showError('فشل الاتصال بالخادم');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        });
    }

    // Date filter change
    if (dateFilterEl) {
        dateFilterEl.addEventListener('change', function () {
            loadTodos();
        });
    }

    // Assigned to filter change
    if (assignedToFilterEl) {
        assignedToFilterEl.addEventListener('change', function () {
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

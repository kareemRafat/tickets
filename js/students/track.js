document.addEventListener('DOMContentLoaded', function () {
    const ticketList = document.getElementById('ticket-list');
    const detailsPanel = document.getElementById('ticket-details');
    const apiUrl = detailsPanel ? detailsPanel.dataset.apiUrl : null;

    if (!ticketList || !detailsPanel || !apiUrl) return;

    ticketList.addEventListener('click', function (e) {
        const item = e.target.closest('.ticket-item');
        if (!item) return;

        const ticketId = item.dataset.ticketId;
        if (!ticketId) return;

        document.querySelectorAll('.ticket-item').forEach(function (el) {
            el.classList.remove('active');
        });
        item.classList.add('active');

        loadTicket(ticketId);
    });

    function loadTicket(id) {
        var startTime = Date.now();
        detailsPanel.innerHTML = '<div class="flex items-center justify-center h-full py-20"><div class="text-center"><svg class="animate-spin h-10 w-10 mx-auto mb-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><p class="text-gray-500 dark:text-gray-400">جارٍ تحميل التذكرة...</p></div></div>';

        fetch(apiUrl + '?id=' + id)
            .then(function (res) {
                if (!res.ok) throw new Error('فشل في تحميل التذكرة');
                return res.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'حدث خطأ');
                var elapsed = Date.now() - startTime;
                var remaining = 500 - elapsed;
                if (remaining > 0) {
                    setTimeout(function () { renderTicket(data); }, remaining);
                } else {
                    renderTicket(data);
                }
            })
            .catch(function (err) {
                var elapsed = Date.now() - startTime;
                var remaining = 500 - elapsed;
                var showError = function () {
                    detailsPanel.innerHTML = '<div class="flex items-center justify-center h-full py-20"><div class="text-center text-red-500"><svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg><p class="text-base font-medium">' + err.message + '</p></div></div>';
                };
                if (remaining > 0) {
                    setTimeout(showError, remaining);
                } else {
                    showError();
                }
            });
    }

    function renderTicket(data) {
        var t = data.ticket;
        var replies = data.replies || [];
        var sl = data.status_labels || {};

        var statusClass = t.status === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : t.status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';

        var priorityClass = t.priority === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : t.priority === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';

        var repliesHtml = '';
        if (replies.length === 0) {
            repliesHtml = '<div class="text-center py-8 text-gray-500 dark:text-gray-400"><svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg><p class="text-base">لا توجد ردود حتى الآن. سيتم الرد عليك في أقرب وقت ممكن.</p></div>';
        } else {
            var items = '';
            replies.forEach(function (r) {
                var statusChange = '';
                if (r.old_status && r.new_status) {
                    statusChange = '<div class="mb-2 text-sm text-gray-500 dark:text-gray-400">تم تغيير الحالة من <span class="font-medium">' + (sl[r.old_status] || r.old_status) + '</span> إلى <span class="font-medium">' + (sl[r.new_status] || r.new_status) + '</span></div>';
                }
                items += '<li class="mb-6 last:mb-0">' +
                    '<span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -right-3 ring-8 ring-white dark:ring-gray-800 dark:bg-blue-900">' +
                    '<svg class="w-3 h-3 text-blue-800 dark:text-blue-200" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 11a1 1 0 11-2 0 1 1 0 012 0zm0-3a1 1 0 01-2 0V7a1 1 0 112 0v3z"/></svg>' +
                    '</span>' +
                    '<div class="p-4 bg-gray-50 rounded-xl border border-gray-100 dark:bg-gray-700/30 dark:border-gray-700/50">' +
                    '<div class="flex items-center justify-between mb-2">' +
                    '<span class="text-base font-semibold text-gray-900 dark:text-white">' + r.employee_name + '</span>' +
                    '<time class="text-sm text-gray-500 dark:text-gray-400">' + formatDate(r.created_at) + '</time>' +
                    '</div>' +
                    statusChange +
                    '<p class="text-base text-gray-700 dark:text-gray-300 whitespace-pre-wrap">' + r.reply + '</p>' +
                    '</div>' +
                    '</li>';
            });
            repliesHtml = '<ol class="relative border-r border-gray-200 dark:border-gray-700 pr-6">' + items + '</ol>';
        }

        var closedAtHtml = '';
        if (t.closed_at) {
            closedAtHtml = '<div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">تاريخ الإغلاق</dt><dd class="text-gray-900 dark:text-white font-mono text-sm">' + formatDate(t.closed_at) + '</dd></div>';
        }

        detailsPanel.innerHTML =
            '<div class="space-y-6">' +

            '<div class="flex items-center gap-2 flex-wrap">' +
            '<h2 class="text-xl font-bold text-gray-900 dark:text-white">' + t.ticket_number + '</h2>' +
            '<span class="px-2.5 py-0.5 text-sm font-medium rounded-full ' + statusClass + '">' + t.status_label + '</span>' +
            '<span class="px-2.5 py-0.5 text-sm font-medium rounded-full ' + priorityClass + '">' + t.priority_label + '</span>' +
            '</div>' +

            '<h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">' + t.subject + '</h3>' +

            '<div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">' +
            '<h4 class="text-base font-bold text-gray-900 dark:text-white mb-3">الوصف</h4>' +
            '<p class="text-base text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">' + t.description + '</p>' +
            '<div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">' +
            'تم الإنشاء: ' + formatDate(t.created_at) +
            '</div>' +
            '</div>' +

            '<div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">' +
            '<h4 class="text-base font-bold text-gray-900 dark:text-white mb-4">الردود من فريق الدعم</h4>' +
            repliesHtml +
            '</div>' +

            '<div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">' +
            '<h4 class="text-base font-bold text-gray-900 dark:text-white mb-3">معلومات التذكرة</h4>' +
            '<dl class="space-y-3 text-base">' +
            '<div class="flex justify-between py-1"><dt class="text-gray-500 dark:text-gray-400">التصنيف</dt><dd class="font-semibold text-gray-900 dark:text-white">' + t.category_name + '</dd></div>' +
            '<div class="flex justify-between py-1"><dt class="text-gray-500 dark:text-gray-400">الأولوية</dt><dd><span class="px-2 py-0.5 text-sm font-medium rounded-full ' + priorityClass + '">' + t.priority_label + '</span></dd></div>' +
            '<div class="flex justify-between py-1"><dt class="text-gray-500 dark:text-gray-400">الحالة</dt><dd><span class="px-2 py-0.5 text-sm font-medium rounded-full ' + statusClass + '">' + t.status_label + '</span></dd></div>' +
            '<div class="flex justify-between py-1"><dt class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء</dt><dd class="text-gray-900 dark:text-white font-mono text-sm">' + formatDate(t.created_at) + '</dd></div>' +
            closedAtHtml +
            '</dl>' +
            '</div>' +

            '<div class="p-5 bg-white rounded-2xl border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700">' +
            '<h4 class="text-base font-bold text-gray-900 dark:text-white mb-3">بيانات الطالب</h4>' +
            '<dl class="space-y-3 text-base">' +
            '<div class="flex justify-between py-1"><dt class="text-gray-500 dark:text-gray-400">الاسم</dt><dd class="font-semibold text-gray-900 dark:text-white text-sm">' + t.student_name + '</dd></div>' +
            '<div class="flex justify-between py-1"><dt class="text-gray-500 dark:text-gray-400">رقم الهاتف</dt><dd class="text-sm text-gray-900 dark:text-white">' + t.contact_phone + '</dd></div>' +
            '</dl>' +
            '</div>' +

            '</div>';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dateStr;
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        var hours = String(d.getHours()).padStart(2, '0');
        var mins = String(d.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + ' ' + hours + ':' + mins;
    }
});

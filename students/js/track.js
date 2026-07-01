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

        // Mobile: show details panel, hide list
        if (window.innerWidth < 768) {
            document.querySelector('.track-container').classList.add('mobile-detail-open');
        }

        loadTicket(ticketId);
    });

    // Mobile back to list
    document.addEventListener('click', function (e) {
        if (e.target.closest('.back-to-list')) {
            document.querySelector('.track-container').classList.remove('mobile-detail-open');
        }
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

        var statusClass = t.status === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-800/50 dark:text-green-300' : t.status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/50 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-800/50 dark:text-red-300';

        var priorityClass = t.priority === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : t.priority === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';

        var repliesHtml = '';
        if (replies.length === 0) {
            repliesHtml = '<div class="text-center py-10 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-200 dark:border-gray-700"><svg class="w-14 h-14 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg><p class="text-base text-gray-500 dark:text-gray-400">لا توجد ردود حتى الآن. سيتم الرد عليك في أقرب وقت ممكن.</p></div>';
        } else {
            var items = '';
            replies.forEach(function (r) {
                var initial = r.employee_name ? r.employee_name.charAt(0).toUpperCase() : '?';

                items += '<div class="flex gap-3 p-5 bg-gray-50 rounded-xl border border-gray-200 dark:bg-gray-700/30 dark:border-gray-700">' +
                    '<div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-700 dark:text-blue-300 text-base font-bold shrink-0 mt-0.5">' + initial + '</div>' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="flex items-center justify-between gap-2">' +
                    '<span class="text-base font-semibold text-gray-900 dark:text-white">' + r.employee_name + '</span>' +
                    '<time class="text-sm text-gray-400 dark:text-gray-500 shrink-0">' + formatDate(r.created_at) + '</time>' +
                    '</div>' +
                    '<hr class="my-3 border-gray-200 dark:border-gray-600">' +
                    '<p class="text-base text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">' + r.reply + '</p>' +
                    '</div>' +
                    '</div>';
            });
            repliesHtml = '<div class="space-y-4">' + items + '</div>';
        }

        detailsPanel.innerHTML =
            '<div class="space-y-6">' +

            '<div>' +
            '<div class="flex md:hidden items-center gap-2 mb-4">' +
            '<button class="back-to-list px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition-all">' +
            '← عودة</button>' +
            '<span class="text-xs text-gray-400 dark:text-gray-500">القائمة</span>' +
            '</div>' +
            '<div class="flex flex-wrap items-center gap-2 mb-4">' +
            '<span class="text-base font-bold text-white bg-sky-600 px-2 py-0.5 rounded dark:text-white">' + t.ticket_number + '</span>' +
            '<span class="px-3 py-1 text-sm font-medium rounded-lg ' + statusClass + '">' + t.status_label + '</span>' +
            '<span class="px-3 py-1 text-sm font-medium rounded-lg ' + priorityClass + '">' + t.priority_label + '</span>' +
            '</div>' +
            '<h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white leading-snug">' + t.subject + '</h3>' +
            '<div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-4 px-4 py-2.5 bg-gray-100 dark:bg-gray-700/50 rounded-xl text-[15px] text-gray-600 dark:text-gray-300 font-semibold">' +
            '<span>' + t.category_name + '</span>' +
            '<span class="w-1 h-1 rounded-full bg-gray-400 dark:bg-gray-500 shrink-0"></span>' +
            '<span>' + t.student_name + '</span>' +
            '<span class="w-1 h-1 rounded-full bg-gray-400 dark:bg-gray-500 shrink-0"></span>' +
            '<time class="text-gray-500 dark:text-gray-400">' + formatDate(t.created_at) + '</time>' +
            '</div>' +
            '</div>' +

            '<div class="bg-gray-50 dark:bg-gray-700/20 rounded-xl p-6 border border-gray-200 dark:border-gray-700">' +
            '<h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 mb-3">وصف التذكرة</h4>' +
            '<p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed text-lg">' + t.description + '</p>' +
            '</div>' +

            '<div>' +
            '<div class="flex items-center gap-2 mb-5">' +
            '<h4 class="text-base font-bold text-gray-500 dark:text-gray-400">الردود</h4>' +
            (replies.length > 0 ? '<span class="px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-full">' + replies.length + '</span>' : '') +
            '</div>' +
            (replies.length > 0 ? repliesHtml : '<div class="text-center py-10 bg-gray-50 dark:bg-gray-700/20 rounded-xl border border-gray-200 dark:border-gray-700"><svg class="w-14 h-14 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg><p class="text-base text-gray-500 dark:text-gray-400">لا توجد ردود حتى الآن. سيتم الرد عليك في أقرب وقت ممكن.</p></div>') +
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
        return year + '-' + month + '-' + day;
    }
});

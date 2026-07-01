document.addEventListener('DOMContentLoaded', function () {
    const ticketList = document.getElementById('ticket-list');
    const detailsPanel = document.getElementById('ticket-details');
    const apiUrl = detailsPanel ? detailsPanel.dataset.apiUrl : null;
    const logoUrl = detailsPanel ? detailsPanel.dataset.logoUrl || '../images/logo.webp' : '../images/logo.webp';

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
        detailsPanel.innerHTML = '<div class="flex items-center justify-center h-full py-24">' +
            '<div class="text-center">' +
            '<svg class="animate-spin h-10 w-10 mx-auto mb-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
            '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
            '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>' +
            '</svg>' +
            '<p class="text-sm font-semibold text-gray-500 dark:text-gray-400">جارٍ تحميل تفاصيل التذكرة...</p>' +
            '</div>' +
            '</div>';

        fetch(apiUrl + '?id=' + id)
            .then(function (res) {
                if (!res.ok) throw new Error('فشل في تحميل التذكرة');
                return res.json();
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'حدث خطأ');
                var elapsed = Date.now() - startTime;
                var remaining = 400 - elapsed;
                if (remaining > 0) {
                    setTimeout(function () { renderTicket(data); }, remaining);
                } else {
                    renderTicket(data);
                }
            })
            .catch(function (err) {
                var elapsed = Date.now() - startTime;
                var remaining = 400 - elapsed;
                var showError = function () {
                    detailsPanel.innerHTML = '<div class="flex items-center justify-center h-full py-20">' +
                        '<div class="text-center text-red-500">' +
                        '<svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>' +
                        '</svg>' +
                        '<p class="text-base font-semibold">' + err.message + '</p>' +
                        '</div>' +
                        '</div>';
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

        var statusClass = t.status === 'open' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800/40' : t.status === 'in_progress' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800/40';

        var priorityClass = t.priority === 'high' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300 border border-rose-200 dark:border-rose-900/40' : t.priority === 'medium' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300 border border-amber-200 dark:border-amber-900/40' : 'bg-gray-50 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300 border border-gray-200 dark:border-gray-700';

        var repliesHtml = '';
        if (replies.length === 0) {
            repliesHtml = '<div class="text-center py-12 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-150 dark:border-gray-800">' +
                '<svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.765 6 6 0 013.04-5.016C5.124 13.917 5.1 12.083 5.1 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>' +
                '<p class="text-sm text-gray-500 dark:text-gray-400 font-medium">لا توجد ردود على هذه التذكرة بعد. سيتم الرد عليك في أقرب وقت ممكن.</p>' +
                '</div>';
        } else {
            var items = '';
            replies.forEach(function (r) {
                var statusChangeHtml = '';
                if (r.old_status && r.new_status && r.old_status !== r.new_status) {
                    var oldLabel = sl[r.old_status] || r.old_status;
                    var newLabel = sl[r.new_status] || r.new_status;
                    statusChangeHtml = '<div class="flex items-center gap-2 mb-3.5 px-3.5 py-2 bg-blue-50/50 dark:bg-blue-950/20 text-xs font-bold text-blue-700 dark:text-blue-300 rounded-xl border border-blue-100/50 dark:border-blue-900/30">' +
                        '<svg class="w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>' +
                        '<span>قام ممثل الدعم بتغيير الحالة من <span class="underline underline-offset-2">' + oldLabel + '</span> إلى <span class="underline underline-offset-2">' + newLabel + '</span></span>' +
                        '</div>';
                }

                items += '<div class="flex flex-col gap-1.5">' +
                    statusChangeHtml +
                    '<div class="flex gap-4 p-5 bg-gray-50 border border-gray-150 dark:bg-gray-900/30 dark:border-gray-800 rounded-2xl shadow-sm transition-all">' +
                    '<div class="w-10 h-10 rounded-full overflow-hidden shrink-0 shadow-md ring-2 ring-white dark:ring-gray-800 bg-white">' +
                    '<img src="' + logoUrl + '" alt="Createivo" class="w-full h-full object-cover">' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">' +
                    '<div class="flex items-center flex-wrap gap-1.5">' +
                    '<span class="text-[15px] font-bold text-gray-900 dark:text-white">Createivo</span>' +
                    '<span class="px-2 py-0.5 text-[10px] font-bold text-blue-700 bg-blue-50 dark:bg-blue-900/40 dark:text-blue-300 rounded-full border border-blue-100/50 dark:border-blue-900/20">الدعم الفني</span>' +
                    '</div>' +
                    '<time class="text-xs text-gray-400 dark:text-gray-500 shrink-0 font-medium">' + formatDate(r.created_at, true) + '</time>' +
                    '</div>' +
                    '<hr class="my-3 border-gray-100 dark:border-gray-800">' +
                    '<p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed text-[14.5px]">' + r.reply + '</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            });
            repliesHtml = '<div class="space-y-5">' + items + '</div>';
        }

        detailsPanel.innerHTML =
            '<div class="space-y-6">' +
            '<div>' +
            '<div class="flex md:hidden items-center gap-2 mb-6">' +
            '<button class="back-to-list inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 dark:text-gray-400 dark:hover:text-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg transition-all">' +
            '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>' +
            'عودة للقائمة</button>' +
            '</div>' +
            '<div class="flex flex-wrap items-center gap-2 mb-3">' +
            '<span class="font-mono text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100/50 dark:border-blue-900/10">' + t.ticket_number + '</span>' +
            '<span class="px-2 py-0.5 text-xs font-bold rounded ' + statusClass + '">' + t.status_label + '</span>' +
            '<span class="px-2 py-0.5 text-xs font-bold rounded ' + priorityClass + '">' + t.priority_label + '</span>' +
            '</div>' +
            '<h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white leading-snug mb-3">' + t.subject + '</h2>' +
            
            '<div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-850 pb-4 mb-4">' +
            '<div class="flex items-center gap-1">' +
            '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a1.462 1.462 0 002.063 0l4.317-4.317a1.462 1.462 0 000-2.063L10.038 3.659a2.25 2.25 0 00-1.591-.659z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 7.5h.008v.008H6V7.5z"/></svg>' +
            '<span>التصنيف: <span class="font-semibold text-gray-800 dark:text-gray-200">' + t.category_name + '</span></span>' +
            '</div>' +
            '<span class="text-gray-300 dark:text-gray-700 hidden sm:inline">•</span>' +
            '<div class="flex items-center gap-1">' +
            '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/></svg>' +
            '<span>الإنشاء: <span class="font-semibold text-gray-800 dark:text-gray-200">' + formatDate(t.created_at) + '</span></span>' +
            '</div>' +
            (t.contact_phone ?
            ' <span class="text-gray-300 dark:text-gray-700 hidden sm:inline">•</span>' +
            '<div class="flex items-center gap-1">' +
            '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-2.824-1.28-5.716-4.172-6.997-6.999l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>' +
            '<span>رقم التواصل: <span class="font-semibold text-gray-800 dark:text-gray-200">' + t.contact_phone + '</span></span>' +
            '</div>' : '') +
            (t.closed_at ?
            ' <span class="text-gray-300 dark:text-gray-700 hidden sm:inline">•</span>' +
            '<div class="flex items-center gap-1 text-red-650 dark:text-red-400">' +
            '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
            '<span>الإغلاق: <span class="font-semibold text-red-650 dark:text-red-400">' + formatDate(t.closed_at) + '</span></span>' +
            '</div>' : '') +
            '</div>' +
            '</div>' +

            '<div>' +
            '<h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">تفاصيل الاستفسار</h4>' +
            '<div class="p-5 bg-gray-50/50 border border-gray-150 dark:bg-gray-900/10 dark:border-gray-800 rounded-2xl">' +
            '<p class="text-gray-700 dark:text-gray-300 text-[15px] whitespace-pre-wrap leading-relaxed">' + t.description + '</p>' +
            '</div>' +
            '</div>' +

            '<div>' +
            '<div class="flex items-center gap-2 mb-4">' +
            '<h4 class="text-sm font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">الردود والتحديثات</h4>' +
            (replies.length > 0 ? '<span class="px-2 py-0.5 text-xs font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-full border border-blue-100/50 dark:border-blue-900/10">' + replies.length + '</span>' : '') +
            '</div>' +
            repliesHtml +
            '</div>' +
            '</div>';
    }

    function formatDate(dateStr, includeTime) {
        if (!dateStr) return '';
        var d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dateStr;
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        var dateString = year + '-' + month + '-' + day;
        if (includeTime) {
            var hours = String(d.getHours()).padStart(2, '0');
            var minutes = String(d.getMinutes()).padStart(2, '0');
            dateString += ' ' + hours + ':' + minutes;
        }
        return dateString;
    }
});

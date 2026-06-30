document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('toggle-status-modal');
    if (!modalEl) return;

    var modal = null;
    var pendingBtn = null;

    function closeModal() { if (modal) modal.hide(); }

    modalEl.querySelector('[data-modal-close]').addEventListener('click', closeModal);
    modalEl.querySelector('[data-modal-cancel]').addEventListener('click', closeModal);

    document.querySelectorAll('[data-toggle-status]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            pendingBtn = btn;

            var name = btn.dataset.employeeName;
            var current = btn.dataset.currentStatus;
            var newStatus = current === 'active' ? 'inactive' : 'active';
            var label = newStatus === 'active' ? 'تفعيل' : 'تعطيل';

            modalEl.querySelector('[data-modal-text]').textContent = 'هل أنت متأكد من ' + label + ' حساب الموظف ' + name + '؟';
            modalEl.querySelector('[data-modal-confirm]').className = newStatus === 'active'
                ? 'px-4 py-2 text-sm font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors'
                : 'px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors';
            modalEl.querySelector('[data-modal-confirm]').textContent = label;

            if (!modal) {
                modal = new Modal(modalEl);
            }
            modal.show();
        });
    });

    modalEl.querySelector('[data-modal-confirm]').addEventListener('click', function () {
        if (!pendingBtn) return;

        var btn = pendingBtn;
        var id = btn.dataset.employeeId;
        var current = btn.dataset.currentStatus;
        var newStatus = current === 'active' ? 'inactive' : 'active';

        modal.hide();

        btn.disabled = true;
        btn.innerHTML = '<svg class="inline w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';

        fetch(btn.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: parseInt(id),
                status: newStatus,
                csrf_token: btn.dataset.csrfToken
            })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                btn.dataset.currentStatus = newStatus;

                var badge = btn.closest('tr').querySelector('[data-status-badge]');
                if (badge) {
                    if (newStatus === 'active') {
                        badge.className = 'inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                        badge.textContent = 'نشط';
                    } else {
                        badge.className = 'inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                        badge.textContent = 'معطل';
                    }
                }

                var toggleLabel = newStatus === 'active' ? 'تعطيل' : 'تفعيل';
                btn.className = newStatus === 'active'
                    ? 'px-3 py-2 text-xs font-medium text-white bg-red-500 hover:bg-red-600 transition-colors focus:z-10'
                    : 'px-3 py-2 text-xs font-medium text-white bg-green-500 hover:bg-green-600 transition-colors focus:z-10';
                btn.innerHTML = '<svg class="inline w-3.5 h-3.5 ml-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> ' + toggleLabel;
            } else {
                alert(data.message);
            }
        })
        .catch(function () {
            alert('حدث خطأ في الاتصال بالخادم.');
        })
        .finally(function () {
            btn.disabled = false;
            pendingBtn = null;
        });
    });
});

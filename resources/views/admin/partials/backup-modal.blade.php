@if ($admin?->isAdmin())
<style>
.backup-modal{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.45);opacity:0;visibility:hidden;transition:opacity .25s,visibility .25s}
.backup-modal.open{opacity:1;visibility:visible}
body.backup-modal-open{overflow:hidden}
.backup-modal-dialog{width:min(920px,100%);max-height:min(90vh,820px);background:#fff;border-radius:22px;box-shadow:0 25px 50px -12px rgba(15,23,42,.25);display:flex;flex-direction:column;overflow:hidden}
.backup-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1.25rem 1.5rem;border-bottom:1px solid rgba(15,23,42,.08)}
.backup-modal-header h2{margin:0;font-size:1.15rem}
.backup-modal-header p{margin:.35rem 0 0;font-size:.85rem;color:var(--muted)}
.backup-modal-close{border:none;background:rgba(15,23,42,.06);width:2.25rem;height:2.25rem;border-radius:10px;font-size:1.35rem;cursor:pointer;color:var(--slate)}
.backup-modal-tabs{display:flex;gap:.5rem;padding:0 1.5rem;border-bottom:1px solid rgba(15,23,42,.08)}
.backup-tab-btn{border:none;background:transparent;padding:.85rem 1rem;font-family:inherit;font-size:.9rem;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.backup-tab-btn.active{color:var(--primary);border-bottom-color:var(--primary)}
.backup-modal-body{padding:1.25rem 1.5rem 1.5rem;overflow:auto;flex:1}
.backup-panel{display:none}.backup-panel.active{display:block}
.backup-toolbar{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;margin-bottom:1rem}
.backup-primary-btn{border:none;background:var(--primary);color:#fff;padding:.65rem 1.15rem;border-radius:12px;font-family:inherit;font-weight:600;font-size:.88rem;cursor:pointer}
.backup-primary-btn:disabled{opacity:.55;cursor:not-allowed}
.backup-note{font-size:.82rem;color:var(--muted);margin:0 0 1rem;line-height:1.7}
.backup-table-wrap{border:1px solid rgba(15,23,42,.08);border-radius:14px;overflow:auto}
.backup-table{width:100%;border-collapse:collapse;font-size:.86rem}
.backup-table th,.backup-table td{padding:.7rem .85rem;text-align:right;border-bottom:1px solid rgba(15,23,42,.06);vertical-align:middle}
.backup-table th{background:rgba(15,23,42,.03);font-weight:700}
.backup-table tr:last-child td{border-bottom:none}
.backup-actions{display:flex;flex-wrap:wrap;gap:.35rem}
.backup-action-btn{border:1px solid rgba(15,23,42,.12);background:#fff;padding:.35rem .65rem;border-radius:8px;font-family:inherit;font-size:.78rem;font-weight:600;cursor:pointer}
.backup-action-btn--danger{color:#b91c1c;border-color:rgba(185,28,28,.25)}
.backup-action-btn--primary{color:var(--primary-dark);border-color:rgba(214,17,25,.25)}
.backup-empty{text-align:center;padding:2rem 1rem;color:var(--muted)}
.backup-restore-card{border:1px solid rgba(15,23,42,.08);border-radius:16px;padding:1.15rem;margin-bottom:1rem}
.backup-restore-card h3{margin:0 0 .5rem;font-size:.95rem}
.backup-restore-card label{display:block;font-size:.82rem;font-weight:600;margin-bottom:.35rem;color:var(--slate-2)}
.backup-restore-card input[type=file],.backup-restore-card select{width:100%;padding:.6rem .75rem;border-radius:10px;border:1px solid rgba(15,23,42,.12);font-family:inherit;font-size:.86rem;margin-bottom:.75rem}
.backup-alert{border-radius:12px;padding:.75rem 1rem;font-size:.85rem;font-weight:600;margin-bottom:1rem;display:none}
.backup-alert.visible{display:block}
.backup-alert--error{background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.2);color:#991b1b}
.backup-alert--success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.2);color:#15803d}
.backup-loading{text-align:center;padding:1.5rem;color:var(--muted)}
.backup-confirm-overlay{position:fixed;inset:0;z-index:110;background:rgba(15,23,42,.5);display:flex;align-items:center;justify-content:center;padding:1rem;opacity:0;visibility:hidden;transition:opacity .2s,visibility .2s}
.backup-confirm-overlay.open{opacity:1;visibility:visible}
.backup-confirm-box{width:min(420px,100%);background:#fff;border-radius:18px;padding:1.25rem 1.35rem;box-shadow:0 20px 40px rgba(15,23,42,.2)}
.backup-confirm-box h3{margin:0 0 .5rem;font-size:1rem}
.backup-confirm-box p{margin:0 0 1.15rem;font-size:.88rem;color:var(--muted);line-height:1.65}
.backup-confirm-actions{display:flex;gap:.5rem;justify-content:flex-end}
.backup-confirm-actions button{border-radius:10px;padding:.55rem 1rem;font-family:inherit;font-weight:600;font-size:.86rem;cursor:pointer;border:1px solid rgba(15,23,42,.12);background:#fff}
.backup-confirm-actions .confirm-yes{background:var(--primary);color:#fff;border-color:var(--primary)}
.backup-confirm-actions .confirm-yes--danger{background:#dc2626;border-color:#dc2626}
</style>

<div class="backup-modal" id="backupModal" role="dialog" aria-modal="true" aria-labelledby="backupModalTitle" hidden>
    <div class="backup-modal-dialog" role="document">
        <div class="backup-modal-header">
            <div>
                <h2 id="backupModalTitle">پشتیبان‌گیری و بازیابی</h2>
                <p>پشتیبان شامل پایگاه داده، تنظیمات سامانه و فایل‌های ذخیره‌شده است.</p>
            </div>
            <button type="button" class="backup-modal-close" id="backupModalClose" aria-label="بستن">&times;</button>
        </div>
        <div class="backup-modal-tabs" role="tablist">
            <button type="button" class="backup-tab-btn active" data-backup-tab="backup" role="tab" aria-selected="true">پشتیبان‌گیری</button>
            <button type="button" class="backup-tab-btn" data-backup-tab="restore" role="tab" aria-selected="false">بازیابی</button>
        </div>
        <div class="backup-modal-body">
            <div id="backupModalAlert" class="backup-alert" role="alert"></div>
            <div class="backup-panel active" data-backup-panel="backup" role="tabpanel">
                <p class="backup-note">قبل از به‌روزرسانی یا تغییرات مهم، یک نسخه پشتیبان جدید بگیرید. بازیابی، داده‌های فعلی را جایگزین می‌کند.</p>
                <div class="backup-toolbar">
                    <button type="button" class="backup-primary-btn" id="backupCreateBtn">پشتیبان‌گیری جدید</button>
                </div>
                <div id="backupListLoading" class="backup-loading" hidden>در حال بارگذاری فهرست…</div>
                <div class="backup-table-wrap" id="backupTableWrap" hidden>
                    <table class="backup-table">
                        <thead>
                            <tr>
                                <th>نام فایل</th>
                                <th>تاریخ</th>
                                <th>حجم</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody id="backupTableBody"></tbody>
                    </table>
                </div>
                <p class="backup-empty" id="backupEmptyState" hidden>هنوز پشتیبانی ثبت نشده است.</p>
            </div>
            <div class="backup-panel" data-backup-panel="restore" role="tabpanel" hidden>
                <p class="backup-note">بازیابی تمام داده‌های فعلی را با محتوای پشتیبان جایگزین می‌کند. پیش از ادامه، از وضعیت کنونی خود نیز نسخه پشتیبان بگیرید.</p>
                <div class="backup-restore-card">
                    <h3>آپلود فایل پشتیبان (ZIP)</h3>
                    <label for="backupUploadFile">انتخاب فایل</label>
                    <input type="file" id="backupUploadFile" accept=".zip,application/zip" />
                    <button type="button" class="backup-primary-btn" id="backupRestoreUploadBtn">بازیابی از فایل آپلودشده</button>
                </div>
                <div class="backup-restore-card">
                    <h3>انتخاب از پشتیبان‌های سرور</h3>
                    <label for="backupRestoreSelect">فایل پشتیبان</label>
                    <select id="backupRestoreSelect">
                        <option value="">— انتخاب کنید —</option>
                    </select>
                    <button type="button" class="backup-primary-btn" id="backupRestoreServerBtn">بازیابی از سرور</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="backup-confirm-overlay" id="backupConfirmOverlay" hidden>
    <div class="backup-confirm-box" role="alertdialog" aria-labelledby="backupConfirmTitle" aria-describedby="backupConfirmMessage">
        <h3 id="backupConfirmTitle">تأیید</h3>
        <p id="backupConfirmMessage"></p>
        <div class="backup-confirm-actions">
            <button type="button" id="backupConfirmCancel">انصراف</button>
            <button type="button" class="confirm-yes" id="backupConfirmYes">تأیید</button>
        </div>
    </div>
</div>

@php
    $backupRoutes = [
        'index' => route('admin.backups.index'),
        'store' => route('admin.backups.store'),
        'restore' => route('admin.backups.restore'),
        'download' => route('admin.backups.download', ['filename' => '__FILE__']),
        'destroy' => route('admin.backups.destroy', ['filename' => '__FILE__']),
    ];
@endphp
<script>
(function () {
    const routes = @json($backupRoutes);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const modal = document.getElementById('backupModal');
    const openBtn = document.getElementById('openBackupModalBtn');
    if (!modal || !openBtn) return;

    const closeBtn = document.getElementById('backupModalClose');
    const alertEl = document.getElementById('backupModalAlert');
    const tableWrap = document.getElementById('backupTableWrap');
    const tableBody = document.getElementById('backupTableBody');
    const emptyState = document.getElementById('backupEmptyState');
    const listLoading = document.getElementById('backupListLoading');
    const restoreSelect = document.getElementById('backupRestoreSelect');
    const uploadInput = document.getElementById('backupUploadFile');
    const confirmOverlay = document.getElementById('backupConfirmOverlay');
    const confirmTitle = document.getElementById('backupConfirmTitle');
    const confirmMessage = document.getElementById('backupConfirmMessage');
    const confirmYes = document.getElementById('backupConfirmYes');
    const confirmCancel = document.getElementById('backupConfirmCancel');

    let backups = [];
    let busy = false;
    let confirmResolver = null;

    function setBusy(state) {
        busy = state;
        document.getElementById('backupCreateBtn').disabled = state;
        document.getElementById('backupRestoreUploadBtn').disabled = state;
        document.getElementById('backupRestoreServerBtn').disabled = state;
    }

    function showAlert(message, type) {
        alertEl.textContent = message;
        alertEl.className = 'backup-alert visible backup-alert--' + (type === 'success' ? 'success' : 'error');
    }

    function hideAlert() {
        alertEl.className = 'backup-alert';
        alertEl.textContent = '';
    }

    function openModal() {
        modal.hidden = false;
        modal.classList.add('open');
        document.body.classList.add('backup-modal-open');
        hideAlert();
        loadList();
    }

    function closeModal() {
        modal.classList.remove('open');
        document.body.classList.remove('backup-modal-open');
        modal.hidden = true;
    }

    function askConfirm(title, message, danger) {
        return new Promise((resolve) => {
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            confirmYes.classList.toggle('confirm-yes--danger', !!danger);
            confirmOverlay.hidden = false;
            confirmOverlay.classList.add('open');
            confirmResolver = resolve;
        });
    }

    function closeConfirm(result) {
        confirmOverlay.classList.remove('open');
        confirmOverlay.hidden = true;
        if (confirmResolver) {
            const fn = confirmResolver;
            confirmResolver = null;
            fn(result);
        }
    }

    confirmCancel.addEventListener('click', () => closeConfirm(false));
    confirmYes.addEventListener('click', () => closeConfirm(true));

    function switchTab(name) {
        document.querySelectorAll('[data-backup-tab]').forEach((btn) => {
            const active = btn.dataset.backupTab === name;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-backup-panel]').forEach((panel) => {
            const active = panel.dataset.backupPanel === name;
            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });
    }

    document.querySelectorAll('[data-backup-tab]').forEach((btn) => {
        btn.addEventListener('click', () => switchTab(btn.dataset.backupTab));
    });

    function renderList(items) {
        backups = items || [];
        tableBody.innerHTML = '';
        restoreSelect.innerHTML = '<option value="">— انتخاب کنید —</option>';
        if (backups.length === 0) {
            tableWrap.hidden = true;
            emptyState.hidden = false;
            return;
        }
        emptyState.hidden = true;
        tableWrap.hidden = false;
        backups.forEach((row) => {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td dir="ltr" style="font-size:0.8rem">' + escapeHtml(row.filename) + '</td>'
                + '<td>' + escapeHtml(row.created_at) + '</td>'
                + '<td>' + escapeHtml(row.size_human) + '</td>'
                + '<td><div class="backup-actions">'
                + '<button type="button" class="backup-action-btn backup-action-btn--primary" data-action="restore" data-file="' + escapeAttr(row.filename) + '">بازگردانی</button>'
                + '<a class="backup-action-btn" data-action="download" href="' + escapeAttr(downloadUrl(row.filename)) + '">دانلود</a>'
                + '<button type="button" class="backup-action-btn backup-action-btn--danger" data-action="delete" data-file="' + escapeAttr(row.filename) + '">حذف</button>'
                + '</div></td>';
            tableBody.appendChild(tr);
            const opt = document.createElement('option');
            opt.value = row.filename;
            opt.textContent = row.filename + ' (' + row.created_at + ' — ' + row.size_human + ')';
            restoreSelect.appendChild(opt);
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function escapeAttr(s) { return escapeHtml(s); }

    function downloadUrl(filename) {
        return routes.download.replace('__FILE__', encodeURIComponent(filename));
    }
    function destroyUrl(filename) {
        return routes.destroy.replace('__FILE__', encodeURIComponent(filename));
    }

    async function api(url, options) {
        const res = await fetch(url, Object.assign({
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }, options || {}));
        let data = {};
        try { data = await res.json(); } catch (e) {}
        if (!res.ok) {
            throw new Error(data.message || 'خطا در ارتباط با سرور.');
        }
        return data;
    }

    async function loadList() {
        listLoading.hidden = false;
        tableWrap.hidden = true;
        emptyState.hidden = true;
        try {
            const data = await api(routes.index);
            renderList(data.backups || []);
        } catch (e) {
            showAlert(e.message, 'error');
        } finally {
            listLoading.hidden = true;
        }
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    document.getElementById('backupCreateBtn').addEventListener('click', async () => {
        if (busy) return;
        const ok = await askConfirm('پشتیبان‌گیری جدید', 'یک فایل ZIP از پایگاه داده، تنظیمات و فایل‌های ذخیره‌شده ساخته می‌شود. ادامه می‌دهید؟', false);
        if (!ok) return;
        setBusy(true);
        hideAlert();
        try {
            const fd = new FormData();
            fd.append('confirmed', '1');
            const data = await api(routes.store, { method: 'POST', body: fd });
            showAlert(data.message || 'پشتیبان ایجاد شد.', 'success');
            renderList(data.backups || []);
        } catch (e) {
            showAlert(e.message, 'error');
        } finally {
            setBusy(false);
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn || busy) return;
        const file = btn.dataset.file;
        const action = btn.dataset.action;
        if (action === 'restore') {
            const ok = await askConfirm('بازگردانی پشتیبان', 'تمام داده‌های فعلی با «' + file + '» جایگزین می‌شود. ادامه می‌دهید؟', true);
            if (!ok) return;
            await doRestore('server', file);
        } else if (action === 'delete') {
            const ok = await askConfirm('حذف پشتیبان', 'فایل «' + file + '» برای همیشه حذف می‌شود. ادامه می‌دهید؟', true);
            if (!ok) return;
            setBusy(true);
            try {
                const fd = new FormData();
                fd.append('confirmed', '1');
                fd.append('_method', 'DELETE');
                const data = await api(destroyUrl(file), { method: 'POST', body: fd });
                showAlert(data.message || 'حذف شد.', 'success');
                renderList(data.backups || []);
            } catch (err) {
                showAlert(err.message, 'error');
            } finally {
                setBusy(false);
            }
        }
    });

    document.getElementById('backupRestoreServerBtn').addEventListener('click', async () => {
        const file = restoreSelect.value;
        if (!file) {
            showAlert('یک فایل پشتیبان از فهرست انتخاب کنید.', 'error');
            return;
        }
        const ok = await askConfirm('بازیابی از سرور', 'تمام داده‌های فعلی با «' + file + '» جایگزین می‌شود. ادامه می‌دهید؟', true);
        if (!ok) return;
        await doRestore('server', file);
    });

    document.getElementById('backupRestoreUploadBtn').addEventListener('click', async () => {
        const file = uploadInput.files && uploadInput.files[0];
        if (!file) {
            showAlert('فایل ZIP پشتیبان را انتخاب کنید.', 'error');
            return;
        }
        const ok = await askConfirm('بازیابی از فایل آپلودشده', 'تمام داده‌های فعلی با محتوای فایل انتخاب‌شده جایگزین می‌شود. ادامه می‌دهید؟', true);
        if (!ok) return;
        await doRestore('upload', null, file);
    });

    async function doRestore(sourceType, filename, uploadFile) {
        setBusy(true);
        hideAlert();
        try {
            const fd = new FormData();
            fd.append('confirmed', '1');
            fd.append('source_type', sourceType);
            if (sourceType === 'server') {
                fd.append('filename', filename);
            } else {
                fd.append('backup_file', uploadFile);
            }
            const data = await api(routes.restore, { method: 'POST', body: fd });
            showAlert(data.message || 'بازیابی انجام شد.', 'success');
            renderList(data.backups || []);
            uploadInput.value = '';
        } catch (e) {
            showAlert(e.message, 'error');
        } finally {
            setBusy(false);
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!confirmOverlay.hidden) {
                closeConfirm(false);
            } else if (modal.classList.contains('open')) {
                closeModal();
            }
        }
    });
})();
</script>
@endif

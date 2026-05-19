@if ($admin && $admin->hasPermission(\App\Support\AdminPermissions::SETTINGS))
@php
    $settingsActiveTab = $settingsActiveTab ?? session('settings_active_tab', 'password');
    $openSettingsModal = (bool) ($openSettingsModal ?? session('open_settings_modal', false));
    $settingsTabs = \App\Support\AdminSettingsTabs::all();
    $settingsTabGroups = [
        'account' => 'حساب و دسترسی',
        'appearance' => 'ظاهر سامانه',
        'security' => 'امنیت',
        'future' => 'به‌زودی',
    ];
@endphp
<style>
    .settings-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 95;
        background: rgba(15, 23, 42, 0.42);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(0.5rem, 2vw, 1.25rem);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s;
    }
    .settings-modal-overlay.open {
        opacity: 1;
        visibility: visible;
    }
    body.settings-modal-open {
        overflow: hidden;
    }
    .settings-modal-shell {
        width: min(1080px, 100%);
        max-height: min(92vh, 860px);
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 28px 60px -18px rgba(15, 23, 42, 0.28);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .settings-modal-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.85rem 1.15rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        flex-shrink: 0;
    }
    .settings-modal-close {
        border: none;
        background: rgba(15, 23, 42, 0.06);
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 12px;
        font-size: 1.4rem;
        line-height: 1;
        cursor: pointer;
        color: var(--slate);
        flex-shrink: 0;
    }
    .settings-modal-breadcrumb {
        flex: 1;
        text-align: center;
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--muted);
    }
    .settings-modal-breadcrumb strong {
        color: var(--slate);
        font-weight: 700;
    }
    .settings-modal-status {
        margin: 0 1.15rem;
        padding: 0.7rem 1rem;
        border-radius: 12px;
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.22);
        color: #15803d;
        font-size: 0.86rem;
        font-weight: 600;
        display: none;
    }
    .settings-modal-status.visible {
        display: block;
    }
    .settings-modal-body {
        display: flex;
        flex: 1;
        min-height: 0;
        overflow: hidden;
    }
    .settings-modal-nav {
        width: min(260px, 34%);
        border-right: 1px solid rgba(15, 23, 42, 0.08);
        background: #f8fafc;
        overflow-y: auto;
        padding: 0.65rem 0.5rem 0.85rem;
        flex-shrink: 0;
    }
    .settings-nav-group-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--muted);
        padding: 0.65rem 0.85rem 0.35rem;
        letter-spacing: 0.02em;
    }
    .settings-nav-item {
        width: 100%;
        border: none;
        background: transparent;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.7rem 0.85rem;
        border-radius: 12px;
        font-family: inherit;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--slate-2);
        cursor: pointer;
        text-align: right;
        transition: background 0.15s, color 0.15s;
    }
    .settings-nav-item i {
        width: 1.15rem;
        text-align: center;
        opacity: 0.85;
        flex-shrink: 0;
    }
    .settings-nav-item:hover:not(:disabled) {
        background: rgba(15, 23, 42, 0.05);
    }
    .settings-nav-item.active {
        background: #fff;
        color: var(--slate);
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
    }
    .settings-nav-item:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .settings-nav-divider {
        height: 1px;
        background: rgba(15, 23, 42, 0.08);
        margin: 0.5rem 0.65rem;
    }
    .settings-modal-content {
        flex: 1;
        overflow-y: auto;
        padding: 1.15rem 1.35rem 1.5rem;
        min-width: 0;
    }
    .settings-modal-panel {
        display: none;
        animation: settingsFadeIn 0.2s ease;
    }
    .settings-modal-panel.active {
        display: block;
    }
    @keyframes settingsFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .settings-modal-panel h3 {
        margin: 0 0 0.35rem;
        font-size: 1.2rem;
    }
    .settings-modal-panel > p:first-of-type {
        margin: 0 0 1.1rem;
        color: var(--muted);
        font-size: 0.88rem;
        line-height: 1.7;
    }
    .settings-modal-panels .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }
    .settings-modal-panels .branding-grid {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }
    .settings-modal-panels .color-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    }
    .settings-modal-panels .form-control {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .settings-modal-panels .form-control label {
        font-weight: 600;
        font-size: 0.86rem;
        color: var(--slate-2);
    }
    .settings-modal-panels .form-control input:not([type="color"]),
    .settings-modal-panels .form-control input[type="file"],
    .settings-modal-panels .form-control select {
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 12px;
        padding: 0.75rem 0.9rem;
        font-family: inherit;
        font-size: 0.9rem;
        background: #fff;
        width: 100%;
    }
    .settings-modal-panels .form-control input:focus,
    .settings-modal-panels .form-control select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.1);
    }
    .settings-modal-panels .form-control input.error {
        border-color: rgba(214, 17, 25, 0.65);
    }
    .settings-modal-panels .form-control input[type="color"] {
        height: 44px;
        padding: 0.15rem;
        cursor: pointer;
    }
    .settings-modal-panels .form-control small {
        color: var(--muted);
        font-size: 0.78rem;
    }
    .settings-modal-panels .error-text {
        color: #b91c1c;
        font-size: 0.8rem;
    }
    .settings-modal-panels .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: flex-end;
        margin-top: 1.15rem;
        padding-top: 0.5rem;
    }
    .settings-modal-panels .primary-btn {
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
    }
    .settings-modal-panels .ghost-btn {
        border: 1px dashed rgba(15, 23, 42, 0.2);
        background: transparent;
        border-radius: 12px;
        padding: 0.75rem 1.25rem;
        font-weight: 600;
        font-family: inherit;
        color: var(--slate);
        cursor: pointer;
    }
    .settings-modal-panels .logo-preview-card {
        border: 1px dashed rgba(15, 23, 42, 0.15);
        border-radius: 16px;
        padding: 1rem;
        text-align: center;
        background: rgba(15, 23, 42, 0.02);
    }
    .settings-modal-panels .logo-preview-card img {
        width: 96px;
        height: 96px;
        border-radius: 20px;
        object-fit: contain;
    }
    .settings-modal-panels .color-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin: 0.85rem 0;
    }
    .settings-modal-panels .color-chip {
        flex: 1 1 140px;
        border-radius: 12px;
        padding: 0.65rem 0.8rem;
        display: flex;
        justify-content: space-between;
        font-size: 0.82rem;
        font-weight: 600;
        color: #fff;
    }
    .settings-modal-panels .color-chip.light { color: var(--slate); }
    .settings-modal-panels .bg-image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
        margin-top: 0.75rem;
    }
    .settings-modal-panels .bg-image-card {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        padding: 0.55rem;
        background: #fff;
    }
    .settings-modal-panels .bg-image-card img {
        width: 100%;
        height: 88px;
        object-fit: cover;
        border-radius: 8px;
    }
    .settings-modal-panels .bg-image-card label {
        font-size: 0.78rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.25rem;
    }
    .settings-modal-panels .placeholder-card {
        border: 2px dashed rgba(15, 23, 42, 0.14);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        color: var(--muted);
        background: rgba(15, 23, 42, 0.02);
    }
    .settings-modal-panels .inline-toggle {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        font-weight: 600;
        font-size: 0.88rem;
    }
    .font-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.85rem;
        margin: 1rem 0;
    }
    .font-picker-card {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        padding: 1rem 1.05rem;
        border: 2px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
    }
    .font-picker-card:hover {
        border-color: rgba(214, 17, 25, 0.35);
    }
    .font-picker-card.is-selected {
        border-color: var(--primary);
        background: rgba(214, 17, 25, 0.04);
        box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.12);
    }
    .font-picker-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .font-picker-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .font-picker-name {
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--slate);
    }
    .font-picker-badge {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--primary-dark);
        background: rgba(214, 17, 25, 0.1);
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
    }
    .font-picker-sample {
        font-size: 1.05rem;
        line-height: 1.65;
        color: var(--slate-2);
    }
    .font-picker-meta {
        font-size: 0.75rem;
        color: var(--muted);
    }
    .settings-subsection-title {
        margin: 1.35rem 0 0.65rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--slate);
    }
    .settings-subsection-title:first-of-type {
        margin-top: 0.25rem;
    }
    .text-scale-picker {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-bottom: 0.5rem;
    }
    .text-scale-option {
        position: relative;
        flex: 1 1 7.5rem;
        min-width: 6.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        padding: 0.75rem 0.6rem;
        border: 2px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        text-align: center;
    }
    .text-scale-option:hover {
        border-color: rgba(214, 17, 25, 0.35);
    }
    .text-scale-option.is-selected {
        border-color: var(--primary);
        background: rgba(214, 17, 25, 0.04);
        box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.12);
    }
    .text-scale-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .text-scale-label {
        font-weight: 700;
        font-size: 0.82rem;
        color: var(--slate);
    }
    .text-scale-preview {
        line-height: 1.4;
        color: var(--slate-2);
        font-weight: 600;
    }
    @media (max-width: 820px) {
        .settings-modal-body {
            flex-direction: column;
        }
        .settings-modal-nav {
            width: 100%;
            border-left: none;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 0.35rem;
            padding: 0.5rem;
        }
        .settings-nav-group-label,
        .settings-nav-divider {
            display: none;
        }
        .settings-nav-item {
            flex: 0 0 auto;
            white-space: nowrap;
            font-size: 0.82rem;
            padding: 0.55rem 0.75rem;
        }
    }
</style>

<div class="settings-modal-overlay" id="settingsModalOverlay" role="dialog" aria-modal="true" aria-labelledby="settingsModalTitle" hidden>
    <div class="settings-modal-shell" role="document">
        <div class="settings-modal-top">
            <button type="button" class="settings-modal-close" id="settingsModalClose" aria-label="بستن تنظیمات">&times;</button>
            <div class="settings-modal-breadcrumb" id="settingsModalBreadcrumb">
                <span id="settingsModalTitle">تنظیمات</span>
                <span aria-hidden="true"> › </span>
                <strong id="settingsModalBreadcrumbTab">{{ \App\Support\AdminSettingsTabs::labelFor($settingsActiveTab) }}</strong>
            </div>
            <span style="width:2.35rem;flex-shrink:0" aria-hidden="true"></span>
        </div>
        @if (session('status') && session('open_settings_modal'))
            <div id="settingsModalStatus" class="settings-modal-status visible" role="status">{{ session('status') }}</div>
        @else
            <div id="settingsModalStatus" class="settings-modal-status" role="status" hidden></div>
        @endif
        <div class="settings-modal-body">
            <nav class="settings-modal-nav" id="settingsModalNav" aria-label="بخش‌های تنظیمات">
                @php $lastGroup = null; @endphp
                @foreach ($settingsTabs as $tab)
                    @php $group = $tab['group'] ?? 'other'; @endphp
                    @if ($lastGroup !== $group)
                        @if ($lastGroup !== null)
                            <div class="settings-nav-divider"></div>
                        @endif
                        @if (isset($settingsTabGroups[$group]))
                            <div class="settings-nav-group-label">{{ $settingsTabGroups[$group] }}</div>
                        @endif
                        @php $lastGroup = $group; @endphp
                    @endif
                    <button
                        type="button"
                        class="settings-nav-item {{ $settingsActiveTab === $tab['id'] ? 'active' : '' }}"
                        data-settings-tab="{{ $tab['id'] }}"
                        data-settings-label="{{ $tab['label'] }}"
                        @disabled(!empty($tab['disabled']))
                    >
                        <i class="fa-solid {{ $tab['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $tab['label'] }}</span>
                    </button>
                @endforeach
                <div class="settings-nav-divider"></div>
                <a href="{{ route('admin.login-audit.index') }}" class="settings-nav-item" style="text-decoration:none">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    <span>گزارش ورود</span>
                </a>
            </nav>
            <div class="settings-modal-content">
                @include('admin.partials.settings-panels')
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('settingsModalOverlay');
    const openBtn = document.getElementById('openSettingsModalBtn');
    if (!overlay) return;

    const closeBtn = document.getElementById('settingsModalClose');
    const nav = document.getElementById('settingsModalNav');
    const breadcrumbTab = document.getElementById('settingsModalBreadcrumbTab');
    const statusEl = document.getElementById('settingsModalStatus');
    const panels = overlay.querySelectorAll('[data-settings-panel]');
    const navButtons = nav ? nav.querySelectorAll('button[data-settings-tab]') : [];
    const initialTab = @json($settingsActiveTab);
    const shouldOpen = @json($openSettingsModal);

    function showStatus(msg) {
        if (!statusEl || !msg) return;
        statusEl.textContent = msg;
        statusEl.hidden = false;
        statusEl.classList.add('visible');
    }

    function activateTab(tabId, label) {
        navButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.settingsTab === tabId);
        });
        panels.forEach((panel) => {
            const active = panel.dataset.settingsPanel === tabId;
            panel.classList.toggle('active', active);
            panel.hidden = !active;
        });
        if (breadcrumbTab && label) {
            breadcrumbTab.textContent = label;
        }
    }

    function openModal(tabId) {
        overlay.hidden = false;
        overlay.classList.add('open');
        document.body.classList.add('settings-modal-open');
        const btn = tabId ? nav?.querySelector('[data-settings-tab="' + tabId + '"]') : null;
        const id = tabId || btn?.dataset.settingsTab || initialTab;
        const label = btn?.dataset.settingsLabel || breadcrumbTab?.textContent;
        activateTab(id, label);
    }

    function closeModal() {
        overlay.classList.remove('open');
        document.body.classList.remove('settings-modal-open');
        overlay.hidden = true;
    }

    openBtn?.addEventListener('click', () => openModal('password'));
    closeBtn?.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

    navButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            activateTab(btn.dataset.settingsTab, btn.dataset.settingsLabel);
        });
    });

    document.querySelectorAll('[data-open-settings]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            openModal(el.getAttribute('data-open-settings') || 'password');
        });
    });

    const logoInput = document.getElementById('logo-upload');
    const logoPreview = document.getElementById('logo-preview');
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', (event) => {
            const [file] = event.target.files || [];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => { logoPreview.src = e.target?.result || logoPreview.src; };
            reader.readAsDataURL(file);
        });
    }

    document.querySelectorAll('.font-picker-card').forEach((card) => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('button')) return;
            const input = card.querySelector('.font-picker-input');
            if (!input) return;
            input.checked = true;
            card.closest('.font-picker-grid')?.querySelectorAll('.font-picker-card').forEach((c) => {
                c.classList.toggle('is-selected', c === card);
                const badge = c.querySelector('.font-picker-badge');
                if (badge) badge.remove();
            });
            if (!card.querySelector('.font-picker-badge')) {
                const badge = document.createElement('span');
                badge.className = 'font-picker-badge';
                badge.textContent = 'فعال';
                card.querySelector('.font-picker-card-head')?.appendChild(badge);
            }
        });
    });

    document.querySelectorAll('.text-scale-option').forEach((option) => {
        option.addEventListener('click', () => {
            const input = option.querySelector('.text-scale-input');
            if (!input) return;
            input.checked = true;
            option.closest('.text-scale-picker')?.querySelectorAll('.text-scale-option').forEach((o) => {
                o.classList.toggle('is-selected', o === option);
            });
        });
    });

    panels.forEach((p) => {
        if (!p.classList.contains('active')) p.hidden = true;
    });

    if (shouldOpen) openModal(initialTab);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    window.adminSettingsModal = { open: openModal, close: closeModal, showStatus };
})();
</script>
@endif

@extends('admin.layouts.app')

@section('page-title', 'مدیریت ناظر واحدها')
@section('page-description', 'در این بخش می‌توانید برای هر واحد ناظر مشخص کنید، اطلاعات را ویرایش یا حذف نمایید و فهرست فعلی ناظرها را ببینید.')

@php
    $oldFormType = old('form');
    $editingSupervisorId = old('supervisor_id');
    $shouldOpenCreateModal = $errors->createSupervisor->any() || ($oldFormType === 'create');
    $shouldOpenEditModal = $errors->updateSupervisor->any() || ($oldFormType === 'update');
    $skipPortalClientSync = $shouldOpenEditModal && $errors->updateSupervisor->any();
    $filters = $filters ?? ['search' => request('search'), 'unit' => request('unit')];
    $assignableLabelMap = $permissionLabels ?? [];
    $normalizePortalPermKeysForUi = static function ($perms, array $labelMap): array {
        $flat = \Illuminate\Support\Arr::flatten(is_array($perms) ? $perms : []);
        $out = [];
        foreach ($flat as $pk) {
            if ($pk === null) {
                continue;
            }
            $pk = is_string($pk) ? $pk : (is_scalar($pk) ? (string) $pk : '');
            if ($pk === '' || ! array_key_exists($pk, $labelMap)) {
                continue;
            }
            $out[] = $pk;
        }

        return array_values(array_unique($out));
    };
@endphp

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .supervisors-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        body.modal-open {
            overflow: hidden;
        }
        .supervisors-card {
            background: #fff;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid rgba(15,23,42,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .supervisors-card h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .supervisor-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .supervisor-actions button,
        .supervisor-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .supervisor-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .supervisor-actions .ghost {
            background: rgba(15,23,42,0.05);
            color: var(--slate);
        }
        .supervisors-table-wrapper {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            overflow-x: auto;
        }
        table.supervisors-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 920px;
        }
        table.supervisors-table th,
        table.supervisors-table td {
            padding: 1rem 1.25rem;
            text-align: right;
        }
        table.supervisors-table thead {
            background: rgba(15,23,42,0.03);
            color: var(--muted);
            font-size: 0.95rem;
        }
        table.supervisors-table tbody tr + tr {
            border-top: 1px solid rgba(15,23,42,0.06);
        }
        .supervisors-table .actions {
            display: flex;
            gap: 0.6rem;
            align-items: stretch;
        }
        .action-btn {
            border: none;
            border-radius: 12px;
            padding: 0.55rem 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .actions form {
            margin: 0;
            display: flex;
        }
        .action-btn.edit {
            background: rgba(15,23,42,0.08);
            color: var(--slate);
        }
        .action-btn.delete {
            background: rgba(214,17,25,0.15);
            color: var(--primary);
        }
        .supervisors-empty {
            background: #fff;
            border-radius: 24px;
            border: 1px dashed rgba(15,23,42,0.2);
            padding: 1.5rem;
            text-align: center;
            color: var(--muted);
        }
        .filter-bar {
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
        }
        .filter-bar form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
            width: 100%;
        }
        .filter-control {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 14px;
            padding: 0.7rem 1rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            min-width: 180px;
        }
        .filter-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .filter-actions button,
        .filter-actions a {
            border: none;
            border-radius: 12px;
            padding: 0.65rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .filter-actions .apply {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .filter-actions .reset {
            background: rgba(15,23,42,0.05);
            color: var(--slate);
            text-decoration: none;
        }
        .supervisor-card-grid {
            display: none;
            gap: 1rem;
        }
        .supervisor-card-item {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(15,23,42,0.08);
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .supervisor-card-item span {
            color: var(--muted);
            font-size: 0.9rem;
        }
        .supervisor-card-item strong {
            font-size: 1.05rem;
            color: var(--slate);
        }
        .supervisor-card-item .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }
        .supervisor-card-item .card-buttons {
            display: flex;
            gap: 0.4rem;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding: max(0.5rem, env(safe-area-inset-top)) max(0.75rem, env(safe-area-inset-right)) max(0.65rem, env(safe-area-inset-bottom)) max(0.75rem, env(safe-area-inset-left));
            z-index: 100;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .modal.open {
            display: flex;
        }
        .modal-dialog {
            width: min(580px, 100%);
            background: #fff;
            border-radius: 28px;
            padding: 1.8rem;
            box-shadow: 0 40px 80px rgba(15,23,42,0.25);
            border: 1px solid rgba(15,23,42,0.08);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .supervisor-modal-shell {
            width: min(640px, 100%);
            max-height: min(88vh, 52rem);
            max-height: min(88dvh, 52rem);
            margin: auto;
            padding: 0;
            gap: 0;
            overflow: hidden;
            min-height: 0;
            flex-shrink: 0;
        }
        .supervisor-modal-form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }
        .supervisor-modal-shell .modal-header {
            flex: 0 0 auto;
            padding: 1.15rem 1.4rem 0.85rem;
            border-bottom: 1px solid rgba(15,23,42,0.07);
        }
        .supervisor-modal-shell .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding: 1rem 1.4rem 1.15rem;
            overscroll-behavior: contain;
        }
        .supervisor-modal-shell .modal-actions {
            flex: 0 0 auto;
            padding: 0.85rem 1.4rem 1.15rem;
            margin: 0;
            border-top: 1px solid rgba(15,23,42,0.08);
            background: linear-gradient(180deg, rgba(255,255,255,0.96) 0%, #fff 35%);
            box-shadow: 0 -6px 24px rgba(15,23,42,0.05);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .modal-close {
            border: none;
            background: rgba(15,23,42,0.08);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .modal-body label {
            font-weight: 600;
        }
        .modal-body select {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
        }
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .submit-btn {
            border: none;
            border-radius: 16px;
            padding: 0.85rem 1.6rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .cancel-btn {
            border: none;
            border-radius: 16px;
            padding: 0.85rem 1.6rem;
            background: rgba(15,23,42,0.05);
            color: var(--slate);
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .supervisors-table-wrapper {
                display: none;
            }
            .supervisor-card-grid {
                display: grid;
            }
            .supervisor-modal-shell {
                max-height: min(92dvh, 100%);
                border-radius: 22px;
            }
            .portal-permissions-grid {
                grid-template-columns: 1fr;
            }
        }
        .select2-container--default .select2-selection--single {
            border-radius: 16px;
            border: 1px solid rgba(15,23,42,0.15);
            height: 48px;
            display: flex;
            align-items: center;
            font-family: 'Vazirmatn', system-ui, sans-serif;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--slate);
            line-height: 48px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 10px;
        }
        .error-text {
            color: #b91c1c;
            font-size: 0.85rem;
            font-weight: 600;
            display: block;
            margin-top: 0.25rem;
        }
        .modal-dialog--wide:not(.supervisor-modal-shell) {
            width: min(640px, 100%);
        }
        .modal-body input[type="text"],
        .modal-body input[type="password"] {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-family: inherit;
            width: 100%;
            box-sizing: border-box;
        }
        .modal-section-title {
            margin: 0.75rem 0 0.35rem;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--slate);
            padding-top: 0.35rem;
            border-top: 1px solid rgba(15,23,42,0.08);
        }
        .modal-section-title:first-of-type {
            border-top: none;
            padding-top: 0;
        }
        .modal-help {
            font-size: 0.82rem;
            color: var(--muted);
            font-weight: 500;
            margin: 0 0 0.5rem;
            line-height: 1.45;
        }
        .portal-permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 11rem), 1fr));
            gap: 0.45rem 0.75rem;
            margin-top: 0.35rem;
        }
        .portal-permissions-grid label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .portal-active-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 600;
        }
        .portal-active-row input {
            width: auto;
        }
        .account-badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .account-badge--ok {
            background: rgba(21,128,61,0.12);
            color: #166534;
        }
        .account-badge--off {
            background: rgba(100,116,139,0.15);
            color: #475569;
        }
        .account-badge--none {
            background: rgba(15,23,42,0.06);
            color: var(--muted);
        }
    </style>

    <div class="supervisors-wrapper">
        <div class="supervisors-card">
            <div>
                <h2>لیست ناظر واحدها</h2>
                <p>برای هر واحد ناظر ثبت کنید و در صورت نیاز تغییر دهید.</p>
            </div>
            <div class="supervisor-actions">
                <button type="button" class="primary" id="openCreateSupervisorModal">تخصیص ناظر جدید</button>
                <a href="{{ route('admin.unit-supervisors.index') }}" class="ghost">بارگذاری مجدد</a>
            </div>
        </div>

        @if (session('status'))
            <div class="status-message">
                {{ session('status') }}
            </div>
        @endif

        <div class="filter-bar">
            <form method="GET" action="{{ route('admin.unit-supervisors.index') }}">
                <input type="text"
                       name="search"
                       class="filter-control"
                       placeholder="جستجو بر اساس نام یا کد پرسنلی"
                       value="{{ $filters['search'] }}">
                <select name="unit" class="filter-control">
                    <option value="">همه واحدها</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" {{ (string) $filters['unit'] === (string) $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
                <div class="filter-actions">
                    <button type="submit" class="apply">اعمال فیلتر</button>
                    <a href="{{ route('admin.unit-supervisors.index') }}" class="reset">حذف فیلترها</a>
                </div>
            </form>
        </div>

        @if ($supervisors->count())
            <div class="supervisors-table-wrapper">
                <table class="supervisors-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام کاربر</th>
                            <th>کد پرسنلی</th>
                            <th>واحد</th>
                            <th>نام کاربری پنل</th>
                            <th>وضعیت حساب</th>
                            <th>تاریخ ثبت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($supervisors as $supervisor)
                            @php
                                $portalAccount = $supervisor->resolvePortalAdmin();
                                $portalPermKeysForBtn = $normalizePortalPermKeysForUi($portalAccount?->permissions, $assignableLabelMap);
                            @endphp
                            <tr>
                                <td>#{{ $supervisor->id }}</td>
                                <td>{{ trim(($supervisor->personnel->first_name ?? '') . ' ' . ($supervisor->personnel->last_name ?? '')) ?: 'نامشخص' }}</td>
                                <td>{{ $supervisor->personnel_code }}</td>
                                <td>{{ $supervisor->unit?->name ?? '—' }}</td>
                                <td>{{ $portalAccount?->username ?? '—' }}</td>
                                <td>
                                    @if ($portalAccount)
                                        @if ($portalAccount->is_active)
                                            <span class="account-badge account-badge--ok">فعال</span>
                                        @else
                                            <span class="account-badge account-badge--off">غیرفعال</span>
                                        @endif
                                    @else
                                        <span class="account-badge account-badge--none">بدون حساب</span>
                                    @endif
                                </td>
                                <td>{{ jalali_date($supervisor->created_at) }}</td>
                                <td class="actions">
                                    <button type="button"
                                            class="action-btn edit edit-supervisor-btn"
                                            data-id="{{ $supervisor->id }}"
                                            data-personnel-code="{{ $supervisor->personnel_code }}"
                                            data-unit-id="{{ $supervisor->unit_id }}"
                                            data-action="{{ route('admin.unit-supervisors.update', $supervisor) }}"
                                            data-portal-username="{{ $portalAccount?->username ?? '' }}"
                                            data-portal-active="{{ $portalAccount && $portalAccount->is_active ? '1' : '0' }}"
                                            data-portal-permissions='@json($portalPermKeysForBtn)'
                                            data-requires-survey-publish-approval="{{ $portalAccount && $portalAccount->requires_survey_publish_approval ? '1' : '0' }}">
                                        ویرایش
                                    </button>
                                    <form method="POST" action="{{ route('admin.unit-supervisors.destroy', $supervisor) }}" onsubmit="return confirm('از حذف ناظر مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn delete">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="supervisor-card-grid">
                @foreach ($supervisors as $supervisor)
                    @php
                        $portalAccount = $supervisor->resolvePortalAdmin();
                        $portalPermKeysForBtn = $normalizePortalPermKeysForUi($portalAccount?->permissions, $assignableLabelMap);
                    @endphp
                    <div class="supervisor-card-item">
                        <div>
                            <span>نام کاربر</span>
                            <strong>{{ trim(($supervisor->personnel->first_name ?? '') . ' ' . ($supervisor->personnel->last_name ?? '')) ?: 'نامشخص' }}</strong>
                        </div>
                        <div>
                            <span>کد پرسنلی</span>
                            <strong>{{ $supervisor->personnel_code }}</strong>
                        </div>
                        <div>
                            <span>واحد</span>
                            <strong>{{ $supervisor->unit?->name ?? '—' }}</strong>
                        </div>
                        <div>
                            <span>پنل</span>
                            <strong>
                                @if ($portalAccount)
                                    {{ $portalAccount->username }}
                                    @if ($portalAccount->is_active)
                                        <span class="account-badge account-badge--ok">فعال</span>
                                    @else
                                        <span class="account-badge account-badge--off">غیرفعال</span>
                                    @endif
                                @else
                                    <span class="account-badge account-badge--none">بدون حساب</span>
                                @endif
                            </strong>
                        </div>
                        <div class="card-actions">
                            <time>{{ jalali_date($supervisor->created_at) }}</time>
                            <div class="card-buttons">
                                <button type="button"
                                        class="action-btn edit edit-supervisor-btn"
                                        data-id="{{ $supervisor->id }}"
                                        data-personnel-code="{{ $supervisor->personnel_code }}"
                                        data-unit-id="{{ $supervisor->unit_id }}"
                                        data-action="{{ route('admin.unit-supervisors.update', $supervisor) }}"
                                        data-portal-username="{{ $portalAccount?->username ?? '' }}"
                                        data-portal-active="{{ $portalAccount && $portalAccount->is_active ? '1' : '0' }}"
                                        data-portal-permissions='@json($portalPermKeysForBtn)'
                                        data-requires-survey-publish-approval="{{ $portalAccount && $portalAccount->requires_survey_publish_approval ? '1' : '0' }}">
                                    ویرایش
                                </button>
                                <form method="POST" action="{{ route('admin.unit-supervisors.destroy', $supervisor) }}" onsubmit="return confirm('از حذف ناظر مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display:flex; justify-content:center;">
                {{ $supervisors->links() }}
            </div>
        @else
            <div class="supervisors-empty">
                هیچ ناظری ثبت نشده است. از دکمه «تخصیص ناظر جدید» استفاده کنید.
            </div>
        @endif
    </div>

    <div class="modal {{ $shouldOpenCreateModal ? 'open' : '' }}" id="createSupervisorModal" data-open="{{ $shouldOpenCreateModal ? 'true' : 'false' }}" data-keep-latin-numbers>
        <div class="modal-dialog modal-dialog--wide supervisor-modal-shell">
            <div class="modal-header">
                <h3>تخصیص ناظر جدید</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form class="supervisor-modal-form" method="POST" action="{{ route('admin.unit-supervisors.store') }}">
                @csrf
                <input type="hidden" name="form" value="create">
                <div class="modal-body">
                    <label for="create-supervisor-personnel">انتخاب کاربر</label>
                    <select id="create-supervisor-personnel" name="personnel_code" class="supervisor-select">
                        <option value="">-- انتخاب کاربر --</option>
                        @foreach ($personnel as $person)
                            <option value="{{ $person->personnel_code }}" {{ ($shouldOpenCreateModal ? old('personnel_code') : '') === $person->personnel_code ? 'selected' : '' }}>
                                {{ $person->first_name }} {{ $person->last_name }} - {{ $person->personnel_code }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->createSupervisor->has('personnel_code'))
                        <span class="error-text">{{ $errors->createSupervisor->first('personnel_code') }}</span>
                    @endif

                    <label for="create-supervisor-unit" style="margin-top:0.6rem;">انتخاب واحد</label>
                    <select id="create-supervisor-unit" name="unit_id" class="supervisor-select">
                        <option value="">-- انتخاب واحد --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" {{ ($shouldOpenCreateModal ? old('unit_id') : '') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->createSupervisor->has('unit_id'))
                        <span class="error-text">{{ $errors->createSupervisor->first('unit_id') }}</span>
                    @endif

                    <p class="modal-section-title">دسترسی پنل مدیریت <span style="font-weight:500;color:var(--muted);font-size:0.85rem;">(اختیاری)</span></p>
                    <p class="modal-help">با نام کاربری و رمز، ناظر می‌تواند وارد پنل شود؛ با تیک‌های زیر مشخص کنید کدام بخش‌ها را ببیند. خالی بگذارید اگر فقط ثبت ناظر بدون حساب ورود کافی است.</p>

                    <label for="create-portal-username">نام کاربری پنل</label>
                    <input type="text" name="portal_username" id="create-portal-username" value="{{ old('portal_username') }}" autocomplete="username" placeholder="مثال: nazer.unit1">
                    @if ($errors->createSupervisor->has('portal_username'))
                        <span class="error-text">{{ $errors->createSupervisor->first('portal_username') }}</span>
                    @endif

                    <label for="create-portal-password" style="margin-top:0.6rem;">رمز عبور</label>
                    <input type="password" name="portal_password" id="create-portal-password" autocomplete="new-password" placeholder="حداقل ۸ کاراکتر">
                    @if ($errors->createSupervisor->has('portal_password'))
                        <span class="error-text">{{ $errors->createSupervisor->first('portal_password') }}</span>
                    @endif

                    <label for="create-portal-password-confirmation" style="margin-top:0.6rem;">تکرار رمز عبور</label>
                    <input type="password" name="portal_password_confirmation" id="create-portal-password-confirmation" autocomplete="new-password">
                    @if ($errors->createSupervisor->has('portal_password_confirmation'))
                        <span class="error-text">{{ $errors->createSupervisor->first('portal_password_confirmation') }}</span>
                    @endif

                    <p class="modal-section-title" style="font-size:0.9rem;">بخش‌های قابل مشاهده در پنل</p>
                    <p class="modal-help">این فهرست از تنظیمات دسترسی برنامه پر می‌شود؛ هر بخش جدیدی که در منوی مدیریت یا لیبل‌های دسترسی تعریف کنید، اینجا هم برای تیک زدن ظاهر می‌شود.</p>
                    <div class="portal-permissions-grid">
                        @foreach ($permissionLabels as $key => $label)
                            <label>
                                <input type="checkbox" name="portal_permissions[]" value="{{ $key }}"
                                    @checked(in_array($key, old('portal_permissions', $defaultPortalPermissions), true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="portal-active-row">
                        <input type="checkbox" name="portal_active" value="1" id="create-portal-active"
                            @checked(!$errors->createSupervisor->any() || old('portal_active'))>
                        <label for="create-portal-active" style="margin:0;">حساب پنل فعال باشد</label>
                    </div>

                    <p class="modal-section-title" style="font-size:0.9rem;">نیاز به تأیید انتشار نظرسنجی</p>
                    <p class="modal-help">در صورت «بله»، پس از آماده‌سازی نظرسنجی، مدیر اصلی باید انتشار را تأیید کند؛ در غیر این صورت سرپرست با «ایجاد لینک و فعال‌سازی» خودش منتشر می‌کند.</p>
                    @php
                        $reqPubOld = old('requires_survey_publish_approval');
                        $reqPubCheckedYes = $reqPubOld === true || $reqPubOld === 1 || $reqPubOld === '1';
                    @endphp
                    <div class="portal-active-row" style="flex-wrap:wrap; gap:0.75rem;">
                        <label style="margin:0; font-weight:600;"><input type="radio" name="requires_survey_publish_approval" value="0" id="create-requires-approval-no" @checked(!$reqPubCheckedYes)> خیر</label>
                        <label style="margin:0; font-weight:600;"><input type="radio" name="requires_survey_publish_approval" value="1" id="create-requires-approval-yes" @checked($reqPubCheckedYes)> بله</label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت ناظر</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal {{ $shouldOpenEditModal ? 'open' : '' }}" id="editSupervisorModal" data-open="{{ $shouldOpenEditModal ? 'true' : 'false' }}" data-old-edit-id="{{ $shouldOpenEditModal ? $editingSupervisorId : '' }}" data-skip-client-sync="{{ $skipPortalClientSync ? 'true' : 'false' }}" data-keep-latin-numbers>
        <div class="modal-dialog modal-dialog--wide supervisor-modal-shell">
            <div class="modal-header">
                <h3>ویرایش ناظر واحد</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form class="supervisor-modal-form" method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="update">
                <input type="hidden" name="supervisor_id" value="{{ $editingSupervisorId }}">
                <div class="modal-body">
                    <label for="edit-supervisor-personnel">انتخاب کاربر</label>
                    <select id="edit-supervisor-personnel" name="personnel_code" class="supervisor-select">
                        <option value="">-- انتخاب کاربر --</option>
                        @foreach ($personnel as $person)
                            <option value="{{ $person->personnel_code }}" {{ ($shouldOpenEditModal ? old('personnel_code') : '') === $person->personnel_code ? 'selected' : '' }}>
                                {{ $person->first_name }} {{ $person->last_name }} - {{ $person->personnel_code }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->updateSupervisor->has('personnel_code'))
                        <span class="error-text">{{ $errors->updateSupervisor->first('personnel_code') }}</span>
                    @endif

                    <label for="edit-supervisor-unit" style="margin-top:0.6rem;">انتخاب واحد</label>
                    <select id="edit-supervisor-unit" name="unit_id" class="supervisor-select">
                        <option value="">-- انتخاب واحد --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" {{ ($shouldOpenEditModal ? old('unit_id') : '') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->updateSupervisor->has('unit_id'))
                        <span class="error-text">{{ $errors->updateSupervisor->first('unit_id') }}</span>
                    @endif

                    <p class="modal-section-title">دسترسی پنل مدیریت</p>
                    <p class="modal-help">برای همان کد پرسنلی در همهٔ واحدهایی که ناظر است یک حساب مشترک استفاده می‌شود. رمز را خالی بگذارید تا رمز فعلی عوض نشود.</p>

                    <label for="edit-portal-username">نام کاربری پنل</label>
                    <input type="text" name="portal_username" id="edit-portal-username" value="{{ old('portal_username') }}" autocomplete="username" placeholder="خالی = بدون تغییر نام کاربری در صورت وجود حساب">
                    @if ($errors->updateSupervisor->has('portal_username'))
                        <span class="error-text">{{ $errors->updateSupervisor->first('portal_username') }}</span>
                    @endif

                    <label for="edit-portal-password" style="margin-top:0.6rem;">رمز عبور جدید</label>
                    <input type="password" name="portal_password" id="edit-portal-password" autocomplete="new-password" placeholder="خالی = حفظ رمز فعلی">
                    @if ($errors->updateSupervisor->has('portal_password'))
                        <span class="error-text">{{ $errors->updateSupervisor->first('portal_password') }}</span>
                    @endif

                    <label for="edit-portal-password-confirmation" style="margin-top:0.6rem;">تکرار رمز عبور</label>
                    <input type="password" name="portal_password_confirmation" id="edit-portal-password-confirmation" autocomplete="new-password">
                    @if ($errors->updateSupervisor->has('portal_password_confirmation'))
                        <span class="error-text">{{ $errors->updateSupervisor->first('portal_password_confirmation') }}</span>
                    @endif

                    <p class="modal-section-title" style="font-size:0.9rem;">بخش‌های قابل مشاهده در پنل</p>
                    <p class="modal-help">فهرست زیر به‌صورت خودکار از تنظیمات دسترسی برنامه به‌روز می‌شود؛ بخش‌های جدید منو یا کلیدهای دسترسی جدید بدون تغییر این صفحه اینجا دیده می‌شوند.</p>
                    <div class="portal-permissions-grid">
                        @foreach ($permissionLabels as $key => $label)
                            <label>
                                <input type="checkbox" name="portal_permissions[]" value="{{ $key }}" class="edit-portal-perm"
                                    @if ($skipPortalClientSync)
                                        @checked(in_array($key, old('portal_permissions', []), true))
                                    @endif>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="portal-active-row">
                        <input type="checkbox" name="portal_active" value="1" id="edit-portal-active"
                            @if ($skipPortalClientSync)
                                @checked(old('portal_active'))
                            @endif>
                        <label for="edit-portal-active" style="margin:0;">حساب پنل فعال باشد</label>
                    </div>

                    <p class="modal-section-title" style="font-size:0.9rem;">نیاز به تأیید انتشار نظرسنجی</p>
                    <p class="modal-help">همانند بالا؛ برای حساب‌های موجود با دکمهٔ ویرایش از مقدار ذخیره‌شده پر می‌شود.</p>
                    @php
                        $reqPubEditOld = old('requires_survey_publish_approval');
                        $reqPubEditYes = $skipPortalClientSync && ($reqPubEditOld === true || $reqPubEditOld === 1 || $reqPubEditOld === '1');
                    @endphp
                    <div class="portal-active-row" style="flex-wrap:wrap; gap:0.75rem;">
                        <label style="margin:0; font-weight:600;"><input type="radio" name="requires_survey_publish_approval" value="0" id="edit-requires-approval-no" @if ($skipPortalClientSync) @checked(!$reqPubEditYes) @endif> خیر</label>
                        <label style="margin:0; font-weight:600;"><input type="radio" name="requires_survey_publish_approval" value="1" id="edit-requires-approval-yes" @if ($skipPortalClientSync) @checked($reqPubEditYes) @endif> بله</label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت تغییرات</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        const initSupervisorSelect2 = () => {
            if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
                return;
            }
            jQuery('.supervisor-select').each(function () {
                const $select = jQuery(this);
                if ($select.data('select2-initialized')) {
                    return;
                }
                const parentModal = $select.closest('.modal');
                $select.select2({
                    width: '100%',
                    dir: 'rtl',
                    dropdownParent: parentModal.length ? parentModal : undefined,
                    language: {
                        noResults: () => 'نتیجه‌ای یافت نشد',
                    },
                });
                const $container = $select.next('.select2-container');
                $container.attr('data-keep-latin-numbers', '');
                jQuery(document.body).find('.select2-dropdown').attr('data-keep-latin-numbers', '');
                $select.data('select2-initialized', true);
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            initSupervisorSelect2();
            const body = document.body;
            const createModal = document.getElementById('createSupervisorModal');
            const editModal = document.getElementById('editSupervisorModal');
            const openCreateBtn = document.getElementById('openCreateSupervisorModal');

            const openModal = (modal) => {
                if (!modal) return;
                modal.classList.add('open');
                body.classList.add('modal-open');
            };

            const closeModal = (modal) => {
                if (!modal) return;
                modal.classList.remove('open');
                if (!document.querySelector('.modal.open')) {
                    body.classList.remove('modal-open');
                }
            };

            openCreateBtn?.addEventListener('click', () => openModal(createModal));

            document.querySelectorAll('[data-modal-close]').forEach((btn) => {
                btn.addEventListener('click', () => closeModal(btn.closest('.modal')));
            });

            document.querySelectorAll('.modal').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            const editForm = editModal?.querySelector('form');
            const editPersonnel = document.getElementById('edit-supervisor-personnel');
            const editUnit = document.getElementById('edit-supervisor-unit');
            const editIdField = editModal?.querySelector('input[name="supervisor_id"]');

            const openEditModal = (btn) => {
                if (!editModal || !editForm || !btn) return;
                const ds = btn.dataset;
                if (ds.action) {
                    editForm.action = ds.action;
                }
                if (editIdField) {
                    editIdField.value = ds.id || '';
                }
                if (editPersonnel) {
                    editPersonnel.value = ds.personnelCode || '';
                    jQuery(editPersonnel).trigger('change.select2');
                }
                if (editUnit) {
                    editUnit.value = ds.unitId || '';
                    jQuery(editUnit).trigger('change.select2');
                }

                const skipPortal = editModal.dataset.skipClientSync === 'true';
                if (!skipPortal) {
                    const userEl = document.getElementById('edit-portal-username');
                    const passEl = document.getElementById('edit-portal-password');
                    const passConfEl = document.getElementById('edit-portal-password-confirmation');
                    const activeEl = document.getElementById('edit-portal-active');
                    if (userEl) {
                        userEl.value = ds.portalUsername || '';
                    }
                    if (passEl) passEl.value = '';
                    if (passConfEl) passConfEl.value = '';
                    if (activeEl) {
                        activeEl.checked = ds.portalActive === '1';
                    }
                    const reqYes = document.getElementById('edit-requires-approval-yes');
                    const reqNo = document.getElementById('edit-requires-approval-no');
                    if (reqYes && reqNo) {
                        const needsAppr = ds.requiresSurveyPublishApproval === '1';
                        reqYes.checked = needsAppr;
                        reqNo.checked = !needsAppr;
                    }
                    let rawStr = btn.getAttribute('data-portal-permissions');
                    if (rawStr === null || rawStr.trim() === '') {
                        rawStr = ds.portalPermissions || '[]';
                    }
                    let perms = [];
                    try {
                        const raw = JSON.parse(rawStr || '[]');
                        perms = Array.isArray(raw) ? raw : (raw && typeof raw === 'object' ? Object.values(raw) : []);
                    } catch (e) {
                        perms = [];
                    }
                    if (!Array.isArray(perms)) {
                        perms = [];
                    }
                    perms = perms.map(String);
                    const allowed = new Set(perms);
                    editModal.querySelectorAll('.edit-portal-perm').forEach((cb) => {
                        cb.checked = allowed.has(String(cb.value));
                    });
                }

                openModal(editModal);
            };

            document.querySelectorAll('.edit-supervisor-btn').forEach((btn) => {
                btn.addEventListener('click', () => openEditModal(btn));
            });

            if (createModal?.dataset.open === 'true') {
                openModal(createModal);
            }

            if (editModal?.dataset.open === 'true') {
                const targetId = editModal.dataset.oldEditId;
                if (targetId) {
                    const trigger = document.querySelector(`.edit-supervisor-btn[data-id="${targetId}"]`);
                    if (trigger) {
                        trigger.click();
                    } else {
                        openModal(editModal);
                    }
                } else {
                    openModal(editModal);
                }
            }

        });
    </script>
@endsection

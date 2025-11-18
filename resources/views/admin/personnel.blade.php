@extends('admin.layouts.app')

@section('page-title', 'تعریف پرسنل')
@section('page-description', 'مدیریت و تعریف اطلاعات پرسنل سازمان همراه با مشاهده سریع سوابق و ویرایش جزئیات.')

@php
    $oldFormType = old('form');
    $editingPersonnelId = old('personnel_id');
    $shouldOpenCreateModal = $errors->createPersonnel->any() || ($oldFormType === 'create');
    $shouldOpenEditModal = $errors->updatePersonnel->any() || ($oldFormType === 'update');
    $shouldOpenBulkModal = $errors->bulkPersonnel->any() || ($oldFormType === 'bulk');
    $filters = $filters ?? [
        'search' => request('search'),
        'unit' => request('unit'),
        'position' => request('position'),
        'gender' => request('gender'),
    ];
@endphp

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
    <style>
        .personnel-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        body.modal-open {
            overflow: hidden;
        }
        .personnel-card {
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
        .personnel-card h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .personnel-card p {
            margin: 0.35rem 0 0;
            color: var(--muted);
        }
        .personnel-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .personnel-actions button,
        .personnel-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .personnel-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .personnel-actions .ghost {
            background: rgba(15,23,42,0.05);
            color: var(--slate);
            text-decoration: none;
        }
        .personnel-actions .outline {
            border: 1px dashed rgba(15,23,42,0.2);
            background: transparent;
            color: var(--slate);
        }
        .status-message {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #15803d;
            border-radius: 18px;
            padding: 0.9rem 1.2rem;
            font-weight: 600;
        }
        .filter-bar {
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .filter-bar form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            align-items: center;
        }
        .filter-control {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 14px;
            padding: 0.75rem 1rem;
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
        .bulk-hint {
            background: rgba(15,23,42,0.04);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.8;
        }
        .personnel-table-wrapper {
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(15,23,42,0.08);
            overflow-x: auto;
        }
        .personnel-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 960px;
        }
        .personnel-table th,
        .personnel-table td {
            padding: 0.95rem 0.75rem;
            text-align: center;
            border-bottom: 1px solid rgba(15,23,42,0.06);
            font-size: 0.95rem;
        }
        .personnel-table th {
            background: rgba(15,23,42,0.02);
            color: var(--muted);
            font-weight: 600;
        }
        .personnel-table tr:last-child td {
            border-bottom: none;
        }
        .personnel-table .actions {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .action-btn {
            border: none;
            border-radius: 12px;
            padding: 0.55rem 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        .action-btn.edit {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }
        .action-btn.delete {
            background: rgba(239, 68, 68, 0.15);
            color: #b91c1c;
        }
        .empty-state {
            background: #fff;
            border-radius: 24px;
            padding: 2rem;
            border: 1px dashed rgba(15,23,42,0.15);
            text-align: center;
            color: var(--muted);
        }
        .personnel-card-grid {
            display: none;
            gap: 1rem;
        }
        .personnel-card-item {
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(15,23,42,0.08);
            padding: 1.2rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            box-shadow: 0 18px 35px rgba(15,23,42,0.05);
        }
        .personnel-card-item .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .personnel-card-item strong {
            font-size: 1.05rem;
            color: var(--slate);
        }
        .personnel-card-item span {
            color: var(--muted);
            font-size: 0.9rem;
        }
        .personnel-card-item .card-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .personnel-card-item .card-meta div {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            border-radius: 16px;
            background: rgba(15,23,42,0.03);
            padding: 0.55rem 0.75rem;
        }
        .personnel-card-item .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .personnel-card-item .card-actions time {
            color: var(--muted);
            font-size: 0.85rem;
        }
        .personnel-card-item .card-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 1rem;
        }
        .modal.open {
            display: flex;
        }
        .modal-dialog {
            width: min(640px, 100%);
            background: #fff;
            border-radius: 28px;
            padding: 1.8rem;
            box-shadow: 0 40px 80px rgba(15,23,42,0.25);
            border: 1px solid rgba(15,23,42,0.08);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
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
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--slate);
        }
        .modal-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 0.9rem 1rem;
        }
        .modal-body .field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .modal-body label {
            font-weight: 600;
        }
        .modal-body input,
        .modal-body select {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .modal-body input[type="file"] {
            border-radius: 16px;
            background: rgba(15,23,42,0.02);
            padding: 0.75rem 1rem;
        }
        .modal-body input:focus,
        .modal-body select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.15);
        }
        .jalali-date-input {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .jalali-date-input input[type="text"] {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            direction: rtl;
        }
        .error-text {
            color: #b91c1c;
            font-size: 0.85rem;
        }
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .download-template {
            border: none;
            border-radius: 16px;
            padding: 0.85rem 1.6rem;
            background: rgba(15,23,42,0.08);
            color: var(--slate);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 0.85rem 1.6rem;
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
        @media (max-width: 960px) {
            .personnel-table-wrapper {
                display: none;
            }
            .personnel-card-grid {
                display: grid;
            }
        }
    </style>

    <div class="personnel-wrapper">
        <div class="personnel-card">
            <div>
                <h2>تعریف و مدیریت پرسنل</h2>
                <p>لیست کامل کاربران سازمان به همراه اطلاعات پرسنلی و لینک‌های ویرایش سریع.</p>
            </div>
            <div class="personnel-actions">
                <button type="button" class="primary" id="openCreatePersonnelModal">افزودن پرسنل جدید</button>
                <button type="button" class="outline" id="openBulkImportModal">بارگذاری یکجا</button>
                <a href="{{ route('admin.personnel.index') }}" class="ghost">بارگذاری مجدد</a>
            </div>
        </div>

    @if (session('status'))
        <div class="status-message">
            {{ session('status') }}
        </div>
    @endif
    @if (session('bulk_errors') && count(session('bulk_errors')))
        <div class="status-message" style="background: rgba(248,113,113,0.12); border-color: rgba(248,113,113,0.4); color: #b91c1c;">
            <strong>موارد دارای خطا:</strong>
            <ul style="margin: 0.35rem 1.2rem 0; padding: 0; list-style: disc; text-align: right;">
                @foreach (session('bulk_errors') as $issue)
                    <li>{{ $issue }}</li>
                @endforeach
            </ul>
        </div>
@endif

    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.personnel.index') }}">
            <input type="text"
                   name="search"
                   class="filter-control"
                   placeholder="جستجو..."
                   value="{{ $filters['search'] }}">
            <select name="unit" class="filter-control">
                <option value="">همه واحدها</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" {{ (string) $filters['unit'] === (string) $unit->id ? 'selected' : '' }}>
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            <select name="position" class="filter-control">
                <option value="">همه سمت ها</option>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" {{ (string) $filters['position'] === (string) $position->id ? 'selected' : '' }}>
                        {{ $position->name }}
                    </option>
                @endforeach
            </select>
            <select name="gender" class="filter-control">
                <option value="">همه جنسیت ها</option>
                @foreach ($genders as $key => $label)
                    <option value="{{ $key }}" {{ $filters['gender'] === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <div class="filter-actions">
                <button type="submit" class="apply">اعمال فیلتر</button>
                <a href="{{ route('admin.personnel.index') }}" class="reset">بازنشانی جستجو</a>
            </div>
        </form>
    </div>
    <div class="modal {{ $shouldOpenBulkModal ? 'open' : '' }}" id="bulkImportModal" data-open="{{ $shouldOpenBulkModal ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>بارگذاری یکجا پرسنل</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.personnel.bulk-import') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="form" value="bulk">
                <div class="modal-body">
                    <div class="field" style="grid-column: 1 / -1;">
                        <div class="bulk-hint">
                            فایل اکسل باید دارای ستون‌های <strong>first_name, last_name, personnel_code, mobile, position_id, unit_id, gender, national_code, birth_date</strong> باشد.
                            برای ستون‌های شناسه (سمت، واحد و جنسیت) مقدار عددی همان شناسه ثبت‌شده در سیستم را وارد کنید. تاریخ تولد را می‌توانید به میلادی (YYYY-MM-DD) یا شمسی (YYYY/MM/DD) بنویسید.
                        </div>
                    </div>
                    <div class="field">
                        <label for="bulk-import-file">انتخاب فایل اکسل</label>
                        <input id="bulk-import-file" type="file" name="import_file" accept=".xlsx,.xls">
                        @if ($errors->bulkPersonnel->has('import_file'))
                            <span class="error-text">{{ $errors->bulkPersonnel->first('import_file') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label>راهنمای سریع</label>
                        <ul style="margin:0; padding-right: 1rem; color: var(--muted); line-height: 1.8;">
                            <li>در ستون‌های position_id و unit_id شناسه عددی سمت و واحد را درج کنید (مثلاً 1 برای مدیرعامل).</li>
                            <li>در ستون gender از اعداد 1 (مرد)، 2 (زن) یا 3 (سایر) استفاده کنید.</li>
                            <li>برای جلوگیری از حذف صفرهای ابتدای کد پرسنلی، ستون personnel_code را روی حالت «متن» تنظیم کنید یا مقدار را با '00123 وارد نمایید.</li>
                            <li>پیش از بارگذاری، از فایل نمونه برای مشاهده ساختار صحیح استفاده کنید.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">شروع آپلود</button>
                    <a href="{{ route('admin.personnel.template') }}" class="download-template" target="_blank" rel="noopener">دانلود فایل نمونه</a>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

        @if ($personnel->count())
            <div class="personnel-table-wrapper">
                <table class="personnel-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>نام</th>
                            <th>نام خانوادگی</th>
                            <th>کد پرسنلی</th>
                            <th>شماره موبایل</th>
                            <th>سمت</th>
                            <th>واحد</th>
                            <th>جنسیت</th>
                            <th>کد ملی</th>
                            <th>تاریخ تولد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($personnel as $member)
                            <tr>
                                <td>#{{ $member->id }}</td>
                                <td>{{ $member->first_name }}</td>
                                <td>{{ $member->last_name }}</td>
                                <td>{{ $member->personnel_code }}</td>
                                <td>{{ $member->mobile }}</td>
                                <td>{{ $member->position?->name ?? '—' }}</td>
                                <td>{{ $member->unit?->name ?? '—' }}</td>
                                <td>{{ $genders[$member->gender] ?? $member->gender }}</td>
                                <td>{{ $member->national_code }}</td>
                                <td>{{ jalali_date($member->birth_date) }}</td>
                                <td class="actions">
                                    <button type="button"
                                            class="action-btn edit edit-personnel-btn"
                                            data-id="{{ $member->id }}"
                                            data-first-name="{{ e($member->first_name) }}"
                                            data-last-name="{{ e($member->last_name) }}"
                                            data-personnel-code="{{ e($member->personnel_code) }}"
                                            data-mobile="{{ e($member->mobile) }}"
                                            data-position-id="{{ $member->position_id }}"
                                            data-unit-id="{{ $member->unit_id }}"
                                            data-gender="{{ $member->gender }}"
                                            data-national-code="{{ e($member->national_code) }}"
                                            data-birth-date="{{ optional($member->birth_date)->format('Y-m-d') }}"
                                            data-action="{{ route('admin.personnel.update', $member) }}">
                                        ویرایش
                                    </button>
                                    <form method="POST" action="{{ route('admin.personnel.destroy', $member) }}" onsubmit="return confirm('آیا از حذف این پرسنل مطمئن هستید؟');">
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

            <div class="personnel-card-grid">
                @foreach ($personnel as $member)
                    <div class="personnel-card-item">
                        <div class="card-header">
                            <div>
                                <strong>{{ $member->first_name }} {{ $member->last_name }}</strong>
                                <div style="font-size:0.85rem;color:var(--muted);">{{ $member->personnel_code }}</div>
                            </div>
                            <span>#{{ $member->id }}</span>
                        </div>
                        <div class="card-meta">
                            <div>
                                <span>موبایل</span>
                                <strong>{{ $member->mobile }}</strong>
                            </div>
                            <div>
                                <span>کد ملی</span>
                                <strong>{{ $member->national_code }}</strong>
                            </div>
                            <div>
                                <span>سمت</span>
                                <strong>{{ $member->position?->name ?? '—' }}</strong>
                            </div>
                            <div>
                                <span>واحد</span>
                                <strong>{{ $member->unit?->name ?? '—' }}</strong>
                            </div>
                            <div>
                                <span>جنسیت</span>
                                <strong>{{ $genders[$member->gender] ?? $member->gender }}</strong>
                            </div>
                            <div>
                                <span>تاریخ تولد</span>
                                <strong>{{ jalali_date($member->birth_date) }}</strong>
                            </div>
                        </div>
                        <div class="card-actions">
                            <time>ثبت {{ jalali_date($member->created_at) }}</time>
                            <div class="card-buttons">
                                <button type="button"
                                        class="action-btn edit edit-personnel-btn"
                                        data-id="{{ $member->id }}"
                                        data-first-name="{{ e($member->first_name) }}"
                                        data-last-name="{{ e($member->last_name) }}"
                                        data-personnel-code="{{ e($member->personnel_code) }}"
                                        data-mobile="{{ e($member->mobile) }}"
                                        data-position-id="{{ $member->position_id }}"
                                        data-unit-id="{{ $member->unit_id }}"
                                        data-gender="{{ $member->gender }}"
                                        data-national-code="{{ e($member->national_code) }}"
                                        data-birth-date="{{ optional($member->birth_date)->format('Y-m-d') }}"
                                        data-action="{{ route('admin.personnel.update', $member) }}">
                                    ویرایش
                                </button>
                                <form method="POST" action="{{ route('admin.personnel.destroy', $member) }}" onsubmit="return confirm('آیا از حذف این پرسنل مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $personnel->links() }}
        @else
            <div class="empty-state">
                هیچ پرسنلی ثبت نشده است. جهت شروع بر روی «افزودن پرسنل جدید» کلیک کنید.
            </div>
        @endif
    </div>

    <div class="modal {{ $shouldOpenCreateModal ? 'open' : '' }}" id="createPersonnelModal" data-open="{{ $shouldOpenCreateModal ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>افزودن پرسنل جدید</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.personnel.store') }}">
                @csrf
                <input type="hidden" name="form" value="create">
                <div class="modal-body">
                    <div class="field">
                        <label for="create-first-name">نام</label>
                        <input id="create-first-name" type="text" name="first_name" value="{{ $shouldOpenCreateModal ? old('first_name') : '' }}" placeholder="مثلاً: علی">
                        @if ($errors->createPersonnel->has('first_name'))
                            <span class="error-text">{{ $errors->createPersonnel->first('first_name') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-last-name">نام خانوادگی</label>
                        <input id="create-last-name" type="text" name="last_name" value="{{ $shouldOpenCreateModal ? old('last_name') : '' }}" placeholder="مثلاً: احمدی">
                        @if ($errors->createPersonnel->has('last_name'))
                            <span class="error-text">{{ $errors->createPersonnel->first('last_name') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-personnel-code">کد پرسنلی</label>
                        <input id="create-personnel-code" type="text" name="personnel_code" value="{{ $shouldOpenCreateModal ? old('personnel_code') : '' }}">
                        @if ($errors->createPersonnel->has('personnel_code'))
                            <span class="error-text">{{ $errors->createPersonnel->first('personnel_code') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-national-code">کد ملی</label>
                        <input id="create-national-code" type="text" name="national_code" value="{{ $shouldOpenCreateModal ? old('national_code') : '' }}">
                        @if ($errors->createPersonnel->has('national_code'))
                            <span class="error-text">{{ $errors->createPersonnel->first('national_code') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-mobile">شماره موبایل</label>
                        <input id="create-mobile" type="text" name="mobile" value="{{ $shouldOpenCreateModal ? old('mobile') : '' }}">
                        @if ($errors->createPersonnel->has('mobile'))
                            <span class="error-text">{{ $errors->createPersonnel->first('mobile') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-birth-date">تاریخ تولد</label>
                        <div class="jalali-date-input" data-jalali-input>
                            <input id="create-birth-date-display" type="text" placeholder="مثلاً 1400/01/12" data-jdp data-jalali-display value="{{ $shouldOpenCreateModal ? jalali_date(old('birth_date')) : '' }}">
                            <input id="create-birth-date" type="hidden" name="birth_date" data-jalali-hidden value="{{ $shouldOpenCreateModal ? old('birth_date') : '' }}">
                        </div>
                        @if ($errors->createPersonnel->has('birth_date'))
                            <span class="error-text">{{ $errors->createPersonnel->first('birth_date') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-position">سمت</label>
                        <select id="create-position" name="position_id">
                            <option value="">انتخاب سمت</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" {{ ($shouldOpenCreateModal && (string) old('position_id') === (string) $position->id) ? 'selected' : '' }}>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->createPersonnel->has('position_id'))
                            <span class="error-text">{{ $errors->createPersonnel->first('position_id') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-unit">واحد</label>
                        <select id="create-unit" name="unit_id">
                            <option value="">انتخاب واحد</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" {{ ($shouldOpenCreateModal && (string) old('unit_id') === (string) $unit->id) ? 'selected' : '' }}>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->createPersonnel->has('unit_id'))
                            <span class="error-text">{{ $errors->createPersonnel->first('unit_id') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="create-gender">جنسیت</label>
                        <select id="create-gender" name="gender">
                            <option value="">انتخاب جنسیت</option>
                            @foreach ($genders as $key => $label)
                                <option value="{{ $key }}" {{ ($shouldOpenCreateModal && old('gender') === $key) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($errors->createPersonnel->has('gender'))
                            <span class="error-text">{{ $errors->createPersonnel->first('gender') }}</span>
                        @endif
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ذخیره پرسنل</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal {{ $shouldOpenEditModal ? 'open' : '' }}" id="editPersonnelModal" data-open="{{ $shouldOpenEditModal ? 'true' : 'false' }}" data-old-edit-id="{{ $shouldOpenEditModal ? $editingPersonnelId : '' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>ویرایش اطلاعات پرسنل</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="#">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="update">
                <input type="hidden" name="personnel_id" value="{{ $editingPersonnelId }}">
                <div class="modal-body">
                    <div class="field">
                        <label for="edit-first-name">نام</label>
                        <input id="edit-first-name" type="text" name="first_name" value="{{ $shouldOpenEditModal ? old('first_name') : '' }}">
                        @if ($errors->updatePersonnel->has('first_name'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('first_name') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-last-name">نام خانوادگی</label>
                        <input id="edit-last-name" type="text" name="last_name" value="{{ $shouldOpenEditModal ? old('last_name') : '' }}">
                        @if ($errors->updatePersonnel->has('last_name'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('last_name') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-personnel-code">کد پرسنلی</label>
                        <input id="edit-personnel-code" type="text" name="personnel_code" value="{{ $shouldOpenEditModal ? old('personnel_code') : '' }}">
                        @if ($errors->updatePersonnel->has('personnel_code'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('personnel_code') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-national-code">کد ملی</label>
                        <input id="edit-national-code" type="text" name="national_code" value="{{ $shouldOpenEditModal ? old('national_code') : '' }}">
                        @if ($errors->updatePersonnel->has('national_code'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('national_code') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-mobile">شماره موبایل</label>
                        <input id="edit-mobile" type="text" name="mobile" value="{{ $shouldOpenEditModal ? old('mobile') : '' }}">
                        @if ($errors->updatePersonnel->has('mobile'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('mobile') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-birth-date">تاریخ تولد</label>
                        <div class="jalali-date-input" data-jalali-input>
                            <input id="edit-birth-date-display" type="text" placeholder="مثلاً 1400/01/12" data-jdp data-jalali-display value="{{ $shouldOpenEditModal ? jalali_date(old('birth_date')) : '' }}">
                            <input id="edit-birth-date" type="hidden" name="birth_date" data-jalali-hidden value="{{ $shouldOpenEditModal ? old('birth_date') : '' }}">
                        </div>
                        @if ($errors->updatePersonnel->has('birth_date'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('birth_date') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-position">سمت</label>
                        <select id="edit-position" name="position_id">
                            <option value="">انتخاب سمت</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" {{ ($shouldOpenEditModal && (string) old('position_id') === (string) $position->id) ? 'selected' : '' }}>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->updatePersonnel->has('position_id'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('position_id') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-unit">واحد</label>
                        <select id="edit-unit" name="unit_id">
                            <option value="">انتخاب واحد</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" {{ ($shouldOpenEditModal && (string) old('unit_id') === (string) $unit->id) ? 'selected' : '' }}>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->updatePersonnel->has('unit_id'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('unit_id') }}</span>
                        @endif
                    </div>
                    <div class="field">
                        <label for="edit-gender">جنسیت</label>
                        <select id="edit-gender" name="gender">
                            <option value="">انتخاب جنسیت</option>
                            @foreach ($genders as $key => $label)
                                <option value="{{ $key }}" {{ ($shouldOpenEditModal && old('gender') === $key) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($errors->updatePersonnel->has('gender'))
                            <span class="error-text">{{ $errors->updatePersonnel->first('gender') }}</span>
                        @endif
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت تغییرات</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
    <script>
        const gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        const jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        const persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

        const pad2 = (num) => String(num).padStart(2, '0');
        const normalizeDigits = (value) => {
            if (!value) return '';
            return value.replace(/[۰-۹]/g, (d) => String(persianDigits.indexOf(d)));
        };

        const isLeapGregorian = (year) => {
            return ((year % 4 === 0) && (year % 100 !== 0)) || (year % 400 === 0);
        };

        const gregorianToJalali = (gy, gm, gd) => {
            gy = parseInt(gy, 10);
            gm = parseInt(gm, 10);
            gd = parseInt(gd, 10);
            let jy;

            if (gy > 1600) {
                jy = 979;
                gy -= 1600;
            } else {
                jy = 0;
                gy -= 621;
            }

            const gy2 = gm > 2 ? gy + 1 : gy;
            let days = (365 * gy)
                + Math.floor((gy2 + 3) / 4)
                - Math.floor((gy2 + 99) / 100)
                + Math.floor((gy2 + 399) / 400)
                - 80
                + gd;

            for (let i = 0; i < gm - 1; i++) {
                days += gDaysInMonth[i];
            }

            if (gm > 2 && isLeapGregorian(gy2)) {
                days++;
            }

            jy += 33 * Math.floor(days / 12053);
            days %= 12053;

            jy += 4 * Math.floor(days / 1461);
            days %= 1461;

            if (days > 365) {
                jy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }

            let jm = 0;
            for (; jm < 11 && days >= jDaysInMonth[jm]; jm++) {
                days -= jDaysInMonth[jm];
            }

            const jd = days + 1;

            return [jy, jm + 1, jd];
        };

        const jalaliToGregorian = (jy, jm, jd) => {
            jy = parseInt(jy, 10);
            jm = parseInt(jm, 10);
            jd = parseInt(jd, 10);

            jy -= 979;
            let gy = 1600;
            let days = (365 * jy)
                + Math.floor(jy / 33) * 8
                + Math.floor(((jy % 33) + 3) / 4)
                + jd - 1;

            for (let i = 0; i < jm - 1; i++) {
                days += jDaysInMonth[i];
            }

            gy += 400 * Math.floor(days / 146097);
            days %= 146097;

            if (days > 36524) {
                gy += 100 * Math.floor(--days / 36524);
                days %= 36524;
                if (days >= 365) {
                    days++;
                }
            }

            gy += 4 * Math.floor(days / 1461);
            days %= 1461;

            if (days > 365) {
                gy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }

            let gm = 0;
            while (gm < 12) {
                const leapAdd = (gm === 1 && isLeapGregorian(gy)) ? 1 : 0;
                const monthLength = gDaysInMonth[gm] + leapAdd;
                if (days < monthLength) {
                    break;
                }
                days -= monthLength;
                gm++;
            }

            const gd = days + 1;

            return [gy, gm + 1, gd];
        };

        const initJalaliInputs = () => {
            if (!window.jalaliDatepicker) {
                console.warn('jalaliDatepicker library not loaded.');
                return;
            }
            window.jalaliDatepicker.startWatch({ time: false });
            document.querySelectorAll('[data-jalali-input]').forEach((wrapper) => {
                const displayInput = wrapper.querySelector('[data-jalali-display]');
                const hiddenInput = wrapper.querySelector('[data-jalali-hidden]');
                if (!displayInput || !hiddenInput) {
                    return;
                }

                const updateDisplay = () => {
                    if (!hiddenInput.value) {
                        displayInput.value = '';
                        return;
                    }
                    const parts = hiddenInput.value.split('-').map((part) => parseInt(part, 10));
                    if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) {
                        displayInput.value = '';
                        return;
                    }
                    const [jy, jm, jd] = gregorianToJalali(parts[0], parts[1], parts[2]);
                    displayInput.value = `${jy}/${pad2(jm)}/${pad2(jd)}`;
                };

                const updateHidden = () => {
                    const normalized = normalizeDigits(displayInput.value.trim());
                    const matches = normalized.match(/^(\d{3,4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
                    if (!matches) {
                        hiddenInput.value = '';
                        return;
                    }
                    const jy = parseInt(matches[1], 10);
                    const jm = parseInt(matches[2], 10);
                    const jd = parseInt(matches[3], 10);
                    if (!jy || jm < 1 || jm > 12 || jd < 1 || jd > 31) {
                        hiddenInput.value = '';
                        return;
                    }
                    const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd);
                    hiddenInput.value = `${gy}-${pad2(gm)}-${pad2(gd)}`;
                };

                displayInput.addEventListener('change', updateHidden);
                displayInput.addEventListener('blur', updateHidden);
                hiddenInput.addEventListener('refreshJalaliDisplay', updateDisplay);

                updateDisplay();
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            initJalaliInputs();
            const body = document.body;
            const createModal = document.getElementById('createPersonnelModal');
            const editModal = document.getElementById('editPersonnelModal');
            const bulkModal = document.getElementById('bulkImportModal');
            const openCreateBtn = document.getElementById('openCreatePersonnelModal');
            const openBulkBtn = document.getElementById('openBulkImportModal');

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

            openCreateBtn?.addEventListener('click', () => {
                openModal(createModal);
                createModal?.querySelector('input[name="first_name"]')?.focus();
            });
            openBulkBtn?.addEventListener('click', () => openModal(bulkModal));

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
            const editFields = {
                first_name: document.getElementById('edit-first-name'),
                last_name: document.getElementById('edit-last-name'),
                personnel_code: document.getElementById('edit-personnel-code'),
                national_code: document.getElementById('edit-national-code'),
                mobile: document.getElementById('edit-mobile'),
                birth_date: document.getElementById('edit-birth-date'),
                position_id: document.getElementById('edit-position'),
                unit_id: document.getElementById('edit-unit'),
                gender: document.getElementById('edit-gender'),
                personnel_id: editModal?.querySelector('input[name="personnel_id"]'),
            };

            const fillEditForm = (data) => {
                if (!editForm || !editModal) return;
                editForm.action = data.action || '#';
                if (editFields.personnel_id) {
                    editFields.personnel_id.value = data.id || '';
                }
                if (editFields.first_name) editFields.first_name.value = data.firstName || '';
                if (editFields.last_name) editFields.last_name.value = data.lastName || '';
                if (editFields.personnel_code) editFields.personnel_code.value = data.personnelCode || '';
                if (editFields.national_code) editFields.national_code.value = data.nationalCode || '';
                if (editFields.mobile) editFields.mobile.value = data.mobile || '';
                if (editFields.birth_date) {
                    editFields.birth_date.value = data.birthDate || '';
                    editFields.birth_date.dispatchEvent(new Event('refreshJalaliDisplay'));
                }
                if (editFields.position_id) editFields.position_id.value = data.positionId || '';
                if (editFields.unit_id) editFields.unit_id.value = data.unitId || '';
                if (editFields.gender) editFields.gender.value = data.gender || '';
            };

            document.querySelectorAll('.edit-personnel-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    fillEditForm({
                        id: btn.dataset.id,
                        action: btn.dataset.action,
                        firstName: btn.dataset.firstName,
                        lastName: btn.dataset.lastName,
                        personnelCode: btn.dataset.personnelCode,
                        nationalCode: btn.dataset.nationalCode,
                        mobile: btn.dataset.mobile,
                        birthDate: btn.dataset.birthDate,
                        positionId: btn.dataset.positionId,
                        unitId: btn.dataset.unitId,
                        gender: btn.dataset.gender,
                    });
                    openModal(editModal);
                    requestAnimationFrame(() => editFields.first_name?.focus());
                });
            });

            if (createModal?.dataset.open === 'true') {
                openModal(createModal);
                createModal?.querySelector('input[name="first_name"]')?.focus();
            }

            if (editModal?.dataset.open === 'true') {
                const targetId = editModal.dataset.oldEditId;
                if (targetId) {
                    const trigger = document.querySelector(`.edit-personnel-btn[data-id="${targetId}"]`);
                    if (trigger) {
                        trigger.click();
                    } else {
                        openModal(editModal);
                    }
                } else {
                    openModal(editModal);
                }
            }

            if (bulkModal?.dataset.open === 'true') {
                openModal(bulkModal);
            }
        });
    </script>
@endsection

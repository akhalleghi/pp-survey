@extends('admin.layouts.app')

@section('page-title', 'تعریف پرسنل')
@section('page-description', 'مدیریت و تعریف اطلاعات پرسنل سازمان همراه با مشاهده سریع سوابق و ویرایش جزئیات.')

@php
    $oldFormType = old('form');
    $editingPersonnelId = old('personnel_id');
    $shouldOpenCreateModal = $errors->createPersonnel->any() || ($oldFormType === 'create');
    $shouldOpenEditModal = $errors->updatePersonnel->any() || ($oldFormType === 'update');
@endphp

@section('content')
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
        .status-message {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #15803d;
            border-radius: 18px;
            padding: 0.9rem 1.2rem;
            font-weight: 600;
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
        .modal-body input:focus,
        .modal-body select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.15);
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
                <a href="{{ route('admin.personnel.index') }}" class="ghost">بارگذاری مجدد</a>
            </div>
        </div>

        @if (session('status'))
            <div class="status-message">
                {{ session('status') }}
            </div>
        @endif

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
                                <td>{{ optional($member->birth_date)->format('Y/m/d') }}</td>
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
                                <strong>{{ optional($member->birth_date)->format('Y/m/d') }}</strong>
                            </div>
                        </div>
                        <div class="card-actions">
                            <time>ثبت {{ $member->created_at->format('Y/m/d') }}</time>
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
                        <input id="create-birth-date" type="date" name="birth_date" value="{{ $shouldOpenCreateModal ? old('birth_date') : '' }}">
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
                        <input id="edit-birth-date" type="date" name="birth_date" value="{{ $shouldOpenEditModal ? old('birth_date') : '' }}">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            const createModal = document.getElementById('createPersonnelModal');
            const editModal = document.getElementById('editPersonnelModal');
            const openCreateBtn = document.getElementById('openCreatePersonnelModal');

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
                if (editFields.birth_date) editFields.birth_date.value = data.birthDate || '';
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
        });
    </script>
@endsection

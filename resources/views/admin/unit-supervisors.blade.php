@extends('admin.layouts.app')

@section('page-title', 'مدیریت ناظر واحدها')
@section('page-description', 'در این بخش می‌توانید برای هر واحد ناظر مشخص کنید، اطلاعات را ویرایش یا حذف نمایید و فهرست فعلی ناظرها را ببینید.')

@php
    $oldFormType = old('form');
    $editingSupervisorId = old('supervisor_id');
    $shouldOpenCreateModal = $errors->createSupervisor->any() || ($oldFormType === 'create');
    $shouldOpenEditModal = $errors->updateSupervisor->any() || ($oldFormType === 'update');
    $filters = $filters ?? ['search' => request('search'), 'unit' => request('unit')];
@endphp

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
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
            min-width: 720px;
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
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 100;
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
                            <th>تاریخ ثبت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($supervisors as $supervisor)
                            <tr>
                                <td>#{{ $supervisor->id }}</td>
                                <td>{{ trim(($supervisor->personnel->first_name ?? '') . ' ' . ($supervisor->personnel->last_name ?? '')) ?: 'نامشخص' }}</td>
                                <td>{{ $supervisor->personnel_code }}</td>
                                <td>{{ $supervisor->unit?->name ?? '—' }}</td>
                                <td>{{ jalali_date($supervisor->created_at) }}</td>
                                <td class="actions">
                                    <button type="button"
                                            class="action-btn edit edit-supervisor-btn"
                                            data-id="{{ $supervisor->id }}"
                                            data-personnel-code="{{ $supervisor->personnel_code }}"
                                            data-unit-id="{{ $supervisor->unit_id }}"
                                            data-action="{{ route('admin.unit-supervisors.update', $supervisor) }}">
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
                        <div class="card-actions">
                            <time>{{ jalali_date($supervisor->created_at) }}</time>
                            <div class="card-buttons">
                                <button type="button"
                                        class="action-btn edit edit-supervisor-btn"
                                        data-id="{{ $supervisor->id }}"
                                        data-personnel-code="{{ $supervisor->personnel_code }}"
                                        data-unit-id="{{ $supervisor->unit_id }}"
                                        data-action="{{ route('admin.unit-supervisors.update', $supervisor) }}">
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

    <div class="modal {{ $shouldOpenCreateModal ? 'open' : '' }}" id="createSupervisorModal" data-open="{{ $shouldOpenCreateModal ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>تخصیص ناظر جدید</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.unit-supervisors.store') }}">
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
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت ناظر</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal {{ $shouldOpenEditModal ? 'open' : '' }}" id="editSupervisorModal" data-open="{{ $shouldOpenEditModal ? 'true' : 'false' }}" data-old-edit-id="{{ $shouldOpenEditModal ? $editingSupervisorId : '' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>ویرایش ناظر واحد</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="#">
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
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت تغییرات</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const initSupervisorSelect2 = () => {
            if (!window.jQuery || !jQuery().select2) {
                return;
            }
            jQuery('.supervisor-select').each(function () {
                const parentModal = jQuery(this).closest('.modal');
                if (jQuery(this).data('select2')) {
                    jQuery(this).select2('destroy');
                }
                jQuery(this).select2({
                    width: '100%',
                    dir: 'rtl',
                    dropdownParent: parentModal.length ? parentModal : undefined,
                    language: {
                        noResults: () => 'نتیجه‌ای یافت نشد',
                    },
                });
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
                initSupervisorSelect2();
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

            const openEditModal = (id, personnelCode, unitId, action) => {
                if (!editModal || !editForm) return;
                if (action) {
                    editForm.action = action;
                }
                if (editIdField) {
                    editIdField.value = id || '';
                }
                if (editPersonnel) {
                    editPersonnel.value = personnelCode || '';
                }
                if (editUnit) {
                    editUnit.value = unitId || '';
                }
                openModal(editModal);
            };

            document.querySelectorAll('.edit-supervisor-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    openEditModal(btn.dataset.id, btn.dataset.personnelCode, btn.dataset.unitId, btn.dataset.action);
                });
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

            document.querySelectorAll('.modal').forEach((modal) => {
                modal.addEventListener('transitionend', () => initSupervisorSelect2());
            });
        });
    </script>
@endsection

@extends('admin.layouts.app')

@section('page-title', 'شرکت‌ها')
@section('page-description', 'شرکت‌های پیمانکار و کارفرما را مدیریت کنید.')

@section('content')
    <style>
        .companies-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        body.modal-open {
            overflow: hidden;
        }
        .companies-card {
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
        .companies-card h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .company-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .company-actions button,
        .company-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
        }
        .company-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .company-actions .ghost {
            background: rgba(15,23,42,0.05);
            color: var(--slate);
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
            width: min(520px, 100%);
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
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        .modal-body label {
            font-weight: 600;
        }
        .modal-body input,
        .modal-body select {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-family: var(--app-font-family);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }
        .modal-body input:focus,
        .modal-body select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.15);
        }
        .modal-body input + .error-text,
        .modal-body select + .error-text {
            margin-top: -0.35rem;
        }
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .modal-actions button {
            border: none;
            border-radius: 16px;
            padding: 0.85rem 1.6rem;
            font-weight: 600;
            cursor: pointer;
        }
        .modal-actions .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .modal-actions .cancel-btn {
            background: rgba(15,23,42,0.08);
            color: var(--slate);
        }
        .companies-table-wrapper {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            overflow: hidden;
        }
        table.companies-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.companies-table th,
        table.companies-table td {
            padding: 1rem 1.25rem;
            text-align: right;
        }
        table.companies-table thead {
            background: rgba(15,23,42,0.03);
            font-size: 0.95rem;
            color: var(--muted);
        }
        table.companies-table tbody tr + tr {
            border-top: 1px solid rgba(15,23,42,0.06);
        }
        .type-badge {
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            background: rgba(15,23,42,0.05);
            font-size: 0.85rem;
            color: var(--slate);
            display: inline-block;
        }
        .type-badge.contractor {
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }
        .type-badge.employer {
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }
        table.companies-table .actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-start;
            align-items: center;
        }
        .action-btn {
            border: none;
            border-radius: 12px;
            padding: 0.55rem 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .action-btn.edit {
            background: rgba(15,23,42,0.08);
            color: var(--slate);
        }
        .action-btn.delete {
            background: rgba(214, 17, 25, 0.15);
            color: var(--primary);
        }
        .companies-empty {
            background: #fff;
            border-radius: 24px;
            border: 1px dashed rgba(15,23,42,0.2);
            padding: 2rem;
            text-align: center;
            color: var(--muted);
        }
        .companies-filter {
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }
        .companies-filter form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            width: 100%;
            align-items: center;
        }
        .companies-filter input[type="text"] {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 14px;
            padding: 0.75rem 1rem;
            flex: 1 1 240px;
            font-family: var(--app-font-family);
        }
        .companies-filter .filter-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }
        .companies-filter .filter-actions button,
        .companies-filter .filter-actions a {
            border: none;
            border-radius: 12px;
            padding: 0.65rem 1.3rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .companies-filter .filter-actions .apply {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .companies-filter .filter-actions .reset {
            background: rgba(15,23,42,0.05);
            color: var(--slate);
            text-decoration: none;
        }
        .companies-card-grid {
            display: none;
            gap: 1rem;
        }
        .company-card-item {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(15,23,42,0.08);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .company-card-item span {
            color: var(--muted);
            font-size: 0.9rem;
        }
        .company-card-item strong {
            font-size: 1.1rem;
        }
        .company-card-item .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .company-card-item .card-buttons {
            display: flex;
            gap: 0.4rem;
        }
        .company-card-item time {
            font-size: 0.85rem;
            color: var(--muted);
        }
        .status-message {
            background: rgba(46, 213, 115, 0.15);
            border: 1px solid rgba(46, 213, 115, 0.4);
            color: #0d8a4d;
            padding: 0.85rem 1.1rem;
            border-radius: 16px;
            font-weight: 600;
        }
        .error-text {
            color: var(--primary);
            font-size: 0.85rem;
        }
        @media (max-width: 768px) {
            .companies-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .company-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .companies-table-wrapper {
                display: none;
            }
            .companies-card-grid {
                display: grid;
            }
        }
    </style>

    @php
        $oldFormType = old('form');
        $editingCompanyId = old('company_id');
        $shouldOpenCreateModal = $errors->createCompany->any() || ($oldFormType === 'create');
        $shouldOpenEditModal = $errors->updateCompany->any() || ($oldFormType === 'update');
        $editAction = $editingCompanyId ? route('admin.companies.update', $editingCompanyId) : '#';
        $search = request('search');
    @endphp

    <div class="companies-wrapper">
        @if (session('status'))
            <div class="status-message">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="status-message" style="background: rgba(214,17,25,0.12); color: var(--primary); border-color: rgba(214,17,25,0.3);">
                لطفاً خطاهای فرم را بررسی کنید.
            </div>
        @endif

        <div class="companies-filter">
            <form method="GET" action="{{ route('admin.companies.index') }}">
                <input type="text"
                       name="search"
                       class="filter-control"
                       placeholder="جستجو بر اساس نام شرکت"
                       value="{{ $search ?? '' }}">
                <div class="filter-actions">
                    <button type="submit" class="apply">جستجو</button>
                    @if (!empty($search))
                        <a href="{{ route('admin.companies.index') }}" class="reset">حذف فیلتر</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="companies-card">
            <div>
                <h2>شرکت‌ها</h2>
                <p style="color: var(--muted); margin: 0.35rem 0 0;">فهرست شرکت‌های پیمانکار و کارفرما را مدیریت کنید.</p>
            </div>
            <div class="company-actions">
                <button type="button" class="primary" id="openCreateModal">افزودن شرکت جدید</button>
                <a href="{{ route('admin.companies.index') }}" class="ghost">بارگذاری مجدد</a>
            </div>
        </div>

        @if ($companies->count())
            <div class="companies-table-wrapper">
                <table class="companies-table">
                    <thead>
                        <tr>
                            <th>آیدی</th>
                            <th>نام شرکت</th>
                            <th>نوع شرکت</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>#{{ $company->id }}</td>
                                <td>{{ $company->name }}</td>
                                <td>
                                    <span class="type-badge {{ $company->type }}">{{ $company->type_label }}</span>
                                </td>
                                <td>{{ jalali_date($company->created_at, 'Y/m/d H:i') }}</td>
                                <td class="actions">
                                    <button type="button"
                                            class="action-btn edit edit-company-btn"
                                            data-id="{{ $company->id }}"
                                            data-name="{{ e($company->name) }}"
                                            data-type="{{ $company->type }}"
                                            data-action="{{ route('admin.companies.update', $company) }}">
                                        ویرایش
                                    </button>
                                    <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('این شرکت حذف شود؟');">
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

            <div class="companies-card-grid">
                @foreach ($companies as $company)
                    <div class="company-card-item">
                        <div>
                            <span>نام شرکت</span>
                            <strong>{{ $company->name }}</strong>
                        </div>
                        <div>
                            <span>نوع:</span>
                            <span class="type-badge {{ $company->type }}">{{ $company->type_label }}</span>
                        </div>
                        <div>
                            <span>شناسه:</span>
                            <strong>#{{ $company->id }}</strong>
                        </div>
                        <div class="card-actions">
                            <time>{{ jalali_date($company->created_at) }}</time>
                            <div class="card-buttons">
                                <button type="button"
                                        class="action-btn edit edit-company-btn"
                                        data-id="{{ $company->id }}"
                                        data-name="{{ e($company->name) }}"
                                        data-type="{{ $company->type }}"
                                        data-action="{{ route('admin.companies.update', $company) }}">
                                    ویرایش
                                </button>
                                <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('این شرکت حذف شود؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn delete">حذف</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                {{ $companies->links() }}
            </div>
        @else
            <div class="companies-empty">
                هنوز شرکتی تعریف نشده است. با زدن دکمه «افزودن شرکت جدید» اولین شرکت را ثبت کنید.
            </div>
        @endif
    </div>

    <div class="modal {{ $shouldOpenCreateModal ? 'open' : '' }}" id="createCompanyModal" data-open="{{ $shouldOpenCreateModal ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>افزودن شرکت جدید</h3>
                <button type="button" class="modal-close" data-modal-close aria-label="بستن">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.companies.store') }}">
                @csrf
                <input type="hidden" name="form" value="create">
                <div class="modal-body">
                    <label for="create-company-name">نام شرکت</label>
                    <input id="create-company-name" type="text" name="name" value="{{ $shouldOpenCreateModal ? old('name') : '' }}" placeholder="مثلاً: شرکت نمونه" autocomplete="organization">
                    @if ($errors->createCompany->has('name'))
                        <span class="error-text">{{ $errors->createCompany->first('name') }}</span>
                    @endif

                    <label for="create-company-type">نوع شرکت</label>
                    <select id="create-company-type" name="type">
                        <option value="" disabled {{ $shouldOpenCreateModal && ! old('type') ? 'selected' : '' }}>انتخاب کنید</option>
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected($shouldOpenCreateModal && old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($errors->createCompany->has('type'))
                        <span class="error-text">{{ $errors->createCompany->first('type') }}</span>
                    @endif
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ذخیره</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal {{ $shouldOpenEditModal ? 'open' : '' }}" id="editCompanyModal" data-open="{{ $shouldOpenEditModal ? 'true' : 'false' }}" data-old-edit-id="{{ $shouldOpenEditModal ? $editingCompanyId : '' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>ویرایش شرکت</h3>
                <button type="button" class="modal-close" data-modal-close aria-label="بستن">&times;</button>
            </div>
            <form method="POST" action="{{ $editAction }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="update">
                <input type="hidden" name="company_id" value="{{ $editingCompanyId }}">
                <div class="modal-body">
                    <label for="edit-company-name">نام شرکت</label>
                    <input id="edit-company-name" type="text" name="name" value="{{ $shouldOpenEditModal ? old('name') : '' }}" placeholder="نام شرکت" autocomplete="organization">
                    @if ($errors->updateCompany->has('name'))
                        <span class="error-text">{{ $errors->updateCompany->first('name') }}</span>
                    @endif

                    <label for="edit-company-type">نوع شرکت</label>
                    <select id="edit-company-type" name="type">
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected($shouldOpenEditModal && old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($errors->updateCompany->has('type'))
                        <span class="error-text">{{ $errors->updateCompany->first('type') }}</span>
                    @endif
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ذخیره تغییرات</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            const createModal = document.getElementById('createCompanyModal');
            const editModal = document.getElementById('editCompanyModal');
            const openCreateBtn = document.getElementById('openCreateModal');

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
                createModal?.querySelector('input[name="name"]')?.focus();
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

            const editModalInput = editModal?.querySelector('#edit-company-name');
            const editModalSelect = editModal?.querySelector('#edit-company-type');
            const editModalForm = editModal?.querySelector('form');
            const editHiddenField = editModal?.querySelector('input[name="company_id"]');

            const openEditModal = (id, name, type, action) => {
                if (!editModal || !editModalForm) return;
                if (action) {
                    editModalForm.action = action;
                }
                if (editHiddenField) {
                    editHiddenField.value = id || '';
                }
                if (editModalInput) {
                    editModalInput.value = name || '';
                }
                if (editModalSelect && type) {
                    editModalSelect.value = type;
                }
                openModal(editModal);
                requestAnimationFrame(() => editModalInput?.focus());
            };

            document.querySelectorAll('.edit-company-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const { id, name, type, action } = btn.dataset;
                    openEditModal(id, name, type, action);
                });
            });

            if (createModal?.dataset.open === 'true') {
                openModal(createModal);
                createModal?.querySelector('input[name="name"]')?.focus();
            }

            if (editModal?.dataset.open === 'true') {
                const targetId = editModal.dataset.oldEditId;
                if (targetId) {
                    const trigger = document.querySelector(`.edit-company-btn[data-id="${targetId}"]`);
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

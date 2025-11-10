@extends('admin.layouts.app')

@section('page-title', 'تعریف واحدها')
@section('page-description', 'واحدهای سازمانی را مدیریت کنید و در صورت نیاز واحد جدیدی بیافزایید.')

@section('content')
    <style>
        .units-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        body.modal-open {
            overflow: hidden;
        }
        .units-card {
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
        .units-card h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .unit-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .unit-actions button,
        .unit-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
        }
        .unit-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .unit-actions .ghost {
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
        .modal-body input {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .modal-body input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.15);
        }
        .modal-body input + .error-text {
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
        .units-table-wrapper {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            overflow: hidden;
        }
        table.units-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.units-table th,
        table.units-table td {
            padding: 1rem 1.25rem;
            text-align: right;
        }
        table.units-table thead {
            background: rgba(15,23,42,0.03);
            font-size: 0.95rem;
            color: var(--muted);
        }
        table.units-table tbody tr + tr {
            border-top: 1px solid rgba(15,23,42,0.06);
        }
        .units-status {
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            background: rgba(15,23,42,0.05);
            font-size: 0.85rem;
            color: var(--muted);
        }
        .units-table .actions {
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
        .units-empty {
            background: #fff;
            border-radius: 24px;
            border: 1px dashed rgba(15,23,42,0.2);
            padding: 2rem;
            text-align: center;
            color: var(--muted);
        }
        .units-card-grid {
            display: none;
            gap: 1rem;
        }
        .unit-card-item {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(15,23,42,0.08);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .unit-card-item span {
            color: var(--muted);
            font-size: 0.9rem;
        }
        .unit-card-item strong {
            font-size: 1.1rem;
        }
        .unit-card-item .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .unit-card-item .card-buttons {
            display: flex;
            gap: 0.4rem;
        }
        .unit-card-item time {
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
            .units-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .unit-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .units-table-wrapper {
                display: none;
            }
            .units-card-grid {
                display: grid;
            }
        }
    </style>

    @php
        $oldFormType = old('form');
        $editingUnitId = old('unit_id');
        $shouldOpenCreateModal = $errors->createUnit->any() || ($oldFormType === 'create');
        $shouldOpenEditModal = $errors->updateUnit->any() || ($oldFormType === 'update');
        $editAction = $editingUnitId ? route('admin.units.update', $editingUnitId) : '#';
    @endphp

    <div class="units-wrapper">
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

        <div class="units-card">
            <div>
                <h2>واحدهای سازمانی</h2>
                <p style="color: var(--muted); margin: 0.35rem 0 0;">فهرست واحدهای فعال را در یک نگاه ببینید یا مورد جدیدی اضافه کنید.</p>
            </div>
            <div class="unit-actions">
                <button type="button" class="primary" id="openCreateModal">افزودن واحد جدید</button>
                <a href="{{ route('admin.units.index') }}" class="ghost">بارگذاری مجدد</a>
            </div>
        </div>

        @if ($units->count())
            <div class="units-table-wrapper">
                <table class="units-table">
                    <thead>
                        <tr>
                            <th>آیدی</th>
                            <th>نام واحد</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($units as $unit)
                            <tr>
                                <td>#{{ $unit->id }}</td>
                                <td>{{ $unit->name }}</td>
                                <td>{{ $unit->created_at->format('Y/m/d H:i') }}</td>
                                <td class="actions">
                                    <button type="button"
                                            class="action-btn edit edit-unit-btn"
                                            data-id="{{ $unit->id }}"
                                            data-name="{{ e($unit->name) }}"
                                            data-action="{{ route('admin.units.update', $unit) }}">
                                        ویرایش
                                    </button>
                                    <form method="POST" action="{{ route('admin.units.destroy', $unit) }}" onsubmit="return confirm('این واحد حذف شود؟');">
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

            <div class="units-card-grid">
                @foreach ($units as $unit)
                    <div class="unit-card-item">
                        <div>
                            <span>نام واحد</span>
                            <strong>{{ $unit->name }}</strong>
                        </div>
                        <div>
                            <span>شناسه:</span>
                            <strong>#{{ $unit->id }}</strong>
                        </div>
                        <div class="card-actions">
                            <time>{{ $unit->created_at->format('Y/m/d') }}</time>
                            <div class="card-buttons">
                                <button type="button"
                                        class="action-btn edit edit-unit-btn"
                                        data-id="{{ $unit->id }}"
                                        data-name="{{ e($unit->name) }}"
                                        data-action="{{ route('admin.units.update', $unit) }}">
                                    ویرایش
                                </button>
                                <form method="POST" action="{{ route('admin.units.destroy', $unit) }}" onsubmit="return confirm('این واحد حذف شود؟');">
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
                {{ $units->links() }}
            </div>
        @else
            <div class="units-empty">
                هنوز واحدی تعریف نشده است. با زدن دکمه «افزودن واحد جدید» اولین واحد را ثبت کنید.
            </div>
        @endif
    </div>

    <div class="modal {{ $shouldOpenCreateModal ? 'open' : '' }}" id="createUnitModal" data-open="{{ $shouldOpenCreateModal ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>افزودن واحد جدید</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.units.store') }}">
                @csrf
                <input type="hidden" name="form" value="create">
                <div class="modal-body">
                    <label for="create-unit-name">نام واحد</label>
                    <input id="create-unit-name" type="text" name="name" value="{{ $shouldOpenCreateModal ? old('name') : '' }}" placeholder="مثلاً: واحد روابط عمومی">
                    @if ($errors->createUnit->has('name'))
                        <span class="error-text">{{ $errors->createUnit->first('name') }}</span>
                    @endif
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت واحد</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal {{ $shouldOpenEditModal ? 'open' : '' }}" id="editUnitModal" data-open="{{ $shouldOpenEditModal ? 'true' : 'false' }}" data-old-edit-id="{{ $shouldOpenEditModal ? $editingUnitId : '' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>ویرایش واحد</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ $editAction }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="update">
                <input type="hidden" name="unit_id" value="{{ $editingUnitId }}">
                <div class="modal-body">
                    <label for="edit-unit-name">نام واحد</label>
                    <input id="edit-unit-name" type="text" name="name" value="{{ $shouldOpenEditModal ? old('name') : '' }}" placeholder="نام جدید واحد">
                    @if ($errors->updateUnit->has('name'))
                        <span class="error-text">{{ $errors->updateUnit->first('name') }}</span>
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
            const createModal = document.getElementById('createUnitModal');
            const editModal = document.getElementById('editUnitModal');
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

            const editModalInput = editModal?.querySelector('#edit-unit-name');
            const editModalForm = editModal?.querySelector('form');
            const editHiddenField = editModal?.querySelector('input[name="unit_id"]');

            const openEditModal = (id, name, action) => {
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
                openModal(editModal);
                requestAnimationFrame(() => editModalInput?.focus());
            };

            document.querySelectorAll('.edit-unit-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const { id, name, action } = btn.dataset;
                    openEditModal(id, name, action);
                });
            });

            if (createModal?.dataset.open === 'true') {
                openModal(createModal);
                createModal?.querySelector('input[name="name"]')?.focus();
            }

            if (editModal?.dataset.open === 'true') {
                const targetId = editModal.dataset.oldEditId;
                if (targetId) {
                    const trigger = document.querySelector(`.edit-unit-btn[data-id="${targetId}"]`);
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

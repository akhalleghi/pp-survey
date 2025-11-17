@extends('admin.layouts.app')

@section('page-title', 'تعریف سمت‌های سازمانی')
@section('page-description', 'سمت‌های سازمانی را مدیریت کنید و در صورت نیاز سمت جدیدی اضافه یا ویرایش کنید.')

@section('content')
    <style>
        .positions-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        body.modal-open {
            overflow: hidden;
        }
        .positions-card {
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
        .positions-card h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .position-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .position-actions button,
        .position-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.8rem 1.4rem;
            font-weight: 600;
            cursor: pointer;
        }
        .position-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .position-actions .ghost {
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
        .positions-table-wrapper {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            overflow: hidden;
        }
        table.positions-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.positions-table th,
        table.positions-table td {
            padding: 1rem 1.25rem;
            text-align: right;
        }
        table.positions-table thead {
            background: rgba(15,23,42,0.03);
            font-size: 0.95rem;
            color: var(--muted);
        }
        table.positions-table tbody tr + tr {
            border-top: 1px solid rgba(15,23,42,0.06);
        }
        .positions-status {
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            background: rgba(15,23,42,0.05);
            font-size: 0.85rem;
            color: var(--muted);
        }
        .positions-table .actions {
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
        .positions-empty {
            background: #fff;
            border-radius: 24px;
            border: 1px dashed rgba(15,23,42,0.2);
            padding: 2rem;
            text-align: center;
            color: var(--muted);
        }
        .positions-card-grid {
            display: none;
            gap: 1rem;
        }
        .position-card-item {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(15,23,42,0.08);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .position-card-item span {
            color: var(--muted);
            font-size: 0.9rem;
        }
        .position-card-item strong {
            font-size: 1.1rem;
        }
        .position-card-item .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .position-card-item .card-buttons {
            display: flex;
            gap: 0.4rem;
        }
        .position-card-item time {
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
            .positions-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .position-actions {
                width: 100%;
                justify-content: flex-start;
            }
            .positions-table-wrapper {
                display: none;
            }
            .positions-card-grid {
                display: grid;
            }
        }
    </style>

    @php
        $oldFormType = old('form');
        $editingPositionId = old('position_id');
        $shouldOpenCreateModal = $errors->createPosition->any() || ($oldFormType === 'create');
        $shouldOpenEditModal = $errors->updatePosition->any() || ($oldFormType === 'update');
        $editAction = $editingPositionId ? route('admin.positions.update', $editingPositionId) : '#';
    @endphp

    <div class="positions-wrapper">
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

        <div class="positions-card">
            <div>
                <h2>سمت‌های سازمانی</h2>
                <p style="color: var(--muted); margin: 0.35rem 0 0;">لیست سمت‌های فعال را در یک نگاه ببینید یا سمت تازه‌ای اضافه کنید.</p>
            </div>
            <div class="position-actions">
                <button type="button" class="primary" id="openCreateModal">افزودن سمت جدید</button>
                <a href="{{ route('admin.positions.index') }}" class="ghost">بارگذاری مجدد</a>
            </div>
        </div>

        @if ($positions->count())
            <div class="positions-table-wrapper">
                <table class="positions-table">
                    <thead>
                        <tr>
                            <th>آیدی</th>
                            <th>نام سمت</th>
                            <th>تاریخ ایجاد</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($positions as $position)
                            <tr>
                                <td>#{{ $position->id }}</td>
                                <td>{{ $position->name }}</td>
                                <td>{{ jalali_date($position->created_at, 'Y/m/d H:i') }}</td>
                                <td class="actions">
                                    <button type="button"
                                            class="action-btn edit edit-position-btn"
                                            data-id="{{ $position->id }}"
                                            data-name="{{ e($position->name) }}"
                                            data-action="{{ route('admin.positions.update', $position) }}">
                                        ویرایش
                                    </button>
                                    <form method="POST" action="{{ route('admin.positions.destroy', $position) }}" onsubmit="return confirm('این سمت حذف شود؟');">
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

            <div class="positions-card-grid">
                @foreach ($positions as $position)
                    <div class="position-card-item">
                        <div>
                            <span>نام سمت</span>
                            <strong>{{ $position->name }}</strong>
                        </div>
                        <div>
                            <span>شناسه:</span>
                            <strong>#{{ $position->id }}</strong>
                        </div>
                        <div class="card-actions">
                            <time>{{ jalali_date($position->created_at) }}</time>
                            <div class="card-buttons">
                                <button type="button"
                                        class="action-btn edit edit-position-btn"
                                        data-id="{{ $position->id }}"
                                        data-name="{{ e($position->name) }}"
                                        data-action="{{ route('admin.positions.update', $position) }}">
                                    ویرایش
                                </button>
                                <form method="POST" action="{{ route('admin.positions.destroy', $position) }}" onsubmit="return confirm('این سمت حذف شود؟');">
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
                {{ $positions->links() }}
            </div>
        @else
            <div class="positions-empty">
                هنوز واحدی تعریف نشده است. با زدن دکمه «افزودن سمت جدید» اولین واحد را ثبت کنید.
            </div>
        @endif
    </div>

    <div class="modal {{ $shouldOpenCreateModal ? 'open' : '' }}" id="createPositionModal" data-open="{{ $shouldOpenCreateModal ? 'true' : 'false' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>افزودن سمت جدید</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.positions.store') }}">
                @csrf
                <input type="hidden" name="form" value="create">
                <div class="modal-body">
                    <label for="create-position-name">نام سمت</label>
                    <input id="create-position-name" type="text" name="name" value="{{ $shouldOpenCreateModal ? old('name') : '' }}" placeholder="مثلاً: واحد روابط عمومی">
                    @if ($errors->createPosition->has('name'))
                        <span class="error-text">{{ $errors->createPosition->first('name') }}</span>
                    @endif
                </div>
                <div class="modal-actions">
                    <button type="submit" class="submit-btn">ثبت سمت</button>
                    <button type="button" class="cancel-btn" data-modal-close>انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal {{ $shouldOpenEditModal ? 'open' : '' }}" id="editPositionModal" data-open="{{ $shouldOpenEditModal ? 'true' : 'false' }}" data-old-edit-id="{{ $shouldOpenEditModal ? $editingPositionId : '' }}">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>ویرایش سمت</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form method="POST" action="{{ $editAction }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="form" value="update">
                <input type="hidden" name="position_id" value="{{ $editingPositionId }}">
                <div class="modal-body">
                    <label for="edit-position-name">نام سمت</label>
                    <input id="edit-position-name" type="text" name="name" value="{{ $shouldOpenEditModal ? old('name') : '' }}" placeholder="نام جدید واحد">
                    @if ($errors->updatePosition->has('name'))
                        <span class="error-text">{{ $errors->updatePosition->first('name') }}</span>
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
            const createModal = document.getElementById('createPositionModal');
            const editModal = document.getElementById('editPositionModal');
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

            const editModalInput = editModal?.querySelector('#edit-position-name');
            const editModalForm = editModal?.querySelector('form');
            const editHiddenField = editModal?.querySelector('input[name="position_id"]');

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

            document.querySelectorAll('.edit-position-btn').forEach((btn) => {
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
                    const trigger = document.querySelector(`.edit-position-btn[data-id="${targetId}"]`);
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

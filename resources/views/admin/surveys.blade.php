@extends('admin.layouts.app')

@section('page-title', 'مدیریت نظرسنجی‌ها')
@section('page-description', 'ساخت نظرسنجی جدید، پایش وضعیت و اعمال تنظیمات محدودیت پاسخ و دسترسی کاربران.')

@php
    $audiencePresets = $audiencePresets ?? ['همه کاربران', 'براساس واحد', 'براساس جنسیت', 'براساس سمت', 'براساس مدرک تحصیلی', 'انتخابی توسط ادمین'];
    $metrics = $metrics ?? ['active' => 0, 'responses' => 0, 'avg_questions' => 0, 'closed' => 0];
    $statusLabels = ['active' => 'فعال', 'draft' => 'در حال آماده سازی', 'closed' => 'بسته شده'];
    $statusFilters = ['' => 'همه', 'active' => 'فعال', 'draft' => 'در حال آماده سازی', 'closed' => 'بسته شده'];
    $units = $units ?? collect();
@endphp

@section('content')
    <style>
        :root {
            --surface: #fff;
            --border: rgba(15, 23, 42, 0.08);
        }
        .surveys-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .surveys-hero {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: clamp(1.2rem, 4vw, 2.2rem);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1.25rem;
            align-items: center;
        }
        .surveys-hero h2 {
            margin: 0;
            font-size: clamp(1.2rem, 3vw, 1.85rem);
        }
        .surveys-hero p {
            margin: 0.5rem 0 0;
            color: var(--muted);
            line-height: 1.8;
        }
        .hero-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .hero-actions button {
            border: none;
            border-radius: 18px;
            padding: 0.9rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
        }
        .hero-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .hero-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .survey-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
        }
        .survey-stat-card {
            background: var(--surface);
            border-radius: 22px;
            border: 1px solid var(--border);
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            position: relative;
            overflow: hidden;
        }
        .survey-stat-card::after {
            content: '';
            position: absolute;
            inset: auto auto -40% -30%;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(214, 17, 25, 0.15), transparent 65%);
        }
        .survey-stat-card span {
            color: var(--muted);
            font-size: 0.85rem;
        }
        .survey-stat-card strong {
            font-size: clamp(1.4rem, 2vw, 1.9rem);
        }
        .survey-table-card {
            background: var(--surface);
            border-radius: 28px;
            border: 1px solid var(--border);
            padding: clamp(1rem, 3vw, 1.8rem);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .table-head {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }
        .survey-search {
            flex: 1;
            min-width: 220px;
            position: relative;
        }
        .survey-search input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            padding-right: 2.6rem;
            font-family: inherit;
        }
        .survey-search svg {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--muted);
        }
        .survey-filters {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .filter-chip {
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.4rem 0.85rem;
            font-size: 0.85rem;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.02);
        }
        .filter-chip.active {
            background: rgba(214, 17, 25, 0.12);
            border-color: rgba(214, 17, 25, 0.5);
            color: var(--primary);
        }
        .surveys-table {
            width: 100%;
            border-collapse: collapse;
        }
        .surveys-table thead {
            background: rgba(15, 23, 42, 0.03);
            color: var(--muted);
        }
        .surveys-table th,
        .surveys-table td {
            padding: 1rem 0.75rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .survey-name {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .survey-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }
        .survey-tag {
            padding: 0.2rem 0.7rem;
            background: rgba(15, 23, 42, 0.06);
            border-radius: 999px;
            font-size: 0.75rem;
            color: var(--muted);
        }
        .survey-tag.muted {
            opacity: 0.7;
        }
        .survey-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .survey-status.active {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
        }
        .survey-status.draft {
            background: rgba(234, 179, 8, 0.22);
            color: #a16207;
        }
        .survey-status.closed {
            background: rgba(15, 23, 42, 0.12);
            color: var(--muted);
        }
        .survey-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
        .survey-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(15, 23, 42, 0.04);
            border-radius: 12px;
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
            color: var(--muted);
        }
        .survey-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .survey-actions button,
        .survey-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.55rem 1rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .survey-actions .outline {
            background: rgba(15, 23, 42, 0.07);
            color: var(--slate);
        }
        .survey-actions .ghost {
            background: transparent;
            color: var(--muted);
        }
        .mobile-card {
            display: none;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 110;
        }
        .modal.open {
            display: flex;
        }
        .modal-dialog {
            width: min(640px, 100%);
            background: var(--surface);
            border-radius: 30px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 40px 80px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
        }
        .modal-close {
            border: none;
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .form-field input,
        .form-field select,
        .form-field textarea {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 0.85rem 1rem;
            font-family: inherit;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.8rem;
        }
        .settings-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 0.9rem;
            background: rgba(15, 23, 42, 0.02);
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 600;
        }
        .audience-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }
        .audience-chip {
            background: rgba(214, 17, 25, 0.15);
            color: var(--primary);
            border-radius: 999px;
            padding: 0.3rem 0.85rem;
            font-size: 0.8rem;
        }
        .modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }
        .modal-actions button {
            border: none;
            border-radius: 18px;
            padding: 0.9rem 1.6rem;
            font-weight: 600;
            cursor: pointer;
        }
        .modal-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .modal-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .error-text {
            color: #dc2626;
            font-size: 0.85rem;
        }
        @media (max-width: 960px) {
            .surveys-hero {
                grid-template-columns: 1fr;
            }
            .hero-actions {
                justify-content: flex-start;
            }
        }
        @media (max-width: 768px) {
            .table-head {
                flex-direction: column;
                align-items: stretch;
            }
            .surveys-table thead {
                display: none;
            }
            .surveys-table,
            .surveys-table tbody,
            .surveys-table tr,
            .surveys-table td {
                display: block;
                width: 100%;
            }
            .surveys-table tr {
                margin-bottom: 1rem;
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 18px;
                overflow: hidden;
            }
            .surveys-table td {
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }
            .surveys-table td:last-child {
                border-bottom: none;
            }
        }
        @media (max-width: 520px) {
            .surveys-hero,
            .survey-table-card,
            .modal-dialog {
                border-radius: 18px;
                padding: 1rem;
            }
            .survey-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .survey-actions button,
            .survey-actions a {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="surveys-wrapper">
        <section class="surveys-hero">
            <div>
                <h2>کنترل پنل نظرسنجی‌ها</h2>
                <p>از همین صفحه می‌توانید نظرسنجی جدید بسازید، وضعیت انتشار را مدیریت کنید و محدودیت پاسخ یا دسترسی مخاطبان
                    را روی هر نظرسنجی اعمال کنید.</p>
            </div>
            <div class="hero-actions">
                <button type="button" class="primary" id="openAddSurvey">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m7-7H5" />
                    </svg>
                    افزودن نظرسنجی
                </button>
                <button type="button" class="outline">راهنمای ساخت</button>
            </div>
        </section>

        <section class="survey-stats">
            <div class="survey-stat-card">
                <span>نظرسنجی‌های فعال</span>
                <strong>{{ number_format($metrics['active']) }}</strong>
                <small>در حال دریافت پاسخ</small>
            </div>
            <div class="survey-stat-card">
                <span>پاسخ‌های ثبت‌شده</span>
                <strong>{{ number_format($metrics['responses']) }}</strong>
                <small>مجموع همه نظرسنجی‌ها</small>
            </div>
            <div class="survey-stat-card">
                <span>میانگین تعداد سوال</span>
                <strong>{{ number_format($metrics['avg_questions'], 1) }}</strong>
                <small>به‌ازای هر نظرسنجی</small>
            </div>
            <div class="survey-stat-card">
                <span>نظرسنجی‌های بسته شده</span>
                <strong>{{ number_format($metrics['closed']) }}</strong>
                <small>آماده آرشیو یا خروجی</small>
            </div>
        </section>

        <section class="survey-table-card">
            <div class="table-head">
                <form class="survey-search" method="GET" action="{{ route('admin.surveys.index') }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="جستجوی نام یا واحد">
                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                            d="M21 21l-4.35-4.35M10 18a8 8 0 110-16 8 8 0 010 16z" />
                    </svg>
                </form>
                <div class="survey-filters">
                    @foreach ($statusFilters as $key => $label)
                        @php
                            $query = array_filter([
                                'search' => $search,
                                'status' => $key ?: null,
                            ], fn($value) => filled($value));
                            $isActiveFilter = ($key === '' && !$statusFilter) || ($statusFilter === $key);
                        @endphp
                        <a href="{{ route('admin.surveys.index', $query) }}"
                            class="filter-chip {{ $isActiveFilter ? 'active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="survey-table-wrapper">
                <table class="surveys-table">
                    <thead>
                        <tr>
                            <th>نام نظرسنجی</th>
                            <th>واحد مربوطه</th>
                            <th>تعداد سوالات</th>
                            <th>زمان ایجاد</th>
                            <th>تعداد پاسخ</th>
                            <th>وضعیت</th>
                            <th>اقدامات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($surveys as $survey)
                            @php
                                $tagList = $survey->tags ?? [];
                                $unitLabel = $survey->unit?->name ?? 'Unknown Unit';
                                $audienceFilters = $survey->audience_filters ?? [];
                            @endphp
                            <tr>
                                <td>
                                    <div class="survey-name">
                                        <strong>{{ $survey->title }}</strong>
                                        <div class="survey-tags">
                                            @forelse ($tagList as $tag)
                                                <span class="survey-tag">{{ $tag }}</span>
                                            @empty
                                                <span class="survey-tag muted">بدون برچسب</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $unitLabel }}</td>
                                <td>{{ $survey->questions_count }}</td>
                                <td>{{ $survey->created_at ? jalali_date($survey->created_at, 'Y/m/d H:i') : '-' }}</td>
                                <td>{{ number_format($survey->responses_count) }}</td>
                                <td>
                                    <span class="survey-status {{ $survey->status }}">
                                        {{ $statusLabels[$survey->status] ?? 'نامشخص' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="survey-actions">
                                        <button type="button" class="outline">مشاهده گزارش</button>
                                        <a href="{{ route('admin.surveys.edit', $survey) }}" class="outline">تنظیمات</a>
                                        <a href="{{ route('admin.surveys.questions.index', $survey) }}" class="outline">طراحی سوالات</a>
                                        <form method="POST" action="{{ route('admin.surveys.generate-link', $survey) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="outline">ایجاد لینک</button>
                                        </form>
                                        @if ($survey->public_token)
                                            <span class="survey-link">
                                                لینک:
                                                <a href="{{ route('surveys.public.show', $survey->public_token) }}" target="_blank">باز کردن</a>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:2rem 1rem; color:var(--muted);">
                                    هنوز نظرسنجی‌ای ثبت نشده است.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($surveys, 'links'))
                <div class="table-pagination">
                    {{ $surveys->links() }}
                </div>
            @endif
        </section>
    </div>

    {{-- Add Survey Modal --}}
    <div class="modal" id="addSurveyModal" aria-hidden="true">
        <form method="POST" action="{{ route('admin.surveys.store') }}" class="modal-dialog" id="addSurveyForm">
            @csrf
            <div class="modal-header">
                <h3>افزودن نظرسنجی جدید</h3>
                <button class="modal-close" type="button" data-close-modal>&times;</button>
            </div>
            <p>نام نظرسنجی را درج کنید و در صورت تمایل واحد مرتبط و یادداشت کوتاه را اضافه کنید.</p>
            <div class="form-field">
                <label for="surveyNameInput">نام نظرسنجی</label>
                <input type="text" id="surveyNameInput" name="title" value="{{ old('title') }}"
                    placeholder="مثلاً رضایت از خدمات سازمان">
                @error('title', 'createSurvey')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-field">
                <label for="surveyUnitSelect">واحد مربوطه</label>
                <select id="surveyUnitSelect" name="unit_id">
                    <option value="">انتخاب واحد</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit_id', 'createSurvey')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-field">
                <label for="surveyNotes">یادداشت کوتاه (اختیاری)</label>
                <textarea id="surveyNotes" rows="3" name="description" placeholder="هدف نظرسنجی یا نکات مهم ...">{{ old('description') }}</textarea>
                @error('description', 'createSurvey')
                    <small class="error-text">{{ $message }}</small>
                @enderror
            </div>
            <div class="modal-actions">
                <button class="primary" type="submit">ثبت و ادامه</button>
                <button class="ghost" type="button" data-close-modal>انصراف</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            const addSurveyModal = document.getElementById('addSurveyModal');
            const openAddSurvey = document.getElementById('openAddSurvey');

            const setBodyModalState = () => {
                const hasOpenModal = Array.from(document.querySelectorAll('.modal')).some((modal) =>
                    modal.classList.contains('open')
                );
                body.classList.toggle('modal-open', hasOpenModal);
            };

            const toggleModal = (modal, show) => {
                if (!modal) return;
                modal.classList.toggle('open', Boolean(show));
                setBodyModalState();
            };

            if (openAddSurvey) {
                openAddSurvey.addEventListener('click', () => {
                    const addSurveyForm = document.getElementById('addSurveyForm');
                    if (addSurveyForm) {
                        addSurveyForm.reset();
                    }
                    toggleModal(addSurveyModal, true);
                });
            }

            document.addEventListener('click', (event) => {
                const closeBtn = event.target.closest('[data-close-modal]');
                if (closeBtn) {
                    const modal = closeBtn.closest('.modal');
                    toggleModal(modal, false);
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    document.querySelectorAll('.modal.open').forEach((modal) => modal.classList.remove('open'));
                    setBodyModalState();
                }
            });

            @if ($errors->createSurvey->any())
                window.addEventListener('load', () => toggleModal(addSurveyModal, true));
            @endif
        });
    </script>
@endsection
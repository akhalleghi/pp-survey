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
        .status-message {
            background: rgba(46, 213, 115, 0.15);
            border: 1px solid rgba(46, 213, 115, 0.4);
            color: #0d8a4d;
            padding: 0.85rem 1.1rem;
            border-radius: 16px;
            font-weight: 600;
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
        .survey-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-inline: -0.15rem;
            padding-bottom: 0.25rem;
        }
        .surveys-table th:last-child,
        .surveys-table td:last-child {
            width: 1%;
            min-width: 7.5rem;
            max-width: 10rem;
            vertical-align: middle;
        }
        .surveys-table tbody tr {
            position: relative;
        }
        .surveys-table tbody tr:has(.survey-actions-dropdown:hover),
        .surveys-table tbody tr:has(.survey-actions-dropdown:focus-within) {
            z-index: 4;
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
        .survey-actions-dropdown {
            position: relative;
            display: block;
            width: 100%;
        }
        /* پل نامرئی برای حفظ هاور بین دکمه و منو (منو بالای دکمه باز می‌شود) */
        .survey-actions-dropdown::before {
            content: '';
            position: absolute;
            inset-inline: 0;
            bottom: 100%;
            height: 14px;
            z-index: 18;
        }
        .survey-actions-trigger {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 14px;
            padding: 0.5rem 0.65rem;
            font-family: inherit;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--slate);
            background: rgba(15, 23, 42, 0.06);
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .survey-actions-trigger:hover,
        .survey-actions-dropdown:focus-within .survey-actions-trigger {
            background: rgba(15, 23, 42, 0.1);
            border-color: rgba(214, 17, 25, 0.35);
            box-shadow: 0 0 0 1px rgba(214, 17, 25, 0.12);
        }
        .survey-actions-chevron {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .survey-actions-dropdown:hover .survey-actions-chevron,
        .survey-actions-dropdown:focus-within .survey-actions-chevron {
            transform: rotate(180deg);
        }
        .survey-actions-menu {
            position: absolute;
            inset-inline-end: 0;
            bottom: calc(100% + 6px);
            top: auto;
            min-width: 12.25rem;
            width: max-content;
            max-width: min(20rem, 88vw);
            padding: 0.35rem;
            margin: 0;
            list-style: none;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            z-index: 20;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.18s ease, visibility 0.18s ease;
        }
        .survey-actions-menu.survey-actions-menu--layered {
            position: fixed;
            inset-inline-end: auto;
            bottom: auto;
            z-index: 10060;
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.22);
        }
        .survey-actions-dropdown:hover .survey-actions-menu,
        .survey-actions-dropdown:focus-within .survey-actions-menu {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .survey-actions-form {
            display: block;
            margin: 0;
        }
        .survey-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin: 0.15rem 0.25rem 0.1rem;
            padding: 0.45rem 0.55rem;
            font-size: 0.72rem;
            color: var(--muted);
            line-height: 1.4;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
        }
        .survey-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }
        .survey-actions-menu-item {
            display: block;
            width: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }
        .survey-actions-menu-item + .survey-actions-menu-item {
            margin-top: 2px;
        }
        .survey-actions-menu button,
        .survey-actions-menu a {
            border: none;
            border-radius: 12px;
            padding: 0.55rem 0.75rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
            line-height: 1.35;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
            box-sizing: border-box;
            text-align: right;
            font-family: inherit;
            color: var(--slate);
            background: transparent;
            transition: background 0.12s ease, color 0.12s ease;
        }
        .survey-actions-menu a {
            color: var(--slate);
        }
        .survey-actions-menu button:hover,
        .survey-actions-menu a:hover {
            background: rgba(15, 23, 42, 0.06);
            color: var(--primary);
        }
        .survey-actions-menu .is-muted {
            color: var(--muted);
            font-weight: 500;
            cursor: default;
        }
        .survey-actions-menu .is-muted:hover {
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
                overflow: visible;
            }
            .surveys-table td {
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }
            .surveys-table td:last-child {
                border-bottom: none;
            }
            .surveys-table th:last-child,
            .surveys-table td:last-child {
                min-width: 0;
                max-width: none;
                width: 100%;
            }
        }
        @media (max-width: 520px) {
            .surveys-hero,
            .survey-table-card,
            .modal-dialog {
                border-radius: 18px;
                padding: 1rem;
            }
        }
    </style>

    <div class="surveys-wrapper">
        @if (session('status'))
            <div class="status-message">
                {{ session('status') }}
            </div>
        @endif

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
                                    <div class="survey-actions-dropdown">
                                        <button type="button" class="survey-actions-trigger" aria-haspopup="menu"
                                            aria-controls="survey-actions-menu-{{ $survey->id }}">
                                            اقدامات
                                            <svg class="survey-actions-chevron" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div class="survey-actions-menu" id="survey-actions-menu-{{ $survey->id }}"
                                            role="menu" aria-label="اقدامات نظرسنجی">
                                            <div class="survey-actions-menu-item" role="none">
                                                <button type="button" role="menuitem" class="is-muted" disabled>مشاهده گزارش</button>
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                <a href="{{ route('admin.surveys.edit', $survey) }}" role="menuitem">تنظیمات</a>
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                <a href="{{ route('admin.surveys.questions.index', $survey) }}" role="menuitem">طراحی سوالات</a>
                                            </div>
                                            <div class="survey-actions-menu-item" role="none">
                                                <form method="POST" action="{{ route('admin.surveys.generate-link', $survey) }}" class="survey-actions-form">
                                                    @csrf
                                                    <button type="submit" role="menuitem">ایجاد لینک</button>
                                                </form>
                                            </div>
                                            @if ($survey->public_token)
                                                <div class="survey-link" role="none">
                                                    <span>لینک عمومی</span>
                                                    <a href="{{ route('surveys.public.show', $survey->public_token) }}" target="_blank" rel="noopener noreferrer">باز کردن</a>
                                                </div>
                                            @endif
                                        </div>
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

            const surveyTableWrappers = document.querySelectorAll('.survey-table-wrapper');
            const isRtl = () => document.documentElement.getAttribute('dir') === 'rtl';

            const clearSurveyActionsMenuPosition = (dropdown) => {
                const menu = dropdown.querySelector('.survey-actions-menu');
                if (!menu) return;
                menu.classList.remove('survey-actions-menu--layered');
                menu.style.top = '';
                menu.style.left = '';
                menu.style.right = '';
                menu.style.bottom = '';
            };

            const positionSurveyActionsMenu = (dropdown) => {
                const menu = dropdown.querySelector('.survey-actions-menu');
                const trigger = dropdown.querySelector('.survey-actions-trigger');
                if (!menu || !trigger) return;

                menu.classList.add('survey-actions-menu--layered');

                const r = trigger.getBoundingClientRect();
                const vw = window.innerWidth;
                const vh = window.innerHeight;
                const pad = 10;
                const gap = 6;

                const mw = Math.max(menu.offsetWidth, 196);
                const mh = menu.offsetHeight || 120;

                let top = r.top - mh - gap;
                if (top < pad) {
                    top = Math.min(pad, r.bottom + gap);
                }
                if (top + mh > vh - pad) {
                    top = Math.max(pad, vh - pad - mh);
                }

                let left;
                if (isRtl()) {
                    left = r.left;
                    if (left + mw > vw - pad) left = vw - mw - pad;
                    if (left < pad) left = pad;
                    menu.style.left = `${Math.round(left)}px`;
                    menu.style.right = 'auto';
                } else {
                    left = r.right - mw;
                    if (left < pad) left = pad;
                    if (left + mw > vw - pad) left = vw - mw - pad;
                    menu.style.left = `${Math.round(left)}px`;
                    menu.style.right = 'auto';
                }

                menu.style.top = `${Math.round(top)}px`;
                menu.style.bottom = 'auto';
            };

            const schedulePositionSurveyMenus = () => {
                requestAnimationFrame(() => {
                    document.querySelectorAll('.survey-actions-dropdown').forEach((dropdown) => {
                        const open =
                            dropdown.matches(':hover') ||
                            dropdown.contains(document.activeElement);
                        if (open) {
                            positionSurveyActionsMenu(dropdown);
                        } else {
                            clearSurveyActionsMenuPosition(dropdown);
                        }
                    });
                });
            };

            document.querySelectorAll('.survey-actions-dropdown').forEach((dropdown) => {
                let leaveTimer = null;
                dropdown.addEventListener('mouseenter', () => {
                    if (leaveTimer) {
                        window.clearTimeout(leaveTimer);
                        leaveTimer = null;
                    }
                    requestAnimationFrame(() => positionSurveyActionsMenu(dropdown));
                });
                dropdown.addEventListener('mouseleave', () => {
                    if (leaveTimer) window.clearTimeout(leaveTimer);
                    leaveTimer = window.setTimeout(() => {
                        leaveTimer = null;
                        if (dropdown.matches(':hover')) return;
                        if (dropdown.contains(document.activeElement)) return;
                        clearSurveyActionsMenuPosition(dropdown);
                    }, 150);
                });
                dropdown.addEventListener('focusin', () => {
                    requestAnimationFrame(() => positionSurveyActionsMenu(dropdown));
                });
                dropdown.addEventListener('focusout', (event) => {
                    if (dropdown.contains(event.relatedTarget)) return;
                    clearSurveyActionsMenuPosition(dropdown);
                });
            });

            window.addEventListener('scroll', schedulePositionSurveyMenus, true);
            window.addEventListener('resize', schedulePositionSurveyMenus);
            surveyTableWrappers.forEach((wrap) => {
                wrap.addEventListener('scroll', schedulePositionSurveyMenus);
            });
        });
    </script>
@endsection
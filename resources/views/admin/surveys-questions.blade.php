@extends('admin.layouts.app')

@section('page-title', 'طراحی سوالات نظرسنجی')
@section('page-description', 'مدیریت و طراحی انواع سوالات برای این نظرسنجی.')

@section('content')
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker-default.css') }}">
    <style>
        :root {
            --panel: #fff;
            --border: rgba(15, 23, 42, 0.08);
        }
        .content:has(.designer-wrap) {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding-bottom: 1.25rem;
        }
        .designer-page-top {
            flex-shrink: 0;
            margin-bottom: 1rem;
        }
        .designer-back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 14px;
            padding: 0.55rem 1rem;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            background: rgba(15, 23, 42, 0.07);
            color: var(--slate);
            transition: background 0.15s ease;
        }
        .designer-back-link:hover {
            background: rgba(15, 23, 42, 0.11);
            color: var(--slate);
        }
        .designer-back-link i {
            font-size: 0.82rem;
            opacity: 0.85;
        }
        .designer-wrap {
            --designer-panel-h: calc(100dvh - 10.5rem);
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 1.5rem;
            align-items: stretch;
            flex: 1;
            min-height: 0;
            max-height: var(--designer-panel-h);
        }
        .designer-canvas {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 26px;
            padding: 0;
            min-height: 0;
            max-height: var(--designer-panel-h);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .designer-canvas-head {
            flex-shrink: 0;
            padding: 1.5rem 1.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .designer-canvas-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 0 1.5rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scrollbar-gutter: stable;
        }
        .designer-canvas-scroll::-webkit-scrollbar,
        .designer-side-scroll::-webkit-scrollbar {
            width: 7px;
        }
        .designer-canvas-scroll::-webkit-scrollbar-thumb,
        .designer-side-scroll::-webkit-scrollbar-thumb {
            background: rgba(15, 23, 42, 0.18);
            border-radius: 999px;
        }
        .designer-canvas-scroll::-webkit-scrollbar-track,
        .designer-side-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .survey-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .survey-header h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .badge {
            background: rgba(214, 17, 25, 0.12);
            color: var(--primary);
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            font-size: 0.8rem;
        }
        .question-card {
            border: 1px dashed rgba(15, 23, 42, 0.2);
            border-radius: 20px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .question-card header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        .question-card-title-wrap {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            min-width: 0;
            flex: 1;
        }
        .question-num {
            flex-shrink: 0;
            min-width: 2.15rem;
            height: 2.15rem;
            padding: 0 0.45rem;
            border-radius: 12px;
            background: rgba(214, 17, 25, 0.12);
            color: var(--primary);
            font-weight: 800;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .question-num--guide {
            background: rgba(15, 23, 42, 0.07);
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
        }
        .question-card-text {
            min-width: 0;
            flex: 1;
        }
        .question-card-text strong {
            display: block;
            line-height: 1.5;
            word-break: break-word;
        }
        .question-meta {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            font-size: 0.85rem;
            color: var(--muted);
        }
        .question-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .question-reorder {
            display: inline-flex;
            gap: 0.35rem;
            align-items: center;
        }
        .question-reorder form {
            margin: 0;
        }
        .question-reorder .reorder-btn {
            border: none;
            border-radius: 10px;
            width: 2rem;
            height: 2rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .question-reorder .reorder-btn:hover:not(.disabled) {
            background: rgba(15, 23, 42, 0.14);
        }
        .question-reorder .reorder-btn.disabled {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            cursor: not-allowed;
            pointer-events: none;
        }
        .question-actions button {
            border: none;
            border-radius: 12px;
            padding: 0.45rem 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .question-actions a {
            border: none;
            border-radius: 12px;
            padding: 0.45rem 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .question-actions .danger {
            background: rgba(220, 38, 38, 0.15);
            color: #b91c1c;
        }
        .question-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .question-actions .disabled {
            background: rgba(148, 163, 184, 0.25);
            color: #64748b;
            cursor: not-allowed;
            pointer-events: none;
        }
        .question-options {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            font-size: 0.9rem;
        }
        .question-options span {
            background: rgba(15, 23, 42, 0.05);
            padding: 0.35rem 0.6rem;
            border-radius: 12px;
        }
        .designer-side {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
            max-height: var(--designer-panel-h);
            overflow: hidden;
        }
        .designer-side-head {
            flex-shrink: 0;
            padding: 1.25rem 1.25rem 0.75rem;
        }
        .designer-side-scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 0 1.25rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scrollbar-gutter: stable;
        }
        .type-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .type-btn {
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 14px;
            padding: 0.6rem 0.8rem;
            text-align: center;
            cursor: pointer;
            font-size: 0.85rem;
            background: rgba(15, 23, 42, 0.03);
        }
        .type-btn.active {
            border-color: rgba(214, 17, 25, 0.5);
            background: rgba(214, 17, 25, 0.12);
            color: var(--primary);
            font-weight: 600;
        }
        .form-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            font-size: 0.9rem;
        }
        .form-field input,
        .form-field textarea,
        .form-field select {
            border: 1px solid rgba(15, 23, 42, 0.16);
            border-radius: 14px;
            padding: 0.75rem 0.9rem;
            font-family: inherit;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.6rem;
        }
        .inline-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .option-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .option-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 110px auto;
            gap: 0.5rem;
            align-items: center;
        }
        .option-row input {
            font-size: 0.86rem;
        }
        .option-row button {
            border: none;
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
            border-radius: 10px;
            padding: 0.35rem 0.6rem;
            cursor: pointer;
        }
        .option-toolbar {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            margin-bottom: 0.55rem;
        }
        .option-copy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            width: 100%;
            border: 1px dashed rgba(var(--primary-rgb), 0.42);
            border-radius: 12px;
            padding: 0.55rem 0.75rem;
            background: rgba(var(--primary-rgb), 0.06);
            color: var(--primary);
            font-size: 0.82rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, opacity 0.15s ease;
        }
        .option-copy-btn:hover:not(:disabled) {
            background: rgba(var(--primary-rgb), 0.12);
            border-color: rgba(var(--primary-rgb), 0.58);
        }
        .option-copy-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            border-color: rgba(15, 23, 42, 0.14);
            background: rgba(15, 23, 42, 0.04);
            color: var(--muted);
        }
        .option-copy-hint {
            margin: 0;
            font-size: 0.76rem;
            line-height: 1.55;
            color: var(--muted);
        }
        .option-actions-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.35rem;
        }
        .option-actions-row .type-btn {
            flex: 1 1 auto;
            min-width: 7rem;
        }
        .save-btn {
            border: none;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            cursor: pointer;
        }
        @media (max-width: 1024px) {
            .content:has(.designer-wrap) {
                overflow: visible;
            }
            .designer-wrap {
                grid-template-columns: 1fr;
                max-height: none;
            }
            .designer-canvas,
            .designer-side {
                max-height: none;
            }
            .designer-canvas-scroll {
                max-height: min(58vh, 520px);
            }
            .designer-side-scroll {
                max-height: min(72vh, 640px);
            }
            .type-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .type-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .option-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $toFaDigits = static fn (int|string $value): string => str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            (string) $value
        );
    @endphp

    <div class="designer-page-top">
        <a href="{{ route('admin.surveys.index') }}" class="designer-back-link">
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            برگشت به لیست نظرسنجی‌ها
        </a>
    </div>

    <div class="designer-wrap">
        <section class="designer-canvas" aria-label="فهرست سوالات">
            <div class="designer-canvas-head">
                @include('admin.partials.survey-publish-rejection-notice', ['survey' => $survey])

                @if (session('status'))
                    <div style="border:1px solid rgba(22,163,74,.28); background:rgba(22,163,74,.08); color:#166534; border-radius:14px; padding:.7rem .9rem; font-size:.9rem;">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="border:1px solid rgba(220,38,38,.28); background:rgba(220,38,38,.08); color:#b91c1c; border-radius:14px; padding:.7rem .9rem; font-size:.9rem;">
                        {{ session('error') }}
                    </div>
                @endif
                @if (!($canReorderQuestions ?? true))
                    <div style="border:1px solid rgba(148,163,184,.35); background:rgba(148,163,184,.12); color:#475569; border-radius:14px; padding:.7rem .9rem; font-size:.88rem;">
                        به دلیل ثبت پاسخ در این نظرسنجی، امکان تغییر ترتیب سوالات وجود ندارد.
                    </div>
                @endif
                <div class="survey-header">
                    <div>
                        <h2>طراحی سوالات: {{ $survey->title }}</h2>
                        <div class="question-meta">
                            <span>واحد: {{ $survey->unit?->name ?? 'نامشخص' }}</span>
                            <span>وضعیت: {{ $survey->status }}</span>
                        </div>
                    </div>
                    <span class="badge">{{ $toFaDigits($survey->questions->count()) }} آیتم</span>
                </div>
            </div>

            <div class="designer-canvas-scroll">
            @forelse ($survey->questions as $question)
                @php
                    $isDisplayOnly = !empty($questionTypes[$question->type]['is_display_only']);
                @endphp
                <article class="question-card">
                    <header>
                        <div class="question-card-title-wrap">
                            <span class="question-num {{ $isDisplayOnly ? 'question-num--guide' : '' }}" aria-hidden="true">{{ $toFaDigits($loop->iteration) }}</span>
                            <div class="question-card-text">
                                <strong>{{ $question->title }}</strong>
                                <div class="question-meta">
                                    <span>نوع: {{ $questionTypes[$question->type]['label'] ?? $question->type }}</span>
                                    @if ($isDisplayOnly)
                                        <span>بدون پاسخ</span>
                                    @else
                                        <span>{{ $question->is_required ? 'اجباری' : 'اختیاری' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="question-actions">
                            @php
                                $hasAnswers = $question->answers->isNotEmpty();
                            @endphp
                            @if ($canReorderQuestions ?? false)
                                <div class="question-reorder" aria-label="تغییر ترتیب سوال">
                                    @if ($loop->first)
                                        <span class="reorder-btn disabled" title="این سوال در ابتدای فهرست است" aria-hidden="true">
                                            <i class="fa-solid fa-chevron-up"></i>
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.surveys.questions.move', [$survey, $question]) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="reorder-btn" title="انتقال به بالا" aria-label="انتقال سوال به بالا">
                                                <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($loop->last)
                                        <span class="reorder-btn disabled" title="این سوال در انتهای فهرست است" aria-hidden="true">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.surveys.questions.move', [$survey, $question]) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="reorder-btn" title="انتقال به پایین" aria-label="انتقال سوال به پایین">
                                                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                            @if ($hasAnswers)
                                <a class="ghost disabled" title="به دلیل ثبت پاسخ، ویرایش این سوال غیرفعال است.">ویرایش</a>
                            @else
                                <a class="ghost" href="{{ route('admin.surveys.questions.edit', [$survey, $question]) }}">ویرایش</a>
                            @endif
                            <form method="POST" action="{{ route('admin.surveys.questions.destroy', [$survey, $question]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="danger" type="submit">حذف</button>
                            </form>
                        </div>
                    </header>
                    @if ($question->description)
                        <div class="question-meta">{{ $question->description }}</div>
                    @endif
                    @if (!empty($question->settings))
                        <div class="question-meta">
                            @php
                                $settingsBadges = [];
                                $settings = $question->settings ?? [];
                                if (!empty($settings['min_length'])) {
                                    $settingsBadges[] = 'حداقل کاراکتر: ' . $settings['min_length'];
                                }
                                if (!empty($settings['max_length'])) {
                                    $settingsBadges[] = 'حداکثر کاراکتر: ' . $settings['max_length'];
                                }
                                if (!empty($settings['placeholder'])) {
                                    $settingsBadges[] = 'راهنما: ' . $settings['placeholder'];
                                }
                                if (!empty($settings['min_value'])) {
                                    $settingsBadges[] = 'حداقل مقدار: ' . $settings['min_value'];
                                }
                                if (!empty($settings['max_value'])) {
                                    $settingsBadges[] = 'حداکثر مقدار: ' . $settings['max_value'];
                                }
                                if (!empty($settings['step'])) {
                                    $settingsBadges[] = 'گام: ' . $settings['step'];
                                }
                                if (!empty($settings['min_rating'])) {
                                    $settingsBadges[] = 'حداقل امتیاز: ' . $settings['min_rating'];
                                }
                                if (!empty($settings['max_rating'])) {
                                    $settingsBadges[] = 'حداکثر امتیاز: ' . $settings['max_rating'];
                                }
                                if (!empty($settings['rating_step'])) {
                                    $settingsBadges[] = 'گام امتیاز: ' . $settings['rating_step'];
                                }
                                if (!empty($settings['min_date'])) {
                                    $settingsBadges[] = 'حداقل تاریخ: ' . $settings['min_date'];
                                }
                                if (!empty($settings['max_date'])) {
                                    $settingsBadges[] = 'حداکثر تاریخ: ' . $settings['max_date'];
                                }
                                if (!empty($settings['min_choices'])) {
                                    $settingsBadges[] = 'حداقل انتخاب: ' . $settings['min_choices'];
                                }
                                if (!empty($settings['max_choices'])) {
                                    $settingsBadges[] = 'حداکثر انتخاب: ' . $settings['max_choices'];
                                }
                                if (!empty($settings['max_file_size_kb'])) {
                                    $settingsBadges[] = 'حداکثر حجم فایل: ' . number_format((int) $settings['max_file_size_kb']) . 'KB';
                                }
                                if (!empty($settings['allowed_extensions'])) {
                                    $settingsBadges[] = 'پسوند مجاز: ' . $settings['allowed_extensions'];
                                }
                            @endphp
                            @foreach ($settingsBadges as $badge)
                                <span>{{ $badge }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if (in_array($question->type, ['multiple_choice', 'checkboxes', 'dropdown', 'rating', 'yes_no', 'linear_scale'], true))
                        <div class="question-options">
                            @foreach ($question->options as $option)
                                <span>{{ $option->label }} <small style="color:var(--muted)">({{ $option->value }})</small></span>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="question-card" style="text-align:center;">
                    هنوز آیتمی ثبت نشده. از ستون کناری نوع سوال را انتخاب کنید و فرم را تکمیل کنید.
                </div>
            @endforelse
            </div>
        </section>

        <aside class="designer-side" aria-label="افزودن سوال جدید">
            <div class="designer-side-head">
                <strong>افزودن سوال جدید</strong>
                <p class="question-meta" style="margin:.35rem 0 0;">نوع سوال را انتخاب کنید و فرم را تکمیل کنید.</p>
            </div>
            <div class="designer-side-scroll">
            @if ($errors->any())
                <div class="question-meta" style="color: #dc2626;">
                    لطفا خطاهای فرم را بررسی کنید.
                </div>
            @endif

            <div class="type-grid" id="questionTypeGrid">
                @foreach ($questionTypes as $key => $type)
                    <button type="button" class="type-btn" data-type="{{ $key }}" data-has-options="{{ $type['has_options'] ? '1' : '0' }}" data-is-display-only="{{ !empty($type['is_display_only']) ? '1' : '0' }}">
                        {{ $type['label'] }}
                    </button>
                @endforeach
            </div>

            <form method="POST" action="{{ route('admin.surveys.questions.store', $survey) }}" class="form-card" id="questionForm">
                @csrf
                <input type="hidden" name="type" id="questionTypeInput" value="short_text">

                <div class="form-field">
                    <label for="questionTitle" id="questionTitleLabel">عنوان سوال</label>
                    <input id="questionTitle" name="title" type="text" placeholder="مثلاً میزان رضایت شما؟" required>
                    @error('title')
                        <small class="question-meta" style="color: #dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="questionDescription" id="questionDescriptionLabel">توضیح کوتاه (اختیاری)</label>
                    <textarea id="questionDescription" name="description" rows="2" placeholder="راهنمایی برای پاسخ دهنده"></textarea>
                    @error('description')
                        <small class="question-meta" style="color: #dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <p class="question-meta" id="staticTextHint" style="display:none;">این آیتم فقط برای نمایش متن راهنماست و پاسخی از شرکت‌کننده دریافت نمی‌کند.</p>

                <label class="inline-toggle" id="requiredToggleWrapper">
                    <input type="checkbox" name="is_required" value="1" id="questionRequiredInput">
                    سوال اجباری باشد
                </label>

                <div class="form-field" id="settingsWrapper">
                    <label>تنظیمات سوال</label>
                    <div class="settings-grid">
                        <div data-setting-group="text">
                            <input type="number" min="1" name="settings[min_length]" placeholder="حداقل کاراکتر">
                        </div>
                        <div data-setting-group="text">
                            <input type="number" min="1" name="settings[max_length]" placeholder="حداکثر کاراکتر">
                        </div>
                        <div data-setting-group="text">
                            <input type="text" name="settings[placeholder]" placeholder="راهنمای پاسخ">
                        </div>
                        <div data-setting-group="number">
                            <input type="number" name="settings[min_value]" placeholder="حداقل مقدار">
                        </div>
                        <div data-setting-group="number">
                            <input type="number" name="settings[max_value]" placeholder="حداکثر مقدار">
                        </div>
                        <div data-setting-group="number">
                            <input type="number" name="settings[step]" placeholder="گام (Step)">
                        </div>
                        <div data-setting-group="rating">
                            <input type="number" min="1" name="settings[min_rating]" placeholder="حداقل امتیاز">
                        </div>
                        <div data-setting-group="rating">
                            <input type="number" min="1" name="settings[max_rating]" placeholder="حداکثر امتیاز">
                        </div>
                        <div data-setting-group="rating">
                            <input type="number" min="1" name="settings[rating_step]" placeholder="گام امتیاز">
                        </div>
                        <div data-setting-group="date">
                            <input type="text" class="jalali-picker-input" name="settings[min_date]" placeholder="حداقل تاریخ (مثلاً 1403/01/01)">
                        </div>
                        <div data-setting-group="date">
                            <input type="text" class="jalali-picker-input" name="settings[max_date]" placeholder="حداکثر تاریخ (مثلاً 1403/12/29)">
                        </div>
                        <div data-setting-group="choice">
                            <input type="number" min="1" name="settings[min_choices]" placeholder="حداقل انتخاب">
                        </div>
                        <div data-setting-group="choice">
                            <input type="number" min="1" name="settings[max_choices]" placeholder="حداکثر انتخاب">
                        </div>
                        <div data-setting-group="file">
                            <input type="number" min="1" name="settings[max_file_size_kb]" placeholder="حداکثر حجم فایل (KB)">
                        </div>
                        <div data-setting-group="file">
                            <input type="text" name="settings[allowed_extensions]" placeholder="پسوندها: pdf,jpg,png,docx">
                        </div>
                    </div>
                </div>

                <div class="form-field" id="optionsWrapper" style="display:none;">
                    <label>گزینه‌ها (متن قابل نمایش + مقدار ذخیره‌سازی)</label>
                    <div class="option-toolbar">
                        <button
                            type="button"
                            class="option-copy-btn"
                            id="copyOptionsFromPreviousBtn"
                            hidden
                            disabled
                            title="کپی گزینه‌ها از آخرین سوال چندگزینه‌ای قبلی"
                        >
                            <i class="fa-solid fa-copy" aria-hidden="true"></i>
                            کپی گزینه‌ها از سوال قبلی
                        </button>
                        <p class="option-copy-hint" id="copyOptionsHint" hidden>
                            برای فعال شدن این دکمه، ابتدا یک سوال از نوع «چندگزینه‌ای» یا «چندگزینه‌ای چندانتخابی» با گزینه ثبت کنید.
                        </p>
                    </div>
                    <div class="option-list" id="optionList">
                        <div class="option-row">
                            <input type="text" name="options[0][label]" placeholder="مثلاً خیلی خوب">
                            <input type="text" name="options[0][value]" placeholder="مثلاً 1">
                            <button type="button" class="remove-option">حذف</button>
                        </div>
                        <div class="option-row">
                            <input type="text" name="options[1][label]" placeholder="مثلاً خوب">
                            <input type="text" name="options[1][value]" placeholder="مثلاً 2">
                            <button type="button" class="remove-option">حذف</button>
                        </div>
                    </div>
                    <div class="option-actions-row">
                        <button type="button" class="type-btn" id="addOptionBtn">افزودن گزینه</button>
                    </div>
                    @error('options')
                        <small class="question-meta" style="color: #dc2626;">{{ $message }}</small>
                    @enderror
                </div>

                <button type="submit" class="save-btn">ثبت سوال</button>
            </form>
            </div>
        </aside>
    </div>

    <script src="{{ asset('vendor/persian-datepicker-behzadi/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const initPersianDatepickerInputs = () => {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.persianDatepicker) {
                    return;
                }

                window.jQuery('.jalali-picker-input').each(function () {
                    window.jQuery(this).persianDatepicker({
                        formatDate: 'YYYY/0M/0D',
                        closeOnBlur: true,
                        selectedBefore: !!this.value,
                        selectedDate: this.value || null,
                    });
                });
            };

            initPersianDatepickerInputs();

            const typeButtons = document.querySelectorAll('.type-btn[data-type]');
            const typeInput = document.getElementById('questionTypeInput');
            const optionsWrapper = document.getElementById('optionsWrapper');
            const optionList = document.getElementById('optionList');
            const addOptionBtn = document.getElementById('addOptionBtn');
            const settingsWrapper = document.getElementById('settingsWrapper');
            const requiredToggleWrapper = document.getElementById('requiredToggleWrapper');
            const requiredInput = document.getElementById('questionRequiredInput');
            const titleLabel = document.getElementById('questionTitleLabel');
            const titleInput = document.getElementById('questionTitle');
            const descriptionLabel = document.getElementById('questionDescriptionLabel');
            const descriptionInput = document.getElementById('questionDescription');
            const staticTextHint = document.getElementById('staticTextHint');
            const copyOptionsBtn = document.getElementById('copyOptionsFromPreviousBtn');
            const copyOptionsHint = document.getElementById('copyOptionsHint');
            const COPYABLE_OPTION_TYPES = ['multiple_choice', 'checkboxes'];
            const previousChoiceOptionsSource = @json($previousChoiceOptionsSource);

            const settingsGroups = {
                short_text: ['text'],
                long_text: ['text'],
                static_text_short: [],
                static_text_long: [],
                multiple_choice: ['choice'],
                checkboxes: ['choice'],
                dropdown: ['choice'],
                rating: ['choice', 'rating'],
                number: ['number'],
                email: [],
                date: ['date'],
                phone: ['text'],
                url: ['text'],
                yes_no: ['choice'],
                linear_scale: ['choice', 'rating'],
                file_upload: ['file']
            };

            const updateCopyOptionsButton = (type) => {
                if (!copyOptionsBtn || !copyOptionsHint) {
                    return;
                }

                const supportsCopy = COPYABLE_OPTION_TYPES.includes(type);
                const hasSource = Array.isArray(previousChoiceOptionsSource?.options)
                    && previousChoiceOptionsSource.options.length > 0;

                copyOptionsBtn.hidden = !supportsCopy;
                copyOptionsHint.hidden = !supportsCopy || hasSource;
                copyOptionsBtn.disabled = !supportsCopy || !hasSource;

                if (supportsCopy && hasSource) {
                    copyOptionsBtn.title = `کپی گزینه‌ها از سوال «${previousChoiceOptionsSource.question_title}»`;
                } else {
                    copyOptionsBtn.title = 'کپی گزینه‌ها از آخرین سوال چندگزینه‌ای قبلی';
                }
            };

            const createOptionRow = (label = '', value = '') => {
                const row = document.createElement('div');
                row.className = 'option-row';

                const labelInput = document.createElement('input');
                labelInput.type = 'text';
                labelInput.placeholder = 'متن گزینه';
                labelInput.required = true;
                labelInput.value = label;

                const valueInput = document.createElement('input');
                valueInput.type = 'text';
                valueInput.placeholder = 'مقدار ذخیره';
                valueInput.required = true;
                valueInput.value = value;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-option';
                removeBtn.textContent = 'حذف';

                row.append(labelInput, valueInput, removeBtn);
                return row;
            };

            const rebuildOptionNames = () => {
                const rows = optionList.querySelectorAll('.option-row');
                rows.forEach((row, index) => {
                    const labelInput = row.querySelector('input[name$="[label]"]');
                    const valueInput = row.querySelector('input[name$="[value]"]');
                    if (labelInput) {
                        labelInput.name = `options[${index}][label]`;
                    }
                    if (valueInput) {
                        valueInput.name = `options[${index}][value]`;
                    }
                });
            };

            const applyCopiedOptions = () => {
                if (!Array.isArray(previousChoiceOptionsSource?.options) || !previousChoiceOptionsSource.options.length) {
                    return;
                }

                optionList.innerHTML = '';
                previousChoiceOptionsSource.options.forEach((option) => {
                    optionList.appendChild(createOptionRow(option.label ?? '', option.value ?? ''));
                });
                rebuildOptionNames();
            };

            const setActiveType = (type, hasOptions, isDisplayOnly) => {
                typeInput.value = type;
                typeButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.type === type));
                optionsWrapper.style.display = hasOptions ? 'block' : 'none';
                optionList.querySelectorAll('input').forEach((input) => {
                    input.disabled = !hasOptions;
                    input.required = hasOptions;
                });
                updateCopyOptionsButton(type);
                const activeGroups = settingsGroups[type] || [];
                if (settingsWrapper) {
                    settingsWrapper.style.display = isDisplayOnly ? 'none' : 'block';
                }
                document.querySelectorAll('[data-setting-group]').forEach((el) => {
                    el.style.display = activeGroups.includes(el.dataset.settingGroup) ? 'block' : 'none';
                    const input = el.querySelector('input');
                    if (input) {
                        input.disabled = !activeGroups.includes(el.dataset.settingGroup);
                    }
                });
                if (requiredToggleWrapper) {
                    requiredToggleWrapper.style.display = isDisplayOnly ? 'none' : 'inline-flex';
                }
                if (requiredInput && isDisplayOnly) {
                    requiredInput.checked = false;
                }
                if (staticTextHint) {
                    staticTextHint.style.display = isDisplayOnly ? 'block' : 'none';
                }
                if (titleLabel && titleInput && descriptionLabel && descriptionInput) {
                    if (type === 'static_text_short') {
                        titleLabel.textContent = 'متن نمایشی (کوتاه)';
                        titleInput.placeholder = 'مثلاً لطفاً قبل از ادامه، راهنما را بخوانید.';
                        titleInput.required = true;
                        descriptionLabel.textContent = 'زیرنویس (اختیاری)';
                        descriptionInput.placeholder = 'توضیح تکمیلی کوتاه';
                        descriptionInput.required = false;
                        descriptionInput.rows = 2;
                    } else if (type === 'static_text_long') {
                        titleLabel.textContent = 'عنوان (اختیاری)';
                        titleInput.placeholder = 'مثلاً راهنمای تکمیل فرم';
                        titleInput.required = false;
                        descriptionLabel.textContent = 'متن نمایشی (بلند)';
                        descriptionInput.placeholder = 'متن توضیحات یا راهنمای کامل را اینجا بنویسید…';
                        descriptionInput.required = true;
                        descriptionInput.rows = 6;
                    } else {
                        titleLabel.textContent = 'عنوان سوال';
                        titleInput.placeholder = 'مثلاً میزان رضایت شما؟';
                        titleInput.required = true;
                        descriptionLabel.textContent = 'توضیح کوتاه (اختیاری)';
                        descriptionInput.placeholder = 'راهنمایی برای پاسخ دهنده';
                        descriptionInput.required = false;
                        descriptionInput.rows = 2;
                    }
                }
            };

            typeButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    setActiveType(
                        btn.dataset.type,
                        btn.dataset.hasOptions === '1',
                        btn.dataset.isDisplayOnly === '1'
                    );
                });
            });

            setActiveType('short_text', false, false);

            addOptionBtn?.addEventListener('click', () => {
                optionList.appendChild(createOptionRow());
                rebuildOptionNames();
            });

            copyOptionsBtn?.addEventListener('click', () => {
                if (copyOptionsBtn.disabled) {
                    return;
                }
                applyCopiedOptions();
            });

            optionList?.addEventListener('click', (event) => {
                const button = event.target.closest('.remove-option');
                if (!button) return;
                const row = button.closest('.option-row');
                if (!row) return;
                const rows = optionList.querySelectorAll('.option-row');
                if (rows.length <= 1) return;
                row.remove();
                rebuildOptionNames();
            });
        });
    </script>
@endsection

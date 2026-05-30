@extends('admin.layouts.app')

@section('page-title', 'تنظیمات نظرسنجی')
@section('page-description', 'ویرایش تجربه مخاطب، زمان‌بندی، محدودیت‌ها و ظاهر نظرسنجی.')

@section('content')
    @php
        $audienceConfig = $audienceConfig ?? [];
        $selectedModes = old('audience_modes', $audienceConfig['modes'] ?? []);
        $selectedUnits = old('audience_unit_ids', $audienceConfig['unit_ids'] ?? []);
        $selectedPositions = old('audience_position_ids', $audienceConfig['position_ids'] ?? []);
        $selectedCompanies = old('audience_company_ids', $audienceConfig['company_ids'] ?? []);
        $selectedGenders = old('audience_genders', $audienceConfig['genders'] ?? []);
        $selectedPersonnel = old('audience_personnel_ids', $audienceConfig['personnel_ids'] ?? []);
        $selectedIdentityMode = old('access_identity_mode', $audienceConfig['identity_mode'] ?? 'none');
        $requireSmsOtp = (bool) old('require_sms_otp', $audienceConfig['require_sms_otp'] ?? false);
    @endphp
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker-default.css') }}">
    <style>
        .survey-settings {
            width: 100%;
            max-width: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .survey-settings-top {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }
        .survey-settings-top h2 {
            margin: 0 0 0.35rem;
            font-size: clamp(1.15rem, 2.5vw, 1.45rem);
        }
        .survey-settings-top .lead {
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.7;
            max-width: 36rem;
        }
        .survey-settings-nav {
            display: inline-flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .survey-settings-nav a {
            border-radius: 14px;
            padding: 0.55rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            background: rgba(15, 23, 42, 0.07);
            color: var(--slate);
        }
        .survey-settings-nav a:hover {
            background: rgba(15, 23, 42, 0.11);
        }
        .survey-settings-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .ss-card {
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: clamp(1.1rem, 2vw, 1.6rem) clamp(1rem, 2.2vw, 1.8rem);
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.04);
        }
        .ss-card-head {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            margin-bottom: 1.1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.07);
        }
        .ss-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            background: rgba(214, 17, 25, 0.1);
        }
        .ss-card-head h3 {
            margin: 0;
            font-size: 1.05rem;
        }
        .ss-card-head p {
            margin: 0.35rem 0 0;
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.65;
        }
        .ss-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
        .ss-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .ss-field > span:first-child,
        .ss-field > label > span:first-child {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--slate);
        }
        .ss-field .hint {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.55;
        }
        .ss-field input[type="text"],
        .ss-field input[type="number"],
        .ss-field select,
        .ss-field textarea {
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 14px;
            padding: 0.75rem 0.9rem;
            font-family: inherit;
            font-size: 0.92rem;
        }
        .ss-field textarea {
            min-height: 120px;
            resize: vertical;
        }
        .ss-field textarea.ss-textarea-sm {
            min-height: 72px;
        }
        .jalali-date-input input[type="text"] {
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 14px;
            padding: 0.75rem 0.9rem;
            font-size: 0.92rem;
            font-family: inherit;
            direction: rtl;
        }
        .jalali-date-input input[readonly] {
            cursor: pointer;
            background: rgba(248, 250, 252, 0.92);
        }
        .survey-date-clear {
            margin-top: 0.4rem;
            border: none;
            background: transparent;
            color: var(--primary, #d61119);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            font-family: inherit;
        }
        .survey-date-clear:hover {
            opacity: 0.88;
        }
        .ss-toggle-grid {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .ss-toggle {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0.85rem;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(15, 23, 42, 0.02);
            cursor: pointer;
        }
        .ss-toggle:has(input:disabled) {
            opacity: 0.65;
            cursor: default;
        }
        .ss-toggle input {
            width: 18px;
            height: 18px;
            margin-top: 0.15rem;
            flex-shrink: 0;
            accent-color: var(--primary, #d61119);
        }
        .ss-toggle div {
            flex: 1;
            min-width: 0;
        }
        .ss-toggle strong {
            display: block;
            font-size: 0.88rem;
            margin-bottom: 0.2rem;
        }
        .ss-toggle span.desc {
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.55;
        }
        .bg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
        }
        .bg-option {
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 16px;
            padding: 0.55rem;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            background: #fff;
            cursor: pointer;
        }
        .bg-option:has(input:checked) {
            border-color: rgba(214, 17, 25, 0.45);
            box-shadow: 0 0 0 1px rgba(214, 17, 25, 0.12);
        }
        .bg-option img {
            width: 100%;
            height: 96px;
            object-fit: cover;
            border-radius: 12px;
        }
        .bg-option .cap {
            font-size: 0.75rem;
            color: var(--muted);
            word-break: break-word;
        }
        .bg-upload {
            border: 1px dashed rgba(15, 23, 42, 0.22);
            border-radius: 16px;
            padding: 1rem;
            margin-top: 0.5rem;
        }
        .bg-preview {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }
        .bg-preview img {
            width: 140px;
            height: 90px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(15, 23, 42, 0.12);
        }
        .ss-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            justify-content: flex-end;
            margin-top: 0.25rem;
        }
        @media (min-width: 1280px) {
            .ss-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 768px) {
            .survey-settings-top {
                flex-direction: column;
            }
            .survey-settings-nav {
                width: 100%;
            }
        }
        .ss-actions button,
        .ss-actions a {
            border: none;
            border-radius: 14px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .ss-actions .primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .ss-actions .ghost {
            background: rgba(15, 23, 42, 0.08);
            color: var(--slate);
        }
        .ss-alert {
            padding: 0.75rem 1rem;
            border-radius: 14px;
            background: rgba(220, 38, 38, 0.08);
            color: #b91c1c;
            font-size: 0.88rem;
        }
        .pwt-datepicker {
            z-index: 1200;
        }
        select[multiple] {
            min-height: 160px;
        }
        .audience-mode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 0.75rem;
            margin-top: 0.35rem;
        }
        .audience-mode-option {
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 14px;
            padding: 0.7rem 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.02);
        }
        .audience-mode-option:has(input:checked) {
            border-color: rgba(214, 17, 25, 0.45);
            background: rgba(214, 17, 25, 0.06);
        }
        .audience-target {
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 16px;
            padding: 0.9rem;
            background: rgba(15, 23, 42, 0.02);
            margin-top: 0.75rem;
        }
        .audience-target.is-hidden {
            display: none;
        }
        .dual-listbox {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            gap: 0.85rem;
            align-items: stretch;
            margin-top: 0.35rem;
        }
        .dlb-panel {
            display: flex;
            flex-direction: column;
            min-width: 0;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
        }
        .dlb-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(15, 23, 42, 0.03);
        }
        .dlb-panel-head strong {
            font-size: 0.86rem;
            color: var(--slate);
        }
        .dlb-count {
            font-size: 0.74rem;
            font-weight: 700;
            color: var(--primary-dark, #b30e15);
            background: rgba(214, 17, 25, 0.1);
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            white-space: nowrap;
        }
        .dlb-search-wrap {
            position: relative;
            padding: 0.65rem 0.75rem 0.45rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .dlb-search-wrap i {
            position: absolute;
            top: 50%;
            right: 1.15rem;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.82rem;
            pointer-events: none;
        }
        .dlb-search {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 12px;
            padding: 0.58rem 2.1rem 0.58rem 0.75rem;
            font-family: inherit;
            font-size: 0.84rem;
            background: rgba(248, 250, 252, 0.85);
        }
        .dlb-search:focus {
            outline: none;
            border-color: rgba(214, 17, 25, 0.45);
            box-shadow: 0 0 0 3px rgba(214, 17, 25, 0.08);
        }
        .dlb-list {
            list-style: none;
            margin: 0;
            padding: 0.35rem;
            max-height: 280px;
            overflow: auto;
            min-height: 200px;
        }
        .dlb-item {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            padding: 0.55rem 0.65rem;
            border-radius: 12px;
            cursor: pointer;
            user-select: none;
            border: 1px solid transparent;
            transition: background 0.12s ease, border-color 0.12s ease;
        }
        .dlb-item:hover {
            background: rgba(15, 23, 42, 0.04);
        }
        .dlb-item.is-highlighted {
            background: rgba(214, 17, 25, 0.08);
            border-color: rgba(214, 17, 25, 0.22);
        }
        .dlb-item-name {
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--slate);
            line-height: 1.45;
        }
        .dlb-item-meta {
            font-size: 0.76rem;
            color: var(--muted);
            line-height: 1.4;
        }
        .dlb-empty {
            padding: 1.5rem 0.85rem;
            text-align: center;
            color: var(--muted);
            font-size: 0.82rem;
        }
        .dlb-controls {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.25rem 0;
        }
        .dlb-btn {
            width: 42px;
            height: 38px;
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 12px;
            background: #fff;
            color: var(--slate);
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background 0.12s ease, border-color 0.12s ease, transform 0.08s ease;
        }
        .dlb-btn:hover:not(:disabled) {
            background: rgba(214, 17, 25, 0.08);
            border-color: rgba(214, 17, 25, 0.35);
            color: var(--primary-dark, #b30e15);
        }
        .dlb-btn:active:not(:disabled) {
            transform: scale(0.97);
        }
        .dlb-btn:disabled {
            opacity: 0.38;
            cursor: not-allowed;
        }
        .dlb-hint {
            margin-top: 0.55rem;
        }
        @media (max-width: 860px) {
            .dual-listbox {
                grid-template-columns: 1fr;
            }
            .dlb-controls {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        .theme-color-row .theme-color-inner {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            width: 100%;
            flex-wrap: wrap;
        }
        .theme-color-picker {
            width: 52px;
            height: 44px;
            padding: 2px;
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 12px;
            cursor: pointer;
            background: #fff;
            flex-shrink: 0;
        }
        .theme-color-text {
            flex: 1;
            min-width: 0;
        }
        .questions-display-options {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-top: 0.35rem;
        }
        .questions-display-option {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            padding: 0.72rem 0.85rem;
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, 0.1);
            background: rgba(248, 250, 252, 0.65);
            cursor: pointer;
            font-size: 0.84rem;
            line-height: 1.55;
            color: var(--slate);
            transition: border-color 0.15s ease, background 0.15s ease;
        }
        .questions-display-option:hover {
            border-color: rgba(214, 17, 25, 0.22);
            background: rgba(255, 255, 255, 0.92);
        }
        .questions-display-option:has(input:checked) {
            border-color: rgba(214, 17, 25, 0.38);
            background: rgba(214, 17, 25, 0.06);
            font-weight: 600;
        }
        .questions-display-option input {
            margin-top: 0.22rem;
            flex-shrink: 0;
            accent-color: var(--primary);
        }
        .wizard-nav-theme {
            margin-top: 1.15rem;
            padding: 1rem 1rem 1.05rem;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(248, 250, 252, 0.85);
        }
        .wizard-nav-theme > .hint:first-of-type {
            margin: 0 0 0.65rem;
            font-weight: 600;
            color: var(--slate);
            grid-column: unset;
        }
        .wizard-nav-theme-demos {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.9rem;
        }
        .wizard-nav-theme-demos .cap {
            font-size: 0.78rem;
            color: var(--muted);
        }
        .wizard-nav-theme-chip {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.2);
            flex-shrink: 0;
        }
        .wizard-nav-theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
    </style>

    <div class="survey-settings">
        <div class="survey-settings-top">
            <div>
                <h2>{{ old('title', $survey->title) }}</h2>
                <p class="lead">تغییرات این صفحه روی لینک عمومی و تجربه پاسخ‌دهنده اعمال می‌شود. پس از ویرایش، «ذخیره تنظیمات» را بزنید.</p>
            </div>
            <div class="survey-settings-nav">
                <a href="{{ route('admin.surveys.index') }}">← فهرست نظرسنجی‌ها</a>
                <a href="{{ route('admin.surveys.questions.index', $survey) }}">طراحی سوالات</a>
                <a href="#appearance">تنظیمات ظاهری</a>
            </div>
        </div>

        @include('admin.partials.survey-publish-rejection-notice', ['survey' => $survey])

        @if ($errors->updateSurvey->any())
            <div class="ss-alert">برخی فیلدها نیاز به اصلاح دارند؛ پیام‌های قرمز زیر هر بخش را ببینید.</div>
        @endif

        <form class="survey-settings-form" method="POST" action="{{ route('admin.surveys.update', $survey) }}" data-jalali-form enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">📝</div>
                    <div>
                        <h3>نام نظرسنجی</h3>
                        <p>عنوانی که در فهرست مدیریت و در تجربهٔ مخاطب (صفحهٔ عمومی) دیده می‌شود را می‌توانید اینجا ویرایش کنید.</p>
                    </div>
                </div>
                <div class="ss-field">
                    <label for="survey-title">
                        <span>نام نظرسنجی</span>
                    </label>
                    <input id="survey-title" type="text" name="title" maxlength="255" required
                           value="{{ old('title', $survey->title) }}"
                           placeholder="مثلاً نظرسنجی رضایت شغلی ۱۴۰۳">
                    @error('title', 'updateSurvey')
                        <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">👋</div>
                    <div>
                        <h3>شروع برای مخاطب (لینک عمومی)</h3>
                        <p>اگر متن آغاز را پر کنید، مخاطب ابتدا این متن را می‌خواند و با دکمه «شروع نظرسنجی» وارد سوالات می‌شود. اگر خالی بماند، مستقیم به سوالات می‌رود.</p>
                    </div>
                </div>
                <div class="ss-field">
                    <label for="intro_text">
                        <span>متن آغاز نظرسنجی</span>
                    </label>
                    <textarea id="intro_text" name="intro_text" rows="6" placeholder="مثلاً هدف نظرسنجی، نحوه پاسخ‌دهی، حدود زمان تخمینی و هر نکته‌ای که باید قبل از شروع خوانده شود...">{{ old('intro_text', $survey->intro_text) }}</textarea>
                    <span class="hint">این متن روی صفحه عمومی با قالب ساده نمایش داده می‌شود (خط جدید حفظ می‌شود).</span>
                    @error('intro_text', 'updateSurvey')
                        <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">📅</div>
                    <div>
                        <h3>زمان‌بندی و وضعیت انتشار</h3>
                        <p>وضعیت «فعال» یعنی لینک عمومی در بازه تاریخ (در صورت تعیین) قابل استفاده است. اگر برای حساب شما «تأیید مدیر قبل از انتشار» فعال باشد، اینجا گزینهٔ «فعال» نمایش داده نمی‌شود و از لیست نظرسنجی‌ها گزینهٔ ارسال برای تأیید را می‌زنید.</p>
                    </div>
                </div>
                <div class="ss-grid">
                    <label class="ss-field">
                        <span>وضعیت</span>
                        <select name="status">
                            @foreach ($statusOptions as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $survey->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                        @if (!empty($supervisorPublishRestricted))
                            <span class="hint">انتشار نهایی فقط پس از تأیید مدیر انجام می‌شود؛ از صفحهٔ لیست نظرسنجی‌ها گزینهٔ «ارسال برای تأیید مدیر» را بزنید.</span>
                        @endif
                    </label>
                    <label class="ss-field">
                        <span>شروع انتشار</span>
                        <input type="hidden"
                               id="start-at-iso"
                               name="start_at"
                               value="{{ survey_publish_iso_from_state('start_at', $survey->start_at) }}">
                        <div class="jalali-date-input" data-publish-date-wrap>
                            <input id="start-at"
                                   type="text"
                                   autocomplete="off"
                                   aria-label="انتخاب تاریخ شروع انتشار"
                                   placeholder="انتخاب از تقویم"
                                   readonly
                                   data-sync-hidden="start-at-iso"
                                   value="{{ survey_publish_date_form_value('start_at', $survey->start_at) }}"
                                   inputmode="none">
                        </div>
                        <button type="button"
                                class="survey-date-clear"
                                data-clear-display="start-at"
                                data-clear-hidden="start-at-iso">
                            پاک کردن تاریخ شروع
                        </button>
                        <span class="hint">خالی = بدون محدودیت شروع. تاریخ فقط از تقویم؛ مقدار ارسالی به سرور به‌صورت یک تاریخ میلادی یکتا ذخیره می‌شود.</span>
                        @error('start_at', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="ss-field">
                        <span>پایان انتشار</span>
                        <input type="hidden"
                               id="end-at-iso"
                               name="end_at"
                               value="{{ survey_publish_iso_from_state('end_at', $survey->end_at) }}">
                        <div class="jalali-date-input" data-publish-date-wrap>
                            <input id="end-at"
                                   type="text"
                                   autocomplete="off"
                                   aria-label="انتخاب تاریخ پایان انتشار"
                                   placeholder="انتخاب از تقویم"
                                   readonly
                                   data-sync-hidden="end-at-iso"
                                   value="{{ survey_publish_date_form_value('end_at', $survey->end_at) }}"
                                   inputmode="none">
                        </div>
                        <button type="button"
                                class="survey-date-clear"
                                data-clear-display="end-at"
                                data-clear-hidden="end-at-iso">
                            پاک کردن تاریخ پایان
                        </button>
                        <span class="hint">خالی = بدون تاریخ پایان. برای حذف کامل، «پاک کردن تاریخ پایان» را بزنید و ذخیره کنید.</span>
                        @error('end_at', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">⏱️</div>
                    <div>
                        <h3>محدودیت پاسخ</h3>
                        <p>سقف تعداد پاسخ و بازه ویرایش اختیاری هستند؛ خالی بگذارید یعنی بدون سقف یا بدون محدودیت ویرایش جداگانه.</p>
                    </div>
                </div>
                <div class="ss-grid">
                    <label class="ss-field">
                        <span>بازه زمانی پاسخ (ساعت)</span>
                        <input type="number" name="response_window_hours" min="1" max="720"
                               value="{{ old('response_window_hours', $survey->response_window_hours) }}">
                        <span class="hint">حداکثر زمان پیشنهادی برای تکمیل یک بار پاسخ‌دهی (۱ تا ۷۲۰ ساعت).</span>
                        @error('response_window_hours', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="ss-field">
                        <span>سقف تعداد پاسخ</span>
                        <input type="number" name="response_limit" min="1"
                               value="{{ old('response_limit', $survey->response_limit) }}"
                               placeholder="نامحدود">
                        <span class="hint">خالی = بدون سقف.</span>
                        @error('response_limit', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="ss-field">
                        <span>بازه ویرایش پاسخ (ساعت)</span>
                        <input type="number" name="response_edit_window_hours" min="1" max="720"
                               value="{{ old('response_edit_window_hours', $survey->response_edit_window_hours) }}"
                               placeholder="مثلاً ۲۴">
                        <span class="hint">پس از ارسال، تا چند ساعت امکان ویرایش باشد (در صورت فعال بودن گزینه زیر).</span>
                        @error('response_edit_window_hours', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">🔒</div>
                    <div>
                        <h3>حریم خصوصی و کنترل تکرار</h3>
                        <p>این گزینه‌ها رفتار سیستم را مشخص می‌کنند؛ بخشی از آن‌ها نیاز به ماژول‌های دیگر (ورود کاربر، ثبت پاسخ در پایگاه) دارند.</p>
                    </div>
                </div>
                <div class="ss-toggle-grid">
                    <label class="ss-toggle">
                        <input type="hidden" name="require_auth" value="0">
                        <input type="checkbox" name="require_auth" value="1" @checked(old('require_auth', $survey->require_auth))>
                        <div>
                            <strong>فقط کاربران واردشده</strong>
                            <span class="desc">در صورت فعال بودن، بازدیدکننده بدون ورود به حساب نمی‌تواند به فرم عمومی دسترسی بگیرد (نیازمند سیستم ورود کاربر عادی).</span>
                        </div>
                    </label>
                    <label class="ss-toggle">
                        <input type="hidden" name="is_anonymous" value="0">
                        <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous', $survey->is_anonymous))>
                        <div>
                            <strong>پاسخ‌های ناشناس</strong>
                            <span class="desc">برای ذخیره بدون شناسه شخصی (زمانی که ثبت پاسخ در سرور پیاده شود).</span>
                        </div>
                    </label>
                    <label class="ss-toggle">
                        <input type="hidden" name="track_location" value="0">
                        <input type="checkbox" name="track_location" value="1" @checked(old('track_location', $survey->track_location))>
                        <div>
                            <strong>ثبت موقعیت جغرافیایی</strong>
                            <span class="desc">در صورت پیاده‌سازی سمت مرورگر، موقعیت تقریبی با رضایت کاربر ذخیره می‌شود.</span>
                        </div>
                    </label>
                    <label class="ss-toggle">
                        <input type="hidden" name="prevent_multiple_submissions" value="0">
                        <input type="checkbox" name="prevent_multiple_submissions" value="1" @checked(old('prevent_multiple_submissions', $survey->prevent_multiple_submissions))>
                        <div>
                            <strong>جلوگیری از ارسال چندباره</strong>
                            <span class="desc">برای نظرسنجی‌های یک‌بارمصرف؛ نیازمند منطق ثبت پاسخ در بک‌اند.</span>
                        </div>
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">✨</div>
                    <div>
                        <h3>تجربه پاسخ‌دهی</h3>
                        <p>ترتیب سوالات و گزینه‌ها در صورت فعال بودن، هنگام باز شدن لینک عمومی به‌صورت تصادفی اعمال می‌شود.</p>
                    </div>
                </div>
                <div class="ss-toggle-grid">
                    <label class="ss-toggle">
                        <input type="hidden" name="allow_edit" value="0">
                        <input type="checkbox" name="allow_edit" value="1" @checked(old('allow_edit', $survey->allow_edit))>
                        <div>
                            <strong>اجازه ویرایش پاسخ</strong>
                            <span class="desc">در بازه ویرایش (در صورت تعیین)، امکان اصلاح پاسخ وجود داشته باشد.</span>
                        </div>
                    </label>
                    <label class="ss-toggle">
                        <input type="hidden" name="allow_partial" value="0">
                        <input type="checkbox" name="allow_partial" value="1" @checked(old('allow_partial', $survey->allow_partial))>
                        <div>
                            <strong>ذخیره پاسخ ناقص</strong>
                            <span class="desc">اجازه ذخیره میان‌باره قبل از ارسال نهایی.</span>
                        </div>
                    </label>
                    <label class="ss-toggle">
                        <input type="hidden" name="shuffle_questions" value="0">
                        <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $survey->shuffle_questions))>
                        <div>
                            <strong>ترتیب تصادفی سوالات</strong>
                            <span class="desc">در لینک عمومی، هر بار ترتیب سوالات متفاوت دیده می‌شود.</span>
                        </div>
                    </label>
                    <label class="ss-toggle">
                        <input type="hidden" name="shuffle_options" value="0">
                        <input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $survey->shuffle_options))>
                        <div>
                            <strong>ترتیب تصادفی گزینه‌ها</strong>
                            <span class="desc">برای سوالات چندگزینه‌ای، ترتیب گزینه‌ها تصادفی می‌شود.</span>
                        </div>
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">📊</div>
                    <div>
                        <h3>نمایش نتایج</h3>
                        <p>تنظیمات نمایش نتایج پس از ارسال؛ بخش نمایش به کاربر نهایی در فرانت در آینده کامل می‌شود.</p>
                    </div>
                </div>
                <div class="ss-grid">
                    <label class="ss-toggle" style="grid-column: 1 / -1;">
                        <input type="hidden" name="show_results_after_submit" value="0">
                        <input type="checkbox" name="show_results_after_submit" value="1" @checked(old('show_results_after_submit', $survey->show_results_after_submit))>
                        <div>
                            <strong>نمایش نتایج بعد از ارسال</strong>
                            <span class="desc">در صورت پیاده‌سازی صفحه خلاصه، نتایج تجمیعی به پاسخ‌دهنده نشان داده شود.</span>
                        </div>
                    </label>
                    <label class="ss-field">
                        <span>دسترسی به نتایج</span>
                        <select name="result_visibility">
                            @foreach ($resultVisibilityOptions as $key => $label)
                                <option value="{{ $key }}" @selected(old('result_visibility', $survey->result_visibility) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('result_visibility', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">💬</div>
                    <div>
                        <h3>پیام پایان و اعلان</h3>
                        <p>پیام تشکر بعد از اتمام مراحل در صفحه عمومی نمایش داده می‌شود. ایمیل‌ها برای اعلان‌های آینده ذخیره می‌شوند.</p>
                    </div>
                </div>
                <div class="ss-grid">
                    <label class="ss-field" style="grid-column: 1 / -1;">
                        <span>پیام تشکر بعد از ثبت</span>
                        <textarea class="ss-textarea-sm" name="thank_you_message" rows="3" maxlength="255" placeholder="مثلاً: از وقتی که گذاشتید متشکریم.">{{ old('thank_you_message', $survey->thank_you_message) }}</textarea>
                        <span class="hint">حداکثر ۲۵۵ کاراکتر (مطابق محدودیت پایگاه داده).</span>
                        @error('thank_you_message', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="ss-field" style="grid-column: 1 / -1;">
                        <span>ایمیل‌های اعلان</span>
                        <input type="text" name="notification_emails"
                               value="{{ old('notification_emails', implode(', ', $survey->notification_emails ?? [])) }}"
                               placeholder="example@domain.com, ops@domain.com">
                        <span class="hint">چند ایمیل را با ویرگول انگلیسی جدا کنید.</span>
                        @error('notification_emails', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                </div>
            </section>

            <section class="ss-card">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">👥</div>
                    <div>
                        <h3>فیلتر مخاطب و احراز هویت پرسنلی</h3>
                        <p>مدیر می‌تواند مشخص کند کاربر با کد پرسنلی یا کد ملی وارد شود و فقط گروه‌های مجاز امکان شرکت داشته باشند.</p>
                    </div>
                </div>
                <div class="ss-grid">
                    <label class="ss-field">
                        <span>روش احراز هویت در لینک عمومی</span>
                        <select name="access_identity_mode" id="accessIdentityMode">
                            @foreach ($identityModeOptions as $key => $label)
                                <option value="{{ $key }}" @selected($selectedIdentityMode === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="hint">اگر فیلتر مخاطب فعال باشد، این گزینه نباید روی «بدون احراز هویت» بماند.</span>
                        @error('access_identity_mode', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </label>
                    <label class="ss-toggle" id="requireSmsOtpWrap">
                        <input type="hidden" name="require_sms_otp" value="0">
                        <input type="checkbox" name="require_sms_otp" value="1" id="requireSmsOtp" @checked($requireSmsOtp)>
                        <div>
                            <strong>نیاز به احراز هویت پیامکی</strong>
                            <span class="desc">پس از تایید کد پرسنلی/ملی، کد تایید به موبایل ثبت‌شده ارسال می‌شود. پنل پیامک باید فعال باشد.</span>
                        </div>
                    </label>
                    @error('require_sms_otp', 'updateSurvey')
                        <span class="hint" style="color: #dc2626; display: block; margin-top: 0.35rem;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="ss-field" style="margin-top: 0.65rem;">
                    <span>گروه‌های هدف</span>
                    <div class="audience-mode-grid" id="audienceModesWrap">
                        @foreach ($audiencePresets as $modeKey => $modeLabel)
                            <label class="audience-mode-option">
                                <input type="checkbox" name="audience_modes[]" value="{{ $modeKey }}"
                                    @checked(in_array($modeKey, $selectedModes, true))
                                    data-audience-toggle="{{ $modeKey }}">
                                <span>{{ $modeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('audience_modes', 'updateSurvey')
                        <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="audience-target" data-audience-target="unit">
                    <label class="ss-field">
                        <span>واحدهای مجاز</span>
                        <select name="audience_unit_ids[]" multiple size="6">
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(in_array((string) $unit->id, array_map('strval', $selectedUnits), true))>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">فقط پرسنل واحدهای انتخاب‌شده مجاز خواهند بود.</span>
                    </label>
                </div>
                <div class="audience-target" data-audience-target="gender">
                    <label class="ss-field">
                        <span>جنسیت‌های مجاز</span>
                        <select name="audience_genders[]" multiple size="4">
                            @foreach ($genderOptions as $genderKey => $genderLabel)
                                <option value="{{ $genderKey }}" @selected(in_array($genderKey, $selectedGenders, true))>
                                    {{ $genderLabel }}
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">برای «هر دو» زن و مرد، هر دو مورد را انتخاب کنید.</span>
                    </label>
                </div>
                <div class="audience-target" data-audience-target="position">
                    <label class="ss-field">
                        <span>سمت‌های مجاز</span>
                        <select name="audience_position_ids[]" multiple size="6">
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" @selected(in_array((string) $position->id, array_map('strval', $selectedPositions), true))>
                                    {{ $position->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="audience-target" data-audience-target="company">
                    <label class="ss-field">
                        <span>شرکت‌های مجاز</span>
                        <select name="audience_company_ids[]" multiple size="6">
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(in_array((string) $company->id, array_map('strval', $selectedCompanies), true))>
                                    {{ $company->name }} ({{ $company->type_label }})
                                </option>
                            @endforeach
                        </select>
                        <span class="hint">فقط پرسنل شرکت‌های انتخاب‌شده مجاز خواهند بود.</span>
                    </label>
                </div>
                <div class="audience-target" data-audience-target="personnel">
                    @php
                        $personnelPickerItems = collect($personnelOptions)->map(function ($person) {
                            return [
                                'id' => (int) $person->id,
                                'name' => trim($person->first_name . ' ' . $person->last_name),
                                'personnel_code' => (string) ($person->personnel_code ?? ''),
                                'national_code' => (string) ($person->national_code ?? ''),
                            ];
                        })->values()->all();
                        $selectedPersonnelIds = array_values(array_map('intval', (array) $selectedPersonnel));
                    @endphp
                    <div class="ss-field">
                        <span>افراد مجاز (انتخابی)</span>
                        <div
                            id="surveyPersonnelDualListbox"
                            class="dual-listbox"
                            dir="rtl"
                            data-personnel='@json($personnelPickerItems)'
                            data-selected='@json($selectedPersonnelIds)'
                        >
                            <div class="dlb-panel dlb-panel-available">
                                <div class="dlb-panel-head">
                                    <strong>لیست تمام پرسنل</strong>
                                    <span class="dlb-count" id="dlbAvailableCount">۰</span>
                                </div>
                                <div class="dlb-search-wrap">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <input type="search" class="dlb-search" id="dlbAvailableSearch" placeholder="جستجو: نام، کد پرسنلی، کد ملی…" autocomplete="off">
                                </div>
                                <ul class="dlb-list" id="dlbAvailableList" role="listbox" aria-label="لیست تمام پرسنل"></ul>
                            </div>

                            <div class="dlb-controls" aria-label="انتقال پرسنل">
                                <button type="button" class="dlb-btn" id="dlbMoveAllToSelected" title="انتقال همه به لیست انتخابی" aria-label="انتقال همه به لیست انتخابی">
                                    <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="dlb-btn" id="dlbMoveToSelected" title="انتقال موارد انتخاب‌شده به لیست انتخابی" aria-label="انتقال موارد انتخاب‌شده به لیست انتخابی">
                                    <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="dlb-btn" id="dlbMoveToAvailable" title="بازگرداندن موارد انتخاب‌شده" aria-label="بازگرداندن موارد انتخاب‌شده">
                                    <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="dlb-btn" id="dlbMoveAllToAvailable" title="بازگرداندن همه" aria-label="بازگرداندن همه">
                                    <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="dlb-panel dlb-panel-selected">
                                <div class="dlb-panel-head">
                                    <strong>لیست کاربران انتخابی</strong>
                                    <span class="dlb-count" id="dlbSelectedCount">۰</span>
                                </div>
                                <div class="dlb-search-wrap">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <input type="search" class="dlb-search" id="dlbSelectedSearch" placeholder="جستجو در انتخاب‌شده‌ها…" autocomplete="off">
                                </div>
                                <ul class="dlb-list" id="dlbSelectedList" role="listbox" aria-label="لیست کاربران انتخابی"></ul>
                            </div>
                        </div>
                        <div id="dlbHiddenInputs"></div>
                        <span class="hint dlb-hint">برای انتقال سریع، روی نام پرسنل دوبار کلیک کنید. از دکمه‌های وسط برای انتقال تکی یا گروهی استفاده کنید.</span>
                        @error('audience_personnel_ids', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                        @error('audience_personnel_ids.*', 'updateSurvey')
                            <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="ss-card" id="appearance">
                <div class="ss-card-head">
                    <div class="ss-card-icon" aria-hidden="true">🎨</div>
                    <div>
                        <h3>ظاهر صفحه عمومی</h3>
                        <p>پس‌زمینهٔ تصویری و رنگ‌های کارت سوالات در لینک عمومی؛ همچنین نحوهٔ نمایش سوالات (مرحله‌ای یا یک‌صفحه‌ای). برای پس‌زمینه، پیش‌تنظیم یا آپلود را انتخاب کنید؛ رنگ دکمه‌های قبلی/بعدی ویزارد و سایر رنگ‌های فرم را در همین کارت تنظیم کنید.</p>
                    </div>
                </div>
                <div class="ss-field">
                    <span>پس‌زمینه</span>
                    <div class="bg-grid">
                        <label class="bg-option">
                            <span class="cap">بدون تصویر</span>
                            <input type="radio" name="background_preset" value="none"
                                @checked(old('background_preset') === 'none' || (empty(old('background_preset')) && empty($survey->background_image))))>
                        </label>
                        @foreach ($backgroundImages ?? [] as $image)
                            <label class="bg-option">
                                <img src="{{ asset('bg-images/' . $image) }}" alt="">
                                <span class="cap">{{ $image }}</span>
                                <input type="radio" name="background_preset" value="{{ $image }}"
                                    @checked(old('background_preset', str_starts_with((string) $survey->background_image, 'bg-images/') && ! str_contains((string) $survey->background_image, '/custom/') ? basename((string) $survey->background_image) : '') === $image)>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="bg-upload ss-field">
                    <span>آپلود تصویر اختصاصی</span>
                    <input type="file" name="background_upload" accept="image/*">
                    <span class="hint">حداکثر حدود ۵ مگابایت؛ JPG، PNG یا WEBP.</span>
                    @error('background_upload', 'updateSurvey')
                        <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>
                @if (!empty($survey->background_image))
                    <div class="bg-preview">
                        <span class="hint">پیش‌نمایش فعلی:</span>
                        <img src="{{ asset($survey->background_image) }}" alt="">
                    </div>
                @endif

                @php
                    $appearanceTheme = $publicThemeForForm ?? \App\Models\Survey::defaultPublicTheme();
                    $hexForColorPicker = static function (?string $css): string {
                        $css = trim((string) $css);
                        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $css, $m)) {
                            return '#' . strtolower($m[1]);
                        }
                        if (preg_match('/^#([0-9A-Fa-f]{3})$/', $css, $m)) {
                            $h = $m[1];

                            return '#' . strtolower($h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2]);
                        }
                        if (preg_match('/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/', $css, $m)) {
                            $r = (int) round(min(255, max(0, (float) $m[1])));
                            $g = (int) round(min(255, max(0, (float) $m[2])));
                            $b = (int) round(min(255, max(0, (float) $m[3])));

                            return sprintf('#%02x%02x%02x', $r, $g, $b);
                        }

                        return '#000000';
                    };
                    $appearanceNavLabels = [
                        'nav_prev' => 'رنگ دکمهٔ قبلی (ویزارد)',
                        'nav_next' => 'رنگ دکمهٔ بعدی (ویزارد)',
                    ];
                    $appearanceLabels = [
                        'card_bg' => 'پس‌زمینهٔ کارت سوالات',
                        'card_border' => 'حاشیهٔ کارت سوالات',
                        'title' => 'رنگ عنوان سوال',
                        'body' => 'رنگ متن اصلی سوال',
                        'muted' => 'رنگ متن کم‌رنگ',
                        'required_star' => 'رنگ ستارهٔ اجباری',
                        'input_bg' => 'پس‌زمینهٔ ورودی',
                        'input_border' => 'حاشیهٔ ورودی',
                        'input_text' => 'متن داخل ورودی',
                        'input_placeholder' => 'رنگ placeholder',
                        'option_hover' => 'پس‌زمینهٔ هاور گزینه‌ها',
                        'error_color' => 'رنگ پیام خطا',
                        'rating_wrap_bg' => 'پس‌زمینهٔ بلوک امتیاز',
                        'rating_wrap_border' => 'حاشیهٔ بلوک امتیاز',
                        'footer_percent' => 'متن درصد پیشرفت (پایین)',
                        'track_bg' => 'نوار پیشرفت (زمینه)',
                        'fill' => 'نوار پیشرفت (پرشده)',
                    ];
                @endphp
                <div class="ss-field" style="margin-top: 1rem;">
                    <span>نحوهٔ نمایش سوالات به کاربر</span>
                    <p class="hint">در حالت تک‌به‌تک، کاربر با دکمه‌های قبلی و بعدی بین سوالات جابه‌جا می‌شود و نوار پیشرفت در پایین دیده می‌شود. در حالت یک‌صفحه‌ای، همهٔ سوالات زیر هم نشان داده می‌شوند و فقط یک دکمه برای ثبت نهایی وجود دارد.</p>
                    <div class="questions-display-options" role="radiogroup" aria-label="نحوهٔ نمایش سوالات">
                        @foreach (\App\Models\Survey::questionsDisplayModeOptions() as $modeValue => $modeLabel)
                            <label class="questions-display-option">
                                <input type="radio"
                                    name="public_theme[questions_display_mode]"
                                    value="{{ $modeValue }}"
                                    @checked(\App\Models\Survey::normalizeQuestionsDisplayMode(old('public_theme.questions_display_mode', $appearanceTheme['questions_display_mode'] ?? null)) === $modeValue)>
                                <span>{{ $modeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('public_theme.questions_display_mode', 'updateSurvey')
                        <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="wizard-nav-theme">
                    <p class="hint">در حالت <strong>تک‌به‌تک</strong>، رنگ دکمه‌های قبلی و بعدی در پایین کارت سوال (لینک عمومی) اعمال می‌شود. دکمهٔ «پایان» روی آخرین سوال با رنگ ثابت قرمز است. در حالت یک‌صفحه‌ای این دکمه‌ها نمایش داده نمی‌شوند.</p>
                    <div class="wizard-nav-theme-demos" aria-hidden="true">
                        <span class="cap">پیش‌نمایش</span>
                        <span class="wizard-nav-theme-chip" title="قبلی" style="background: {{ $appearanceTheme['nav_prev'] ?? '' }}"></span>
                        <span class="wizard-nav-theme-chip" title="بعدی" style="background: {{ $appearanceTheme['nav_next'] ?? '' }}"></span>
                    </div>
                    <div class="wizard-nav-theme-grid">
                        @foreach ($appearanceNavLabels as $key => $label)
                            <div class="ss-field theme-color-row">
                                <span>{{ $label }}</span>
                                <div class="theme-color-inner">
                                    <input type="color"
                                        class="theme-color-picker"
                                        data-theme-pair="{{ $key }}"
                                        value="{{ $hexForColorPicker($appearanceTheme[$key] ?? '') }}"
                                        title="انتخاب رنگ"
                                        aria-label="انتخاب رنگ {{ $label }}">
                                    <input type="text"
                                        class="theme-color-text"
                                        name="public_theme[{{ $key }}]"
                                        data-theme-pair="{{ $key }}"
                                        value="{{ $appearanceTheme[$key] ?? '' }}"
                                        dir="ltr"
                                        autocomplete="off"
                                        maxlength="80"
                                        placeholder="#rrggbb یا rgba(...)">
                                </div>
                                @error('public_theme.' . $key, 'updateSurvey')
                                    <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="ss-grid" style="margin-top:1.15rem;padding-top:1.1rem;border-top:1px solid rgba(15,23,42,0.08);">
                    <p class="hint" style="grid-column:1/-1;margin:0 0 0.35rem;font-weight:600;color:var(--slate);">سایر رنگ‌های فرم عمومی (کارت، ورودی، نوار پیشرفت)</p>
                    @foreach ($appearanceLabels as $key => $label)
                        <div class="ss-field theme-color-row">
                            <span>{{ $label }}</span>
                            <div class="theme-color-inner">
                                <input type="color"
                                    class="theme-color-picker"
                                    data-theme-pair="{{ $key }}"
                                    value="{{ $hexForColorPicker($appearanceTheme[$key] ?? '') }}"
                                    title="انتخاب رنگ"
                                    aria-label="انتخاب رنگ {{ $label }}">
                                <input type="text"
                                    class="theme-color-text"
                                    name="public_theme[{{ $key }}]"
                                    data-theme-pair="{{ $key }}"
                                    value="{{ $appearanceTheme[$key] ?? '' }}"
                                    dir="ltr"
                                    autocomplete="off"
                                    maxlength="80"
                                    placeholder="#rrggbb یا rgba(...)">
                            </div>
                            @error('public_theme.' . $key, 'updateSurvey')
                                <span class="hint" style="color: #dc2626;">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="ss-actions">
                <button type="submit" class="primary">ذخیره تنظیمات</button>
                <a href="{{ route('admin.surveys.index') }}" class="ghost">انصراف و بازگشت</a>
            </div>
        </form>
    </div>

    <script src="{{ asset('vendor/persian-datepicker-behzadi/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            const jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
            const pad2 = (num) => String(num).padStart(2, '0');

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

                return `${jy}/${pad2(jm + 1)}/${pad2(days + 1)}`;
            };

            const syncJalaliDisplayFromHidden = (displayInput, hiddenInput) => {
                if (!displayInput || !hiddenInput) {
                    return;
                }
                if (!hiddenInput.value) {
                    displayInput.value = '';
                    return;
                }
                const parts = hiddenInput.value.split('-').map((part) => parseInt(part, 10));
                if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) {
                    displayInput.value = '';
                    return;
                }
                displayInput.value = gregorianToJalali(parts[0], parts[1], parts[2]);
            };

            const publishDatePairs = [
                { display: document.getElementById('start-at'), hidden: document.getElementById('start-at-iso') },
                { display: document.getElementById('end-at'), hidden: document.getElementById('end-at-iso') },
            ];

            const syncHiddenFromPicker = (displayInput, hiddenInput, { allowDisplayFallback = false } = {}) => {
                if (!displayInput || !hiddenInput) {
                    return;
                }
                const readG = () => displayInput.getAttribute('data-gdate') || displayInput.getAttribute('data-gDate') || '';
                const gDate = String(readG() || '').trim();
                const displayValue = String(displayInput.value || '').trim();
                if (!displayValue) {
                    hiddenInput.value = '';
                    return;
                }
                if (gDate) {
                    hiddenInput.value = gDate;
                    return;
                }
                if (allowDisplayFallback) {
                    hiddenInput.value = displayValue;
                }
            };

            const initSurveyPublishDatePickers = () => {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.persianDatepicker) {
                    console.error('persianDatepicker (Behzadi) در دسترس نیست.');
                    return;
                }

                publishDatePairs.forEach(({ display, hidden }) => {
                    if (!display || !hidden) {
                        return;
                    }
                    display.dataset.dateDirty = '0';
                    display.dataset.initialDisplayValue = String(display.value || '').trim();
                    try {
                        window.jQuery(display).persianDatepicker('destroy');
                    } catch (_) {
                        /* اولین بار یا در حال نبودن پلاگین */
                    }
                    syncJalaliDisplayFromHidden(display, hidden);
                    window.jQuery(display).persianDatepicker({
                        formatDate: 'YYYY/0M/0D',
                        closeOnBlur: true,
                        selectedBefore: !!String(display.value || '').trim(),
                        selectedDate: display.value || null,
                        onSelect: function () {
                            display.dataset.dateDirty = '1';
                            syncHiddenFromPicker(display, hidden, { allowDisplayFallback: true });
                        },
                    });
                });
            };

            initSurveyPublishDatePickers();

            document.querySelectorAll('.survey-date-clear[data-clear-display][data-clear-hidden]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const display = document.getElementById(btn.getAttribute('data-clear-display') || '');
                    const hidden = document.getElementById(btn.getAttribute('data-clear-hidden') || '');
                    if (!display || !hidden) {
                        return;
                    }
                    hidden.value = '';
                    display.value = '';
                    display.dataset.dateDirty = '1';
                    initSurveyPublishDatePickers();
                });
            });

            const syncPublishHiddenFromDisplay = (display, fallbackHidden) => {
                if (!display || !fallbackHidden) {
                    return;
                }
                const hidSel = display.getAttribute('data-sync-hidden');
                const hiddenEl = (hidSel && document.getElementById(hidSel)) || fallbackHidden;
                const displayValue = String(display.value || '').trim();
                const isDirty = String(display.dataset.dateDirty || '0') === '1';
                const initialDisplayValue = String(display.dataset.initialDisplayValue || '').trim();
                if (!displayValue) {
                    hiddenEl.value = '';
                    return;
                }
                // اگر کاربر تاریخ را دست نزده باشد، hidden اولیه همان مقدار پایدار ISO می‌ماند.
                if (!isDirty && displayValue === initialDisplayValue) {
                    return;
                }
                syncHiddenFromPicker(display, hiddenEl, { allowDisplayFallback: true });
            };

            const settingsForm = document.querySelector('.survey-settings-form');
            settingsForm?.addEventListener('submit', () => {
                publishDatePairs.forEach(({ display, hidden }) => {
                    syncPublishHiddenFromDisplay(display, hidden);
                });
            });

            const modeCheckboxes = Array.from(document.querySelectorAll('[data-audience-toggle]'));
            const identityMode = document.getElementById('accessIdentityMode');
            const syncAudienceTargets = () => {
                const activeModes = modeCheckboxes.filter((input) => input.checked).map((input) => input.value);
                document.querySelectorAll('[data-audience-target]').forEach((section) => {
                    const target = section.getAttribute('data-audience-target');
                    section.classList.toggle('is-hidden', !activeModes.includes(target));
                });
                if (activeModes.length === 0 && identityMode && identityMode.value !== 'none') {
                    identityMode.value = 'none';
                }
            };
            modeCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', syncAudienceTargets));
            syncAudienceTargets();

            const requireSmsOtp = document.getElementById('requireSmsOtp');
            const requireSmsOtpWrap = document.getElementById('requireSmsOtpWrap');
            const syncSmsOtpToggle = () => {
                const identityEnabled = identityMode && identityMode.value !== 'none';
                if (requireSmsOtpWrap) {
                    requireSmsOtpWrap.classList.toggle('is-disabled', !identityEnabled);
                }
                if (requireSmsOtp && !identityEnabled) {
                    requireSmsOtp.checked = false;
                    requireSmsOtp.disabled = true;
                } else if (requireSmsOtp) {
                    requireSmsOtp.disabled = false;
                }
            };
            identityMode?.addEventListener('change', syncSmsOtpToggle);
            syncSmsOtpToggle();

            (function initSurveyPersonnelDualListbox() {
                const root = document.getElementById('surveyPersonnelDualListbox');
                if (!root) {
                    return;
                }

                const parseJson = (value, fallback) => {
                    try {
                        return JSON.parse(value || '');
                    } catch (_) {
                        return fallback;
                    }
                };

                const allPersonnel = parseJson(root.dataset.personnel, []);
                const personnelById = new Map(allPersonnel.map((item) => [String(item.id), item]));
                const selectedIds = new Set(parseJson(root.dataset.selected, []).map(String));
                const availableHighlight = new Set();
                const selectedHighlight = new Set();

                const availableSearchEl = document.getElementById('dlbAvailableSearch');
                const selectedSearchEl = document.getElementById('dlbSelectedSearch');
                const availableListEl = document.getElementById('dlbAvailableList');
                const selectedListEl = document.getElementById('dlbSelectedList');
                const hiddenInputsEl = document.getElementById('dlbHiddenInputs');
                const availableCountEl = document.getElementById('dlbAvailableCount');
                const selectedCountEl = document.getElementById('dlbSelectedCount');

                const btnMoveAllToSelected = document.getElementById('dlbMoveAllToSelected');
                const btnMoveToSelected = document.getElementById('dlbMoveToSelected');
                const btnMoveToAvailable = document.getElementById('dlbMoveToAvailable');
                const btnMoveAllToAvailable = document.getElementById('dlbMoveAllToAvailable');

                const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
                const arabicDigits = '٠١٢٣٤٥٦٧٨٩';
                const toEnglishDigits = (value) => String(value || '').replace(/[۰-۹٠-٩]/g, (ch) => {
                    const p = persianDigits.indexOf(ch);
                    if (p >= 0) {
                        return String(p);
                    }
                    const a = arabicDigits.indexOf(ch);
                    return a >= 0 ? String(a) : ch;
                });

                const toPersianNumber = (value) => String(value).replace(/\d/g, (d) => persianDigits[Number(d)]);

                const normalizeSearch = (value) => toEnglishDigits(String(value || '').trim()).toLowerCase();

                const itemSearchText = (item) => normalizeSearch([
                    item.name,
                    item.personnel_code,
                    item.national_code,
                ].join(' '));

                const matchesSearch = (item, query) => {
                    if (!query) {
                        return true;
                    }
                    return itemSearchText(item).includes(query);
                };

                const getAvailableItems = () => allPersonnel.filter((item) => !selectedIds.has(String(item.id)));
                const getSelectedItems = () => Array.from(selectedIds)
                    .map((id) => personnelById.get(String(id)))
                    .filter(Boolean);

                const toggleHighlight = (set, id) => {
                    const key = String(id);
                    if (set.has(key)) {
                        set.delete(key);
                    } else {
                        set.add(key);
                    }
                };

                const renderList = (listEl, items, highlightSet, emptyLabel) => {
                    listEl.innerHTML = '';
                    if (!items.length) {
                        const empty = document.createElement('li');
                        empty.className = 'dlb-empty';
                        empty.textContent = emptyLabel;
                        listEl.appendChild(empty);
                        return;
                    }

                    items.forEach((item) => {
                        const li = document.createElement('li');
                        li.className = 'dlb-item';
                        li.dataset.id = String(item.id);
                        li.setAttribute('role', 'option');
                        li.setAttribute('aria-selected', highlightSet.has(String(item.id)) ? 'true' : 'false');
                        if (highlightSet.has(String(item.id))) {
                            li.classList.add('is-highlighted');
                        }

                        const name = document.createElement('span');
                        name.className = 'dlb-item-name';
                        name.textContent = item.name || '—';

                        const meta = document.createElement('span');
                        meta.className = 'dlb-item-meta';
                        meta.textContent = `کد پرسنلی: ${item.personnel_code || '—'} · کد ملی: ${item.national_code || '—'}`;

                        li.append(name, meta);
                        listEl.appendChild(li);
                    });
                };

                const syncHiddenInputs = () => {
                    hiddenInputsEl.innerHTML = '';
                    Array.from(selectedIds)
                        .map((id) => parseInt(id, 10))
                        .filter((id) => !Number.isNaN(id))
                        .sort((a, b) => a - b)
                        .forEach((id) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'audience_personnel_ids[]';
                            input.value = String(id);
                            hiddenInputsEl.appendChild(input);
                        });
                };

                const updateCounts = () => {
                    const availableCount = getAvailableItems().length;
                    const selectedCount = selectedIds.size;
                    if (availableCountEl) {
                        availableCountEl.textContent = toPersianNumber(availableCount);
                    }
                    if (selectedCountEl) {
                        selectedCountEl.textContent = toPersianNumber(selectedCount);
                    }
                };

                const updateButtons = () => {
                    const availableQuery = normalizeSearch(availableSearchEl?.value || '');
                    const selectedQuery = normalizeSearch(selectedSearchEl?.value || '');
                    const visibleAvailable = getAvailableItems().filter((item) => matchesSearch(item, availableQuery));
                    const visibleSelected = getSelectedItems().filter((item) => matchesSearch(item, selectedQuery));
                    const highlightedAvailable = Array.from(availableHighlight).filter((id) => !selectedIds.has(id));
                    const highlightedSelected = Array.from(selectedHighlight).filter((id) => selectedIds.has(id));

                    if (btnMoveAllToSelected) {
                        btnMoveAllToSelected.disabled = visibleAvailable.length === 0;
                    }
                    if (btnMoveToSelected) {
                        btnMoveToSelected.disabled = highlightedAvailable.length === 0;
                    }
                    if (btnMoveToAvailable) {
                        btnMoveToAvailable.disabled = highlightedSelected.length === 0;
                    }
                    if (btnMoveAllToAvailable) {
                        btnMoveAllToAvailable.disabled = visibleSelected.length === 0;
                    }
                };

                const render = () => {
                    const availableQuery = normalizeSearch(availableSearchEl?.value || '');
                    const selectedQuery = normalizeSearch(selectedSearchEl?.value || '');
                    const availableItems = getAvailableItems()
                        .filter((item) => matchesSearch(item, availableQuery))
                        .sort((a, b) => (a.name || '').localeCompare(b.name || '', 'fa'));
                    const selectedItems = getSelectedItems()
                        .filter((item) => matchesSearch(item, selectedQuery))
                        .sort((a, b) => (a.name || '').localeCompare(b.name || '', 'fa'));

                    Array.from(availableHighlight).forEach((id) => {
                        if (selectedIds.has(id) || !personnelById.has(id)) {
                            availableHighlight.delete(id);
                        }
                    });
                    Array.from(selectedHighlight).forEach((id) => {
                        if (!selectedIds.has(id)) {
                            selectedHighlight.delete(id);
                        }
                    });

                    renderList(availableListEl, availableItems, availableHighlight, 'پرسنلی برای نمایش وجود ندارد.');
                    renderList(selectedListEl, selectedItems, selectedHighlight, 'هنوز پرسنلی انتخاب نشده است.');
                    syncHiddenInputs();
                    updateCounts();
                    updateButtons();
                };

                const moveToSelected = (ids) => {
                    ids.forEach((id) => {
                        if (personnelById.has(String(id)) && !selectedIds.has(String(id))) {
                            selectedIds.add(String(id));
                        }
                    });
                    availableHighlight.clear();
                    render();
                };

                const moveToAvailable = (ids) => {
                    ids.forEach((id) => selectedIds.delete(String(id)));
                    selectedHighlight.clear();
                    render();
                };

                availableListEl?.addEventListener('click', (event) => {
                    const itemEl = event.target.closest('.dlb-item');
                    if (!itemEl) {
                        return;
                    }
                    if (!event.ctrlKey && !event.metaKey) {
                        availableHighlight.clear();
                        selectedHighlight.clear();
                    }
                    toggleHighlight(availableHighlight, itemEl.dataset.id);
                    render();
                });

                selectedListEl?.addEventListener('click', (event) => {
                    const itemEl = event.target.closest('.dlb-item');
                    if (!itemEl) {
                        return;
                    }
                    if (!event.ctrlKey && !event.metaKey) {
                        selectedHighlight.clear();
                        availableHighlight.clear();
                    }
                    toggleHighlight(selectedHighlight, itemEl.dataset.id);
                    render();
                });

                availableListEl?.addEventListener('dblclick', (event) => {
                    const itemEl = event.target.closest('.dlb-item');
                    if (!itemEl) {
                        return;
                    }
                    moveToSelected([itemEl.dataset.id]);
                });

                selectedListEl?.addEventListener('dblclick', (event) => {
                    const itemEl = event.target.closest('.dlb-item');
                    if (!itemEl) {
                        return;
                    }
                    moveToAvailable([itemEl.dataset.id]);
                });

                btnMoveAllToSelected?.addEventListener('click', () => {
                    const availableQuery = normalizeSearch(availableSearchEl?.value || '');
                    moveToSelected(getAvailableItems()
                        .filter((item) => matchesSearch(item, availableQuery))
                        .map((item) => item.id));
                });

                btnMoveToSelected?.addEventListener('click', () => {
                    moveToSelected(Array.from(availableHighlight));
                });

                btnMoveToAvailable?.addEventListener('click', () => {
                    moveToAvailable(Array.from(selectedHighlight));
                });

                btnMoveAllToAvailable?.addEventListener('click', () => {
                    const selectedQuery = normalizeSearch(selectedSearchEl?.value || '');
                    moveToAvailable(getSelectedItems()
                        .filter((item) => matchesSearch(item, selectedQuery))
                        .map((item) => item.id));
                });

                availableSearchEl?.addEventListener('input', render);
                selectedSearchEl?.addEventListener('input', render);

                render();
            })();

            const cssColorToHex6 = (raw) => {
                const v = String(raw || '').trim();
                if (/^#[0-9A-Fa-f]{6}$/i.test(v)) {
                    return v.startsWith('#') ? v.toLowerCase() : `#${v.toLowerCase()}`;
                }
                if (/^#[0-9A-Fa-f]{3}$/i.test(v)) {
                    const h = v.slice(1);
                    return `#${h[0]}${h[0]}${h[1]}${h[1]}${h[2]}${h[2]}`.toLowerCase();
                }
                const m = v.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i);
                if (!m) return null;
                const r = Math.min(255, Math.max(0, Math.round(parseFloat(m[1]))));
                const g = Math.min(255, Math.max(0, Math.round(parseFloat(m[2]))));
                const b = Math.min(255, Math.max(0, Math.round(parseFloat(m[3]))));
                const x = (n) => n.toString(16).padStart(2, '0');
                return `#${x(r)}${x(g)}${x(b)}`;
            };
            document.querySelectorAll('.theme-color-picker[data-theme-pair]').forEach((picker) => {
                const key = picker.getAttribute('data-theme-pair');
                const text = document.querySelector(`.theme-color-text[data-theme-pair="${key}"]`);
                if (!text) return;
                const applyHexToPicker = () => {
                    const hex = cssColorToHex6(text.value);
                    if (hex) {
                        picker.value = hex;
                    }
                };
                picker.addEventListener('input', () => {
                    text.value = picker.value;
                });
                text.addEventListener('input', applyHexToPicker);
                text.addEventListener('change', applyHexToPicker);
                applyHexToPicker();
            });
        });
    </script>
@endsection

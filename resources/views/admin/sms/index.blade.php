@extends('admin.layouts.app')

@section('page-title', 'مدیریت پیامک')
@section('page-description', 'مشاهده تاریخچه ارسال و ارسال گروهی پیامک دعوت به نظرسنجی با تأیید چندمرحله‌ای.')

@section('content')
    @include('admin.partials.fontawesome-local')
    @php
        $statusLabels = [
            'sent' => 'ارسال شده',
            'failed' => 'ناموفق',
            'pending' => 'در انتظار',
        ];
        $campaignStatusLabels = [
            'awaiting_send' => 'در انتظار تأیید',
            'queued' => 'در صف',
            'processing' => 'در حال ارسال',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
        ];
    @endphp
    <style>
        .sms-page { display: flex; flex-direction: column; gap: 1.25rem; }
        .sms-tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .sms-tab {
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #fff;
            border-radius: 14px;
            padding: 0.65rem 1.1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            color: var(--slate);
        }
        .sms-tab.is-active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border-color: transparent;
        }
        .sms-panel {
            display: none;
            flex-direction: column;
            gap: 1rem;
        }
        .sms-panel.is-active { display: flex; }
        .sms-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 24px;
            padding: clamp(1rem, 3vw, 1.5rem);
        }
        .sms-card h2, .sms-card h3 { margin: 0 0 0.5rem; font-size: 1.1rem; }
        .sms-card p.hint { margin: 0 0 1rem; color: var(--muted); font-size: 0.88rem; line-height: 1.7; }
        .sms-alert {
            border-radius: 14px;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            line-height: 1.65;
        }
        .sms-alert.warn { background: rgba(234, 179, 8, 0.12); border: 1px solid rgba(202, 138, 4, 0.35); color: #854d0e; }
        .sms-alert.ok { background: rgba(22, 163, 74, 0.1); border: 1px solid rgba(22, 163, 74, 0.25); color: #166534; }
        .sms-alert.err { background: rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.25); color: #b91c1c; }
        .sms-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .sms-field label { display: block; font-weight: 600; font-size: 0.86rem; margin-bottom: 0.35rem; }
        .sms-field input, .sms-field select, .sms-field textarea {
            width: 100%;
            border: 1px solid rgba(15, 23, 42, 0.15);
            border-radius: 14px;
            padding: 0.65rem 0.85rem;
            font-family: inherit;
            font-size: 0.9rem;
            background: rgba(15, 23, 42, 0.02);
        }
        .sms-field textarea { min-height: 160px; resize: vertical; line-height: 1.75; }
        .target-panel { display: none; margin-top: 0.75rem; }
        .target-panel.is-visible { display: block; }
        .sms-section {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            padding: 1rem 1.1rem;
            margin-bottom: 1rem;
            background: rgba(15, 23, 42, 0.015);
        }
        .sms-section-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0 0 0.85rem;
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--slate);
        }
        .sms-section-title i {
            width: 2rem;
            height: 2rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(var(--primary-rgb, 214, 17, 25), 0.14), rgba(var(--primary-rgb, 214, 17, 25), 0.06));
            color: var(--primary);
            font-size: 0.95rem;
        }
        .sms-select-wrap {
            display: flex;
            align-items: stretch;
            gap: 0;
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }
        .sms-select-wrap .select-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 3rem;
            background: rgba(var(--primary-rgb, 214, 17, 25), 0.08);
            color: var(--primary);
            font-size: 1.1rem;
        }
        .sms-select-wrap select {
            flex: 1;
            border: none;
            border-radius: 0;
            padding: 0.75rem 0.85rem;
            font-weight: 600;
            background: transparent;
        }
        .sms-select-wrap select:focus { outline: none; box-shadow: inset 0 0 0 2px rgba(var(--primary-rgb, 214, 17, 25), 0.2); }
        .audience-info-box {
            display: none;
            margin-top: 0.75rem;
            padding: 0.75rem 0.9rem;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(15, 23, 42, 0.04));
            border: 1px solid rgba(59, 130, 246, 0.2);
            font-size: 0.86rem;
            line-height: 1.7;
        }
        .audience-info-box.is-visible { display: block; }
        .audience-info-box strong { color: var(--slate); }
        .sms-tab i { margin-left: 0.35rem; }
        .audience-target { display: none; margin-top: 0.65rem; }
        .audience-target.is-visible { display: block; }
        .audience-mode-grid { display: flex; flex-wrap: wrap; gap: 0.45rem; }
        .audience-mode-grid label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            font-size: 0.82rem;
            cursor: pointer;
        }
        .personnel-pick-wrap { border: 1px solid rgba(15, 23, 42, 0.1); border-radius: 16px; padding: 0.75rem; }
        .personnel-pick-list { max-height: 220px; overflow: auto; margin-top: 0.5rem; }
        .personnel-pick-list label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.35rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.05);
            font-size: 0.85rem;
            cursor: pointer;
        }
        .selected-chips { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.5rem; }
        .chip {
            background: rgba(var(--primary-rgb, 214, 17, 25), 0.1);
            color: var(--primary-dark);
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .sms-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; }
        .btn-sms {
            border: none;
            border-radius: 14px;
            padding: 0.72rem 1.2rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-sms.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .btn-sms.ghost {
            background: transparent;
            border: 1px dashed rgba(15, 23, 42, 0.25);
            color: var(--slate);
        }
        .btn-sms:disabled { opacity: 0.55; cursor: not-allowed; }
        .preview-box {
            display: none;
            margin-top: 1rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 18px;
            overflow: hidden;
        }
        .preview-box.is-visible { display: block; }
        .preview-head {
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.04);
            font-size: 0.88rem;
            font-weight: 700;
        }
        .preview-table-wrap { max-height: 320px; overflow: auto; }
        .preview-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .preview-table th, .preview-table td {
            padding: 0.6rem 0.75rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .preview-table th { background: rgba(15, 23, 42, 0.03); font-size: 0.78rem; }
        .log-filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.85rem;
            align-items: end;
        }
        .log-table-wrap {
            overflow: auto;
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }
        .log-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; min-width: 880px; }
        .log-table th, .log-table td {
            padding: 0.7rem 0.85rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            vertical-align: top;
        }
        .log-table th { background: rgba(15, 23, 42, 0.04); font-size: 0.78rem; }
        .status-pill {
            display: inline-flex;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
        }
        .status-pill.sent { background: rgba(22, 163, 74, 0.12); color: #166534; }
        .status-pill.failed { background: rgba(220, 38, 38, 0.1); color: #b91c1c; }
        .status-pill.pending { background: rgba(234, 179, 8, 0.15); color: #854d0e; }
        .msg-preview { max-width: 280px; white-space: pre-wrap; line-height: 1.55; font-size: 0.8rem; color: var(--slate); }
        .sms-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .sms-modal-backdrop.is-open { display: flex; }
        .sms-modal {
            background: #fff;
            border-radius: 22px;
            width: min(100%, 560px);
            max-height: min(90vh, 720px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 0;
        }
        .sms-modal-head {
            flex-shrink: 0;
            padding: 1.15rem 1.25rem 0.75rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }
        .sms-modal h3 { margin: 0; font-size: 1.05rem; }
        .confirm-steps {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 0 1.25rem 1.15rem;
        }
        .confirm-step {
            display: none;
            flex-direction: column;
            min-height: 0;
            flex: 1;
            overflow: hidden;
            padding-top: 0.75rem;
        }
        .confirm-step.is-active {
            display: flex;
        }
        .confirm-step-body {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding-left: 0.1rem;
            margin-bottom: 0.75rem;
        }
        .modal-review-block {
            margin-bottom: 0.85rem;
        }
        .modal-review-block h4 {
            margin: 0 0 0.4rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--slate);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .modal-message-preview {
            margin: 0;
            padding: 0.65rem 0.75rem;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 0.8rem;
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 140px;
            overflow-y: auto;
        }
        .modal-recipients-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
        }
        .modal-recipients-table th,
        .modal-recipients-table td {
            padding: 0.45rem 0.5rem;
            text-align: right;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .modal-recipients-table th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 1;
        }
        .modal-recipients-wrap {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
        }
        .confirm-step-footer {
            flex-shrink: 0;
            padding-top: 0.35rem;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
        }
        .phrase-copy-row {
            display: flex;
            gap: 0.45rem;
            align-items: stretch;
            margin-top: 0.5rem;
        }
        .phrase-copy-row input { flex: 1; }
        .btn-sms.sm {
            padding: 0.55rem 0.75rem;
            font-size: 0.8rem;
        }
        #phraseStepError.sms-alert { margin-top: 0.5rem; }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .progress-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 300;
            background: rgba(15, 23, 42, 0.62);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .progress-modal-backdrop.is-open { display: flex; }
        .progress-modal-backdrop.is-locked { pointer-events: auto; }
        .progress-modal {
            width: min(100%, 480px);
            background: #fff;
            border-radius: 24px;
            padding: 1.35rem 1.35rem 1.15rem;
            box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28);
            text-align: center;
        }
        .progress-modal-icon {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 0.85rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(var(--primary-rgb, 214, 17, 25), 0.15), rgba(var(--primary-rgb, 214, 17, 25), 0.05));
            color: var(--primary);
            font-size: 1.35rem;
        }
        .progress-modal.is-sending .progress-modal-icon i {
            animation: sms-spin 1.1s linear infinite;
        }
        .progress-modal.is-done .progress-modal-icon {
            background: rgba(22, 163, 74, 0.12);
            color: #16a34a;
        }
        .progress-modal.is-done .progress-modal-icon i { animation: none; }
        @keyframes sms-spin {
            to { transform: rotate(360deg); }
        }
        .progress-modal h3 {
            margin: 0 0 0.35rem;
            font-size: 1.12rem;
            color: var(--slate);
        }
        .progress-modal .progress-status-text {
            margin: 0 0 1rem;
            font-size: 0.86rem;
            color: var(--muted);
            line-height: 1.65;
        }
        .progress-bar-outer {
            height: 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            overflow: hidden;
            margin-bottom: 0.45rem;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
        }
        .progress-bar-inner {
            display: block;
            height: 100%;
            width: 0%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transition: width 0.35s ease;
        }
        .progress-percent {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.85rem;
            font-variant-numeric: tabular-nums;
        }
        .progress-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.45rem;
            margin-bottom: 0.85rem;
        }
        .progress-stat {
            border-radius: 14px;
            padding: 0.5rem 0.35rem;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }
        .progress-stat strong {
            display: block;
            font-size: 1rem;
            font-weight: 800;
            color: var(--slate);
        }
        .progress-stat span {
            font-size: 0.72rem;
            color: var(--muted);
        }
        .progress-current {
            text-align: right;
            font-size: 0.82rem;
            color: var(--slate);
            padding: 0.65rem 0.75rem;
            border-radius: 14px;
            background: rgba(59, 130, 246, 0.06);
            border: 1px solid rgba(59, 130, 246, 0.15);
            margin-bottom: 0.85rem;
            line-height: 1.6;
            min-height: 3.2rem;
        }
        .progress-current.is-ok { border-color: rgba(22, 163, 74, 0.25); background: rgba(22, 163, 74, 0.06); }
        .progress-current.is-fail { border-color: rgba(220, 38, 38, 0.25); background: rgba(254, 242, 242, 0.8); }
        .progress-warn {
            font-size: 0.78rem;
            color: #b45309;
            margin: 0 0 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
        }
        .progress-modal .btn-close-progress {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
        }
        .progress-modal.is-done .btn-close-progress { display: inline-flex; justify-content: center; }
        .progress-modal.is-done .progress-warn { display: none; }
        @media (max-width: 640px) {
            .sms-actions { flex-direction: column; }
            .btn-sms { width: 100%; }
        }
    </style>

    <div class="sms-page">
        <div class="sms-tabs" role="tablist">
            <button type="button" class="sms-tab @if ($activeTab === 'send') is-active @endif" data-tab="send">
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> ارسال پیامک
            </button>
            <button type="button" class="sms-tab @if ($activeTab === 'history') is-active @endif" data-tab="history">
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> تاریخچه ارسال
            </button>
        </div>

        <div id="panelSend" class="sms-panel @if ($activeTab === 'send') is-active @endif">
            @if (! $activeProvider?->config)
                <div class="sms-alert warn">
                    پنل پیامکی فعال یا پیکربندی نشده است. از <strong>تنظیمات سازمان → پنل پیامک</strong> ابتدا اتصال را برقرار کنید.
                </div>
            @else
                <div class="sms-alert ok">
                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    پنل فعال: <strong>{{ $activeProvider->name }}</strong>
                    — شماره ارسال: <strong>{{ $activeProvider->config->send_number }}</strong>
                </div>
            @endif

            <form id="smsSendForm" class="sms-card" novalidate>
                @csrf
                <h2><i class="fa-solid fa-comment-sms" aria-hidden="true"></i> ارسال پیامک نظرسنجی</h2>
                <p class="hint">مراحل: انتخاب روش گیرندگان → تکمیل جزئیات → پیش‌نمایش فهرست → تأیید چندمرحله‌ای (عبارت امنیتی + رمز مدیر).</p>

                <section class="sms-section">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
                        <span>۱. نحوه انتخاب گیرندگان</span>
                    </h3>
                    <div class="sms-select-wrap">
                        <span class="select-icon" aria-hidden="true">
                            <i id="targetingModeIcon" class="fa-solid {{ $targetingIcons[\App\Support\SmsTargetingMode::SURVEY_ELIGIBLE] ?? 'fa-user-check' }}"></i>
                        </span>
                        <select id="targetingMode" name="targeting_mode" aria-label="نحوه انتخاب گیرندگان">
                            @foreach ($targetingModes as $modeKey => $modeLabel)
                                <option value="{{ $modeKey }}" @selected($loop->first)>{{ $modeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                <section class="sms-section" id="surveySection">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                        <span>۲. نظرسنجی مرتبط</span>
                    </h3>
                    <div class="sms-field">
                        <label for="surveyId" id="surveyIdLabel">انتخاب نظرسنجی</label>
                        <select id="surveyId" name="survey_id">
                            <option value="">— انتخاب کنید —</option>
                            @foreach ($surveys as $survey)
                                <option value="{{ $survey->id }}" @selected(old('survey_id') == $survey->id) data-has-link="{{ $survey->public_token ? '1' : '0' }}">
                                    {{ $survey->title }}
                                    @if (! $survey->public_token) (بدون لینک عمومی) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="audienceInfoBox" class="audience-info-box" role="status">
                        <i class="fa-solid fa-filter" aria-hidden="true"></i>
                        <strong>فیلتر مخاطب این نظرسنجی:</strong>
                        <span id="audienceInfoText">—</span>
                    </div>
                </section>

                <section class="sms-section target-panel" data-target="survey_eligible">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                        <span>مخاطبان مجاز نظرسنجی</span>
                    </h3>
                    <p class="hint" style="margin-bottom:0;">
                        فقط پرسنلی که در <strong>تنظیمات همان نظرسنجی → فیلتر مخاطب</strong> مجاز به شرکت هستند و شماره موبایل دارند، در فهرست می‌آیند.
                    </p>
                </section>

                <section class="sms-section target-panel" data-target="custom_filters">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-filter" aria-hidden="true"></i>
                        <span>فیلتر سفارشی پرسنل</span>
                    </h3>
                    <p class="hint">مانند تنظیمات نظرسنجی؛ همه معیارهای انتخاب‌شده با هم (AND) اعمال می‌شوند.</p>
                    <div class="audience-mode-grid" id="audienceModesWrap">
                        @foreach ($audiencePresets as $modeKey => $modeLabel)
                            <label>
                                <input type="checkbox" name="audience_modes[]" value="{{ $modeKey }}" data-audience-toggle="{{ $modeKey }}">
                                <span>{{ $modeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="audience-target" data-audience-target="unit">
                        <div class="sms-field">
                            <label>واحدهای مجاز</label>
                            <select name="audience_unit_ids[]" multiple size="5">
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="audience-target" data-audience-target="gender">
                        <div class="sms-field">
                            <label>جنسیت</label>
                            <select name="audience_genders[]" multiple size="3">
                                @foreach ($genderOptions as $gk => $gl)
                                    <option value="{{ $gk }}">{{ $gl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="audience-target" data-audience-target="position">
                        <div class="sms-field">
                            <label>سمت‌ها</label>
                            <select name="audience_position_ids[]" multiple size="5">
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="audience-target" data-audience-target="personnel">
                        <div class="sms-field">
                            <label>افراد مشخص</label>
                            <select name="audience_personnel_ids[]" multiple size="6">
                                @foreach ($personnelOptions as $p)
                                    <option value="{{ $p->id }}">{{ trim($p->first_name.' '.$p->last_name) }} — {{ $p->personnel_code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="sms-section target-panel" data-target="selected_personnel">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                        <span>انتخاب پرسنل</span>
                    </h3>
                    <div class="personnel-pick-wrap">
                        <div class="sms-field">
                            <label for="personnelSearch">جستجوی پرسنل (نام، کد پرسنلی، موبایل)</label>
                            <input type="search" id="personnelSearch" placeholder="حداقل ۲ حرف بنویسید…" autocomplete="off">
                        </div>
                        <div class="personnel-pick-list" id="personnelSearchResults"></div>
                        <div class="selected-chips" id="selectedPersonnelChips"></div>
                    </div>
                </section>

                <section class="sms-section target-panel" data-target="free_numbers">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-phone-volume" aria-hidden="true"></i>
                        <span>شماره‌های دلخواه</span>
                    </h3>
                    <div class="sms-field">
                        <label for="freeNumbers">هر خط یک شماره موبایل</label>
                        <textarea id="freeNumbers" name="free_numbers" rows="6" placeholder="09121234567&#10;09131234567"></textarea>
                    </div>
                </section>

                <section class="sms-section">
                    <h3 class="sms-section-title">
                        <i class="fa-solid fa-message" aria-hidden="true"></i>
                        <span>۳. متن پیامک</span>
                    </h3>
                    <div class="sms-field">
                    <label for="messageBody" class="sr-only">متن پیامک</label>
                    <textarea id="messageBody" name="message" required maxlength="900" placeholder="متن دعوت به نظرسنجی…"></textarea>
                    <p class="hint" style="margin: 0.35rem 0 0;">با انتخاب نظرسنجی، قالب پیش‌فرض پر می‌شود. برای شخصی‌سازی از <code>{name}</code> استفاده کنید.</p>
                    </div>
                </section>

                <div id="formErrors" class="sms-alert err" style="display:none; margin-top:0.75rem;"></div>

                <div class="sms-actions">
                    <button type="button" class="btn-sms primary" id="btnPreview" @disabled(! $activeProvider?->config)>
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> پیش‌نمایش گیرندگان
                    </button>
                </div>

                <div id="previewBox" class="preview-box">
                    <div class="preview-head" id="previewSummary"></div>
                    <div class="preview-table-wrap">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نام</th>
                                    <th>کد پرسنلی</th>
                                    <th>موبایل</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                        </table>
                    </div>
                    <div style="padding: 0.85rem 1rem;">
                        <label style="display:flex;align-items:flex-start;gap:0.5rem;font-size:0.88rem;cursor:pointer;">
                            <input type="checkbox" id="previewAck" style="margin-top:0.2rem;">
                            <span>فهرست گیرندگان و متن نمونه را بررسی کردم و از صحت آن اطمینان دارم.</span>
                        </label>
                        <div class="sms-actions">
                            <button type="button" class="btn-sms primary" id="btnDraft" disabled>ادامه — ثبت پیش‌نویس و تأیید نهایی</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="panelHistory" class="sms-panel @if ($activeTab === 'history') is-active @endif">
            <div class="sms-card log-filters">
                <h2>فیلتر تاریخچه</h2>
                <form method="GET" action="{{ route('admin.sms.index') }}">
                    <input type="hidden" name="tab" value="history">
                    <div class="sms-field">
                        <label>موبایل گیرنده</label>
                        <input type="text" name="log_mobile" value="{{ request('log_mobile') }}" placeholder="0912…">
                    </div>
                    <div class="sms-field">
                        <label>وضعیت</label>
                        <select name="log_status">
                            <option value="">همه</option>
                            @foreach ($statusLabels as $sk => $sl)
                                <option value="{{ $sk }}" @selected(request('log_status') === $sk)>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sms-field">
                        <label>از تاریخ</label>
                        <input type="text" name="log_from" value="{{ request('log_from') }}" placeholder="۱۴۰۳/۰۸/۰۱" data-jalali-picker>
                    </div>
                    <div class="sms-field">
                        <label>تا تاریخ</label>
                        <input type="text" name="log_to" value="{{ request('log_to') }}" placeholder="۱۴۰۳/۰۸/۳۰" data-jalali-picker>
                    </div>
                    <div class="sms-actions" style="margin:0;">
                        <button type="submit" class="btn-sms primary">اعمال فیلتر</button>
                        <a href="{{ route('admin.sms.index', ['tab' => 'history']) }}" class="btn-sms ghost">پاک کردن</a>
                    </div>
                </form>
            </div>

            <div class="sms-card">
                <h2>پیامک‌های ارسال‌شده</h2>
                <div class="log-table-wrap">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>تاریخ و زمان</th>
                                <th>گیرنده</th>
                                <th>نام</th>
                                <th>فرستنده</th>
                                <th>پنل</th>
                                <th>نظرسنجی</th>
                                <th>وضعیت</th>
                                <th>متن</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td>{{ $log->sent_at?->format('Y/m/d H:i') ?? $log->created_at->format('Y/m/d H:i') }}</td>
                                    <td dir="ltr">{{ $log->recipient_mobile ? '0'.$log->recipient_mobile : '—' }}</td>
                                    <td>{{ $log->recipient_name ?? '—' }}</td>
                                    <td dir="ltr">{{ $log->sender_number ?? '—' }}</td>
                                    <td>{{ $log->provider_name ?? $log->provider?->name ?? '—' }}</td>
                                    <td>{{ $log->campaign?->survey?->title ?? '—' }}</td>
                                    <td>
                                        <span class="status-pill {{ $log->status }}">{{ $statusLabels[$log->status] ?? $log->status }}</span>
                                    </td>
                                    <td><div class="msg-preview">{{ $log->message_body }}</div></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;color:var(--muted);padding:2rem;">هنوز پیامکی ثبت نشده است.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($logs->hasPages())
                    <div style="margin-top:1rem;">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="sms-modal-backdrop" id="confirmModal" aria-hidden="true">
        <div class="sms-modal" role="dialog" aria-labelledby="confirmModalTitle">
            <div class="sms-modal-head">
                <h3 id="confirmModalTitle"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> تأیید نهایی ارسال</h3>
            </div>
            <div class="confirm-steps">
                <div class="confirm-step is-active" data-step="1">
                    <div class="confirm-step-body">
                        <p class="hint" id="confirmSummary"></p>
                        <div class="modal-review-block">
                            <h4><i class="fa-solid fa-message" aria-hidden="true"></i> نمونه متن پیامک</h4>
                            <pre class="modal-message-preview" id="modalMessagePreview">—</pre>
                        </div>
                        <div class="modal-review-block">
                            <h4><i class="fa-solid fa-users" aria-hidden="true"></i> فهرست گیرندگان (<span id="modalRecipientCount">0</span> نفر)</h4>
                            <div class="modal-recipients-wrap">
                                <table class="modal-recipients-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>نام</th>
                                            <th>کد پرسنلی</th>
                                            <th>موبایل</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalRecipientsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <label style="display:flex;gap:0.5rem;font-size:0.88rem;cursor:pointer;">
                            <input type="checkbox" id="modalAck">
                            <span>فهرست گیرندگان و متن پیامک را بررسی کردم و از ارسال اطمینان دارم.</span>
                        </label>
                    </div>
                    <div class="confirm-step-footer sms-actions">
                        <button type="button" class="btn-sms ghost" data-close-modal>انصراف</button>
                        <button type="button" class="btn-sms primary" id="btnStep1Next" disabled>مرحله بعد</button>
                    </div>
                </div>
                <div class="confirm-step" data-step="2">
                    <div class="confirm-step-body">
                        <p class="hint">عبارت زیر را وارد کنید (می‌توانید با دکمه «کپی» از ارقام فارسی هم استفاده کنید):</p>
                        <p style="font-weight:800;text-align:center;padding:0.5rem;background:rgba(15,23,42,0.05);border-radius:12px;direction:ltr;" id="confirmPhraseDisplay"></p>
                        <div class="phrase-copy-row">
                            <input type="text" id="confirmPhraseInput" class="sms-field" style="margin:0;padding:0.65rem 0.85rem;border:1px solid rgba(15,23,42,0.15);border-radius:14px;" autocomplete="off" placeholder="عبارت تأیید" dir="ltr">
                            <button type="button" class="btn-sms ghost sm" id="btnCopyPhrase" title="کپی عبارت">
                                <i class="fa-solid fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="phraseStepError" class="sms-alert err" style="display:none;"></div>
                    </div>
                    <div class="confirm-step-footer sms-actions">
                        <button type="button" class="btn-sms ghost" data-prev-step>بازگشت</button>
                        <button type="button" class="btn-sms primary" id="btnStep2Next">مرحله بعد — ورود رمز</button>
                    </div>
                </div>
                <div class="confirm-step" data-step="3">
                    <div class="confirm-step-body">
                        <p class="hint"><i class="fa-solid fa-circle-check" style="color:#16a34a" aria-hidden="true"></i> عبارت تأیید ثبت شد. اکنون رمز ورود پنل مدیریت را وارد کنید.</p>
                        <div class="sms-field">
                            <label>رمز عبور مدیر</label>
                            <input type="password" id="adminPasswordConfirm" autocomplete="current-password">
                        </div>
                        <div id="sendErrors" class="sms-alert err" style="display:none;"></div>
                    </div>
                    <div class="confirm-step-footer sms-actions">
                        <button type="button" class="btn-sms ghost" data-prev-step>بازگشت</button>
                        <button type="button" class="btn-sms primary" id="btnFinalSend">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> ارسال قطعی
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="progress-modal-backdrop is-locked" id="progressModal" aria-hidden="true" role="alertdialog" aria-labelledby="progressModalTitle">
        <div class="progress-modal is-sending" id="progressModalCard">
            <div class="progress-modal-icon" aria-hidden="true">
                <i class="fa-solid fa-paper-plane" id="progressModalIcon"></i>
            </div>
            <h3 id="progressModalTitle">در حال ارسال پیامک‌ها</h3>
            <p class="progress-status-text" id="progressStatusText">لطفاً این پنجره را تا پایان ارسال نبندید.</p>
            <div class="progress-percent" id="progressPercent">۰٪</div>
            <div class="progress-bar-outer" aria-hidden="true">
                <span class="progress-bar-inner" id="progressBarInner"></span>
            </div>
            <div class="progress-stats">
                <div class="progress-stat">
                    <strong id="progressSentCount">0</strong>
                    <span>موفق</span>
                </div>
                <div class="progress-stat">
                    <strong id="progressFailedCount">0</strong>
                    <span>ناموفق</span>
                </div>
                <div class="progress-stat">
                    <strong id="progressRemainingCount">0</strong>
                    <span>باقی‌مانده</span>
                </div>
            </div>
            <div class="progress-current" id="progressCurrent">در حال آماده‌سازی…</div>
            <p class="progress-warn">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                تا اتمام ارسال، این صفحه را نبندید.
            </p>
            <div id="progressError" class="sms-alert err" style="display:none; margin-bottom:0.75rem; text-align:right;"></div>
            <button type="button" class="btn-sms primary btn-close-progress" id="btnProgressClose">
                مشاهده تاریخچه ارسال
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const routes = {
                preview: @json(route('admin.sms.preview')),
                store: @json(route('admin.sms.campaigns.store')),
                send: @json(url('/admin/sms/campaigns')),
                sendStep: @json(url('/admin/sms/campaigns')),
                personnelSearch: @json(route('admin.sms.personnel-search')),
                surveyTemplate: @json(url('/admin/sms/surveys')),
                historyUrl: @json(route('admin.sms.index', ['tab' => 'history'])),
            };
            const targetingIcons = @json($targetingIcons);

            document.querySelectorAll('.sms-tab').forEach((tab) => {
                tab.addEventListener('click', () => {
                    const id = tab.dataset.tab;
                    document.querySelectorAll('.sms-tab').forEach((t) => t.classList.toggle('is-active', t === tab));
                    document.querySelectorAll('.sms-panel').forEach((p) => p.classList.remove('is-active'));
                    document.getElementById(id === 'history' ? 'panelHistory' : 'panelSend')?.classList.add('is-active');
                });
            });

            const form = document.getElementById('smsSendForm');
            const previewBox = document.getElementById('previewBox');
            const previewAck = document.getElementById('previewAck');
            const btnDraft = document.getElementById('btnDraft');
            let previewChecksum = '';
            let campaignId = null;
            let confirmPhrase = '';
            let lockedConfirmPhrase = '';
            let lastPreviewData = null;
            const selectedPersonnel = new Map();

            const normalizePhrase = (value) => {
                let s = String(value || '').trim();
                const digitMap = {
                    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
                    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
                    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
                };
                s = s.replace(/[۰-۹٠-٩]/g, (ch) => digitMap[ch] ?? ch);
                s = s.replace(/[\u200c\u200f\ufeff\s]+/g, '');
                s = s.replace(/[\u2010-\u2015\u2212\u0640]/g, '-');
                return s;
            };

            const renderModalReview = (data) => {
                if (!data) return;
                document.getElementById('modalMessagePreview').textContent = data.sample_message || '—';
                document.getElementById('modalRecipientCount').textContent = String(data.recipient_count || 0);
                const tbody = document.getElementById('modalRecipientsBody');
                tbody.innerHTML = '';
                (data.recipients || []).forEach((row, i) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${i + 1}</td><td>${row.name || '—'}</td><td>${row.personnel_code || '—'}</td><td dir="ltr">${row.mobile}</td>`;
                    tbody.appendChild(tr);
                });
            };

            const targetingModeEl = document.getElementById('targetingMode');
            const getTargetingMode = () => targetingModeEl?.value || '';

            const loadSurveyMeta = async () => {
                const id = document.getElementById('surveyId')?.value;
                const audienceBox = document.getElementById('audienceInfoBox');
                const audienceText = document.getElementById('audienceInfoText');
                if (!id || getTargetingMode() !== 'survey_eligible') {
                    audienceBox?.classList.remove('is-visible');
                    return;
                }
                try {
                    const res = await fetch(`${routes.surveyTemplate}/${id}/template`, { headers: { Accept: 'application/json' } });
                    const json = await res.json();
                    if (json.ok && json.audience_summary) {
                        audienceText.textContent = json.audience_summary;
                        audienceBox?.classList.add('is-visible');
                    }
                } catch (_) {}
            };

            const updateTargetingUI = () => {
                const mode = getTargetingMode();
                document.querySelectorAll('.target-panel').forEach((el) => {
                    el.classList.toggle('is-visible', el.dataset.target === mode);
                });
                const iconEl = document.getElementById('targetingModeIcon');
                if (iconEl && targetingIcons[mode]) {
                    iconEl.className = `fa-solid ${targetingIcons[mode]}`;
                }
                const surveySection = document.getElementById('surveySection');
                const surveySelect = document.getElementById('surveyId');
                const label = document.getElementById('surveyIdLabel');
                if (surveySection) {
                    surveySection.style.display = mode === 'free_numbers' ? 'none' : '';
                }
                if (surveySelect) {
                    surveySelect.required = mode === 'survey_eligible';
                }
                if (label) {
                    label.textContent = mode === 'survey_eligible'
                        ? 'انتخاب نظرسنجی (الزامی)'
                        : 'نظرسنجی (اختیاری — برای قالب و لینک)';
                }
                if (mode === 'survey_eligible') {
                    loadSurveyMeta();
                } else {
                    document.getElementById('audienceInfoBox')?.classList.remove('is-visible');
                }
            };
            targetingModeEl?.addEventListener('change', updateTargetingUI);
            updateTargetingUI();

            const syncAudienceTargets = () => {
                document.querySelectorAll('[data-audience-toggle]').forEach((cb) => {
                    const target = document.querySelector(`[data-audience-target="${cb.dataset.audienceToggle}"]`);
                    if (target) target.classList.toggle('is-visible', cb.checked);
                });
            };
            document.querySelectorAll('[data-audience-toggle]').forEach((cb) => {
                cb.addEventListener('change', syncAudienceTargets);
            });
            syncAudienceTargets();

            document.getElementById('surveyId')?.addEventListener('change', async (e) => {
                const id = e.target.value;
                await loadSurveyMeta();
                if (!id) return;
                try {
                    const res = await fetch(`${routes.surveyTemplate}/${id}/template`, { headers: { Accept: 'application/json' } });
                    const json = await res.json();
                    if (json.ok && json.template) {
                        document.getElementById('messageBody').value = json.template;
                    } else if (json.message) {
                        showFormError(json.message);
                    }
                } catch (_) {}
            });

            const personnelSearch = document.getElementById('personnelSearch');
            let searchTimer;
            personnelSearch?.addEventListener('input', () => {
                clearTimeout(searchTimer);
                const q = personnelSearch.value.trim();
                if (q.length < 2) {
                    document.getElementById('personnelSearchResults').innerHTML = '';
                    return;
                }
                searchTimer = setTimeout(async () => {
                    const res = await fetch(`${routes.personnelSearch}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                    const json = await res.json();
                    const box = document.getElementById('personnelSearchResults');
                    box.innerHTML = '';
                    (json.items || []).forEach((item) => {
                        const label = document.createElement('label');
                        const checked = selectedPersonnel.has(item.id);
                        label.innerHTML = `<input type="checkbox" value="${item.id}" ${checked ? 'checked' : ''}> <span>${item.name} — ${item.personnel_code} — ${item.mobile}</span>`;
                        label.querySelector('input').addEventListener('change', (ev) => {
                            if (ev.target.checked) selectedPersonnel.set(item.id, item);
                            else selectedPersonnel.delete(item.id);
                            renderChips();
                        });
                        box.appendChild(label);
                    });
                }, 300);
            });

            const renderChips = () => {
                const wrap = document.getElementById('selectedPersonnelChips');
                wrap.innerHTML = '';
                selectedPersonnel.forEach((item) => {
                    const span = document.createElement('span');
                    span.className = 'chip';
                    span.textContent = item.name;
                    wrap.appendChild(span);
                });
            };

            const formDataPayload = () => {
                const fd = new FormData(form);
                if (getTargetingMode() === 'selected_personnel') {
                    fd.delete('personnel_ids[]');
                    selectedPersonnel.forEach((_, id) => fd.append('personnel_ids[]', id));
                }
                return fd;
            };

            const showFormError = (msg) => {
                const el = document.getElementById('formErrors');
                el.style.display = 'block';
                el.textContent = typeof msg === 'string' ? msg : Object.values(msg).flat().join(' ');
            };
            const hideFormError = () => {
                const el = document.getElementById('formErrors');
                el.style.display = 'none';
            };

            document.getElementById('btnPreview')?.addEventListener('click', async () => {
                hideFormError();
                const mode = getTargetingMode();
                const surveyId = document.getElementById('surveyId')?.value;
                if (mode === 'survey_eligible' && !surveyId) {
                    showFormError('برای «مخاطبان مجاز نظرسنجی»، ابتدا نظرسنجی را انتخاب کنید.');
                    return;
                }
                previewBox.classList.remove('is-visible');
                previewAck.checked = false;
                btnDraft.disabled = true;
                const res = await fetch(routes.preview, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: formDataPayload(),
                });
                const json = await res.json();
                if (!json.ok) {
                    showFormError(json.errors || 'خطا در پیش‌نمایش');
                    return;
                }
                const d = json.data;
                lastPreviewData = d;
                previewChecksum = d.checksum;
                let summary = `${d.recipient_count} گیرنده — ${d.targeting_label}`;
                if (d.survey_title) summary += ` — ${d.survey_title}`;
                if (d.audience_summary) summary += ` | فیلتر: ${d.audience_summary}`;
                document.getElementById('previewSummary').textContent = summary;
                const tbody = document.getElementById('previewTableBody');
                tbody.innerHTML = '';
                d.recipients.forEach((row, i) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${i + 1}</td><td>${row.name || '—'}</td><td>${row.personnel_code || '—'}</td><td dir="ltr">${row.mobile}</td>`;
                    tbody.appendChild(tr);
                });
                previewBox.classList.add('is-visible');
            });

            previewAck?.addEventListener('change', () => {
                btnDraft.disabled = !previewAck.checked;
            });

            document.getElementById('btnDraft')?.addEventListener('click', async () => {
                if (btnDraft.disabled || btnDraft.dataset.loading === '1') return;
                hideFormError();
                btnDraft.dataset.loading = '1';
                btnDraft.disabled = true;
                const fd = formDataPayload();
                fd.append('recipients_checksum', previewChecksum);
                const res = await fetch(routes.store, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: fd,
                });
                const json = await res.json();
                if (!json.ok) {
                    showFormError(json.errors || 'خطا در ثبت پیش‌نویس');
                    btnDraft.dataset.loading = '0';
                    btnDraft.disabled = !previewAck.checked;
                    return;
                }
                campaignId = json.campaign.id;
                confirmPhrase = json.campaign.confirm_phrase || '';
                document.getElementById('confirmSummary').textContent =
                    `ارسال ${json.campaign.recipient_count} پیامک${json.campaign.survey_title ? ' برای «' + json.campaign.survey_title + '»' : ''}. این عمل قابل بازگشت نیست.`;
                document.getElementById('confirmPhraseDisplay').textContent = confirmPhrase;
                document.getElementById('confirmPhraseInput').value = '';
                lockedConfirmPhrase = '';
                renderModalReview(lastPreviewData);
                openModal();
            });

            const modal = document.getElementById('confirmModal');
            const openModal = () => {
                modal.classList.add('is-open');
                document.getElementById('modalAck').checked = false;
                document.getElementById('btnStep1Next').disabled = true;
                document.getElementById('phraseStepError').style.display = 'none';
                document.getElementById('sendErrors').style.display = 'none';
                setStep(1);
            };
            const closeModal = () => {
                if (sendInProgress) return;
                modal.classList.remove('is-open');
            };
            modal.querySelectorAll('[data-close-modal]').forEach((b) => b.addEventListener('click', closeModal));

            const setStep = (n) => {
                modal.querySelectorAll('.confirm-step').forEach((s) => {
                    s.classList.toggle('is-active', parseInt(s.dataset.step, 10) === n);
                });
            };
            document.getElementById('modalAck')?.addEventListener('change', (e) => {
                document.getElementById('btnStep1Next').disabled = !e.target.checked;
            });
            document.getElementById('btnStep1Next')?.addEventListener('click', () => setStep(2));

            document.getElementById('btnCopyPhrase')?.addEventListener('click', async () => {
                const text = confirmPhrase || document.getElementById('confirmPhraseDisplay')?.textContent || '';
                try {
                    await navigator.clipboard.writeText(text);
                    document.getElementById('confirmPhraseInput').value = text;
                } catch (_) {
                    document.getElementById('confirmPhraseInput').value = text;
                    document.getElementById('confirmPhraseInput').select();
                }
            });

            document.getElementById('btnStep2Next')?.addEventListener('click', () => {
                const errEl = document.getElementById('phraseStepError');
                errEl.style.display = 'none';
                const input = normalizePhrase(document.getElementById('confirmPhraseInput').value);
                const expected = normalizePhrase(confirmPhrase);
                if (!input) {
                    errEl.style.display = 'block';
                    errEl.textContent = 'لطفاً عبارت تأیید را وارد کنید یا دکمه «کپی» را بزنید.';
                    return;
                }
                if (input !== expected) {
                    errEl.style.display = 'block';
                    errEl.textContent = 'عبارت واردشده مطابقت ندارد. از دکمه کپی استفاده کنید یا عبارت نمایش‌داده‌شده را دقیق بنویسید.';
                    return;
                }
                lockedConfirmPhrase = input;
                setStep(3);
            });
            modal.querySelectorAll('[data-prev-step]').forEach((b) => {
                b.addEventListener('click', () => {
                    const cur = modal.querySelector('.confirm-step.is-active');
                    const n = Math.max(1, parseInt(cur?.dataset.step || '1', 10) - 1);
                    setStep(n);
                });
            });

            const progressModal = document.getElementById('progressModal');
            const progressCard = document.getElementById('progressModalCard');
            let sendInProgress = false;

            const toFaNum = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

            const openProgressModal = (total) => {
                document.getElementById('progressError').style.display = 'none';
                document.getElementById('progressRemainingCount').textContent = String(total);
                document.getElementById('progressSentCount').textContent = '0';
                document.getElementById('progressFailedCount').textContent = '0';
                document.getElementById('progressPercent').textContent = '۰٪';
                document.getElementById('progressBarInner').style.width = '0%';
                document.getElementById('progressCurrent').textContent = 'در حال آماده‌سازی…';
                document.getElementById('progressCurrent').className = 'progress-current';
                progressCard.classList.remove('is-done');
                progressCard.classList.add('is-sending');
                document.getElementById('progressModalIcon').className = 'fa-solid fa-paper-plane';
                document.getElementById('progressModalTitle').textContent = 'در حال ارسال پیامک‌ها';
                document.getElementById('progressStatusText').textContent = 'لطفاً این پنجره را تا پایان ارسال نبندید.';
                progressModal.classList.add('is-open');
                progressModal.setAttribute('aria-hidden', 'false');
            };

            const updateProgressUI = (data) => {
                const pct = Math.min(100, Math.max(0, data.percent || 0));
                document.getElementById('progressPercent').textContent = toFaNum(pct) + '٪';
                document.getElementById('progressBarInner').style.width = pct + '%';
                document.getElementById('progressSentCount').textContent = toFaNum(data.sent || 0);
                document.getElementById('progressFailedCount').textContent = toFaNum(data.failed || 0);
                document.getElementById('progressRemainingCount').textContent = toFaNum(data.remaining || 0);
                const cur = document.getElementById('progressCurrent');
                if (data.current) {
                    const label = data.current.status === 'sent' ? 'ارسال شد' : 'ناموفق';
                    cur.className = 'progress-current ' + (data.current.status === 'sent' ? 'is-ok' : 'is-fail');
                    cur.innerHTML = `<strong>${label}:</strong> ${data.current.name || '—'}<br><span dir="ltr">${data.current.mobile}</span>${data.current.error ? '<br><small>' + data.current.error + '</small>' : ''}`;
                }
            };

            const finishProgressUI = (data) => {
                sendInProgress = false;
                progressCard.classList.remove('is-sending');
                progressCard.classList.add('is-done');
                document.getElementById('progressModalIcon').className = 'fa-solid fa-circle-check';
                document.getElementById('progressModalTitle').textContent = 'ارسال به پایان رسید';
                document.getElementById('progressStatusText').textContent =
                    `${toFaNum(data.sent)} پیامک موفق و ${toFaNum(data.failed)} ناموفق از ${toFaNum(data.total)} گیرنده.`;
                document.getElementById('progressBarInner').style.width = '100%';
                document.getElementById('progressPercent').textContent = '۱۰۰٪';
                document.getElementById('progressCurrent').textContent = 'تمام گیرندگان پردازش شدند.';
                document.getElementById('progressCurrent').className = 'progress-current is-ok';
            };

            const runProgressiveSend = async (id, total) => {
                sendInProgress = true;
                openProgressModal(total);
                try {
                    while (true) {
                        const res = await fetch(`${routes.sendStep}/${id}/send-step`, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: '{}',
                        });
                        const json = await res.json();
                        if (!json.ok) {
                            throw new Error(json.message || 'خطا در ارسال پیامک');
                        }
                        updateProgressUI(json.data);
                        if (json.data.done) {
                            finishProgressUI(json.data);
                            break;
                        }
                    }
                } catch (err) {
                    sendInProgress = false;
                    progressCard.classList.remove('is-sending');
                    document.getElementById('progressError').style.display = 'block';
                    document.getElementById('progressError').textContent = err.message || 'خطای غیرمنتظره';
                    document.getElementById('progressModalTitle').textContent = 'ارسال متوقف شد';
                    document.getElementById('btnProgressClose').style.display = 'inline-flex';
                    document.getElementById('btnProgressClose').textContent = 'بستن';
                }
            };

            window.addEventListener('beforeunload', (e) => {
                if (sendInProgress) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            document.getElementById('btnProgressClose')?.addEventListener('click', () => {
                window.location.href = routes.historyUrl;
            });

            const btnFinalSend = document.getElementById('btnFinalSend');
            btnFinalSend?.addEventListener('click', async () => {
                if (btnFinalSend.disabled || btnFinalSend.dataset.loading === '1') return;
                const errEl = document.getElementById('sendErrors');
                errEl.style.display = 'none';
                if (!lockedConfirmPhrase) {
                    errEl.style.display = 'block';
                    errEl.textContent = 'ابتدا مرحلهٔ عبارت تأیید را تکمیل کنید.';
                    setStep(2);
                    return;
                }
                const password = document.getElementById('adminPasswordConfirm').value;
                if (!password) {
                    errEl.style.display = 'block';
                    errEl.textContent = 'رمز عبور مدیر را وارد کنید.';
                    return;
                }
                btnFinalSend.dataset.loading = '1';
                btnFinalSend.disabled = true;
                const res = await fetch(`${routes.send}/${campaignId}/send`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        confirm_phrase: lockedConfirmPhrase,
                        admin_password: password,
                        acknowledged: true,
                    }),
                });
                const json = await res.json();
                btnFinalSend.dataset.loading = '0';
                btnFinalSend.disabled = false;
                if (!json.ok) {
                    const errors = json.errors || {};
                    const parts = [];
                    if (errors.confirm_phrase) parts.push(errors.confirm_phrase.join(' '));
                    if (errors.admin_password) parts.push(errors.admin_password.join(' '));
                    if (errors.campaign) parts.push(errors.campaign.join(' '));
                    errEl.style.display = 'block';
                    errEl.textContent = parts.join(' ') || 'خطا در تأیید';
                    if (errors.confirm_phrase) setStep(2);
                    return;
                }
                closeModal();
                await runProgressiveSend(json.campaign_id, json.total || 0);
            });
        });
    </script>
@endsection

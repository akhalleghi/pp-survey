<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $survey->title }}</title>
    <link rel="stylesheet" href="/fonts/vazirmatn/vazirmatn.css">
    <link rel="stylesheet" href="{{ asset('vendor/persian-datepicker-behzadi/persianDatepicker-default.css') }}">
    @php
        $surveyBackground = $survey->background_image ? asset($survey->background_image) : null;
        $defaultThankYou = 'از مشارکت شما سپاسگزاریم.';
        $themeColors = \App\Support\AppSettings::get('colors', []);
        $primary = $themeColors['primary'] ?? '#D61119';
        $primaryDark = $themeColors['primary_dark'] ?? '#ab0c12';
        $slate = $themeColors['slate'] ?? '#0F172A';
        $muted = $themeColors['muted'] ?? '#6B7280';
        $background = $themeColors['background'] ?? '#f4f5f7';
        $textPrimary = $themeColors['text_primary'] ?? '#111827';
        $surveyFooterText = \App\Support\AppSettings::get('survey_footer_text', 'طراحی و توسعه توسط واحد فناوری اطلاعات توسعه نرم افزار');
        $hexToRgb = static function (string $hex): string {
            $clean = ltrim(trim($hex), '#');
            if (strlen($clean) === 3) {
                $clean = $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2];
            }
            if (strlen($clean) !== 6 || !ctype_xdigit($clean)) {
                return '15,23,42';
            }
            return hexdec(substr($clean, 0, 2)) . ',' . hexdec(substr($clean, 2, 2)) . ',' . hexdec(substr($clean, 4, 2));
        };
        $primaryRgb = $hexToRgb($primary);
        $primaryDarkRgb = $hexToRgb($primaryDark);
        $toFaDigits = static function (int|string $value): string {
            return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        };
        $publicTheme = array_merge(\App\Models\Survey::defaultPublicTheme(), $survey->public_theme ?? []);
        $pubCss = static function (?string $v): string {
            $v = trim((string) $v);
            $v = str_replace(["\n", "\r", '"', "'", '<', '>', ';', '{', '}'], '', $v);

            return strlen($v) > 80 ? substr($v, 0, 80) : $v;
        };
    @endphp
    <style>
        :root {
            --survey-bg-image: none;
            --primary: {{ $primary }};
            --primary-dark: {{ $primaryDark }};
            --slate: {{ $slate }};
            --muted: {{ $muted }};
            --surface-bg: {{ $background }};
            --text-primary: {{ $textPrimary }};
            --primary-rgb: {{ $primaryRgb }};
            --primary-dark-rgb: {{ $primaryDarkRgb }};
            --pub-card-bg: {{ $pubCss($publicTheme['card_bg'] ?? '') }};
            --pub-card-border: {{ $pubCss($publicTheme['card_border'] ?? '') }};
            --pub-title: {{ $pubCss($publicTheme['title'] ?? '') }};
            --pub-body: {{ $pubCss($publicTheme['body'] ?? '') }};
            --pub-muted: {{ $pubCss($publicTheme['muted'] ?? '') }};
            --pub-required-star: {{ $pubCss($publicTheme['required_star'] ?? '') }};
            --pub-input-bg: {{ $pubCss($publicTheme['input_bg'] ?? '') }};
            --pub-input-border: {{ $pubCss($publicTheme['input_border'] ?? '') }};
            --pub-input-text: {{ $pubCss($publicTheme['input_text'] ?? '') }};
            --pub-input-placeholder: {{ $pubCss($publicTheme['input_placeholder'] ?? '') }};
            --pub-option-hover: {{ $pubCss($publicTheme['option_hover'] ?? '') }};
            --pub-error: {{ $pubCss($publicTheme['error_color'] ?? '') }};
            --pub-rating-wrap-bg: {{ $pubCss($publicTheme['rating_wrap_bg'] ?? '') }};
            --pub-rating-wrap-border: {{ $pubCss($publicTheme['rating_wrap_border'] ?? '') }};
            --pub-footer-percent: {{ $pubCss($publicTheme['footer_percent'] ?? '') }};
            --pub-track-bg: {{ $pubCss($publicTheme['track_bg'] ?? '') }};
            --pub-fill: {{ $pubCss($publicTheme['fill'] ?? '') }};
            --pub-nav-prev: {{ $pubCss($publicTheme['nav_prev'] ?? '') }};
            --pub-nav-next: {{ $pubCss($publicTheme['nav_next'] ?? '') }};
        }
        @if ($surveyBackground)
        :root { --survey-bg-image: url('{{ $surveyBackground }}'); }
        @endif
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            color: var(--text-primary);
            background-color: var(--surface-bg);
            background-image:
                radial-gradient(circle at top right, rgba(var(--primary-rgb), 0.08), transparent 45%),
                radial-gradient(circle at 20% 10%, rgba(var(--primary-dark-rgb), 0.08), transparent 40%),
                var(--survey-bg-image);
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        .wrap { width: 100%; max-width: 980px; }
        .survey-panel.is-hidden { display: none !important; }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            border: 1px solid rgba(var(--primary-rgb), 0.45);
            padding: clamp(0.85rem, 2vw, 1.25rem);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1);
        }
        .hero-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }
        .hero-head h1 {
            margin: 0 0 0.2rem;
            font-size: clamp(1.1rem, 2.5vw, 1.45rem);
            letter-spacing: -0.01em;
        }
        .helper { color: var(--muted); font-size: 0.84rem; line-height: 1.72; margin: 0; }
        .badge {
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.14), rgba(var(--primary-dark-rgb), 0.08));
            color: var(--primary-dark);
            border: 1px solid rgba(var(--primary-rgb), 0.22);
            padding: 0.3rem 0.68rem;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .survey-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
            gap: 0.55rem;
            margin: 0.75rem 0 0.8rem;
        }
        .meta-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.015), rgba(15, 23, 42, 0.045));
            padding: 0.55rem 0.68rem;
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }
        .meta-card .label { color: var(--muted); font-size: 0.73rem; }
        .meta-card .value { color: var(--text-primary); font-weight: 700; font-size: 0.83rem; }
        .participant-chip {
            margin-top: 0.35rem;
            display: inline-flex;
            align-items: center;
            gap: 0.34rem;
            border-radius: 999px;
            padding: 0.28rem 0.62rem;
            background: rgba(22, 163, 74, 0.12);
            border: 1px solid rgba(22, 163, 74, 0.22);
            color: #166534;
            font-size: 0.74rem;
            font-weight: 700;
        }
        .intro-body {
            margin: 0.75rem 0 0.95rem;
            padding: 0.95rem 1rem;
            line-height: 1.95;
            color: var(--slate);
            font-size: 0.97rem;
            border: 1px solid rgba(15, 23, 42, 0.09);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.02);
        }
        .intro-body p { margin: 0 0 0.75rem; }
        .intro-body p:last-child { margin-bottom: 0; }
        .access-gate {
            margin-top: 0.75rem;
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 14px;
            background: rgba(248, 250, 252, 0.85);
            padding: 0.8rem;
        }
        .access-gate h2 { margin: 0 0 0.18rem; font-size: 0.92rem; }
        .access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.55rem;
            margin-top: 0.6rem;
        }
        .access-grid label { display: flex; flex-direction: column; gap: 0.26rem; font-size: 0.77rem; color: var(--slate); }
        .access-grid input,
        .input,
        .question textarea {
            border: 1px solid rgba(15, 23, 42, 0.14);
            border-radius: 10px;
            padding: 0.58rem 0.7rem;
            font-family: inherit;
            font-size: 0.84rem;
            width: 100%;
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }
        .access-grid input:focus,
        .input:focus,
        .question textarea:focus {
            outline: none;
            border-color: rgba(var(--primary-rgb), 0.45);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }
        .access-error { margin-top: 0.5rem; color: var(--primary-dark); font-size: 0.75rem; }
        .btn {
            border: none;
            border-radius: 12px;
            padding: 0.62rem 1rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.83rem;
            transition: transform 0.16s ease, box-shadow 0.16s ease, opacity 0.16s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn.primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 8px 18px rgba(var(--primary-rgb), 0.24);
        }
        .btn.ghost { background: rgba(15, 23, 42, 0.09); color: var(--slate); }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: none; transform: none; }
        .wizard-head { margin-bottom: 0.28rem; }
        .question {
            border: 1px solid rgba(15, 23, 42, 0.09);
            border-radius: 14px;
            padding: 0.82rem;
            margin-top: 0.62rem;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            box-shadow: inset 0 1px 0 rgba(15, 23, 42, 0.03);
        }
        .question.active { display: block; }
        .question.error { border-color: rgba(220, 38, 38, 0.62); box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.18); }
        .q-title { margin: 0 0 0.28rem; font-size: 0.94rem; }
        .required-badge {
            display: inline-flex;
            align-items: center;
            margin-right: 0.35rem;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            background: rgba(220, 38, 38, 0.12);
            color: #b91c1c;
            border: 1px solid rgba(220, 38, 38, 0.22);
        }
        .q-desc { margin: 0 0 0.45rem; color: var(--muted); font-size: 0.79rem; }
        .option-list { display: flex; flex-direction: column; gap: 0.34rem; margin-top: 0.35rem; }
        .option-list label {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            border-radius: 8px;
            padding: 0.21rem 0.3rem;
            font-size: 0.82rem;
            transition: background 0.14s ease;
        }
        .option-list label:hover { background: rgba(15, 23, 42, 0.05); }
        .rating-slider-wrap {
            margin-top: 0.35rem;
            padding: 0.6rem 0.65rem;
            border: 1px solid rgba(15, 23, 42, 0.1);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.03);
        }
        .rating-current {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--slate);
            margin-bottom: 0.45rem;
        }
        .rating-slider {
            width: 100%;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .rating-ends {
            display: flex;
            justify-content: space-between;
            gap: 0.55rem;
            margin-top: 0.28rem;
            font-size: 0.73rem;
            color: var(--muted);
        }
        .rating-ends span {
            max-width: 48%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .error-text { color: var(--primary-dark); font-size: 0.74rem; margin-top: 0.34rem; }
        .wizard-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.55rem;
            margin-top: 0.75rem;
        }
        .progress { margin-top: 0.75rem; }
        .progress-bar {
            height: 8px;
            background: rgba(15, 23, 42, 0.09);
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-bar span {
            display: block;
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }
        .progress-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.74rem;
            color: var(--muted);
            margin-top: 0.3rem;
        }
        .complete-wrap { text-align: center; }
        .complete-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.6rem;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .complete-msg { color: var(--slate); line-height: 1.8; margin: 0.38rem 0 0; font-size: 0.88rem; }
        .survey-footer-note {
            margin-top: 0.55rem;
            text-align: center;
            font-size: 0.66rem;
            color: rgba(15, 23, 42, 0.55);
            letter-spacing: 0.01em;
            user-select: none;
        }
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
        body:has(#surveyWizard:not(.is-hidden)) {
            padding-bottom: calc(6rem + env(safe-area-inset-bottom, 0px));
        }
        .survey-wizard-root {
            width: 100%;
            max-width: 560px;
            margin-inline: auto;
        }
        .survey-wizard-form {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            backdrop-filter: none !important;
            padding: 0 !important;
            display: flex;
            flex-direction: column;
            min-height: min(calc(100dvh - 5.5rem), 820px);
        }
        .survey-wizard-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: stretch;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0.75rem 0.2rem 1rem;
            color: inherit;
        }
        .survey-wizard-card {
            background: var(--pub-card-bg);
            border: 1px solid var(--pub-card-border);
            border-radius: 20px;
            padding: clamp(0.9rem, 2.5vw, 1.15rem);
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.12);
        }
        .survey-wizard-top {
            margin-bottom: 1.05rem;
            text-align: right;
        }
        .survey-wizard-title {
            margin: 0 0 0.35rem;
            font-size: clamp(1rem, 3.5vw, 1.18rem);
            font-weight: 700;
            color: var(--pub-title);
            line-height: 1.45;
        }
        .survey-wizard-desc {
            margin: 0;
            font-size: 0.84rem;
            color: var(--pub-muted);
            line-height: 1.65;
        }
        .survey-wizard-root .participant-chip {
            background: rgba(22, 163, 74, 0.1);
            border-color: rgba(22, 163, 74, 0.28);
            color: #166534;
            margin-bottom: 1rem;
        }
        .survey-wizard-root .wizard-question {
            border: none;
            background: transparent;
            box-shadow: none;
            padding: 0.2rem 0;
            margin-top: 0;
        }
        .q-section-line {
            margin: 0 0 0.75rem;
            font-size: clamp(0.95rem, 3vw, 1.06rem);
            font-weight: 700;
            color: var(--pub-title);
            letter-spacing: -0.02em;
            line-height: 1.55;
        }
        .wizard-q-title {
            margin: 0 0 0.9rem;
            font-size: clamp(0.98rem, 3.1vw, 1.14rem);
            font-weight: 600;
            color: var(--pub-title);
            line-height: 1.75;
        }
        .wizard-q-title--merged {
            font-size: clamp(1rem, 3.2vw, 1.16rem);
            font-weight: 700;
        }
        .wizard-q-title .q-step-num { font-weight: 800; }
        .wizard-q-title .q-step-sep {
            font-weight: 600;
            opacity: 0.75;
        }
        .q-required-star {
            color: var(--pub-required-star);
            font-weight: 700;
            margin-right: 0.2rem;
        }
        .survey-wizard-root .q-desc {
            color: var(--pub-muted);
            margin: -0.45rem 0 0.85rem;
            font-size: 0.84rem;
        }
        .survey-wizard-root .input,
        .survey-wizard-root .jalali-answer-display {
            background: var(--pub-input-bg);
            border: 1px solid var(--pub-input-border);
            border-radius: 14px;
            color: var(--pub-input-text);
            padding: 0.78rem 0.95rem;
            font-size: 0.95rem;
        }
        .survey-wizard-root .input::placeholder,
        .survey-wizard-root .jalali-answer-display::placeholder {
            color: var(--pub-input-placeholder);
        }
        .survey-wizard-root .input:focus,
        .survey-wizard-root .jalali-answer-display:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.14);
        }
        .survey-wizard-root .option-list label {
            color: var(--pub-title);
            border-radius: 12px;
            padding: 0.48rem 0.55rem;
        }
        .survey-wizard-root .option-list label:hover {
            background: var(--pub-option-hover);
        }
        .survey-wizard-root .option-list input {
            accent-color: var(--primary);
        }
        .survey-wizard-root .rating-slider-wrap {
            border: 1px solid var(--pub-rating-wrap-border);
            background: var(--pub-rating-wrap-bg);
        }
        .survey-wizard-root .rating-current,
        .survey-wizard-root .rating-ends {
            color: var(--pub-muted);
        }
        .survey-wizard-root .rating-slider {
            accent-color: var(--primary);
        }
        .survey-wizard-root .error-text {
            color: var(--pub-error);
        }
        .survey-wizard-root .wizard-card-errors {
            color: var(--pub-error);
            background: rgba(254, 226, 226, 0.85);
            border: 1px solid rgba(248, 113, 113, 0.45);
            border-radius: 14px;
            padding: 0.65rem 0.85rem;
            margin-bottom: 0.75rem;
            font-size: 0.86rem;
            font-weight: 600;
        }
        .wizard-footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 50;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 0.55rem 0.85rem calc(0.7rem + env(safe-area-inset-bottom, 0px));
            background: linear-gradient(180deg, transparent 0%, rgba(15, 23, 42, 0.38) 28%, rgba(15, 23, 42, 0.78) 100%);
            pointer-events: none;
        }
        .wizard-footer-bar > * {
            pointer-events: auto;
        }
        .wizard-footer-progress {
            flex: 1;
            min-width: 0;
            text-align: right;
            padding-bottom: 0.1rem;
        }
        .wizard-percent-label {
            display: block;
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--pub-footer-percent);
            margin-bottom: 0.42rem;
            text-shadow: 0 1px 10px rgba(0, 0, 0, 0.28);
        }
        .wizard-progress-track {
            height: 5px;
            border-radius: 999px;
            background: var(--pub-track-bg);
            overflow: hidden;
        }
        .wizard-progress-fill {
            display: block;
            height: 100%;
            width: 0%;
            border-radius: inherit;
            background: var(--pub-fill);
            transition: width 0.3s ease;
        }
        .wizard-nav-stack {
            display: flex;
            flex-direction: column;
            gap: 0.38rem;
            flex-shrink: 0;
        }
        .wizard-nav-btn {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: var(--pub-nav-prev);
            color: #fff;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.32);
            transition: transform 0.14s ease, opacity 0.14s ease;
        }
        .wizard-nav-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        .wizard-nav-btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }
        .wizard-nav-btn--next {
            background: var(--pub-nav-next);
        }
        @keyframes wizard-finish-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.55); }
            55% { box-shadow: 0 0 0 14px rgba(220, 38, 38, 0); }
        }
        .wizard-next-finish {
            display: none;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            font-weight: 800;
            font-size: 0.82rem;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }
        .wizard-nav-btn--next.wizard-nav-btn--is-finish {
            background: #dc2626 !important;
            color: #fff;
            width: auto;
            min-width: 4.6rem;
            height: 48px;
            padding: 0 0.72rem;
            animation: wizard-finish-pulse 1.65s ease-in-out infinite;
        }
        .wizard-nav-btn--next.wizard-nav-btn--is-finish:disabled {
            animation: none;
            opacity: 0.85;
        }
        .wizard-nav-btn--next.wizard-nav-btn--is-finish .wizard-next-icon {
            display: none;
        }
        .wizard-nav-btn--next.wizard-nav-btn--is-finish .wizard-next-finish {
            display: block;
        }
        .survey-wizard-empty {
            color: var(--pub-muted);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        @media (max-width: 820px) {
            body { padding: 0.72rem; }
            .wrap { max-width: 100%; }
            .card { border-radius: 16px; padding: 0.78rem; }
            .hero-head { flex-direction: column; gap: 0.45rem; }
            .badge { align-self: flex-start; }
            .survey-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .intro-body { font-size: 0.93rem; padding: 0.85rem 0.88rem; }
        }
        @media (max-width: 560px) {
            .survey-meta {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                gap: 0.45rem;
                margin: 0.6rem 0 0.75rem;
                padding-bottom: 0.15rem;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
            }
            .meta-card {
                min-width: max-content;
                flex: 0 0 auto;
                border-radius: 999px;
                padding: 0.34rem 0.62rem;
                display: inline-flex;
                flex-direction: row;
                align-items: center;
                gap: 0.35rem;
                background: rgba(15, 23, 42, 0.055);
            }
            .meta-card .label,
            .meta-card .value {
                font-size: 0.7rem;
                line-height: 1.2;
                white-space: nowrap;
            }
            .meta-card .value {
                font-weight: 800;
            }
            .access-grid { grid-template-columns: 1fr; }
            .wizard-actions { flex-direction: column-reverse; }
            .wizard-actions .btn { width: 100%; }
            .progress-meta { flex-direction: column; gap: 0.14rem; }
            .hero-head h1 { font-size: 1.06rem; }
            .helper { font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        @if ($showIntroStep)
            <div id="surveyIntro" class="survey-panel card @if ($errors->any()) is-hidden @endif">
                <div class="hero-head">
                    <div>
                        <h1>{{ $survey->title }}</h1>
                        @if ($survey->description)
                            <p class="helper">{{ $survey->description }}</p>
                        @endif
                    </div>
                    <span class="badge">آماده شروع</span>
                </div>

                <div class="survey-meta">
                    <div class="meta-card">
                        <span class="label">تعداد سوالات</span>
                        <span class="value">{{ $questionsCount }} سوال</span>
                    </div>
                    <div class="meta-card">
                        <span class="label">زمان تقریبی تکمیل</span>
                        <span class="value">{{ number_format($estimatedDurationMinutes) }} دقیقه</span>
                    </div>
                    <div class="meta-card">
                        <span class="label">نحوه پاسخ‌دهی</span>
                        <span class="value">مرحله‌ای، سوال‌به‌سوال</span>
                    </div>
                </div>

                @if ($participantDisplayName && $audiencePassed)
                    <div class="participant-chip">✓ پاسخ‌دهنده تایید شد: {{ $participantDisplayName }}</div>
                @endif

                @if (filled($survey->intro_text))
                    <div class="intro-body">{!! nl2br(e($survey->intro_text)) !!}</div>
                @endif

                @if ($showAccessGate && !$audiencePassed)
                    <form class="access-gate" method="GET" action="{{ route('surveys.public.show', $survey->public_token) }}">
                        <h2>تایید اطلاعات پرسنلی</h2>
                        <p class="helper">برای ورود به فرم، اطلاعات مورد نیاز را وارد کنید.</p>
                        <div class="access-grid">
                            @if (in_array($identityMode, ['personnel_code', 'either'], true))
                                <label>
                                    <span>کد پرسنلی</span>
                                    <input type="text" name="personnel_code" value="{{ $submittedPersonnelCode }}" autocomplete="off">
                                </label>
                            @endif
                            @if (in_array($identityMode, ['national_code', 'either'], true))
                                <label>
                                    <span>کد ملی</span>
                                    <input type="text" name="national_code" value="{{ $submittedNationalCode }}" autocomplete="off">
                                </label>
                            @endif
                        </div>
                        @if ($accessError)
                            <div class="access-error">{{ $accessError }}</div>
                        @endif
                        <div style="text-align: center; margin-top: 0.75rem;">
                            <button type="submit" class="btn primary" style="min-width: 200px;">بررسی و شروع</button>
                        </div>
                    </form>
                @else
                    <div style="text-align: center; margin-top: 0.25rem;">
                        <button type="button" class="btn primary" id="startSurveyBtn" style="min-width: 220px;">شروع نظرسنجی</button>
                    </div>
                @endif
            </div>
        @endif

        @if (!$showIntroStep && $showAccessGate && !$audiencePassed)
            <div id="surveyAccessOnly" class="survey-panel card">
                <div class="hero-head">
                    <div>
                        <h1>{{ $survey->title }}</h1>
                        @if ($survey->description)
                            <p class="helper">{{ $survey->description }}</p>
                        @endif
                    </div>
                    <span class="badge">تایید هویت</span>
                </div>
                <div class="survey-meta">
                    <div class="meta-card">
                        <span class="label">تعداد سوالات</span>
                        <span class="value">{{ $questionsCount }} سوال</span>
                    </div>
                    <div class="meta-card">
                        <span class="label">زمان تقریبی تکمیل</span>
                        <span class="value">{{ number_format($estimatedDurationMinutes) }} دقیقه</span>
                    </div>
                </div>
                <form class="access-gate" method="GET" action="{{ route('surveys.public.show', $survey->public_token) }}">
                    <h2>تایید اطلاعات پرسنلی</h2>
                    <p class="helper">برای ورود به فرم، اطلاعات مورد نیاز را وارد کنید.</p>
                    <div class="access-grid">
                        @if (in_array($identityMode, ['personnel_code', 'either'], true))
                            <label>
                                <span>کد پرسنلی</span>
                                <input type="text" name="personnel_code" value="{{ $submittedPersonnelCode }}" autocomplete="off">
                            </label>
                        @endif
                        @if (in_array($identityMode, ['national_code', 'either'], true))
                            <label>
                                <span>کد ملی</span>
                                <input type="text" name="national_code" value="{{ $submittedNationalCode }}" autocomplete="off">
                            </label>
                        @endif
                    </div>
                    @if ($accessError)
                        <div class="access-error">{{ $accessError }}</div>
                    @endif
                    <div style="text-align: center; margin-top: 0.75rem;">
                        <button type="submit" class="btn primary" style="min-width: 200px;">بررسی و ورود</button>
                    </div>
                </form>
            </div>
        @endif

        <div id="surveyWizard" class="survey-panel survey-wizard-root @if (($showIntroStep || ($showAccessGate && !$audiencePassed)) && !$errors->any()) is-hidden @endif">
            <form class="survey-wizard-form" id="surveyForm" method="POST" action="{{ route('surveys.public.submit', $survey->public_token) }}">
                @csrf
                <input type="hidden" name="personnel_code" value="{{ $submittedPersonnelCode }}">
                <input type="hidden" name="national_code" value="{{ $submittedNationalCode }}">

                <div class="survey-wizard-body">
                    <div class="survey-wizard-card">
                @if ($errors->any())
                    <div class="wizard-card-errors" role="alert">
                        {{ $errors->first() ?: 'لطفا موارد ضروری فرم را تکمیل کنید.' }}
                    </div>
                @endif
                    <div class="survey-wizard-top">
                        <h1 class="survey-wizard-title">{{ $survey->title }}</h1>
                        @if ($survey->description)
                            <p class="survey-wizard-desc">{{ $survey->description }}</p>
                        @endif
                    </div>

                    @if ($participantDisplayName)
                        <div class="participant-chip">👤 {{ $participantDisplayName }}</div>
                    @endif

                @if ($survey->questions->isEmpty())
                    <p class="survey-wizard-empty">هنوز سوالی برای این نظرسنجی ثبت نشده است.</p>
                @else
                    @foreach ($survey->questions as $question)
                        <div class="question wizard-question" data-question data-question-id="{{ $question->id }}" data-required="{{ $question->is_required ? '1' : '0' }}" data-type="{{ $question->type }}">
                            @if ($question->description)
                                <p class="q-section-line">{{ $toFaDigits($loop->iteration) }} — {{ $question->description }}</p>
                                <h3 class="wizard-q-title">
                                    <span class="q-title-text">{{ $question->title }}</span>@if($question->is_required)<span class="q-required-star" aria-hidden="true">*</span>@endif
                                </h3>
                            @else
                                <h3 class="wizard-q-title wizard-q-title--merged">
                                    <span class="q-step-num">{{ $toFaDigits($loop->iteration) }}</span><span class="q-step-sep"> — </span><span class="q-title-text">{{ $question->title }}</span>@if($question->is_required)<span class="q-required-star" aria-hidden="true">*</span>@endif
                                </h3>
                            @endif

                            @if (in_array($question->type, ['short_text', 'email', 'phone', 'url'], true))
                                <input type="text" class="input" name="answers[{{ $question->id }}][value]" placeholder="{{ $question->type === 'short_text' ? 'حروف فارسی' : 'پاسخ شما' }}"
                                    value="{{ $existingAnswers[$question->id]['text'] ?? '' }}">
                            @elseif ($question->type === 'long_text')
                                <textarea rows="3" class="input" name="answers[{{ $question->id }}][value]" placeholder="پاسخ شما">{{ $existingAnswers[$question->id]['text'] ?? '' }}</textarea>
                            @elseif ($question->type === 'number')
                                <input type="number" class="input" name="answers[{{ $question->id }}][value]" placeholder="عدد"
                                    value="{{ $existingAnswers[$question->id]['number'] ?? '' }}">
                            @elseif ($question->type === 'date')
                                <input
                                    type="text"
                                    class="input jalali-answer-display"
                                    data-hidden-id="answer-date-{{ $question->id }}"
                                    value=""
                                    placeholder="مثلاً 1405/02/08">
                                <input
                                    id="answer-date-{{ $question->id }}"
                                    type="hidden"
                                    name="answers[{{ $question->id }}][value]"
                                    value="{{ $existingAnswers[$question->id]['date'] ?? '' }}">
                            @elseif (in_array($question->type, ['multiple_choice', 'checkboxes', 'dropdown', 'rating', 'yes_no', 'linear_scale'], true))
                                <div class="option-list">
                                    @if ($question->type === 'rating' && $question->options->isNotEmpty())
                                        @php
                                            $ratingOptions = $question->options->values();
                                            $selectedOptionId = (int) ($existingAnswers[$question->id]['option_id'] ?? 0);
                                            $selectedIndex = $ratingOptions->search(fn($item) => (int) $item->id === $selectedOptionId);
                                            if ($selectedIndex === false) {
                                                $selectedIndex = 0;
                                            }
                                        @endphp
                                        <div class="rating-slider-wrap"
                                             data-rating-slider
                                             data-question-id="{{ $question->id }}"
                                             data-option-count="{{ $ratingOptions->count() }}">
                                            <div class="rating-current" data-rating-current>
                                                {{ $ratingOptions[$selectedIndex]->label }}
                                            </div>
                                            <input
                                                type="range"
                                                class="rating-slider"
                                                min="0"
                                                max="{{ max($ratingOptions->count() - 1, 0) }}"
                                                step="1"
                                                value="{{ $selectedIndex }}"
                                                data-rating-range>
                                            <input
                                                type="hidden"
                                                name="answers[{{ $question->id }}][option_id]"
                                                value="{{ $ratingOptions[$selectedIndex]->id }}"
                                                data-rating-option-id>
                                            <div class="rating-ends">
                                                <span title="{{ $ratingOptions->first()?->label }}">{{ $ratingOptions->first()?->label }}</span>
                                                <span title="{{ $ratingOptions->last()?->label }}">{{ $ratingOptions->last()?->label }}</span>
                                            </div>
                                            <script type="application/json" data-rating-options>
                                                {!! $ratingOptions->map(fn($option) => ['id' => (int) $option->id, 'label' => (string) $option->label])->toJson(JSON_UNESCAPED_UNICODE) !!}
                                            </script>
                                        </div>
                                    @elseif ($question->options->isNotEmpty())
                                        @foreach ($question->options as $option)
                                            <label>
                                                @if ($question->type === 'checkboxes')
                                                    <input type="checkbox" name="answers[{{ $question->id }}][option_ids][]" value="{{ $option->id }}"
                                                        @checked(in_array($option->id, $existingAnswers[$question->id]['option_ids'] ?? [], true))>
                                                @else
                                                    <input type="radio" name="answers[{{ $question->id }}][option_id]" value="{{ $option->id }}"
                                                        @checked(($existingAnswers[$question->id]['option_id'] ?? null) == $option->id)>
                                                @endif
                                                {{ $option->label }}
                                            </label>
                                        @endforeach
                                    @elseif ($question->type === 'rating')
                                        @php
                                            $minRating = (int) ($question->settings['min_rating'] ?? 1);
                                            $maxRating = (int) ($question->settings['max_rating'] ?? 5);
                                            if ($minRating < 1) $minRating = 1;
                                            if ($maxRating < $minRating) $maxRating = max($minRating, 5);
                                            $savedRating = (int) ($existingAnswers[$question->id]['number'] ?? 0);
                                        @endphp
                                        @for ($rate = $minRating; $rate <= $maxRating; $rate++)
                                            <label>
                                                <input type="radio" name="answers[{{ $question->id }}][value]" value="{{ $rate }}"
                                                    @checked($savedRating === $rate)>
                                                امتیاز {{ $rate }}
                                            </label>
                                        @endfor
                                    @endif
                                </div>
                            @endif
                            <div class="error-text" hidden>لطفا این سوال را پاسخ دهید.</div>
                        </div>
                    @endforeach
                @endif
                    </div>
                </div>

                @if ($survey->questions->isNotEmpty())
                    <footer class="wizard-footer-bar" role="navigation" aria-label="پیمایش و پیشرفت">
                        <div class="wizard-footer-progress">
                            <span id="progressPercentLabel" class="wizard-percent-label">۰٪ را پاسخ داده‌اید</span>
                            <div class="wizard-progress-track" aria-hidden="true">
                                <span id="progressFill" class="wizard-progress-fill"></span>
                            </div>
                        </div>
                        <div class="wizard-nav-stack">
                            <button type="button" class="wizard-nav-btn" id="prevQuestion" aria-label="سوال قبلی">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                </svg>
                                <span class="sr-only">قبلی</span>
                            </button>
                            <button type="button" class="wizard-nav-btn wizard-nav-btn--next" id="nextQuestion" aria-label="سوال بعدی">
                                <span class="wizard-next-icon" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                                <span class="wizard-next-finish" aria-hidden="true">پایان</span>
                                <span class="sr-only wizard-next-sr">بعدی</span>
                            </button>
                        </div>
                    </footer>
                @endif
            </form>
        </div>

        <div id="surveyComplete" class="survey-panel is-hidden">
            <div class="card complete-wrap">
                <div class="complete-icon" aria-hidden="true">✓</div>
                <h1 style="font-size: 1.35rem; margin-bottom: 0.35rem;">پاسخ های شما باموفقیت ثبت گردیدند</h1>
                <p class="complete-msg" id="thankYouMessage">{{ $survey->thank_you_message ?: $defaultThankYou }}</p>
            </div>
        </div>
        @if (filled($surveyFooterText))
            <footer class="survey-footer-note">{{ $surveyFooterText }}</footer>
        @endif
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
                if (!displayInput || !hiddenInput) return;
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

            const initPersianDatepickerAnswers = () => {
                if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.persianDatepicker) {
                    return;
                }

                document.querySelectorAll('.jalali-answer-display[data-hidden-id]').forEach((displayInput) => {
                    const hiddenInput = document.getElementById(displayInput.dataset.hiddenId || '');
                    if (!hiddenInput) return;

                    syncJalaliDisplayFromHidden(displayInput, hiddenInput);

                    window.jQuery(displayInput).persianDatepicker({
                        formatDate: 'YYYY/0M/0D',
                        closeOnBlur: true,
                        selectedBefore: !!displayInput.value,
                        selectedDate: displayInput.value || null,
                        onSelect: function () {
                            const gDate = displayInput.getAttribute('data-gdate') || displayInput.getAttribute('data-gDate') || '';
                            hiddenInput.value = gDate;
                        },
                    });

                    displayInput.addEventListener('input', () => {
                        if (!displayInput.value.trim()) {
                            hiddenInput.value = '';
                        }
                    });
                });
            };

            initPersianDatepickerAnswers();

            const showIntroStep = @json($showIntroStep);
            const showAccessGate = @json($showAccessGate);
            const audiencePassed = @json($audiencePassed);
            const showCompletedOnLoad = @json($showCompletedOnLoad);
            const allowPartial = @json((bool) $survey->allow_partial);
            const introEl = document.getElementById('surveyIntro');
            const wizardEl = document.getElementById('surveyWizard');
            const completeEl = document.getElementById('surveyComplete');
            const surveyForm = document.getElementById('surveyForm');
            let index = 0;
            window.__allowSurveySubmit = false;
            surveyForm?.addEventListener('submit', (e) => {
                if (!window.__allowSurveySubmit) {
                    e.preventDefault();
                }
            });

            const showWizard = () => {
                introEl?.classList.add('is-hidden');
                wizardEl?.classList.remove('is-hidden');
                wizardEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            document.getElementById('startSurveyBtn')?.addEventListener('click', showWizard);

            if (!showIntroStep && (!showAccessGate || audiencePassed)) {
                introEl?.classList.add('is-hidden');
                wizardEl?.classList.remove('is-hidden');
            }

            const questions = Array.from(document.querySelectorAll('[data-question]'));
            if (!questions.length) {
                if (showCompletedOnLoad) {
                    wizardEl?.classList.add('is-hidden');
                    introEl?.classList.add('is-hidden');
                    completeEl?.classList.remove('is-hidden');
                }
                return;
            }

            const wizardFocusQuestionId = @json($wizardFocusQuestionId ?? null);
            if (wizardFocusQuestionId) {
                const idx = questions.findIndex((el) => String(el.dataset.questionId || '') === String(wizardFocusQuestionId));
                if (idx >= 0) {
                    index = idx;
                }
            }

            const toFaDigitsJs = (num) => String(num).replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

            const isQuestionAnswered = (question) => {
                const required = question.dataset.required === '1';
                if (!required) return true;
                if (question.dataset.type === 'rating') {
                    const hiddenOption = question.querySelector('input[type="hidden"][name$="[option_id]"]');
                    if (hiddenOption && hiddenOption.value.trim().length > 0) {
                        return true;
                    }
                    const ratingValue = question.querySelector('input[name$="[value]"]');
                    if (ratingValue && String(ratingValue.value || '').trim().length > 0) {
                        return true;
                    }
                }
                const hiddenVal = question.querySelector('input[type="hidden"][name$="[value]"]');
                if (hiddenVal && hiddenVal.value.trim().length > 0) {
                    return true;
                }
                const textInputs = question.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], textarea');
                if (textInputs.length) {
                    return Array.from(textInputs).some((input) => input.value.trim().length > 0);
                }
                const optionInputs = question.querySelectorAll('input[type="radio"], input[type="checkbox"]');
                if (optionInputs.length) {
                    return Array.from(optionInputs).some((input) => input.checked);
                }
                return false;
            };

            const hasProgressInput = (question) => {
                if (question.dataset.type === 'rating') {
                    const hiddenOption = question.querySelector('input[type="hidden"][name$="[option_id]"]');
                    if (hiddenOption && hiddenOption.value.trim().length > 0) return true;
                    const ratingValue = question.querySelector('input[name$="[value]"]');
                    if (ratingValue && String(ratingValue.value || '').trim().length > 0) return true;
                }
                const hiddenAns = question.querySelector('input[type="hidden"][name$="[value]"]');
                if (hiddenAns && hiddenAns.value.trim().length > 0) return true;
                const textInputs = question.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], textarea');
                if (textInputs.length) {
                    return Array.from(textInputs).some((input) => input.value.trim().length > 0);
                }
                const optionInputs = question.querySelectorAll('input[type="radio"], input[type="checkbox"]');
                if (optionInputs.length) {
                    return Array.from(optionInputs).some((input) => input.checked);
                }
                return false;
            };

            const getProgressPercent = () => {
                if (!questions.length) return 0;
                let done = 0;
                questions.forEach((q) => {
                    if (q.dataset.required === '1') {
                        if (isQuestionAnswered(q)) done += 1;
                    } else if (hasProgressInput(q)) {
                        done += 1;
                    }
                });
                return Math.round((done / questions.length) * 100);
            };

            const prevBtn = document.getElementById('prevQuestion');
            const nextBtn = document.getElementById('nextQuestion');
            const nextSr = nextBtn?.querySelector('.wizard-next-sr');
            const progressFill = document.getElementById('progressFill');
            const progressPercentLabel = document.getElementById('progressPercentLabel');
            let finished = false;

            const updateProgressUI = () => {
                const p = getProgressPercent();
                if (progressFill) progressFill.style.width = `${p}%`;
                if (progressPercentLabel) {
                    progressPercentLabel.textContent = `${toFaDigitsJs(p)}٪ را پاسخ داده‌اید`;
                }
            };

            const updateWizard = () => {
                questions.forEach((q, i) => q.classList.toggle('active', i === index));
                if (prevBtn) prevBtn.disabled = index === 0;
                if (nextBtn) {
                    const last = index === questions.length - 1;
                    nextBtn.classList.toggle('wizard-nav-btn--is-finish', last);
                    if (nextSr) nextSr.textContent = last ? 'ثبت نهایی پاسخ‌ها' : 'بعدی';
                    nextBtn.setAttribute('aria-label', last ? 'پایان و ثبت پاسخ‌ها' : 'سوال بعدی');
                }
                updateProgressUI();
            };

            const showComplete = () => {
                finished = true;
                wizardEl?.classList.add('is-hidden');
                introEl?.classList.add('is-hidden');
                completeEl?.classList.remove('is-hidden');
                completeEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            const saveDraft = async () => {
                if (!allowPartial || !surveyForm) return;
                try {
                    const formData = new FormData(surveyForm);
                    const res = await fetch(@json(route('surveys.public.draft', $survey->public_token)), {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!res.ok) {
                        throw new Error('draft');
                    }
                } catch (_) {
                    /* ذخیرهٔ خودکار؛ خطا به‌صورت خاموش نادیده گرفته می‌شود */
                }
            };

            prevBtn?.addEventListener('click', () => {
                if (finished) return;
                if (index > 0) {
                    index -= 1;
                    updateWizard();
                }
            });

            nextBtn?.addEventListener('click', () => {
                if (finished) return;
                const current = questions[index];
                const errorText = current?.querySelector('.error-text');
                const answered = current ? isQuestionAnswered(current) : true;
                if (!answered) {
                    current.classList.add('error');
                    if (errorText) errorText.hidden = false;
                    return;
                }
                if (current) {
                    current.classList.remove('error');
                    if (errorText) errorText.hidden = true;
                }

                if (index < questions.length - 1) {
                    index += 1;
                    updateWizard();
                } else if (surveyForm) {
                    nextBtn.disabled = true;
                    if (nextSr) nextSr.textContent = 'در حال ثبت...';
                    nextBtn.setAttribute('aria-label', 'در حال ارسال پاسخ‌ها');
                    window.__allowSurveySubmit = true;
                    surveyForm.submit();
                } else {
                    showComplete();
                }
            });

            document.querySelectorAll('[data-rating-slider]').forEach((wrap) => {
                const rangeInput = wrap.querySelector('[data-rating-range]');
                const hiddenOptionInput = wrap.querySelector('[data-rating-option-id]');
                const currentLabel = wrap.querySelector('[data-rating-current]');
                const optionsScript = wrap.querySelector('[data-rating-options]');
                if (!rangeInput || !hiddenOptionInput || !currentLabel || !optionsScript) {
                    return;
                }
                let options = [];
                try {
                    options = JSON.parse(optionsScript.textContent || '[]');
                } catch (_) {
                    options = [];
                }
                if (!Array.isArray(options) || options.length === 0) {
                    return;
                }
                const syncRating = () => {
                    const idx = Math.max(0, Math.min(options.length - 1, parseInt(rangeInput.value || '0', 10) || 0));
                    const selected = options[idx];
                    hiddenOptionInput.value = String(selected?.id ?? '');
                    currentLabel.textContent = selected?.label ?? '';
                };
                rangeInput.addEventListener('input', syncRating);
                syncRating();
            });
            surveyForm?.addEventListener('change', () => {
                updateProgressUI();
                if (allowPartial) {
                    window.setTimeout(saveDraft, 400);
                }
            });
            surveyForm?.addEventListener('input', () => {
                updateProgressUI();
                if (allowPartial) {
                    window.setTimeout(saveDraft, 400);
                }
            });

            if (showCompletedOnLoad) {
                showComplete();
            }

            updateWizard();
            if (wizardFocusQuestionId) {
                window.setTimeout(() => {
                    questions[index]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 120);
            }
        });
    </script>
</body>
</html>

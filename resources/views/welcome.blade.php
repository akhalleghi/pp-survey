<!DOCTYPE html>
<html lang="fa" dir="rtl" data-app-text-scale="{{ $appTextScale['id'] ?? 'md' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appSettings['app_name'] ?? 'سامانه نظرسنجی' }}</title>
    @include('components.app-font')
    @php
        $themeColors = $appSettings['colors'] ?? \App\Support\AppSettings::get('colors', []);
        $appName = $appSettings['app_name'] ?? 'سامانه نظرسنجی';
        $footerText = $appSettings['survey_footer_text'] ?? 'طراحی و توسعه توسط واحد فناوری اطلاعات توسعه نرم افزار';
        $primaryHex = ltrim($themeColors['primary'] ?? '#D61119', '#');
        $primaryRgb = sscanf($primaryHex, '%02x%02x%02x') ?: [214, 17, 25];
        $primaryRgbString = implode(',', $primaryRgb);
        $bg = $themeColors['welcome_background'] ?? '#F9FAFB';
    @endphp
    <style>
        :root {
            color-scheme: light;
            --primary: {{ $themeColors['primary'] ?? '#D61119' }};
            --primary-dark: {{ $themeColors['primary_dark'] ?? '#a00b11' }};
            --accent-light: {{ $themeColors['accent_light'] ?? '#ffe8e9' }};
            --text-primary: {{ $themeColors['text_primary'] ?? '#1f2937' }};
            --muted: {{ $themeColors['muted'] ?? '#6b7280' }};
            --page-bg: {{ $bg }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            font-family: var(--app-font-family);
            color: var(--text-primary);
            background: var(--page-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .welcome-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
            background: var(--page-bg);
        }
        .welcome-bg__aurora {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 160vmax;
            height: 160vmax;
            margin-left: -80vmax;
            margin-top: -80vmax;
            background: conic-gradient(
                from 0deg at 50% 50%,
                transparent 0deg,
                rgba({{ $primaryRgbString }}, 0.07) 55deg,
                transparent 110deg,
                rgba({{ $primaryRgbString }}, 0.05) 200deg,
                var(--accent-light) 280deg,
                transparent 360deg
            );
            opacity: 0.75;
            animation: welcome-aurora-spin 48s linear infinite;
        }
        .welcome-bg__blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(72px);
            will-change: transform;
        }
        .welcome-bg__blob--1 {
            width: min(85vw, 28rem);
            height: min(85vw, 28rem);
            top: -12%;
            right: -18%;
            background: rgba({{ $primaryRgbString }}, 0.28);
            animation: welcome-blob-1 20s ease-in-out infinite alternate;
        }
        .welcome-bg__blob--2 {
            width: min(75vw, 22rem);
            height: min(75vw, 22rem);
            bottom: -8%;
            left: -14%;
            background: rgba({{ $primaryRgbString }}, 0.18);
            animation: welcome-blob-2 26s ease-in-out infinite alternate;
        }
        .welcome-bg__blob--3 {
            width: min(55vw, 16rem);
            height: min(55vw, 16rem);
            top: 42%;
            left: 50%;
            margin-left: min(-27.5vw, -8rem);
            background: var(--accent-light);
            opacity: 0.9;
            filter: blur(56px);
            animation: welcome-blob-3 18s ease-in-out infinite alternate;
        }
        .welcome-bg__mesh {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 90% 70% at 20% 30%, rgba({{ $primaryRgbString }}, 0.12), transparent 50%),
                radial-gradient(ellipse 80% 60% at 85% 70%, rgba({{ $primaryRgbString }}, 0.1), transparent 45%),
                radial-gradient(ellipse 60% 50% at 50% 100%, var(--accent-light), transparent 55%);
            animation: welcome-mesh-shift 14s ease-in-out infinite alternate;
        }
        .welcome-bg__dots {
            position: absolute;
            inset: 0;
            opacity: 0.35;
            background-image: radial-gradient(rgba({{ $primaryRgbString }}, 0.22) 1px, transparent 1px);
            background-size: 1.75rem 1.75rem;
            mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, #000 20%, transparent 72%);
            animation: welcome-dots-drift 32s linear infinite;
        }
        .welcome-bg__shine {
            position: absolute;
            inset: -40%;
            background: linear-gradient(
                105deg,
                transparent 42%,
                rgba(255, 255, 255, 0.45) 50%,
                transparent 58%
            );
            animation: welcome-shine-sweep 9s ease-in-out infinite;
            opacity: 0.55;
        }
        @keyframes welcome-aurora-spin {
            to { transform: rotate(360deg); }
        }
        @keyframes welcome-blob-1 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-6%, 14%) scale(1.12); }
            100% { transform: translate(4%, 8%) scale(0.96); }
        }
        @keyframes welcome-blob-2 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(12%, -10%) scale(1.08); }
            100% { transform: translate(-8%, 6%) scale(1.04); }
        }
        @keyframes welcome-blob-3 {
            0% { transform: translate(0, 0) scale(1); opacity: 0.85; }
            50% { transform: translate(-12%, -8%) scale(1.15); opacity: 1; }
            100% { transform: translate(10%, 5%) scale(0.92); opacity: 0.8; }
        }
        @keyframes welcome-mesh-shift {
            0% { transform: scale(1) translate(0, 0); opacity: 1; }
            100% { transform: scale(1.06) translate(2%, -2%); opacity: 0.92; }
        }
        @keyframes welcome-dots-drift {
            0% { background-position: 0 0; }
            100% { background-position: 1.75rem 1.75rem; }
        }
        @keyframes welcome-shine-sweep {
            0%, 100% { transform: translateX(-35%) translateY(-8%) rotate(12deg); opacity: 0; }
            45% { opacity: 0.5; }
            55% { opacity: 0.35; }
            70% { transform: translateX(35%) translateY(8%) rotate(12deg); opacity: 0; }
        }
        .welcome-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 22rem;
            text-align: center;
            animation: welcome-in 0.55s ease-out both;
        }
        @keyframes welcome-in {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .welcome-logo {
            width: 5.5rem;
            height: 5.5rem;
            margin: 0 auto 1.15rem;
            border-radius: 1.25rem;
            object-fit: contain;
            background: #fff;
            padding: 0.45rem;
            box-shadow:
                0 1px 2px rgba(15, 23, 42, 0.06),
                0 8px 24px rgba({{ $primaryRgbString }}, 0.12);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }
        .welcome-title {
            margin: 0 0 0.85rem;
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.55;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }
        .welcome-divider {
            width: 2.5rem;
            height: 3px;
            margin: 0 auto 0.9rem;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            opacity: 0.85;
        }
        .welcome-footer {
            margin: 0;
            font-size: 0.8rem;
            line-height: 1.75;
            color: var(--muted);
        }
        @media (min-width: 480px) {
            .welcome-shell { max-width: 24rem; }
            .welcome-logo {
                width: 6.25rem;
                height: 6.25rem;
            }
            .welcome-title { font-size: 1.5rem; }
            .welcome-footer { font-size: 0.85rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .welcome-shell { animation: none; }
            .welcome-bg__aurora,
            .welcome-bg__blob,
            .welcome-bg__mesh,
            .welcome-bg__dots,
            .welcome-bg__shine { animation: none; }
        }
    </style>
</head>
<body>
    <div class="welcome-bg" aria-hidden="true">
        <div class="welcome-bg__aurora"></div>
        <div class="welcome-bg__mesh"></div>
        <div class="welcome-bg__blob welcome-bg__blob--1"></div>
        <div class="welcome-bg__blob welcome-bg__blob--2"></div>
        <div class="welcome-bg__blob welcome-bg__blob--3"></div>
        <div class="welcome-bg__dots"></div>
        <div class="welcome-bg__shine"></div>
    </div>
    <main class="welcome-shell" role="main">
        <img
            class="welcome-logo"
            src="{{ asset($appSettings['logo_path'] ?? 'storage/logo.png') }}"
            alt="لوگوی {{ $appName }}"
            width="100"
            height="100"
            decoding="async"
        >
        <h1 class="welcome-title">{{ $appName }}</h1>
        <div class="welcome-divider" aria-hidden="true"></div>
        @if (trim((string) $footerText) !== '')
            <p class="welcome-footer">{{ $footerText }}</p>
        @endif
    </main>
</body>
</html>

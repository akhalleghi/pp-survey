<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('page-title', 'پنل مدیریت')</title>

    <link href="{{ asset('fonts/vazirmatn/vazirmatn.css') }}" rel="stylesheet">

    @php
        use Illuminate\Support\Str;

        $themeColors = $appSettings['colors'] ?? \App\Support\AppSettings::get('colors', []);
        $systemBg = $appSettings['system_background'] ?? \App\Support\AppSettings::get('system_background', []);
        $systemBgMode = $systemBg['mode'] ?? 'gradient';
        $systemBgImages = array_values(array_filter((array) ($systemBg['images'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
        $systemBgActive = is_string($systemBg['active_image'] ?? null) ? $systemBg['active_image'] : null;
        $systemBgRandom = array_values(array_filter((array) ($systemBg['random_images'] ?? []), static fn ($v) => is_string($v) && in_array($v, $systemBgImages, true)));
        $pickedSystemBg = null;
        if ($systemBgMode === 'single' && $systemBgActive && in_array($systemBgActive, $systemBgImages, true)) {
            $pickedSystemBg = $systemBgActive;
        } elseif ($systemBgMode === 'random') {
            $pool = !empty($systemBgRandom) ? $systemBgRandom : $systemBgImages;
            if (!empty($pool)) {
                $pickedSystemBg = $pool[array_rand($pool)];
            }
        }
        $overlayOpacity = (int) ($systemBg['overlay_opacity'] ?? 35);
        if ($overlayOpacity < 0 || $overlayOpacity > 80) {
            $overlayOpacity = 35;
        }
        $overlayAlpha = number_format($overlayOpacity / 100, 2, '.', '');
        $glassUiEnabled = (bool) ($systemBg['enable_glass_ui'] ?? false);
        $admin = $admin ?? null;
        $navItems = $navItems ?? [];
    @endphp

    <style>
        :root {

            --primary: {{ $themeColors['primary'] ?? '#D61119' }};

            --primary-dark: {{ $themeColors['primary_dark'] ?? '#ab0c12' }};

            --slate: {{ $themeColors['slate'] ?? '#0F172A' }};

            --muted: {{ $themeColors['muted'] ?? '#6B7280' }};

            --sidebar: {{ $themeColors['sidebar'] ?? '#0c111d' }};

        }

        *,
        *::before,
        *::after {

            box-sizing: border-box;

        }

        html {

            overflow-x: hidden;

            max-width: 100%;

        }

        body {

            margin: 0;

            min-height: 100vh;

            font-family: 'Vazirmatn', system-ui, sans-serif;

            @if($pickedSystemBg)
            background:
                linear-gradient(rgba(15, 23, 42, {{ $overlayAlpha }}), rgba(15, 23, 42, {{ $overlayAlpha }})),
                url('{{ asset($pickedSystemBg) }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            @else
            background: {{ $themeColors['background'] ?? '#f4f5f7' }};
            @endif

            color: var(--slate);

            display: flex;

        }

        a {

            text-decoration: none;

        }

        button {

            font-family: inherit;

        }

        .dashboard-shell {

            width: 100%;

            max-width: 100%;

            min-width: 0;

            display: flex;

            min-height: 100vh;

        }

        .sidebar {

            width: 288px;

            background: var(--sidebar);

            color: #f8fafc;

            padding: 2rem 1.5rem 2.5rem;

            display: flex;

            flex-direction: column;

            gap: 2rem;

            position: fixed;

            inset: 0 0 0 auto;

            transform: translateX(0);

            transition: transform 0.3s ease;

            z-index: 40;

        }

        .sidebar-overlay {

            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, 0.35);

            opacity: 0;

            visibility: hidden;

            transition: opacity 0.3s ease;

            z-index: 30;

        }

        .sidebar-overlay.visible {

            opacity: 1;

            visibility: visible;

        }

        .brand {

            display: flex;

            align-items: center;

            gap: 0.9rem;

        }

        .brand img {

            width: 56px;

            height: 56px;

            border-radius: 16px;

            padding: 0.45rem;

            background: rgba(255, 255, 255, 0.08);

            object-fit: contain;

        }

        .brand h1 {

            margin: 0;

            font-size: 1.1rem;

            color: #fff;

        }

        .brand small {

            display: block;

            color: rgba(248, 250, 252, 0.6);

        }

        .nav-section {

            display: flex;

            flex-direction: column;

            gap: 0.4rem;

        }

        .nav-title {

            font-size: 0.8rem;

            letter-spacing: 0.08em;

            color: rgba(248, 250, 252, 0.45);

            text-transform: uppercase;

        }

        .nav-item {

            display: flex;

            align-items: center;

            gap: 0.7rem;

            padding: 0.65rem 0.85rem;

            border-radius: 15px;

            color: rgba(248, 250, 252, 0.85);

            transition: background 0.15s, color 0.15s;

        }

        .nav-item svg {

            width: 20px;

            height: 20px;

        }

        .nav-parent {

            display: flex;

            flex-direction: column;

            gap: 0.3rem;

        }

        .nav-parent-toggle {

            border: none;

            background: none;

            width: 100%;

            display: flex;

            align-items: center;

            gap: 0.7rem;

            padding: 0.65rem 0.85rem;

            border-radius: 15px;

            color: rgba(248, 250, 252, 0.85);

            cursor: pointer;

            transition: background 0.15s, color 0.15s;

            font-size: 1rem;

        }

        .nav-parent-toggle svg {
            width: 20px;
            height: 20px;
        }

        .nav-parent-toggle:hover,

        .nav-parent.expanded .nav-parent-toggle {

            background: rgba(214, 17, 25, 0.2);

            color: #fff;

        }

        .nav-parent-arrow {

            margin-right: auto;

            transition: transform 0.2s ease;

            width: 18px;

            height: 18px;

        }

        .nav-parent.expanded .nav-parent-arrow {

            transform: rotate(180deg);

        }

        .nav-submenu {

            display: none;

            flex-direction: column;

            gap: 0.2rem;

            margin-right: 2.4rem;

        }

        .nav-submenu.open {

            display: flex;

        }

        .nav-item.child {

            padding: 0.55rem 0.75rem;

            border-radius: 12px;

            background: rgba(15, 23, 42, 0.18);

            color: rgba(248, 250, 252, 0.85);

        }

        .nav-item.child:hover,

        .nav-item.child.active {

            background: rgba(214, 17, 25, 0.25);

            color: #fff;

        }

        .nav-item:hover,

        .nav-item.active {

            background: rgba(214, 17, 25, 0.2);

            color: #fff;

        }

        .main-shell {

            flex: 1;

            margin-right: 288px;

            display: flex;

            flex-direction: column;

            position: relative;

            isolation: isolate;

        }

        .topbar {

            background: #fff;

            padding: 1.25rem clamp(1.25rem, 4vw, 2.5rem);

            border-bottom: 1px solid rgba(15, 23, 42, 0.08);

            display: grid;

            grid-template-columns: auto 1fr auto;

            align-items: center;

            gap: 1rem;

            position: sticky;

            top: 0;

            z-index: 100;

            overflow: visible;

        }

        .glass-ui-enabled .topbar {
            background: rgba(255, 255, 255, 0.72) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.45);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
        }

        .glass-ui-enabled .content .card,
        .glass-ui-enabled .content .report-card,
        .glass-ui-enabled .content .settings-card,
        .glass-ui-enabled .content .settings-hero,
        .glass-ui-enabled .content .question-card,
        .glass-ui-enabled .content .designer-canvas,
        .glass-ui-enabled .content .designer-side,
        .glass-ui-enabled .content .audit-hero,
        .glass-ui-enabled .content .audit-filters,
        .glass-ui-enabled .content .audit-table-wrap,
        .glass-ui-enabled .content .audit-unlock-card,
        .glass-ui-enabled .content .table-wrap {
            background: rgba(255, 255, 255, 0.78) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-color: rgba(255, 255, 255, 0.5) !important;
        }

        .page-meta {

            display: flex;

            flex-direction: column;

            gap: 0.35rem;

        }

        .page-meta h1 {

            margin: 0;

            font-size: clamp(1.2rem, 2vw, 1.7rem);

        }

        .page-meta p {

            margin: 0.2rem 0 0;

            color: var(--muted);

        }

        .user-actions {

            display: flex;

            align-items: center;

            gap: 1rem;

            flex-wrap: nowrap;

            overflow: visible;

            flex-shrink: 0;

        }

        .user-name {

            font-weight: 600;

            color: var(--slate);

        }

        .admin-notify-wrap {

            position: relative;

            z-index: 40;

        }

        .admin-notify-trigger {

            position: relative;

            -webkit-tap-highlight-color: transparent;

            touch-action: manipulation;

            border: 1px solid rgba(15, 23, 42, 0.1);

            background: #fff;

            color: var(--slate);

            width: 42px;

            height: 42px;

            border-radius: 14px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;

        }

        .admin-notify-trigger:hover,

        .admin-notify-wrap:focus-within .admin-notify-trigger {

            background: rgba(214, 17, 25, 0.08);

            border-color: rgba(214, 17, 25, 0.25);

            color: var(--primary);

        }

        .admin-notify-trigger svg {

            width: 22px;

            height: 22px;

        }

        .admin-notify-badge {

            position: absolute;

            top: 4px;

            inset-inline-start: 4px;

            min-width: 18px;

            height: 18px;

            padding: 0 5px;

            border-radius: 999px;

            background: var(--primary);

            color: #fff;

            font-size: 10px;

            font-weight: 700;

            line-height: 18px;

            text-align: center;

            box-shadow: 0 2px 6px rgba(214, 17, 25, 0.35);

        }

        .admin-notify-dropdown {

            position: absolute;

            z-index: 50;

            top: calc(100% + 8px);

            inset-inline-end: 0;

            inset-inline-start: auto;

            width: min(22.5rem, calc(100vw - 1.5rem));

            max-width: min(22.5rem, calc(100vw - 1.5rem));

            max-height: min(70vh, 420px);

            overflow: hidden;

            display: flex;

            flex-direction: column;

            background: #fff;

            border: 1px solid rgba(15, 23, 42, 0.1);

            border-radius: 18px;

            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.18);

            box-sizing: border-box;

            opacity: 0;

            visibility: hidden;

            transform: translateY(-6px);

            transition: opacity 0.18s ease, visibility 0.18s ease, transform 0.18s ease;

            pointer-events: none;

        }

        @media (hover: hover) and (pointer: fine) {

            .admin-notify-wrap:hover .admin-notify-dropdown {

                opacity: 1;

                visibility: visible;

                transform: translateY(0);

                pointer-events: auto;

            }

        }

        .admin-notify-wrap:focus-within .admin-notify-dropdown {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);

            pointer-events: auto;

        }

        .admin-notify-wrap.is-open .admin-notify-dropdown {

            opacity: 1;

            visibility: visible;

            transform: translateY(0);

            pointer-events: auto;

        }

        .admin-notify-backdrop {

            position: fixed;

            inset: 0;

            z-index: 50;

            background: rgba(15, 23, 42, 0.18);

            -webkit-tap-highlight-color: transparent;

        }

        /*
         * موبایل: فقط کلاس is-open (جاوااسکریپت) منو را نشان می‌دهد.
         * focus-within/hover با قوانین سراسری تداخل می‌کرد و منو نامرئی می‌شد — از !important برای رفع قطعی استفاده شده.
         */
        @media (max-width: 992px) {

            .admin-notify-wrap {

                position: relative;

                z-index: 1;

            }

            .admin-notify-dropdown {

                position: fixed !important;

                z-index: 300 !important;

                top: max(4rem, calc(env(safe-area-inset-top, 0px) + 3.25rem)) !important;

                left: 50% !important;

                right: auto !important;

                inset-inline-start: auto !important;

                inset-inline-end: auto !important;

                margin-inline: 0 !important;

                width: min(22.5rem, calc(100vw - 2rem)) !important;

                max-width: min(22.5rem, calc(100vw - 2rem)) !important;

                box-sizing: border-box !important;

                max-height: min(75vh, 480px) !important;

                display: flex !important;

                flex-direction: column !important;

                opacity: 0 !important;

                visibility: hidden !important;

                pointer-events: none !important;

                transform: translate3d(-50%, -8px, 0) !important;

                transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease !important;

            }

            .admin-notify-wrap.is-open .admin-notify-dropdown {

                opacity: 1 !important;

                visibility: visible !important;

                pointer-events: auto !important;

                transform: translate3d(-50%, 0, 0) !important;

            }

        }

        .admin-notify-head {

            padding: 0.85rem 1rem;

            border-bottom: 1px solid rgba(15, 23, 42, 0.06);

            font-weight: 700;

            font-size: 0.92rem;

            color: var(--slate);

        }

        .admin-notify-list {

            overflow-y: auto;

            max-height: min(58vh, 360px);

            padding: 0.35rem 0;

        }

        .admin-notify-item {

            display: block;

            padding: 0.65rem 1rem;

            text-decoration: none;

            color: inherit;

            border-bottom: 1px solid rgba(15, 23, 42, 0.04);

            transition: background 0.12s ease;

        }

        .admin-notify-item:last-child {

            border-bottom: none;

        }

        .admin-notify-item:hover {

            background: rgba(214, 17, 25, 0.05);

        }

        .admin-notify-item strong {

            display: block;

            font-size: 0.86rem;

            margin-bottom: 0.25rem;

            color: var(--slate);

        }

        .admin-notify-item p {

            margin: 0;

            font-size: 0.8rem;

            line-height: 1.55;

            color: var(--muted);

        }

        .admin-notify-item time {

            display: block;

            margin-top: 0.35rem;

            font-size: 0.72rem;

            color: rgba(107, 114, 128, 0.95);

        }

        .admin-notify-item--warning {

            border-inline-end: 3px solid rgba(217, 119, 6, 0.85);

        }

        .admin-notify-item--info {

            border-inline-end: 3px solid rgba(37, 99, 235, 0.75);

        }

        .admin-notify-item--danger {

            border-inline-end: 3px solid rgba(220, 38, 38, 0.85);

        }

        .admin-notify-empty {

            margin: 0;

            padding: 1.5rem 1rem;

            text-align: center;

            font-size: 0.86rem;

            color: var(--muted);

            line-height: 1.7;

        }

        .action-buttons {

            display: flex;

            align-items: center;

            gap: 0.6rem;

        }

        .menu-toggle {

            border: none;

            background: rgba(214, 17, 25, 0.12);

            color: var(--primary);

            padding: 0.65rem;

            display: none;

            border-radius: 14px;

            cursor: pointer;

            align-items: center;

            justify-content: center;

            transition: background 0.2s ease;

        }

        .menu-toggle svg {

            width: 22px;

            height: 22px;

        }

        .menu-toggle:hover {

            background: rgba(214, 17, 25, 0.2);

        }

        .logout-form button {

            border: none;

            padding: 0.55rem 1.3rem;

            border-radius: 999px;

            background: linear-gradient(135deg, var(--primary), var(--primary-dark));

            color: #fff;

            font-weight: 600;

            cursor: pointer;

            display: inline-flex;

            align-items: center;

            gap: 0.35rem;

        }

        .content {

            flex: 1;

            padding: 2rem clamp(1.25rem, 4vw, 3rem);

            display: flex;

            flex-direction: column;

            gap: 2rem;

        }

        .stats-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));

            gap: 1.25rem;

        }

        .stat-card {

            background: #fff;

            border-radius: 24px;

            padding: 1.5rem;

            border: 1px solid rgba(15, 23, 42, 0.06);

            box-shadow: 0 25px 40px rgba(15, 23, 42, 0.05);

            display: flex;

            gap: 1rem;

            align-items: center;

        }

        .stat-card-icon {

            width: 52px;

            height: 52px;

            border-radius: 16px;

            background: rgba(214, 17, 25, 0.1);

            display: flex;

            align-items: center;

            justify-content: center;

            color: var(--primary);

        }

        .stat-card-icon svg {

            width: 26px;

            height: 26px;

        }

        .stat-card h3 {

            margin: 0;

            font-size: 2rem;

            color: var(--primary);

        }

        .stat-card span {

            display: block;

            color: var(--muted);

            margin-top: 0.35rem;

        }

        .panel-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));

            gap: 1.25rem;

        }

        .panel {

            background: #fff;

            border-radius: 26px;

            padding: 1.6rem;

            border: 1px solid rgba(15, 23, 42, 0.08);

            box-shadow: 0 25px 45px rgba(15, 23, 42, 0.05);

        }

        .panel h3 {

            margin: 0 0 0.7rem;

            display: flex;

            align-items: center;

            gap: 0.4rem;

        }

        .panel p {

            margin: 0;

            color: var(--muted);

            line-height: 1.8;

        }

        @media (min-width: 993px) {

            body.sidebar-collapsed .sidebar {

                width: 96px;

            }

            body.sidebar-collapsed .main-shell {

                margin-right: 96px;

            }

            body.sidebar-collapsed .brand>div,

            body.sidebar-collapsed .nav-title,

            body.sidebar-collapsed .brand small {

                display: none;

            }

            body.sidebar-collapsed .brand img {

                margin: 0 auto;

            }

            body.sidebar-collapsed .nav-item {

                justify-content: center;

                padding: 0.65rem;

                font-size: 0;

                border-radius: 16px;

            }

            body.sidebar-collapsed .nav-item svg {

                margin: 0;

                width: 22px;

                height: 22px;

            }

            body.sidebar-collapsed .nav-section {

                gap: 0.6rem;

            }

        }

        @media (max-width: 992px) {

            body {

                overflow-x: hidden;

                max-width: 100%;

            }

            .dashboard-shell {

                overflow-x: hidden;

                min-width: 0;

            }

            /* سایدبار باید بالاتر از نوار چسبان باشد وگرنه منو زیر هدر گیر می‌کند */
            .sidebar {

                transform: translateX(110%);

                z-index: 280;

            }

            .sidebar.open {

                transform: translateX(0);

            }

            .sidebar-overlay {

                z-index: 270;

            }

            .main-shell {

                margin-right: 0;

                min-width: 0;

                max-width: 100%;

                overflow-x: hidden;

            }

            .content {

                min-width: 0;

                max-width: 100%;

                overflow-x: hidden;

            }

            .topbar {

                display: grid;

                grid-template-columns: auto minmax(0, 1fr) auto;

                grid-template-areas: 'actions title userzone';

                align-items: center;

                gap: 0.5rem 0.65rem;

                padding: 0.6rem 0.85rem;

                min-height: 3.5rem;

            }

            .action-buttons {

                grid-area: actions;

                position: static;

                transform: none;

                margin: 0;

            }

            .page-meta {

                grid-area: title;

                position: static;

                transform: none;

                text-align: center;

                min-width: 0;

                max-width: 100%;

            }

            .page-meta p {

                display: none;

            }

            .page-meta h1 {

                font-size: clamp(0.9rem, 3.2vw, 1.1rem);

                line-height: 1.3;

                white-space: nowrap;

                overflow: hidden;

                text-overflow: ellipsis;

                max-width: 100%;

            }

            .user-actions {

                grid-area: userzone;

                position: static;

                transform: none;

                gap: 0.4rem;

                margin: 0;

                flex-shrink: 0;

            }

            .user-name {

                display: none;

            }

            .menu-toggle {

                display: flex;

            }

            .menu-toggle,

            .logout-form button {

                width: 44px;

                height: 44px;

                padding: 0;

                border-radius: 12px;

                background: rgba(214, 17, 25, 0.12);

                color: var(--primary);

                box-shadow: none;

                display: flex;

                align-items: center;

                justify-content: center;

            }

            .admin-notify-trigger {

                width: 44px;

                height: 44px;

                min-width: 44px;

                min-height: 44px;

                border-radius: 12px;

            }

            .logout-form {

                margin: 0;

            }

            .logout-form .btn-text {

                display: none;

            }

            .logout-form .btn-icon {

                display: block;

                width: 20px;

                height: 20px;

            }

        }
    </style>

</head>

<body class="{{ $glassUiEnabled ? 'glass-ui-enabled' : '' }}">

    <div class="dashboard-shell">

        <aside class="sidebar" id="adminSidebar">

            <div class="brand">

                <img src="{{ asset($appSettings['logo_path'] ?? 'storage/logo.png') }}"
                    alt="لوگوی {{ $appSettings['app_name'] ?? 'سامانه نظرسنجی' }}">

                <div>

                    <h1>{{ $appSettings['app_name'] ?? 'سامانه نظرسنجی' }}</h1>

                    <small>پنل مدیریت</small>

                </div>

            </div>

            <div class="nav-section">

                <span class="nav-title">منو</span>

                @foreach ($navItems as $item)
                    @php

                        $children = $item['children'] ?? [];
                        $hasChildren = !empty($children);
                        $childActive = false;

                        if ($hasChildren) {
                            foreach ($children as $child) {
                                if (!empty($child['route']) && request()->routeIs($child['route'])) {
                                    $childActive = true;
                                    break;
                                }
                            }
                        }

                        $routeName = $item['route'] ?? null;
                        $active = $hasChildren ? $childActive : ($routeName ? request()->routeIs($routeName) : false);

                        $submenuId = $hasChildren
                            ? 'submenu-' . ((Str::slug($item['label']) ?: 'group') . '-' . $loop->index)
                            : null;

                    @endphp

                    @if ($hasChildren)
                        <div class="nav-parent {{ $active ? 'expanded' : '' }}">

                            <button type="button" class="nav-parent-toggle {{ $active ? 'active' : '' }}"
                                data-target="{{ $submenuId }}" aria-expanded="{{ $active ? 'true' : 'false' }}">

                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="{{ $item['icon'] }}" />

                                </svg>

                                {{ $item['label'] }}

                                <svg class="nav-parent-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M6 9l6 6 6-6" />

                                </svg>

                            </button>

                            <div id="{{ $submenuId }}" class="nav-submenu {{ $active ? 'open' : '' }}">

                                @foreach ($children as $child)
                                    @php

                                        $isChildActive = !empty($child['route'])
                                            ? request()->routeIs($child['route'])
                                            : false;

                                    @endphp

                                    <a href="{{ $child['href'] ?? '#' }}"
                                        class="nav-item child {{ $isChildActive ? 'active' : '' }}">

                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="{{ $child['icon'] }}" />

                                        </svg>

                                        {{ $child['label'] }}

                                    </a>
                                @endforeach

                            </div>

                        </div>
                    @else
                        <a href="{{ $item['href'] ?? '#' }}" class="nav-item {{ $active ? 'active' : '' }}">

                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="{{ $item['icon'] }}" />
                            </svg>

                            {{ $item['label'] }}

                        </a>
                    @endif
                @endforeach

            </div>

        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="main-shell">

            <header class="topbar">

                <div class="action-buttons">

                    <button class="menu-toggle" id="sidebarToggle" aria-label="باز کردن منو">

                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M4 6h16M4 12h16M4 18h16" />

                        </svg>

                    </button>

                </div>

                <div class="page-meta">

                    <h1>@yield('page-title', 'داشبورد مدیریتی')</h1>

                    <p>@yield('page-description', 'عملیات و گزارش‌ها را در این بخش مدیریت کنید.')</p>

                </div>

                <div class="user-actions">

                    <span class="user-name">
                        {{ $admin?->name ?: $admin?->username ?: 'مدیر سیستم' }}
                        @if ($admin && $admin->isSupervisor())
                            <small style="margin-right:0.35rem;opacity:0.85;font-weight:600;color:rgba(248,250,252,0.75);">(ناظر)</small>
                        @endif
                    </span>

                    <div class="admin-notify-wrap">
                        <button type="button" class="admin-notify-trigger" aria-label="اعلان‌ها" aria-haspopup="true"
                            aria-expanded="false" id="adminNotifyTrigger">
                            @if (($headerNotificationCount ?? 0) > 0)
                                <span class="admin-notify-badge" aria-hidden="true">
                                    {{ ($headerNotificationCount ?? 0) > 9 ? '9+' : $headerNotificationCount }}
                                </span>
                            @endif
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                        <div class="admin-notify-dropdown" role="region" aria-labelledby="adminNotifyTrigger" id="adminNotifyPanel">
                            <div class="admin-notify-head">اعلان‌ها</div>
                            @php
                                $notes = $headerNotifications ?? [];
                            @endphp
                            @if (count($notes) === 0)
                                <p class="admin-notify-empty">اعلان جدیدی برای شما ثبت نشده است.</p>
                            @else
                                <div class="admin-notify-list">
                                    @foreach ($notes as $note)
                                        @php
                                            $tone = $note['tone'] ?? 'info';
                                            $toneClass =
                                                match ($tone) {
                                                    'danger' => 'admin-notify-item--danger',
                                                    'warning' => 'admin-notify-item--warning',
                                                    default => 'admin-notify-item--info',
                                                };
                                        @endphp
                                        <a href="{{ $note['href'] ?? '#' }}" class="admin-notify-item {{ $toneClass }}">
                                            <strong>{{ $note['title'] ?? '' }}</strong>
                                            <p>{{ $note['body'] ?? '' }}</p>
                                            @if (!empty($note['at']))
                                                <time
                                                    datetime="{{ $note['at']->toIso8601String() }}">{{ $note['at']->format('Y/m/d H:i') }}</time>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.logout') }}" class="logout-form">

                        @csrf

                        <button type="submit" aria-label="خروج از پنل">

                            <span class="btn-text">خروج</span>

                            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M10 17l5-5-5-5" />

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h11" />

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M13 5V4a2 2 0 00-2-2H6a2 2 0 00-2 2v16a2 2 0 002 2h5a2 2 0 002-2v-1" />

                            </svg>

                        </button>

                    </form>

                </div>

            </header>

            <main class="content">

                @if (session('error'))
                    <div style="margin:0 0 1rem;padding:0.75rem 1rem;border-radius:14px;background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.25);color:#991b1b;font-weight:600;font-size:0.88rem;">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')

            </main>

        </div>

    </div>

    <script>
        const sidebar = document.getElementById('adminSidebar');

        const overlay = document.getElementById('sidebarOverlay');

        const toggleBtn = document.getElementById('sidebarToggle');

        const body = document.body;

        const DESKTOP_BREAKPOINT = 993;

        const navToggles = document.querySelectorAll('.nav-parent-toggle');

        function closeSidebar() {

            sidebar?.classList.remove('open');

            overlay?.classList.remove('visible');

        }

        function openSidebar() {

            sidebar?.classList.add('open');

            overlay?.classList.add('visible');

        }

        toggleBtn?.addEventListener('click', () => {

            if (window.innerWidth >= DESKTOP_BREAKPOINT) {

                body.classList.toggle('sidebar-collapsed');

                return;

            }

            const isOpen = sidebar?.classList.contains('open');

            if (isOpen) {

                closeSidebar();

            } else {

                openSidebar();

            }

        });

        overlay?.addEventListener('click', () => closeSidebar());

        navToggles.forEach((toggle) => {

            toggle.addEventListener('click', () => {

                const targetId = toggle.dataset.target;

                const submenu = document.getElementById(targetId);

                if (!submenu) {

                    return;

                }

                const parent = toggle.closest('.nav-parent');

                const willOpen = !submenu.classList.contains('open');

                submenu.classList.toggle('open', willOpen);

                toggle.classList.toggle('active', willOpen);

                parent?.classList.toggle('expanded', willOpen);

                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

            });

        });

        window.addEventListener('resize', () => {

            if (window.innerWidth >= DESKTOP_BREAKPOINT) {

                sidebar?.classList.remove('open');

                overlay?.classList.remove('visible');

            } else {

                body.classList.remove('sidebar-collapsed');

            }

        });

        (function () {

            const wrap = document.querySelector('.admin-notify-wrap');

            const trigger = document.getElementById('adminNotifyTrigger');

            if (!wrap || !trigger) {

                return;

            }

            let backdropEl = null;

            let suppressOutsideUntil = 0;

            const MOBILE_MAX = 992;

            function removeBackdrop() {

                if (backdropEl) {

                    backdropEl.remove();

                    backdropEl = null;

                }

            }

            function addBackdrop() {

                if (backdropEl || window.innerWidth > MOBILE_MAX) {

                    return;

                }

                backdropEl = document.createElement('div');

                backdropEl.className = 'admin-notify-backdrop';

                backdropEl.setAttribute('aria-hidden', 'true');

                backdropEl.addEventListener('click', () => setOpen(false));

                const shell = document.querySelector('.main-shell');

                if (shell) {

                    shell.insertBefore(backdropEl, shell.firstChild);

                } else {

                    document.body.appendChild(backdropEl);

                }

            }

            function setOpen(open) {

                wrap.classList.toggle('is-open', open);

                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');

                if (open && window.innerWidth <= MOBILE_MAX) {

                    addBackdrop();

                } else {

                    removeBackdrop();

                }

            }

            trigger.addEventListener('click', (e) => {

                e.preventDefault();

                e.stopPropagation();

                suppressOutsideUntil = Date.now() + 450;

                setOpen(!wrap.classList.contains('is-open'));

            });

            document.addEventListener('click', (e) => {

                if (Date.now() < suppressOutsideUntil) {

                    return;

                }

                if (!wrap.contains(e.target)) {

                    setOpen(false);

                }

            });

            document.addEventListener('keydown', (e) => {

                if (e.key === 'Escape') {

                    setOpen(false);

                }

            });

            wrap.querySelectorAll('a.admin-notify-item').forEach((a) => {

                a.addEventListener('click', () => setOpen(false));

            });

            window.addEventListener('resize', () => {

                removeBackdrop();

                if (window.innerWidth > MOBILE_MAX) {

                    wrap.classList.remove('is-open');

                    trigger.setAttribute('aria-expanded', 'false');

                }

            });

        })();
    </script>

    @include('components.persian-digits-script')

</body>

</html>

<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('page-title', 'پنل مدیریت')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">

    @php

        use Illuminate\Support\Str;

        $themeColors = $appSettings['colors'] ?? \App\Support\AppSettings::get('colors', []);

    @endphp
    @php

        $admin = $admin ?? null;

        $navItems = [
            [
                'label' => 'داشبورد',

                'href' => route('admin.dashboard'),

                'icon' =>
                    'M3 9.75l9-7.5 9 7.5V20a1 1 0 01-1 1h-5.5a1 1 0 01-1-1v-5h-4v5a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z',

                'route' => 'admin.dashboard',
            ],

            [
                'label' => 'تنظیمات سازمان',

                'icon' => 'M4 7h16v10H4zM4 10h16',

                'children' => [
                    [
                        'label' => 'واحدهای سازمانی',

                        'href' => route('admin.units.index'),

                        'icon' => 'M3 10h18v9H3zM7 10V5h10v5',

                        'route' => 'admin.units.index',
                    ],

                    [
                        'label' => 'چارت سمت‌ها',

                        'href' => route('admin.positions.index'),

                        'icon' => 'M6 7h4v4H6zM14 7h4v4h-4zM10 13h4v4h-4zM8 9h8M12 13v-2',

                        'route' => 'admin.positions.index',
                    ],

                    [
                        'label' => 'پرسنل سازمان',

                        'href' => route('admin.personnel.index'),

                        'icon' =>
                            'M8.5 7a3.5 3.5 0 117 0 3.5 3.5 0 01-7 0zM4 19.5c0-2.485 3.358-4.5 7.5-4.5s7.5 2.015 7.5 4.5V21H4z',

                        'route' => 'admin.personnel.index',
                    ],

                    [
                        'label' => 'سرپرستان واحدها',

                        'href' => route('admin.unit-supervisors.index'),

                        'icon' =>
                            'M12 6l2 3 3 .5-2.2 2.4.5 3.1L12 13l-3.3 1.9.5-3.1L7 9.5l3-.5zM5 20v-2c0-1.657 3.134-3 7-3s7 1.343 7 3v2',

                        'route' => 'admin.unit-supervisors.index',
                    ],
                ],
            ],

            [
                'label' => 'تنظیمات',

                'href' => route('admin.settings.index'),

                'icon' =>
                    'M11.049 2.927c.3-.921 1.603-.921 1.902 0l.149.457a1 1 0 00.95.69h.48c.969 0 1.371 1.24.588 1.81l-.39.284a1 1 0 000 1.62l.39.284c.783.57.38 1.81-.588 1.81h-.48a1 1 0 00-.95.69l-.149.457c-.3.921-1.603.921-1.902 0l-.149-.457a1 1 0 00-.95-.69h-.48c-.969 0-1.371-1.24-.588-1.81l.39-.284a1 1 0 000-1.62l-.39-.284c-.783-.57-.38-1.81.588-1.81h.48a1 1 0 00.95-.69l.149-.457zM12 15.5a3 3 0 100 6 3 3 0 000-6z',

                'route' => 'admin.settings.index',
            ],
            [
                'label' => 'نظرسنجی‌ها',

                'href' => route('admin.surveys.index'),

                'icon' => 'M5 6h14v4H5zM5 12h14v4H5zM5 18h9',

                'route' => 'admin.surveys.index',
            ],


            [
                'label' => 'گزارشات',

                'href' => '#',

                'icon' => 'M5 9h3v8H5zM10.5 5h3v12h-3zM16 11h3v6h-3z',

                'route' => null,
            ],

            [
                'label' => 'پروفایل کاربر',

                'href' => '#',

                'icon' => 'M12 12a4 4 0 100-8 4 4 0 000 8zm-6 7c0-2.761 3.134-5 6-5s6 2.239 6 5v1H6z',

                'route' => null,
            ],
        ];

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

        body {

            margin: 0;

            min-height: 100vh;

            font-family: 'Vazirmatn', system-ui, sans-serif;

            background: {{ $themeColors['background'] ?? '#f4f5f7' }};

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

            z-index: 10;

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

        }

        .user-name {

            font-weight: 600;

            color: var(--slate);

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

            .sidebar {

                transform: translateX(110%);

            }

            .sidebar.open {

                transform: translateX(0);

            }

            .main-shell {

                margin-right: 0;

            }

            .menu-toggle {

                display: flex;

            }

        }

        @media (max-width: 640px) {

            .topbar {

                display: flex;

                justify-content: center;

                align-items: center;

                padding: 1rem 1.25rem;

                position: relative;

                min-height: 88px;

            }

            .page-meta {

                position: absolute;

                left: 50%;

                transform: translateX(-50%);

                text-align: center;

                align-items: center;

            }

            .page-meta p {

                display: none;

            }

            .page-meta h1 {

                font-size: 1.05rem;

            }

            .user-name {

                display: none;

            }

            .action-buttons,

            .user-actions {

                position: absolute;

                top: 50%;

                transform: translateY(-50%);

            }

            .action-buttons {

                right: 1rem;

            }

            .user-actions {

                left: 1rem;

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

<body>

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

                    <span class="user-name">{{ $admin?->name ?: $admin?->username ?: 'مدیر سیستم' }}</span>

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
    </script>

    @include('components.persian-digits-script')

</body>

</html>

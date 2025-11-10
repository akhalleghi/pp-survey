<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'پنل مدیریت')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #D61119;
            --primary-dark: #ab0c12;
            --slate: #0F172A;
            --muted: #6B7280;
            --sidebar: #0c111d;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            background: #f4f5f7;
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
            background: rgba(0,0,0,0.35);
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
            background: rgba(255,255,255,0.08);
            object-fit: contain;
        }
        .brand h1 {
            margin: 0;
            font-size: 1.1rem;
            color: #fff;
        }
        .brand small {
            display: block;
            color: rgba(248,250,252,0.6);
        }
        .nav-section {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .nav-title {
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            color: rgba(248,250,252,0.45);
            text-transform: uppercase;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.65rem 0.85rem;
            border-radius: 15px;
            color: rgba(248,250,252,0.85);
            transition: background 0.15s, color 0.15s;
        }
        .nav-item svg {
            width: 20px;
            height: 20px;
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
            border-bottom: 1px solid rgba(15,23,42,0.08);
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
        .logout-form .btn-icon {
            display: none;
            width: 18px;
            height: 18px;
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
            border: 1px solid rgba(15,23,42,0.06);
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
            border: 1px solid rgba(15,23,42,0.08);
            box-shadow: 0 25px 45px rgba(15,23,42,0.05);
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
            body.sidebar-collapsed .brand > div,
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
@php
    $admin = $admin ?? null;
    $navItems = [
        [
            'label' => 'داشبورد',
            'href' => route('admin.dashboard'),
            'icon' => 'M3 9.75l9-7.5 9 7.5V20a1 1 0 01-1 1h-5.5a1 1 0 01-1-1v-5h-4v5a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z',
            'route' => 'admin.dashboard',
        ],
        [
            'label' => 'تعریف واحدها',
            'href' => route('admin.units.index'),
            'icon' => 'M3 6h18M3 12h18M3 18h10',
            'route' => 'admin.units.index',
        ],
        [
            'label' => 'تعریف سمت‌ها',
            'href' => route('admin.positions.index'),
            'icon' => 'M12 14l9-5-9-5-9 5 9 5zm0 0v7',
            'route' => 'admin.positions.index',
        ],
        [
            'label' => 'گزارش‌ها',
            'href' => '#',
            'icon' => 'M12 6v12m6-6H6',
            'route' => null,
        ],
        [
            'label' => 'تنظیمات',
            'href' => '#',
            'icon' => 'M4 6h16M4 10h16M4 14h10M4 18h6',
            'route' => null,
        ],
        [
            'label' => 'پروفایل',
            'href' => '#',
            'icon' => 'M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 10-6 0 3 3 0 006 0z',
            'route' => null,
        ],
    ];
@endphp
<div class="dashboard-shell">
    <aside class="sidebar" id="adminSidebar">
        <div class="brand">
            <img src="{{ asset('storage/logo.png') }}" alt="لوگو">
            <div>
                <h1>پنل مدیریت</h1>
                <small>سیستم نظرسنجی</small>
            </div>
        </div>
        <div class="nav-section">
            <span class="nav-title">منو</span>
            @foreach ($navItems as $item)
                @php
                    $active = $item['route'] ? request()->routeIs($item['route']) : false;
                @endphp
                <a href="{{ $item['href'] }}" class="nav-item {{ $active ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="main-shell">
        <header class="topbar">
            <div class="action-buttons">
                <button class="menu-toggle" id="sidebarToggle" aria-label="باز کردن منو">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 17l5-5-5-5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h11"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 5V4a2 2 0 00-2-2H6a2 2 0 00-2 2v16a2 2 0 002 2h5a2 2 0 002-2v-1"/>
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

    window.addEventListener('resize', () => {
        if (window.innerWidth >= DESKTOP_BREAKPOINT) {
            sidebar?.classList.remove('open');
            overlay?.classList.remove('visible');
        } else {
            body.classList.remove('sidebar-collapsed');
        }
    });
</script>
</body>
</html>


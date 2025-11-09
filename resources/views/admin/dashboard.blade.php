<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', system-ui, sans-serif;
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
        }
        .dashboard-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background: #D81C24;
            color: #fff;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        header img {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: rgba(255,255,255,0.15);
            padding: 0.5rem;
            object-fit: contain;
        }
        header .title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        header p {
            margin: 0.2rem 0 0;
            opacity: 0.9;
        }
        .logout-form button {
            border: none;
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 0.7rem 1.4rem;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .logout-form button:hover {
            background: rgba(0,0,0,0.2);
        }
        main {
            flex: 1;
            padding: 2.5rem clamp(1.5rem, 4vw, 4rem);
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: 0 25px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }
        .card h3 {
            margin: 0;
            font-size: 2.2rem;
            color: #D81C24;
        }
        .card span {
            display: block;
            margin-top: 0.5rem;
            color: #475569;
        }
    </style>
</head>
<body>
<div class="dashboard-shell">
    <header>
        <div class="title">
            <img src="{{ asset('storage/logo.png') }}" alt="لوگو">
            <div>
                <h1>خوش آمدید {{ $admin?->name ?: $admin?->username }}!</h1>
                <p>مدیریت نظرسنجی‌ها از اینجا در دسترس شماست.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}" class="logout-form">
            @csrf
            <button type="submit">خروج</button>
        </form>
    </header>

    <main>
        <div class="card-grid">
            <div class="card">
                <h3>۰%</h3>
                <span>میزان مشارکت امروز</span>
            </div>
            <div class="card">
                <h3>۰</h3>
                <span>نظرسنجی‌های فعال</span>
            </div>
            <div class="card">
                <h3>۰</h3>
                <span>پاسخ‌های جدید</span>
            </div>
        </div>
    </main>
</div>
</body>
</html>

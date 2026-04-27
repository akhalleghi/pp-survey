<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'نظرسنجی در دسترس نیست' }}</title>
    <link rel="stylesheet" href="/fonts/vazirmatn/vazirmatn.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            font-family: 'Vazirmatn', system-ui, sans-serif;
            background: #f4f5f7;
            color: #0f172a;
        }
        .card {
            max-width: 480px;
            width: 100%;
            background: #fff;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            padding: 1.75rem;
            text-align: center;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        h1 { margin: 0 0 0.75rem; font-size: 1.25rem; }
        p { margin: 0; color: #64748b; line-height: 1.75; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>

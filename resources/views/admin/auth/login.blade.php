<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ورود ناحیه مدیریت</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
    @php
        $themeColors = $appSettings['colors'] ?? \App\Support\AppSettings::get('colors', []);
        $primaryHex = ltrim($themeColors['primary'] ?? '#D61119', '#');
        $primaryRgb = sscanf($primaryHex, "%02x%02x%02x") ?: [214, 17, 25];
        $primaryRgbString = implode(',', $primaryRgb);
    @endphp
    <style>
        :root {
            color-scheme: light;
            --primary: {{ $themeColors['primary'] ?? '#D61119' }};
            --primary-dark: {{ $themeColors['primary_dark'] ?? '#a00b11' }};
            --accent-light: {{ $themeColors['accent_light'] ?? '#ffe8e9' }};
            --accent-lighter: {{ $themeColors['accent_lighter'] ?? '#f5f5f7' }};
            --text-primary: {{ $themeColors['text_primary'] ?? '#1f2937' }};
            --muted: {{ $themeColors['muted'] ?? '#6b7280' }};
        }
        * { box-sizing: border-box; }
        button, input { font-family: inherit; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Vazirmatn', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at top, var(--accent-light), var(--accent-lighter) 55%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            color: var(--text-primary);
        }
        .login-wrapper { width: 100%; max-width: 460px; }
        .login-card {
            background: #fff;
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow: 0 20px 45px rgba({{ $primaryRgbString }}, 0.15);
            border: 1px solid rgba({{ $primaryRgbString }}, 0.08);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba({{ $primaryRgbString }}, 0.08), transparent 55%);
            pointer-events: none;
        }
        .logo-block { text-align: center; margin-bottom: 1.5rem; }
        .logo-block img {
            width: 88px;
            height: 88px;
            object-fit: contain;
            border-radius: 20px;
            background: #fff;
            padding: 0.75rem;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05), 0 10px 25px rgba(0,0,0,0.08);
        }
        .logo-block h1 { margin: 1rem 0 0.4rem; font-size: 1.5rem; color: var(--text-primary); }
        .logo-block p { margin: 0; color: var(--muted); font-size: 0.95rem; }
        form { display: flex; flex-direction: column; gap: 1.25rem; position: relative; z-index: 1; }
        label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.92rem; }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            font-size: 0.95rem;
            background: #f9fafb;
            transition: border-color 0.2s, background 0.2s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba({{ $primaryRgbString }}, 0.15);
        }
        .captcha-stack { display: flex; flex-direction: column; gap: 0.6rem; }
        .captcha-actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .captcha-display {
            background: repeating-linear-gradient(135deg, rgba({{ $primaryRgbString }},0.12), rgba({{ $primaryRgbString }},0.12) 12px, rgba({{ $primaryRgbString }},0.18) 12px, rgba({{ $primaryRgbString }},0.18) 24px);
            border-radius: 16px;
            padding: 0.8rem 1rem;
            font-size: 1.35rem;
            letter-spacing: 0.35rem;
            text-align: center;
            font-weight: 700;
            color: var(--text-primary);
            border: 1px dashed rgba({{ $primaryRgbString }}, 0.4);
            text-transform: uppercase;
        }
        .refresh-btn {
            flex-shrink: 0;
            border: none;
            background: rgba({{ $primaryRgbString }}, 0.12);
            color: var(--primary);
            padding: 0.65rem 1rem;
            border-radius: 999px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .refresh-btn:hover { background: rgba({{ $primaryRgbString }}, 0.2); }
        .submit-btn {
            border: none;
            border-radius: 18px;
            padding: 0.95rem;
            font-size: 1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            cursor: pointer;
            box-shadow: 0 15px 30px rgba({{ $primaryRgbString }}, 0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 18px 35px rgba({{ $primaryRgbString }}, 0.35); }
        .alert { border-radius: 18px; padding: 0.85rem 1rem; font-size: 0.9rem; margin-bottom: 0.8rem; }
        .alert-error {
            background: rgba({{ $primaryRgbString }}, 0.1);
            color: var(--primary);
            border: 1px solid rgba({{ $primaryRgbString }}, 0.3);
        }
        ul.error-list { margin: 0.4rem 1rem 0 0; }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="logo-block">
            <img src="{{ asset($appSettings['logo_path'] ?? 'storage/logo.png') }}" alt="لوگوی {{ $appSettings['app_name'] ?? 'سامانه نظرسنجی' }}">
            <h1>{{ $appSettings['app_name'] ?? 'سامانه نظرسنجی' }}</h1>
            <p>برای ورود به پنل مدیریت، اطلاعات حساب خود را وارد کنید.</p>
        </div>

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div>
                <label for="username">نام کاربری</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required>
            </div>

            <div>
                <label for="password">رمز عبور</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>

            <div class="captcha-stack">
                <label for="captcha">کد امنیتی</label>
                <div class="captcha-actions">
                    <div class="captcha-display" id="captchaCode">{{ $captcha }}</div>
                    <button type="button" class="refresh-btn" id="refreshCaptcha">تازه‌سازی کد</button>
                </div>
                <input id="captcha" name="captcha" type="text" placeholder="کد نمایش داده‌شده را وارد کنید" autocomplete="off" required>
            </div>

            <button type="submit" class="submit-btn">ورود به پنل مدیریت</button>
        </form>
    </div>
</div>

<script>
    const refreshBtn = document.getElementById('refreshCaptcha');
    const captchaElement = document.getElementById('captchaCode');
    const captchaUrl = "{{ route('admin.captcha.refresh') }}";
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    refreshBtn?.addEventListener('click', () => {
        refreshBtn.disabled = true;
        fetch(captchaUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                captchaElement.textContent = data.captcha ?? '';
            })
            .catch(() => {
                captchaElement.textContent = '-----';
            })
            .finally(() => {
                refreshBtn.disabled = false;
            });
    });
</script>
@include('components.persian-digits-script')
</body>
</html>

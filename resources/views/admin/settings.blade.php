@extends('admin.layouts.app')

@section('page-title', 'تنظیمات سامانه')
@section('page-description', 'در این صفحه می‌توانید تنظیمات حیاتی سامانه را به‌صورت منظم و واکنش‌گرا مدیریت کنید.')

@php
    $activeTab = $activeTab ?? 'password';
    $passwordErrors = $errors->updatePassword ?? $errors;
    $brandingErrors = $errors->updateBranding ?? $errors;
    $colorErrors = $errors->updateColors ?? $errors;
    $systemBackgroundErrors = $errors->updateSystemBackground ?? $errors;
    $securityErrors = $errors->updateSecurity ?? $errors;
    $loginPageErrors = $errors->updateLoginPage ?? $errors;
    $securityCfg = $appSettings['security'] ?? [];
    $systemBgCfg = $appSettings['system_background'] ?? [];
    $systemBgImages = array_values(array_filter((array) ($systemBgCfg['images'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
    $loginPageCfg = $appSettings['login_page'] ?? [];
    $loginPageImages = array_values(array_filter((array) ($loginPageCfg['background_images'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
    $tabs = [
        [
            'id' => 'password',
            'label' => 'تغییر رمز عبور',
            'subtitle' => 'به‌روزرسانی امن رمز مدیر سامانه',
        ],
        [
            'id' => 'branding',
            'label' => 'هویت سامانه',
            'subtitle' => 'مدیریت نام و لوگوی سامانه',
        ],
        [
            'id' => 'colors',
            'label' => 'رنگ‌بندی سامانه',
            'subtitle' => 'سفارشی‌سازی رنگ‌های پنل، لاگین و خوش‌آمد',
        ],
        [
            'id' => 'security',
            'label' => 'امنیت ورود',
            'subtitle' => 'قفل موقت، نشست و نگهداری گزارش ورود',
        ],
        [
            'id' => 'login_page',
            'label' => 'صفحه ورود',
            'subtitle' => 'متن، رفتار لاگین و مدیریت بک‌گراند',
        ],
        [
            'id' => 'profile',
            'label' => 'پروفایل مدیر',
            'subtitle' => 'به‌زودی فعال خواهد شد',
        ],
        [
            'id' => 'notifications',
            'label' => 'اعلان و هشدار',
            'subtitle' => 'در نسخه‌های آتی ارائه می‌شود',
        ],
    ];
@endphp

@section('content')
    <style>
        .settings-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .settings-hero {
            background: #fff;
            border-radius: 28px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: clamp(1.25rem, 3vw, 2rem);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }
        .settings-hero h2 {
            margin: 0;
            font-size: 1.4rem;
        }
        .settings-hero p {
            margin: 0.35rem 0 0;
            color: var(--muted);
        }
        .status-message {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #15803d;
            border-radius: 18px;
            padding: 0.9rem 1.2rem;
            font-weight: 600;
        }
        .settings-card {
            background: #fff;
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: clamp(1.25rem, 3vw, 2rem);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .tab-pill {
            border: 1px solid rgba(15,23,42,0.12);
            border-radius: 18px;
            padding: 0.85rem 1.4rem;
            background: rgba(15,23,42,0.03);
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 180px;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .tab-pill span {
            font-weight: 600;
        }
        .tab-pill small {
            color: var(--muted);
            font-size: 0.78rem;
        }
        .tab-pill.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 10px 25px rgba(214,17,25,0.25);
        }
        .tab-pill.active small {
            color: rgba(255,255,255,0.85);
        }
        .tab-panels {
            border-top: 1px solid rgba(15,23,42,0.07);
            padding-top: 1.5rem;
        }
        .tab-panel {
            display: none;
            animation: fadeIn 0.2s ease;
        }
        .tab-panel.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
        }
        .branding-grid {
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        .color-grid {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .form-control {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .form-control label {
            font-weight: 600;
            color: var(--slate);
        }
        .form-control input:not([type="color"]),
        .form-control input[type="file"],
        .form-control select {
            border: 1px solid rgba(15,23,42,0.15);
            border-radius: 16px;
            padding: 0.9rem 1rem;
            font-family: inherit;
            font-size: 0.95rem;
            background: rgba(15,23,42,0.015);
        }
        .form-control input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(214,17,25,0.12);
        }
        .form-control input.error {
            border-color: rgba(214,17,25,0.7);
        }
        .form-control input[type="color"] {
            height: 48px;
            padding: 0;
            border-radius: 14px;
            cursor: pointer;
        }
        .error-text {
            color: rgba(214,17,25,0.95);
            font-size: 0.85rem;
        }
        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1rem;
        }
        .primary-btn {
            border: none;
            border-radius: 18px;
            padding: 0.95rem 2rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
        }
        .ghost-btn {
            border: 1px dashed rgba(15,23,42,0.25);
            background: transparent;
            border-radius: 18px;
            padding: 0.95rem 1.8rem;
            font-weight: 600;
            color: var(--slate);
        }
        .logo-preview-card {
            border: 1px dashed rgba(15,23,42,0.2);
            border-radius: 20px;
            padding: 1.25rem;
            text-align: center;
            background: rgba(15,23,42,0.02);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
        }
        .logo-preview-card img {
            width: 120px;
            height: 120px;
            border-radius: 28px;
            object-fit: cover;
            box-shadow: 0 15px 45px rgba(15,23,42,0.12);
            border: 1px solid rgba(15,23,42,0.08);
            background: #fff;
        }
        .logo-preview-card span {
            font-weight: 600;
            color: var(--muted);
        }
        .color-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 1rem 0;
        }
        .color-chip {
            flex: 1 1 180px;
            border-radius: 16px;
            padding: 0.85rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 10px 25px rgba(15,23,42,0.08);
        }
        .color-chip.light {
            color: var(--slate);
        }
        .placeholder-card {
            border: 2px dashed rgba(15,23,42,0.2);
            border-radius: 24px;
            padding: 1.75rem;
            text-align: center;
            color: var(--muted);
            background: rgba(15,23,42,0.02);
        }
        .bg-image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
            margin-top: 0.8rem;
        }
        .bg-image-card {
            border: 1px solid rgba(15,23,42,0.12);
            border-radius: 16px;
            padding: 0.65rem;
            background: rgba(15,23,42,0.02);
        }
        .bg-image-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            margin-bottom: 0.5rem;
        }
        .bg-image-card label {
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }
        @media (max-width: 640px) {
            .tab-pill {
                flex: 1;
                min-width: 100%;
            }
            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
                margin-top: 1.25rem;
            }
        }
    </style>

    <div class="settings-wrapper">
        <div class="settings-hero">
            <div>
                <h2>مرکز کنترل تنظیمات</h2>
                <p>تمامی گزینه‌های مدیریتی را در یک صفحه و با تجربه‌ای سازگار مدیریت کنید.</p>
            </div>
            @if (session('status'))
                <div class="status-message">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        <div class="settings-card">
            <div class="settings-tabs" id="settingsTabs" data-active-tab="{{ $activeTab }}">
                @foreach ($tabs as $tab)
                    <button
                        type="button"
                        class="tab-pill {{ $activeTab === $tab['id'] ? 'active' : '' }}"
                        data-tab-target="{{ $tab['id'] }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        <small>{{ $tab['subtitle'] }}</small>
                    </button>
                @endforeach
            </div>
            <div class="tab-panels">
                <section class="tab-panel {{ $activeTab === 'password' ? 'active' : '' }}" data-tab-panel="password">
                    <h3>به‌روزرسانی رمز عبور مدیر</h3>
                    <p>حداقل طول رمز طبق تب «امنیت ورود» (اکنون {{ (int) ($securityCfg['admin_password_min_length'] ?? 8) }} کاراکتر) اعمال می‌شود. از ترکیب حروف و اعداد استفاده کنید.</p>
                    <form method="POST" action="{{ route('admin.settings.password') }}" class="password-form">
                        @csrf
                        <div class="form-grid">
                            <div class="form-control">
                                <label for="current-password">رمز عبور فعلی</label>
                                <input
                                    id="current-password"
                                    type="password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    class="{{ $passwordErrors->has('current_password') ? 'error' : '' }}"
                                >
                                @if ($passwordErrors->has('current_password'))
                                    <span class="error-text">{{ $passwordErrors->first('current_password') }}</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="new-password">رمز عبور جدید</label>
                                <input
                                    id="new-password"
                                    type="password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    class="{{ $passwordErrors->has('new_password') ? 'error' : '' }}"
                                >
                                @if ($passwordErrors->has('new_password'))
                                    <span class="error-text">{{ $passwordErrors->first('new_password') }}</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="new-password-confirmation">تکرار رمز عبور جدید</label>
                                <input
                                    id="new-password-confirmation"
                                    type="password"
                                    name="new_password_confirmation"
                                    autocomplete="new-password"
                                    class="{{ $passwordErrors->has('new_password_confirmation') ? 'error' : '' }}"
                                >
                                @if ($passwordErrors->has('new_password_confirmation'))
                                    <span class="error-text">{{ $passwordErrors->first('new_password_confirmation') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="primary-btn">ثبت و به‌روزرسانی</button>
                            <button type="reset" class="ghost-btn">بازنشانی فرم</button>
                        </div>
                    </form>
                </section>

                <section class="tab-panel {{ $activeTab === 'branding' ? 'active' : '' }}" data-tab-panel="branding">
                    <h3>تنظیم نام و لوگوی سامانه</h3>
                    <p>با به‌روزرسانی این بخش، اطلاعات صفحه‌ی خوش‌آمد و ناحیه مدیریت به‌صورت یکپارچه تغییر می‌کند.</p>
                    <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" class="branding-form">
                        @csrf
                        <div class="form-grid branding-grid">
                            <div class="form-control">
                                <label for="app-name">نام سامانه</label>
                                <input
                                    id="app-name"
                                    type="text"
                                    name="app_name"
                                    value="{{ old('app_name', $appSettings['app_name'] ?? 'سامانه نظرسنجی') }}"
                                    class="{{ $brandingErrors->has('app_name') ? 'error' : '' }}"
                                >
                                @if ($brandingErrors->has('app_name'))
                                    <span class="error-text">{{ $brandingErrors->first('app_name') }}</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="survey-footer-text">متن فوتر فرم نظرسنجی</label>
                                <input
                                    id="survey-footer-text"
                                    type="text"
                                    name="survey_footer_text"
                                    value="{{ old('survey_footer_text', $appSettings['survey_footer_text'] ?? 'طراحی و توسعه توسط واحد فناوری اطلاعات توسعه نرم افزار') }}"
                                    class="{{ $brandingErrors->has('survey_footer_text') ? 'error' : '' }}"
                                    placeholder="متن پایین فرم عمومی نظرسنجی"
                                >
                                @if ($brandingErrors->has('survey_footer_text'))
                                    <span class="error-text">{{ $brandingErrors->first('survey_footer_text') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">این متن در پایین صفحه تکمیل فرم نظرسنجی نمایش داده می‌شود.</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="logo-upload">لوگوی جدید (PNG یا JPG)</label>
                                <input
                                    id="logo-upload"
                                    type="file"
                                    name="logo"
                                    accept="image/png,image/jpeg,image/jpg"
                                    class="{{ $brandingErrors->has('logo') ? 'error' : '' }}"
                                >
                                @if ($brandingErrors->has('logo'))
                                    <span class="error-text">{{ $brandingErrors->first('logo') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">حداکثر اندازه ۲ مگابایت – نسبت پیشنهادی ۱:۱</span>
                                @endif
                            </div>
                            <div class="logo-preview-card">
                                <img src="{{ asset($appSettings['logo_path'] ?? 'storage/logo.png') }}" alt="لوگوی فعلی" id="logo-preview">
                                <span>پیش‌نمایش لوگوی فعلی</span>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="primary-btn">ذخیره تنظیمات برند</button>
                            <button type="reset" class="ghost-btn">بازنشانی فرم</button>
                        </div>
                    </form>
                </section>

                <section class="tab-panel {{ $activeTab === 'colors' ? 'active' : '' }}" data-tab-panel="colors">
                    <h3>رنگ‌بندی عمومی سامانه</h3>
                    <p>این بخش مخصوص رنگ‌های پایه پنل است. تنظیمات تصویر پس‌زمینه سراسری سامانه هم پایین همین تب قرار دارد (مستقل از صفحه ورود).</p>
                    @php
                        $colorValues = $appSettings['colors'] ?? [];
                        $colorFields = [
                            ['name' => 'primary', 'label' => 'رنگ اصلی', 'hint' => 'دکمه‌ها و لینک‌های کلیدی', 'default' => '#D61119'],
                            ['name' => 'primary_dark', 'label' => 'رنگ تیره دکمه‌ها', 'hint' => 'گرادیان و حالت Hover', 'default' => '#ab0c12'],
                            ['name' => 'slate', 'label' => 'متن اصلی', 'hint' => 'عناوین و تیترها', 'default' => '#0F172A'],
                            ['name' => 'muted', 'label' => 'متن خنثی', 'hint' => 'توضیحات و متن‌های ثانویه', 'default' => '#6B7280'],
                            ['name' => 'sidebar', 'label' => 'پس‌زمینه منوی ادمین', 'hint' => 'رنگ پس‌زمینه سایدبار', 'default' => '#0c111d'],
                            ['name' => 'background', 'label' => 'پس‌زمینه داشبورد', 'hint' => 'پس‌زمینه کلی پنل مدیریت', 'default' => '#f4f5f7'],
                            ['name' => 'accent_light', 'label' => 'گرادیان روشن', 'hint' => 'پس‌زمینه صفحه ورود (لایه اول)', 'default' => '#ffe8e9'],
                            ['name' => 'accent_lighter', 'label' => 'گرادیان خیلی روشن', 'hint' => 'پس‌زمینه صفحه ورود (لایه دوم)', 'default' => '#f5f5f7'],
                            ['name' => 'text_primary', 'label' => 'متن عمومی', 'hint' => 'متن صفحه ورود و خوش‌آمد', 'default' => '#111827'],
                            ['name' => 'welcome_background', 'label' => 'پس‌زمینه صفحه Welcome', 'hint' => 'پس‌زمینه اصلی سایت عمومی', 'default' => '#F9FAFB'],
                        ];
                    @endphp
                    <form method="POST" action="{{ route('admin.settings.colors') }}" class="color-form">
                        @csrf
                        <div class="form-grid color-grid">
                            @foreach ($colorFields as $color)
                                @php
                                    $value = old($color['name'], $colorValues[$color['name']] ?? $color['default']);
                                @endphp
                                <div class="form-control">
                                    <label for="color-{{ $color['name'] }}">{{ $color['label'] }}</label>
                                    <input
                                        id="color-{{ $color['name'] }}"
                                        type="color"
                                        name="{{ $color['name'] }}"
                                        value="{{ $value }}"
                                        class="{{ $colorErrors->has($color['name']) ? 'error' : '' }}"
                                    >
                                    <small>{{ $color['hint'] }}</small>
                                    @if ($colorErrors->has($color['name']))
                                        <span class="error-text">{{ $colorErrors->first($color['name']) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="color-preview">
                            <div class="color-chip" style="background: {{ $colorValues['primary'] ?? '#D61119' }};">
                                <span>Primary</span>
                                <span>{{ $colorValues['primary'] ?? '#D61119' }}</span>
                            </div>
                            <div class="color-chip" style="background: {{ $colorValues['sidebar'] ?? '#0c111d' }};">
                                <span>Sidebar</span>
                                <span>{{ $colorValues['sidebar'] ?? '#0c111d' }}</span>
                            </div>
                            <div class="color-chip light" style="background: {{ $colorValues['background'] ?? '#f4f5f7' }};">
                                <span>Dashboard</span>
                                <span>{{ $colorValues['background'] ?? '#f4f5f7' }}</span>
                            </div>
                            <div class="color-chip light" style="background: {{ $colorValues['welcome_background'] ?? '#F9FAFB' }};">
                                <span>Welcome</span>
                                <span>{{ $colorValues['welcome_background'] ?? '#F9FAFB' }}</span>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="primary-btn">ذخیره رنگ‌ها</button>
                            <button type="reset" class="ghost-btn">بازنشانی فرم</button>
                        </div>
                    </form>

                    <hr style="border:none;border-top:1px solid rgba(15,23,42,0.08);margin:1.5rem 0;">
                    <h4 style="margin:0 0 .4rem;">بک‌گراند سراسری پنل (مستقل از لاگین)</h4>
                    <p style="margin:0 0 .9rem;color:var(--muted);font-size:.9rem;">تصویر پشت کل سامانه با لایه مات نمایش داده می‌شود تا آیتم‌ها خوانا بمانند.</p>
                    <form method="POST" action="{{ route('admin.settings.system-background') }}" enctype="multipart/form-data">
                        @csrf
                        @php
                            $sysMode = old('mode', $systemBgCfg['mode'] ?? 'gradient');
                            $sysActive = old('active_image', $systemBgCfg['active_image'] ?? null);
                            $sysRandom = old('random_images', $systemBgCfg['random_images'] ?? []);
                            $sysRandom = is_array($sysRandom) ? $sysRandom : [];
                        @endphp
                        <div class="form-grid">
                            <div class="form-control">
                                <label for="sys-bg-mode">حالت بک‌گراند سراسری</label>
                                <select id="sys-bg-mode" name="mode">
                                    <option value="gradient" @selected($sysMode === 'gradient')>فقط رنگ پس‌زمینه پنل</option>
                                    <option value="single" @selected($sysMode === 'single')>یک تصویر ثابت</option>
                                    <option value="random" @selected($sysMode === 'random')>تصویر تصادفی در هر بار بارگذاری</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label for="sys-overlay-opacity">شدت لایه مات (0 تا 80 درصد)</label>
                                <input id="sys-overlay-opacity" type="number" min="0" max="80" name="overlay_opacity" value="{{ old('overlay_opacity', $systemBgCfg['overlay_opacity'] ?? 35) }}">
                            </div>
                            <div class="form-control" style="justify-content: center;">
                                <label class="inline-toggle">
                                    <input type="checkbox" name="enable_glass_ui" value="1" @checked(old('enable_glass_ui', (bool) ($systemBgCfg['enable_glass_ui'] ?? false)))>
                                    هدر بالا و کارت‌ها حالت شیشه‌ای (Glass) داشته باشند
                                </label>
                                <span class="error-text" style="color: var(--muted);">برای هماهنگی بهتر با بک‌گراند فعال کنید. خوانایی متن حفظ می‌شود.</span>
                            </div>
                            <div class="form-control">
                                <label for="sys-bg-upload">آپلود تصویر(های) جدید</label>
                                <input id="sys-bg-upload" type="file" name="uploads[]" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="{{ $systemBackgroundErrors->has('uploads') || $systemBackgroundErrors->has('uploads.*') ? 'error' : '' }}">
                                @if ($systemBackgroundErrors->has('uploads') || $systemBackgroundErrors->has('uploads.*'))
                                    <span class="error-text">{{ $systemBackgroundErrors->first('uploads') ?: $systemBackgroundErrors->first('uploads.*') }}</span>
                                @endif
                            </div>
                        </div>

                        @if (!empty($systemBgImages))
                            <div class="bg-image-grid">
                                @foreach ($systemBgImages as $img)
                                    <div class="bg-image-card">
                                        <img src="{{ asset($img) }}" alt="بک‌گراند سراسری">
                                        <label><input type="radio" name="active_image" value="{{ $img }}" @checked($sysActive === $img)> تصویر ثابت</label>
                                        <label><input type="checkbox" name="random_images[]" value="{{ $img }}" @checked(in_array($img, $sysRandom, true))> داخل لیست تصادفی</label>
                                        <label><input type="checkbox" name="remove_images[]" value="{{ $img }}"> حذف</label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="form-actions">
                            <button type="submit" class="primary-btn">ذخیره بک‌گراند سراسری</button>
                        </div>
                    </form>
                </section>

                <section class="tab-panel {{ $activeTab === 'security' ? 'active' : '' }}" data-tab-panel="security">
                    <h3>سیاست امنیتی ورود به پنل</h3>
                    <p>محدودیت تلاش ورود، مدت مسدودسازی، طول نگهداری گزارش رویدادهای ورود و زمان بی‌حرکتی نشست را از اینجا مدیریت کنید. این مقادیر بلافاصله روی فرم ورود مدیر اعمال می‌شوند.</p>
                    <form method="POST" action="{{ route('admin.settings.security') }}" class="security-form">
                        @csrf
                        <div class="form-grid">
                            <div class="form-control">
                                <label for="max-login-attempts">حداکثر تلاش ناموفق (نام کاربری و رمز)</label>
                                <input
                                    id="max-login-attempts"
                                    type="number"
                                    name="max_login_attempts"
                                    min="1"
                                    max="100"
                                    value="{{ old('max_login_attempts', $securityCfg['max_login_attempts'] ?? 5) }}"
                                    class="{{ $securityErrors->has('max_login_attempts') ? 'error' : '' }}"
                                >
                                @if ($securityErrors->has('max_login_attempts'))
                                    <span class="error-text">{{ $securityErrors->first('max_login_attempts') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">پس از رسیدن به این تعداد، ورود با همان نام کاربری موقتاً مسدود می‌شود. کپچای اشتباه جداگانه شمارش می‌شود.</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="lockout-minutes">مدت مسدودسازی (دقیقه)</label>
                                <input
                                    id="lockout-minutes"
                                    type="number"
                                    name="lockout_minutes"
                                    min="1"
                                    max="10080"
                                    value="{{ old('lockout_minutes', $securityCfg['lockout_minutes'] ?? 15) }}"
                                    class="{{ $securityErrors->has('lockout_minutes') ? 'error' : '' }}"
                                >
                                @if ($securityErrors->has('lockout_minutes'))
                                    <span class="error-text">{{ $securityErrors->first('lockout_minutes') }}</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="log-retention-days">نگهداری گزارش ورود (روز)</label>
                                <input
                                    id="log-retention-days"
                                    type="number"
                                    name="log_retention_days"
                                    min="1"
                                    max="3650"
                                    value="{{ old('log_retention_days', $securityCfg['log_retention_days'] ?? 90) }}"
                                    class="{{ $securityErrors->has('log_retention_days') ? 'error' : '' }}"
                                >
                                @if ($securityErrors->has('log_retention_days'))
                                    <span class="error-text">{{ $securityErrors->first('log_retention_days') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">رکوردهای قدیمی‌تر به‌صورت خودکار حذف می‌شوند.</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="session-idle">زمان بی‌حرکتی نشست (دقیقه)</label>
                                <input
                                    id="session-idle"
                                    type="number"
                                    name="session_idle_timeout_minutes"
                                    min="0"
                                    max="10080"
                                    value="{{ old('session_idle_timeout_minutes', $securityCfg['session_idle_timeout_minutes'] ?? 0) }}"
                                    class="{{ $securityErrors->has('session_idle_timeout_minutes') ? 'error' : '' }}"
                                >
                                @if ($securityErrors->has('session_idle_timeout_minutes'))
                                    <span class="error-text">{{ $securityErrors->first('session_idle_timeout_minutes') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">مقدار ۰ یعنی غیرفعال (فقط محدودیت نشست لاراول).</span>
                                @endif
                            </div>
                            <div class="form-control">
                                <label for="admin-pwd-min">حداقل طول رمز مدیر (کاراکتر)</label>
                                <input
                                    id="admin-pwd-min"
                                    type="number"
                                    name="admin_password_min_length"
                                    min="8"
                                    max="128"
                                    value="{{ old('admin_password_min_length', $securityCfg['admin_password_min_length'] ?? 8) }}"
                                    class="{{ $securityErrors->has('admin_password_min_length') ? 'error' : '' }}"
                                >
                                @if ($securityErrors->has('admin_password_min_length'))
                                    <span class="error-text">{{ $securityErrors->first('admin_password_min_length') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">هنگام تغییر رمز در همین صفحه اعمال می‌شود.</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="primary-btn">ذخیره تنظیمات امنیتی</button>
                        </div>
                    </form>
                </section>

                <section class="tab-panel {{ $activeTab === 'login_page' ? 'active' : '' }}" data-tab-panel="login_page">
                    <h3>تنظیمات استاندارد صفحه ورود</h3>
                    <p>عنوان/توضیح صفحه ورود، فعال یا غیرفعال بودن کپچا، شفافیت کارت لاگین و مدیریت چندین تصویر پس‌زمینه را از اینجا کنترل کنید.</p>
                    <form method="POST" action="{{ route('admin.settings.login-page') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <div class="form-control">
                                <label for="login-title">عنوان صفحه ورود</label>
                                <input id="login-title" type="text" name="title" value="{{ old('title', $loginPageCfg['title'] ?? 'ورود به ناحیه مدیریت') }}" class="{{ $loginPageErrors->has('title') ? 'error' : '' }}">
                                @error('title')<span class="error-text">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-control">
                                <label for="login-subtitle">توضیح صفحه ورود</label>
                                <input id="login-subtitle" type="text" name="subtitle" value="{{ old('subtitle', $loginPageCfg['subtitle'] ?? 'برای ورود به پنل مدیریت، اطلاعات حساب خود را وارد کنید.') }}" class="{{ $loginPageErrors->has('subtitle') ? 'error' : '' }}">
                                @error('subtitle')<span class="error-text">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-control">
                                <label for="login-bg-mode">حالت پس‌زمینه</label>
                                <select id="login-bg-mode" name="background_mode" class="{{ $loginPageErrors->has('background_mode') ? 'error' : '' }}">
                                    @php $bgMode = old('background_mode', $loginPageCfg['background_mode'] ?? 'gradient'); @endphp
                                    <option value="gradient" @selected($bgMode === 'gradient')>گرادیان پیش‌فرض (بدون عکس)</option>
                                    <option value="single" @selected($bgMode === 'single')>نمایش یک عکس ثابت</option>
                                    <option value="random" @selected($bgMode === 'random')>نمایش تصادفی از عکس‌های انتخاب‌شده</option>
                                </select>
                                @error('background_mode')<span class="error-text">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-control">
                                <label for="card-opacity">شفافیت کارت ورود (70 تا 100)</label>
                                <input id="card-opacity" type="number" min="70" max="100" name="card_opacity" value="{{ old('card_opacity', $loginPageCfg['card_opacity'] ?? 95) }}" class="{{ $loginPageErrors->has('card_opacity') ? 'error' : '' }}">
                                @error('card_opacity')<span class="error-text">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-control">
                                <label for="login-bg-upload">آپلود یک یا چند بک‌گراند</label>
                                <input id="login-bg-upload" type="file" name="background_uploads[]" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="{{ $loginPageErrors->has('background_uploads') || $loginPageErrors->has('background_uploads.*') ? 'error' : '' }}">
                                @if ($loginPageErrors->has('background_uploads') || $loginPageErrors->has('background_uploads.*'))
                                    <span class="error-text">{{ $loginPageErrors->first('background_uploads') ?: $loginPageErrors->first('background_uploads.*') }}</span>
                                @else
                                    <span class="error-text" style="color: var(--muted);">هر فایل حداکثر ۵ مگابایت. فرمت‌های رایج تصویر پشتیبانی می‌شود.</span>
                                @endif
                            </div>
                            <div class="form-control" style="justify-content: center;">
                                <label class="inline-toggle">
                                    <input type="checkbox" name="enable_captcha" value="1" @checked(old('enable_captcha', (bool) ($loginPageCfg['enable_captcha'] ?? true)))>
                                    کپچای صفحه ورود فعال باشد
                                </label>
                            </div>
                        </div>

                        @if (!empty($loginPageImages))
                            @php
                                $activeBg = old('active_background', $loginPageCfg['active_background'] ?? null);
                                $randomBgs = old('random_backgrounds', $loginPageCfg['random_backgrounds'] ?? []);
                                $randomBgs = is_array($randomBgs) ? $randomBgs : [];
                            @endphp
                            <div class="bg-image-grid">
                                @foreach ($loginPageImages as $img)
                                    <div class="bg-image-card">
                                        <img src="{{ asset($img) }}" alt="بک‌گراند ورود">
                                        <label>
                                            <input type="radio" name="active_background" value="{{ $img }}" @checked($activeBg === $img)>
                                            عکس ثابت
                                        </label>
                                        <label>
                                            <input type="checkbox" name="random_backgrounds[]" value="{{ $img }}" @checked(in_array($img, $randomBgs, true))>
                                            در لیست تصادفی
                                        </label>
                                        <label>
                                            <input type="checkbox" name="remove_backgrounds[]" value="{{ $img }}">
                                            حذف تصویر
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="form-actions">
                            <button type="submit" class="primary-btn">ذخیره تنظیمات صفحه ورود</button>
                        </div>
                    </form>
                </section>

                <section class="tab-panel {{ $activeTab === 'profile' ? 'active' : '' }}" data-tab-panel="profile">
                    <div class="placeholder-card">
                        <h3>به‌روزرسانی پروفایل مدیر</h3>
                        <p>این بخش به‌زودی فعال می‌شود. در اینجا می‌توانید مشخصات مدیر و اطلاعات تماس را شخصی‌سازی کنید.</p>
                    </div>
                </section>

                <section class="tab-panel {{ $activeTab === 'notifications' ? 'active' : '' }}" data-tab-panel="notifications">
                    <div class="placeholder-card">
                        <h3>مدیریت اعلان‌ها و هشدارها</h3>
                        <p>در نسخه‌های بعدی امکان تنظیم دقیق ایمیل‌ها، پیامک‌ها و هشدارهای داشبورد در این تب ارائه خواهد شد.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabsContainer = document.getElementById('settingsTabs');
            const buttons = tabsContainer.querySelectorAll('.tab-pill');
            const panels = document.querySelectorAll('.tab-panel');
            const logoInput = document.getElementById('logo-upload');
            const logoPreview = document.getElementById('logo-preview');

            const activateTab = (targetId) => {
                buttons.forEach((btn) => {
                    const isActive = btn.dataset.tabTarget === targetId;
                    btn.classList.toggle('active', isActive);
                });
                panels.forEach((panel) => {
                    const isActive = panel.dataset.tabPanel === targetId;
                    panel.classList.toggle('active', isActive);
                });
            };

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => activateTab(btn.dataset.tabTarget));
            });

            if (logoInput && logoPreview) {
                logoInput.addEventListener('change', (event) => {
                    const [file] = event.target.files || [];
                    if (!file) {
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        logoPreview.src = e.target?.result || logoPreview.src;
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
@endsection

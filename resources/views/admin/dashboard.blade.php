@extends('admin.layouts.app')

@section('page-title', 'داشبورد مدیریتی')
@section('page-description', 'نمای کلی عملکرد سیستم و دسترسی سریع به ماژول‌ها')

@php
    $stats = $stats ?? ['units' => 0, 'positions' => 0, 'personnel' => 0];
    $admin = $admin ?? null;
@endphp

@section('content')
    <style>
        .panel-grid .panel {
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 0.9rem;
        }

        .panel-grid .panel p {
            flex: 1;
        }

        .panel .primary-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
            padding: 0.85rem 1.4rem;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-weight: 600;
        }
    </style>
    <section class="stats-grid">
        @if ($admin && $admin->isSupervisor())
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14m7-7H5" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($stats['units'] ?? 0) }}</h3>
                    <span>واحد تحت سرپرستی</span>
                </div>
            </div>
            @if ($admin->hasPermission(\App\Support\AdminPermissions::SURVEYS))
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M5 7h14v4H5zM5 13h14v4H5zM5 19h9" />
                        </svg>
                    </div>
                    <div>
                        <h3>{{ number_format($stats['active_surveys'] ?? 0) }}</h3>
                        <span>نظرسنجی فعال (خودتان)</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 6h16M4 10h16M4 14h10M4 18h6" />
                        </svg>
                    </div>
                    <div>
                        <h3>{{ number_format($stats['my_responses'] ?? 0) }}</h3>
                        <span>پاسخ ثبت‌شده در نظرسنجی‌های شما</span>
                    </div>
                </div>
            @endif
        @else
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14m7-7H5" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($stats['units']) }}</h3>
                    <span>واحد ثبت شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M5 3v18M19 3v18M5 8h14M5 12h14M5 16h9" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($stats['positions']) }}</h3>
                    <span>سمت تعریف شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 6h16M4 10h16M4 14h10M4 18h6" />
                    </svg>
                </div>
                <div>
                    <h3>{{ number_format($stats['personnel']) }}</h3>
                    <span>پرسنل ثبت شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2a5 5 0 019.288-1.857" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h3>۰</h3>
                    <span>نظرسنجی‌های فعال</span>
                </div>
            </div>
        @endif
    </section>

    <section class="panel-grid">
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_UNITS))
            <div class="panel">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h18M3 12h18M3 18h10" />
                    </svg>
                    تعریف واحدها
                </h3>
                <p>برای افزودن یا ویرایش واحدهای سازمانی به صفحه اختصاصی آن بروید و ساختار تیمی را همیشه به‌روز نگه دارید.</p>
                <a href="{{ route('admin.units.index') }}" class="primary-link">مدیریت واحدها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_POSITIONS))
            <div class="panel">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m6-6H6" />
                    </svg>
                    تعریف سمت‌ها
                </h3>
                <p>سمت‌های کلیدی سازمان را در این بخش مدیریت کنید و برای اعضای تیم جایگاه مشخص تعیین نمایید.</p>
                <a href="{{ route('admin.positions.index') }}" class="primary-link">مدیریت سمت‌ها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::SURVEYS))
            <div class="panel">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M5 7h14v4H5zM5 13h14v4H5zM5 19h9" />
                    </svg>
                    مدیریت نظرسنجی‌ها
                </h3>
                <p>از اینجا می‌توانید نظرسنجی‌های جدید را تعریف کنید، وضعیت انتشار را مدیریت کرده و محدودیت‌های پاسخ‌دهی،
                    دسترسی کاربران و تنظیمات امنیتی را برای هر نظرسنجی کنترل کنید.</p>
                <a href="{{ route('admin.surveys.index') }}" class="primary-link">ورود به نظرسنجی‌ها</a>
            </div>
        @endif

        {{-- <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m6-6H6" />
                </svg>
                تعریف سمت‌ها
            </h3>
            <p>سمت‌های کلیدی سازمان را در این بخش مدیریت کنید و برای اعضای تیم جایگاه مشخص تعیین نمایید.</p>
            <a href="{{ route('admin.positions.index') }}" class="primary-link">مدیریت سمت‌ها</a>
        </div> --}}
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_PERSONNEL))
            <div class="panel">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 14l9-5-9-5-9 5 9 5zm0 0v7" />
                    </svg>
                    تعریف پرسنل
                </h3>
                <p>ثبت و ویرایش اطلاعات کارکنان، تخصیص به واحدها و تعیین سطح دسترسی از این قسمت مدیریت خواهد شد.</p>
                <a href="{{ route('admin.personnel.index') }}" class="primary-link">مدیریت پرسنل</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_SUPERVISORS))
            <div class="panel">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    ناظر واحدها
                </h3>
                <p>برای هر واحد یک ناظر مشخص کرده و به‌روزرسانی‌های لازم را از این بخش انجام دهید.</p>
                <a href="{{ route('admin.unit-supervisors.index') }}" class="primary-link">مدیریت ناظرها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::SETTINGS))
            <div class="panel">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    تنظیمات سامانه
                </h3>
                <p>برندینگ، رنگ‌ها، متن‌های عمومی و رمز ورود مدیر را از این بخش مدیریت کنید.</p>
                <a href="{{ route('admin.settings.index') }}" class="primary-link">تنظیمات</a>
            </div>
        @endif
    </section>
@endsection

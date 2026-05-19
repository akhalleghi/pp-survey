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
                    <i class="fa-solid fa-building" aria-hidden="true"></i>
                </div>
                <div>
                    <h3>{{ number_format($stats['units'] ?? 0) }}</h3>
                    <span>واحد تحت سرپرستی</span>
                </div>
            </div>
            @if ($admin->hasPermission(\App\Support\AdminPermissions::SURVEYS))
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3>{{ number_format($stats['active_surveys'] ?? 0) }}</h3>
                        <span>نظرسنجی فعال (خودتان)</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fa-solid fa-reply" aria-hidden="true"></i>
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
                    <i class="fa-solid fa-building" aria-hidden="true"></i>
                </div>
                <div>
                    <h3>{{ number_format($stats['units']) }}</h3>
                    <span>واحد ثبت شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                </div>
                <div>
                    <h3>{{ number_format($stats['positions']) }}</h3>
                    <span>سمت تعریف شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                </div>
                <div>
                    <h3>{{ number_format($stats['personnel']) }}</h3>
                    <span>پرسنل ثبت شده</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
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
                    <i class="fa-solid fa-sitemap" aria-hidden="true"></i>
                    تعریف واحدها
                </h3>
                <p>برای افزودن یا ویرایش واحدهای سازمانی به صفحه اختصاصی آن بروید و ساختار تیمی را همیشه به‌روز نگه دارید.</p>
                <a href="{{ route('admin.units.index') }}" class="primary-link">مدیریت واحدها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_POSITIONS))
            <div class="panel">
                <h3>
                    <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                    تعریف سمت‌ها
                </h3>
                <p>سمت‌های کلیدی سازمان را در این بخش مدیریت کنید و برای اعضای تیم جایگاه مشخص تعیین نمایید.</p>
                <a href="{{ route('admin.positions.index') }}" class="primary-link">مدیریت سمت‌ها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::SURVEYS))
            <div class="panel">
                <h3>
                    <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                    مدیریت نظرسنجی‌ها
                </h3>
                <p>از اینجا می‌توانید نظرسنجی‌های جدید را تعریف کنید، وضعیت انتشار را مدیریت کرده و محدودیت‌های پاسخ‌دهی،
                    دسترسی کاربران و تنظیمات امنیتی را برای هر نظرسنجی کنترل کنید.</p>
                <a href="{{ route('admin.surveys.index') }}" class="primary-link">ورود به نظرسنجی‌ها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_PERSONNEL))
            <div class="panel">
                <h3>
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    تعریف پرسنل
                </h3>
                <p>ثبت و ویرایش اطلاعات کارکنان، تخصیص به واحدها و تعیین سطح دسترسی از این قسمت مدیریت خواهد شد.</p>
                <a href="{{ route('admin.personnel.index') }}" class="primary-link">مدیریت پرسنل</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::ORG_SUPERVISORS))
            <div class="panel">
                <h3>
                    <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
                    ناظر واحدها
                </h3>
                <p>برای هر واحد یک ناظر مشخص کرده و به‌روزرسانی‌های لازم را از این بخش انجام دهید.</p>
                <a href="{{ route('admin.unit-supervisors.index') }}" class="primary-link">مدیریت ناظرها</a>
            </div>
        @endif
        @if (!$admin || $admin->hasPermission(\App\Support\AdminPermissions::SETTINGS))
            <div class="panel">
                <h3>
                    <i class="fa-solid fa-gear" aria-hidden="true"></i>
                    تنظیمات سامانه
                </h3>
                <p>برندینگ، رنگ‌ها، متن‌های عمومی و رمز ورود مدیر را از این بخش مدیریت کنید.</p>
                <button type="button" class="primary-link" data-open-settings="password" style="border:none;cursor:pointer;font-family:inherit">تنظیمات</button>
            </div>
        @endif
    </section>
@endsection

@extends('admin.layouts.app')

@section('page-title', 'داشبورد مدیریتی')
@section('page-description', 'نمای کلی عملکرد سیستم و دسترسی سریع به ماژول‌ها')

@section('content')
    <style>
        .panel .primary-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 1rem;
            padding: 0.85rem 1.4rem;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            font-weight: 600;
        }
    </style>
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14m7-7H5"/>
                </svg>
            </div>
            <div>
                <h3>۰%</h3>
                <span>میزان مشارکت امروز</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v18M19 3v18M5 8h14M5 12h14M5 16h9"/>
                </svg>
            </div>
            <div>
                <h3>۰</h3>
                <span>نظرسنجی‌های فعال</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h10M4 18h6"/>
                </svg>
            </div>
            <div>
                <h3>۰</h3>
                <span>پاسخ‌های جدید</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2a5 5 0 019.288-1.857"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h3>۰</h3>
                <span>پرسنل ثبت شده</span>
            </div>
        </div>
    </section>

    <section class="panel-grid">
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
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m6-6H6"/>
                </svg>
                تعریف سمت‌ها
            </h3>
            <p>سمت‌های کلیدی سازمان را در این بخش مدیریت کنید و برای اعضای تیم جایگاه مشخص تعیین نمایید.</p>
            <a href="{{ route('admin.positions.index') }}" class="primary-link">مدیریت سمت‌ها</a>
        </div>
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
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5zm0 0v7"/>
                </svg>
                تعریف پرسنل
            </h3>
            <p>ثبت و ویرایش اطلاعات کارکنان، تخصیص به واحدها و تعیین سطح دسترسی از این قسمت مدیریت خواهد شد.</p>
        </div>
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h18M3 12h18M3 18h10"/>
                </svg>
                تعریف واحدها
            </h3>
            <p>ساختار سازمانی، زیرواحدها و ارتباط آن‌ها با پرسنل را در این ماژول مدیریت کنید.</p>
        </div>
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m6-6H6"/>
                </svg>
                ایجاد نظرسنجی جدید
            </h3>
            <p>فرم‌های پویا طراحی کرده، زمان‌بندی انتشار و جامعه هدف را مشخص کنید.</p>
        </div>
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h10M4 18h6"/>
                </svg>
                گزارش‌گیری
            </h3>
            <p>گزارش‌های تحلیلی، نمودارهای تعاملی و خروجی‌های اکسل را اینجا دریافت کنید.</p>
        </div>
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10a3 3 0 10-6 0 3 3 0 006 0z"/>
                </svg>
                مدیریت کاربران مدیر
            </h3>
            <p>افزودن مدیران جدید، تعیین نقش‌ها و مشاهده فعالیت‌ها در این بخش انجام می‌شود.</p>
        </div>
        <div class="panel">
            <h3>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c1.657 0 3-.895 3-2s-1.343-2-3-2-3 .895-3 2 1.343 2 3 2zm0 0v12"/>
                </svg>
                سایر امکانات
            </h3>
            <p>به مرور زیرسیستم‌هایی مثل اعلان‌ها، تنظیمات برندینگ و مرکز پیام نیز فعال خواهند شد.</p>
        </div>
    </section>
@endsection

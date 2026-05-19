@extends('admin.layouts.app')

@section('page-title', 'تنظیمات سامانه')
@section('page-description', 'تنظیمات از طریق مدال پنل در دسترس است.')

@section('content')
    <div style="background:#fff;border-radius:20px;padding:2rem;border:1px solid rgba(15,23,42,0.08);text-align:center;max-width:520px;margin:2rem auto;">
        <p style="margin:0 0 1rem;color:var(--muted);">تنظیمات سامانه اکنون در مدال «تنظیمات برنامه» از منوی کنار باز می‌شود.</p>
        <button type="button" class="primary-link" style="border:none;cursor:pointer" data-open-settings="password" onclick="window.adminSettingsModal?.open('password')">
            باز کردن تنظیمات
        </button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.adminSettingsModal) {
                window.adminSettingsModal.open(@json(session('settings_active_tab', 'password')));
            }
        });
    </script>
@endsection

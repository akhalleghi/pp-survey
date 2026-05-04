<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\AdminLoginSecurityService;
use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $admin = AdminUser::find($request->session()->get('admin_id'));
        $activeTab = $request->session()->pull('settings_active_tab', 'password');
        $appSettings = AppSettings::all();

        return view('admin.settings', compact('admin', 'activeTab', 'appSettings'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = AdminUser::findOrFail($request->session()->get('admin_id'));

        $minLen = AdminLoginSecurityService::adminPasswordMinLength();
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:'.$minLen, 'max:128'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ], [
            'new_password.min' => 'رمز عبور باید حداقل '.$minLen.' کاراکتر باشد.',
        ]);

        if (! Hash::check($validated['current_password'], $admin->password)) {
            return back()
                ->withErrors(['current_password' => 'رمز عبور فعلی صحیح نیست.'], 'updatePassword')
                ->with('settings_active_tab', 'password');
        }

        $admin->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'رمز عبور با موفقیت به‌روزرسانی شد.')
            ->with('settings_active_tab', 'password');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updateBranding', [
            'app_name' => ['required', 'string', 'max:255'],
            'survey_footer_text' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'app_name' => $validated['app_name'],
            'survey_footer_text' => $validated['survey_footer_text'] ?? null,
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->storeAs('', 'logo.png', ['disk' => 'public']);
            $payload['logo_path'] = 'storage/'.$path;
        }

        AppSettings::update($payload);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'هویت بصری سامانه با موفقیت ذخیره شد.')
            ->with('settings_active_tab', 'branding');
    }

    public function updateColors(Request $request): RedirectResponse
    {
        $rules = [
            'primary' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'primary_dark' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'slate' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'muted' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'sidebar' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'background' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'accent_light' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'accent_lighter' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'text_primary' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'welcome_background' => ['required', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ];

        $validated = $request->validateWithBag('updateColors', $rules);

        $currentColors = AppSettings::get('colors', []);
        $merged = array_merge($currentColors, $validated);

        AppSettings::update(['colors' => $merged]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'رنگ‌بندی سامانه با موفقیت ذخیره شد.')
            ->with('settings_active_tab', 'colors');
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $defaults = AppSettings::all();
        $sec = $defaults['security'] ?? [];

        $validated = $request->validateWithBag('updateSecurity', [
            'max_login_attempts' => ['required', 'integer', 'min:1', 'max:100'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'log_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'session_idle_timeout_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'admin_password_min_length' => ['required', 'integer', 'min:8', 'max:128'],
        ]);

        $merged = array_merge($sec, [
            'max_login_attempts' => (int) $validated['max_login_attempts'],
            'lockout_minutes' => (int) $validated['lockout_minutes'],
            'log_retention_days' => (int) $validated['log_retention_days'],
            'session_idle_timeout_minutes' => (int) $validated['session_idle_timeout_minutes'],
            'admin_password_min_length' => (int) $validated['admin_password_min_length'],
        ]);

        AppSettings::update(['security' => $merged]);
        AdminLoginSecurityService::pruneOldLogsIfNeeded();

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'تنظیمات امنیتی ذخیره شد.')
            ->with('settings_active_tab', 'security');
    }
}

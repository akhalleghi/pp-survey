<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\AdminLoginSecurityService;
use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function updateLoginPage(Request $request): RedirectResponse
    {
        $all = AppSettings::all();
        $current = $all['login_page'] ?? [];

        $validated = $request->validateWithBag('updateLoginPage', [
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'enable_captcha' => ['nullable', 'boolean'],
            'background_mode' => ['required', 'in:gradient,single,random'],
            'card_opacity' => ['required', 'integer', 'min:70', 'max:100'],
            'background_uploads' => ['nullable', 'array'],
            'background_uploads.*' => ['image', 'max:5120'],
            'active_background' => ['nullable', 'string', 'max:255'],
            'random_backgrounds' => ['nullable', 'array'],
            'random_backgrounds.*' => ['string', 'max:255'],
            'remove_backgrounds' => ['nullable', 'array'],
            'remove_backgrounds.*' => ['string', 'max:255'],
        ]);

        $existingImages = array_values(array_filter((array) ($current['background_images'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
        $remove = array_values(array_filter((array) ($validated['remove_backgrounds'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
        $removeSet = array_flip($remove);

        foreach ($remove as $path) {
            if (in_array($path, $existingImages, true)) {
                $storagePath = Str::startsWith($path, 'storage/') ? Str::after($path, 'storage/') : $path;
                Storage::disk('public')->delete($storagePath);
            }
        }

        $kept = array_values(array_filter($existingImages, static fn ($path) => ! isset($removeSet[$path])));

        $uploadedPaths = [];
        foreach ($request->file('background_uploads', []) as $file) {
            if (! $file) {
                continue;
            }
            $stored = $file->store('login-backgrounds', 'public');
            $uploadedPaths[] = 'storage/'.$stored;
        }

        $allImages = array_values(array_unique(array_merge($kept, $uploadedPaths)));
        $active = $validated['active_background'] ?? null;
        if (! is_string($active) || ! in_array($active, $allImages, true)) {
            $active = $allImages[0] ?? null;
        }

        $randomSelected = array_values(array_filter((array) ($validated['random_backgrounds'] ?? []), static fn ($v) => is_string($v) && in_array($v, $allImages, true)));
        if ($validated['background_mode'] === 'random' && empty($randomSelected) && ! empty($allImages)) {
            $randomSelected = $allImages;
        }

        $backgroundMode = $validated['background_mode'];
        if ($backgroundMode === 'gradient' && $active !== null) {
            $backgroundMode = 'single';
        }
        if ($backgroundMode === 'gradient' && ! empty($randomSelected)) {
            $backgroundMode = 'random';
        }

        $loginSettings = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'enable_captcha' => $request->boolean('enable_captcha'),
            'background_mode' => $backgroundMode,
            'background_images' => $allImages,
            'active_background' => $active,
            'random_backgrounds' => $randomSelected,
            'card_opacity' => (int) $validated['card_opacity'],
        ];

        AppSettings::update(['login_page' => $loginSettings]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'تنظیمات صفحه ورود ذخیره شد.')
            ->with('settings_active_tab', 'login_page');
    }

    public function updateSystemBackground(Request $request): RedirectResponse
    {
        $all = AppSettings::all();
        $current = $all['system_background'] ?? [];

        $validated = $request->validateWithBag('updateSystemBackground', [
            'mode' => ['required', 'in:gradient,single,random'],
            'overlay_opacity' => ['required', 'integer', 'min:0', 'max:80'],
            'enable_glass_ui' => ['nullable', 'boolean'],
            'uploads' => ['nullable', 'array'],
            'uploads.*' => ['image', 'max:5120'],
            'active_image' => ['nullable', 'string', 'max:255'],
            'random_images' => ['nullable', 'array'],
            'random_images.*' => ['string', 'max:255'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string', 'max:255'],
        ]);

        $existingImages = array_values(array_filter((array) ($current['images'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
        $remove = array_values(array_filter((array) ($validated['remove_images'] ?? []), static fn ($v) => is_string($v) && $v !== ''));
        $removeSet = array_flip($remove);

        foreach ($remove as $path) {
            if (in_array($path, $existingImages, true)) {
                $storagePath = Str::startsWith($path, 'storage/') ? Str::after($path, 'storage/') : $path;
                Storage::disk('public')->delete($storagePath);
            }
        }

        $kept = array_values(array_filter($existingImages, static fn ($path) => ! isset($removeSet[$path])));

        $uploadedPaths = [];
        foreach ($request->file('uploads', []) as $file) {
            if (! $file) {
                continue;
            }
            $stored = $file->store('system-backgrounds', 'public');
            $uploadedPaths[] = 'storage/'.$stored;
        }

        $allImages = array_values(array_unique(array_merge($kept, $uploadedPaths)));
        $active = $validated['active_image'] ?? null;
        if (! is_string($active) || ! in_array($active, $allImages, true)) {
            $active = $allImages[0] ?? null;
        }

        $randomSelected = array_values(array_filter((array) ($validated['random_images'] ?? []), static fn ($v) => is_string($v) && in_array($v, $allImages, true)));
        if ($validated['mode'] === 'random' && empty($randomSelected) && ! empty($allImages)) {
            $randomSelected = $allImages;
        }

        $mode = $validated['mode'];
        if ($mode === 'gradient' && $active !== null) {
            $mode = 'single';
        }
        if ($mode === 'gradient' && ! empty($randomSelected)) {
            $mode = 'random';
        }

        AppSettings::update([
            'system_background' => [
                'mode' => $mode,
                'images' => $allImages,
                'active_image' => $active,
                'random_images' => $randomSelected,
                'overlay_opacity' => (int) $validated['overlay_opacity'],
                'enable_glass_ui' => $request->boolean('enable_glass_ui'),
            ],
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'بک‌گراند سراسری سامانه ذخیره شد.')
            ->with('settings_active_tab', 'colors');
    }
}

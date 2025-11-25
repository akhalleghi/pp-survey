<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
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

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'max:64'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ]);

        if (!Hash::check($validated['current_password'], $admin->password)) {
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
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'app_name' => $validated['app_name'],
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->storeAs('', 'logo.png', ['disk' => 'public']);
            $payload['logo_path'] = 'storage/' . $path;
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
}

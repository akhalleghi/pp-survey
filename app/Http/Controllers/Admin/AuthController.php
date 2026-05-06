<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\AdminUser;
use App\Services\AdminLoginSecurityService;
use App\Support\AdminPermissions;
use App\Support\AppSettings;
use App\Support\PersianCalendar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the admin login form and seed captcha.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('admin_id')) {
            $existing = AdminUser::find($request->session()->get('admin_id'));
            if ($existing && $existing->is_active) {
                $landing = AdminPermissions::defaultLandingRouteName($existing);
                if ($landing !== null) {
                    return redirect()->route($landing);
                }
            }
            $request->session()->forget('admin_id');
        }

        $loginSettings = AppSettings::get('login_page', []);
        $enableCaptcha = (bool) ($loginSettings['enable_captcha'] ?? true);
        $captcha = null;
        if ($enableCaptcha) {
            $captcha = $this->generateCaptcha();
            $request->session()->put('admin_captcha', $captcha);
        } else {
            $request->session()->forget('admin_captcha');
        }

        return view('admin.auth.login', compact('captcha'));
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $loginSettings = AppSettings::get('login_page', []);
        $enableCaptcha = (bool) ($loginSettings['enable_captcha'] ?? true);

        $rules = [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
        if ($enableCaptcha) {
            $rules['captcha'] = ['required', 'string'];
        }
        $validated = $request->validate($rules, [
            'captcha.required' => 'کد امنیتی الزامی است.',
        ]);

        $username = trim($validated['username']);

        if (AdminLoginSecurityService::isLocked($username)) {
            $until = AdminLoginSecurityService::lockedUntil($username);
            $detail = $until !== null
                ? 'تا '.PersianCalendar::formatDateTime(Carbon::parse($until))
                : null;
            AdminLoginSecurityService::logEvent(
                $request,
                $username,
                AdminLoginLog::OUTCOME_LOCKED,
                null,
                $detail
            );

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'به‌دلیل تلاش‌های ناموفق مکرر، ورود به‌طور موقت غیرفعال است. لطفاً بعداً دوباره تلاش کنید.']);
        }

        if ($enableCaptcha) {
            $sessionCaptcha = $request->session()->get('admin_captcha');
            if (! $sessionCaptcha || strcasecmp($validated['captcha'] ?? '', $sessionCaptcha) !== 0) {
                AdminLoginSecurityService::logEvent(
                    $request,
                    $username,
                    AdminLoginLog::OUTCOME_FAILED_CAPTCHA
                );

                return back()
                    ->withInput($request->only('username'))
                    ->withErrors(['captcha' => 'کد امنیتی وارد شده صحیح نیست.']);
            }
        }

        $admin = AdminUser::where('username', $username)->first();

        if (! $admin) {
            AdminLoginSecurityService::recordFailedPasswordAttempt($username);
            AdminLoginSecurityService::logEvent(
                $request,
                $username,
                AdminLoginLog::OUTCOME_USER_NOT_FOUND
            );

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'اطلاعات ورود نادرست است یا حساب غیرفعال شده است.']);
        }

        if (! $admin->is_active) {
            AdminLoginSecurityService::recordFailedPasswordAttempt($username);
            AdminLoginSecurityService::logEvent(
                $request,
                $username,
                AdminLoginLog::OUTCOME_FAILED_INACTIVE,
                $admin
            );

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'اطلاعات ورود نادرست است یا حساب غیرفعال شده است.']);
        }

        if (! Hash::check($validated['password'], $admin->password)) {
            AdminLoginSecurityService::recordFailedPasswordAttempt($username);
            AdminLoginSecurityService::logEvent(
                $request,
                $username,
                AdminLoginLog::OUTCOME_FAILED_PASSWORD,
                $admin
            );

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'اطلاعات ورود نادرست است یا حساب غیرفعال شده است.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        $request->session()->forget('admin_captcha');
        $request->session()->put('admin_last_activity', time());

        $landing = AdminPermissions::defaultLandingRouteName($admin);
        if ($landing === null) {
            $request->session()->forget('admin_id');
            AdminLoginSecurityService::logEvent(
                $request,
                $username,
                AdminLoginLog::OUTCOME_FAILED_NO_ACCESS,
                $admin
            );

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'هیچ بخش مجازی برای این حساب تعریف نشده است. با مدیر تماس بگیرید.']);
        }

        AdminLoginSecurityService::clearThrottle($username);
        AdminLoginSecurityService::logEvent(
            $request,
            $username,
            AdminLoginLog::OUTCOME_SUCCESS,
            $admin
        );

        return redirect()->route($landing);
    }

    /**
     * Destroy admin session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_id');
        $request->session()->forget('admin_last_activity');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Refresh captcha via AJAX.
     */
    public function refreshCaptcha(Request $request): JsonResponse
    {
        $loginSettings = AppSettings::get('login_page', []);
        if (! (bool) ($loginSettings['enable_captcha'] ?? true)) {
            return response()->json(['captcha' => null], 422);
        }

        $captcha = $this->generateCaptcha();
        $request->session()->put('admin_captcha', $captcha);

        return response()->json(['captcha' => $captcha]);
    }

    /**
     * Create a random captcha code.
     */
    protected function generateCaptcha(): string
    {
        return Str::upper(Str::random(5));
    }
}

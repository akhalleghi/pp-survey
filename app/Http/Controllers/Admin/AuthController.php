<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
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
            return redirect()->route('admin.dashboard');
        }

        $captcha = $this->generateCaptcha();
        $request->session()->put('admin_captcha', $captcha);

        return view('admin.auth.login', compact('captcha'));
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'string'],
        ], [
            'captcha.required' => 'کد امنیتی الزامی است.',
        ]);

        $sessionCaptcha = $request->session()->get('admin_captcha');
        if (!$sessionCaptcha || strcasecmp($validated['captcha'], $sessionCaptcha) !== 0) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['captcha' => 'کد امنیتی وارد شده صحیح نیست.']);
        }

        $admin = AdminUser::where('username', $validated['username'])->first();
        if (!$admin || !Hash::check($validated['password'], $admin->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'اطلاعات ورود نادرست است.']);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        $request->session()->forget('admin_captcha');

        return redirect()->route('admin.dashboard');
    }

    /**
     * Destroy admin session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Refresh captcha via AJAX.
     */
    public function refreshCaptcha(Request $request): JsonResponse
    {
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

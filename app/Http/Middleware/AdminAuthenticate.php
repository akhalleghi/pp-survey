<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('admin_id')) {
            return redirect()->route('admin.login')->with('error', 'برای دسترسی به این بخش ابتدا وارد شوید.');
        }

        $admin = AdminUser::find($request->session()->get('admin_id'));
        if (!$admin || !$admin->is_active) {
            $request->session()->forget('admin_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', 'حساب کاربری شما غیرفعال است یا وجود ندارد.');
        }

        $request->attributes->set('current_admin', $admin);

        return $next($request);
    }
}

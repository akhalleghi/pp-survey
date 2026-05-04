<?php

namespace App\Http\Middleware;

use App\Services\AdminLoginSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSessionIdle
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('admin_id')) {
            return $next($request);
        }

        $idleMinutes = AdminLoginSecurityService::sessionIdleTimeoutMinutes();
        if ($idleMinutes <= 0) {
            $request->session()->put('admin_last_activity', time());

            return $next($request);
        }

        $last = (int) $request->session()->get('admin_last_activity', 0);
        $now = time();
        if ($last > 0 && ($now - $last) > $idleMinutes * 60) {
            $request->session()->forget('admin_id');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['username' => 'نشست شما به‌دلیل عدم فعالیت پایان یافت. لطفاً دوباره وارد شوید.']);
        }

        $request->session()->put('admin_last_activity', $now);

        return $next($request);
    }
}

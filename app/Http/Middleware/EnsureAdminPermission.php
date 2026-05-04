<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * @param  string  ...$permissions  حداقل یکی از این مجوزها لازم است (OR).
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = AdminUser::find($request->session()->get('admin_id'));
        if (!$admin || !$admin->is_active) {
            return redirect()
                ->route('admin.login')
                ->with('error', 'برای دسترسی به این بخش ابتدا وارد شوید.');
        }

        if ($admin->isAdmin()) {
            return $next($request);
        }

        foreach ($permissions as $perm) {
            if ($admin->hasPermission($perm)) {
                return $next($request);
            }
        }

        $target = AdminPermissions::defaultLandingRouteName($admin);
        if ($target === null) {
            $request->session()->forget('admin_id');

            return redirect()
                ->route('admin.login')
                ->with('error', 'هیچ بخش مجازی برای حساب شما تعریف نشده است. با مدیر تماس بگیرید.');
        }

        return redirect()
            ->route($target)
            ->with('error', 'شما به این بخش دسترسی ندارید.');
    }
}

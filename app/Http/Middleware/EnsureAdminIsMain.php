<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * محدود کردن عملیات حساس (مثل پشتیبان‌گیری) به مدیر اصلی، نه ناظر.
 */
class EnsureAdminIsMain
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->attributes->get('current_admin');

        if (! $admin || ! $admin->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'فقط مدیر اصلی سامانه به این بخش دسترسی دارد.',
                ], 403);
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'فقط مدیر اصلی سامانه به این بخش دسترسی دارد.');
        }

        return $next($request);
    }
}

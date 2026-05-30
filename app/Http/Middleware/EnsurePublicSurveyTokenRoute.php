<?php

namespace App\Http\Middleware;

use App\Models\Survey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * جلوگیری از تداخل توکن با مسیرهای رزرو شدهٔ اپلیکیشن (مثل surveys، public).
 */
class EnsurePublicSurveyTokenRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->route('token');

        if ($token !== '' && Survey::isReservedPublicToken($token)) {
            abort(404);
        }

        return $next($request);
    }

}

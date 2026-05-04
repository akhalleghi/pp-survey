<?php

namespace App\Services;

use App\Models\AdminLoginLog;
use App\Models\AdminLoginThrottleState;
use App\Models\AdminUser;
use App\Support\AppSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLoginSecurityService
{
    public static function usernameKey(string $username): string
    {
        return hash('sha256', mb_strtolower(trim($username)));
    }

    public static function maxAttempts(): int
    {
        $v = AppSettings::get('security', [])['max_login_attempts'] ?? 5;

        return max(1, min(100, (int) $v));
    }

    public static function lockoutMinutes(): int
    {
        $v = AppSettings::get('security', [])['lockout_minutes'] ?? 15;

        return max(1, min(10080, (int) $v));
    }

    public static function logRetentionDays(): int
    {
        $v = AppSettings::get('security', [])['log_retention_days'] ?? 90;

        return max(1, min(3650, (int) $v));
    }

    public static function sessionIdleTimeoutMinutes(): int
    {
        $v = AppSettings::get('security', [])['session_idle_timeout_minutes'] ?? 0;

        return max(0, min(10080, (int) $v));
    }

    public static function adminPasswordMinLength(): int
    {
        $v = AppSettings::get('security', [])['admin_password_min_length'] ?? 8;

        return max(8, min(128, (int) $v));
    }

    public static function isLocked(string $username): bool
    {
        $key = self::usernameKey($username);
        $row = AdminLoginThrottleState::query()->where('username_key', $key)->first();
        if (! $row || ! $row->locked_until) {
            return false;
        }

        if (Carbon::now()->greaterThan($row->locked_until)) {
            self::clearThrottle($username);

            return false;
        }

        return true;
    }

    public static function lockedUntil(string $username): ?Carbon
    {
        $key = self::usernameKey($username);
        $row = AdminLoginThrottleState::query()->where('username_key', $key)->first();
        if (! $row || ! $row->locked_until || Carbon::now()->greaterThan($row->locked_until)) {
            return null;
        }

        return $row->locked_until;
    }

    public static function clearThrottle(string $username): void
    {
        AdminLoginThrottleState::query()->where('username_key', self::usernameKey($username))->delete();
    }

    /**
     * ثبت تلاش ناموفق رمز/کاربر و قفل در صورت رسیدن به سقف.
     */
    public static function recordFailedPasswordAttempt(string $username): void
    {
        $key = self::usernameKey($username);
        $max = self::maxAttempts();
        $minutes = self::lockoutMinutes();

        DB::transaction(function () use ($key, $max, $minutes, $username) {
            $row = AdminLoginThrottleState::query()->firstOrCreate(
                ['username_key' => $key],
                ['failed_attempts' => 0]
            );
            $row->username = mb_substr(trim($username), 0, 64);
            $row->failed_attempts = $row->failed_attempts + 1;
            $row->last_failed_at = Carbon::now();
            if ($row->failed_attempts >= $max) {
                $row->locked_until = Carbon::now()->addMinutes($minutes);
            }
            $row->save();
        });
    }

    public static function logEvent(
        Request $request,
        string $username,
        string $outcome,
        ?AdminUser $adminUser = null,
        ?string $detail = null
    ): void {
        try {
            AdminLoginLog::query()->create([
                'admin_user_id' => $adminUser?->id,
                'username' => mb_substr($username, 0, 64),
                'outcome' => $outcome,
                'ip_address' => self::clientIp($request),
                'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 2000) : null,
                'detail' => $detail ? mb_substr($detail, 0, 255) : null,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Throwable) {
            // ثبت لاگ نباید ورود را متوقف کند
        }

        if (mt_rand(1, 80) === 1) {
            self::pruneOldLogsIfNeeded();
        }
    }

    public static function clientIp(Request $request): ?string
    {
        $ip = $request->ip();
        if ($ip === null || $ip === '') {
            return null;
        }

        return mb_substr($ip, 0, 45);
    }

    public static function pruneOldLogsIfNeeded(): void
    {
        $days = self::logRetentionDays();
        $cutoff = Carbon::now()->subDays($days);
        try {
            AdminLoginLog::query()->where('created_at', '<', $cutoff)->delete();
        } catch (\Throwable) {
        }
    }

    /**
     * حساب‌هایی که در این لحظه به‌دلیل قفل ورود، مسدود هستند.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AdminLoginThrottleState>
     */
    public static function currentlyLockedStates(): \Illuminate\Database\Eloquent\Collection
    {
        return AdminLoginThrottleState::query()
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', Carbon::now())
            ->orderBy('locked_until')
            ->get();
    }
}

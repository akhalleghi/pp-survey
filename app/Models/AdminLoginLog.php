<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginLog extends Model
{
    public $timestamps = false;

    protected $table = 'admin_login_logs';

    protected $fillable = [
        'admin_user_id',
        'username',
        'outcome',
        'ip_address',
        'user_agent',
        'detail',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAILED_CAPTCHA = 'failed_captcha';

    public const OUTCOME_FAILED_PASSWORD = 'failed_password';

    public const OUTCOME_FAILED_INACTIVE = 'failed_inactive';

    public const OUTCOME_FAILED_NO_ACCESS = 'failed_no_access';

    public const OUTCOME_USER_NOT_FOUND = 'user_not_found';

    public const OUTCOME_LOCKED = 'account_locked';

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    public static function outcomeLabel(string $outcome): string
    {
        return match ($outcome) {
            self::OUTCOME_SUCCESS => 'ورود موفق',
            self::OUTCOME_FAILED_CAPTCHA => 'ناموفق — کد امنیتی نادرست',
            self::OUTCOME_FAILED_PASSWORD => 'ناموفق — رمز یا نام کاربری نادرست',
            self::OUTCOME_FAILED_INACTIVE => 'ناموفق — حساب غیرفعال',
            self::OUTCOME_FAILED_NO_ACCESS => 'ناموفق — بدون مجوز بخش',
            self::OUTCOME_USER_NOT_FOUND => 'ناموفق — کاربر نامعتبر',
            self::OUTCOME_LOCKED => 'مسدود — تلاش بیش از حد',
            default => $outcome,
        };
    }
}

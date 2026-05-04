<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitSupervisor extends Model
{
    /** @var array<string, ?AdminUser> */
    private static array $portalAdminResolveCache = [];

    use HasFactory;

    protected $fillable = [
        'personnel_code',
        'unit_id',
        'admin_user_id',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'personnel_code', 'personnel_code');
    }

    public function linkedAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * حساب پنل ناظر: از FK ردیف، یا در نبود FK با همان کد پرسنلی (برای ردیف‌های قدیمی بدون admin_user_id).
     */
    public function resolvePortalAdmin(): ?AdminUser
    {
        $this->loadMissing('linkedAdminUser');

        if ($this->linkedAdminUser) {
            return $this->linkedAdminUser;
        }

        $code = $this->personnel_code ?? '';
        if ($code === '') {
            return null;
        }

        if (! array_key_exists($code, self::$portalAdminResolveCache)) {
            self::$portalAdminResolveCache[$code] = AdminUser::query()
                ->where('personnel_code', $code)
                ->where('role', AdminUser::ROLE_SUPERVISOR)
                ->first();
        }

        return self::$portalAdminResolveCache[$code];
    }
}

<?php

namespace App\Models;

use App\Support\AdminPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use HasFactory;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPERVISOR = 'supervisor';

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'permissions',
        'personnel_code',
        'is_active',
        'requires_survey_publish_approval',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
            'requires_survey_publish_approval' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? self::ROLE_ADMIN) === self::ROLE_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return ($this->role ?? '') === self::ROLE_SUPERVISOR;
    }

    /**
     * @param  list<string>|null  $permissions
     */
    public function hasPermission(string $key, ?array $permissions = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if ($this->isSupervisor() && $key === AdminPermissions::DASHBOARD) {
            return true;
        }
        $list = $permissions ?? $this->permissions ?? [];

        return in_array($key, $list, true);
    }

    /**
     * شناسهٔ واحدهایی که این ناظر برای آن‌ها ثبت شده است.
     *
     * @return list<int>
     */
    public function supervisedUnitIds(): array
    {
        if ($this->isAdmin() || !$this->personnel_code) {
            return [];
        }

        return UnitSupervisor::query()
            ->where('personnel_code', $this->personnel_code)
            ->pluck('unit_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function createdSurveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'created_by_admin_user_id');
    }

    /**
     * نرمال‌سازی لیست مجوزها برای ذخیره.
     *
     * @param  array<int, string>|null  $input
     * @return list<string>
     */
    public static function normalizePermissionsInput(?array $input): array
    {
        $allowed = AdminPermissions::allKeys();
        $flat = array_values(array_unique(array_filter(
            array_map('strval', $input ?? []),
            fn ($k) => in_array($k, $allowed, true)
        )));

        return $flat;
    }
}

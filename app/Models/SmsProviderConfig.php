<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsProviderConfig extends Model
{
    protected $fillable = [
        'sms_provider_id',
        'username_encrypted',
        'password_encrypted',
        'send_number',
        'is_active',
        'last_tested_at',
        'updated_by_admin_user_id',
    ];

    protected $hidden = [
        'username_encrypted',
        'password_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmsProvider::class, 'sms_provider_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'updated_by_admin_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginThrottleState extends Model
{
    protected $table = 'admin_login_throttle_states';

    protected $fillable = [
        'username_key',
        'username',
        'failed_attempts',
        'locked_until',
        'last_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_until' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }
}

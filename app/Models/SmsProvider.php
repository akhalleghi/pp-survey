<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SmsProvider extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'driver',
        'default_api_url',
        'is_available',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function config(): HasOne
    {
        return $this->hasOne(SmsProviderConfig::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}

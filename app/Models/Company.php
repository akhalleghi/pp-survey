<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    public const TYPE_CONTRACTOR = 'contractor';

    public const TYPE_EMPLOYER = 'employer';

    protected $fillable = [
        'name',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_CONTRACTOR => 'پیمانکار',
            self::TYPE_EMPLOYER => 'کارفرما',
        ];
    }

    /**
     * @return list<string>
     */
    public static function typeKeys(): array
    {
        return array_keys(self::typeLabels());
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}

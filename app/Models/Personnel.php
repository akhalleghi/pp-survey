<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personnel extends Model
{
    use HasFactory;

    public const GENDERS = [
        'male' => 'مرد',
        'female' => 'زن',
        'other' => 'سایر',
    ];

    protected $table = 'personnel';

    protected $fillable = [
        'first_name',
        'last_name',
        'personnel_code',
        'mobile',
        'position_id',
        'unit_id',
        'gender',
        'national_code',
        'birth_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}

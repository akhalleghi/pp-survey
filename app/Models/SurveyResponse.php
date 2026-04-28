<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'personnel_id',
        'respondent_name',
        'identifier_type',
        'respondent_identifier',
        'is_anonymous',
        'status',
        'answers_count',
        'submitted_at',
        'last_seen_at',
        'meta',
        'edit_token',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'submitted_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'meta' => 'array',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function personnel()
    {
        return $this->belongsTo(Personnel::class);
    }

    public function answers()
    {
        return $this->hasMany(SurveyResponseAnswer::class, 'response_id');
    }
}


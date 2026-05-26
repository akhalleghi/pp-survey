<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'position',
        'type',
        'title',
        'description',
        'is_required',
        'settings',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'settings' => 'array',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function options()
    {
        return $this->hasMany(SurveyQuestionOption::class, 'question_id')->orderBy('position');
    }

    public function answers()
    {
        return $this->hasMany(SurveyResponseAnswer::class, 'question_id');
    }

    /** @return list<string> */
    public static function staticDisplayTypes(): array
    {
        return ['static_text_short', 'static_text_long'];
    }

    public function isStaticDisplay(): bool
    {
        return in_array($this->type, self::staticDisplayTypes(), true);
    }
}

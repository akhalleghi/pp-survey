<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponseAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'response_id',
        'question_id',
        'option_id',
        'answer_text',
        'answer_number',
        'answer_date',
        'answer_json',
    ];

    protected $casts = [
        'answer_number' => 'decimal:4',
        'answer_date' => 'date',
        'answer_json' => 'array',
    ];

    public function response()
    {
        return $this->belongsTo(SurveyResponse::class, 'response_id');
    }

    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(SurveyQuestionOption::class, 'option_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'unit_id',
        'description',
        'questions_count',
        'responses_count',
        'status',
        'response_window_hours',
        'response_limit',
        'response_edit_window_hours',
        'is_active',
        'is_anonymous',
        'require_auth',
        'track_location',
        'prevent_multiple_submissions',
        'allow_edit',
        'allow_partial',
        'shuffle_questions',
        'shuffle_options',
        'show_results_after_submit',
        'result_visibility',
        'audience_filters',
        'tags',
        'public_token',
        'start_at',
        'end_at',
        'thank_you_message',
        'background_image',
        'notification_emails',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_anonymous' => 'boolean',
        'require_auth' => 'boolean',
        'track_location' => 'boolean',
        'prevent_multiple_submissions' => 'boolean',
        'allow_edit' => 'boolean',
        'allow_partial' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_results_after_submit' => 'boolean',
        'audience_filters' => 'array',
        'tags' => 'array',
        'notification_emails' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('position');
    }
}

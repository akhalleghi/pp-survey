<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Survey extends Model
{
    use HasFactory;

    /** نمایش سوال‌به‌سوال با دکمه‌های قبلی/بعدی */
    public const QUESTIONS_DISPLAY_WIZARD = 'wizard';

    /** همهٔ سوالات در یک صفحه (بدون دکمهٔ قبلی/بعدی) */
    public const QUESTIONS_DISPLAY_SINGLE_PAGE = 'single_page';

    protected $fillable = [
        'title',
        'unit_id',
        'created_by_admin_user_id',
        'publish_requested_by_admin_user_id',
        'publish_rejection_reason',
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
        'intro_text',
        'background_image',
        'public_theme',
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
        'public_theme' => 'array',
        'notification_emails' => 'array',
        'start_at' => 'date',
        'end_at' => 'date',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    public function publishRequestedBy()
    {
        return $this->belongsTo(AdminUser::class, 'publish_requested_by_admin_user_id');
    }

    public function questions()
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function responses()
    {
        return $this->hasMany(SurveyResponse::class)->latest();
    }

    /**
     * تولید شناسهٔ کوتاه برای لینک عمومی (مناسب پیامک)، بدون تکرار در جدول نظرسنجی‌ها.
     * از حروف و اعداد بدون ابهام در پیامک (بدون ۰/و، ۱/ال، …) استفاده می‌شود.
     */
    public static function generateUniquePublicToken(int $length = 8): string
    {
        $alphabet = '23456789abcdefghjkmnpqrstuvwxyz';
        $maxIndex = strlen($alphabet) - 1;

        for ($attempt = 0; $attempt < 80; $attempt++) {
            $token = '';
            for ($i = 0; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, $maxIndex)];
            }
            if (!static::query()->where('public_token', $token)->exists()) {
                return $token;
            }
        }

        throw new RuntimeException('امکان تولید لینک یکتا پس از چند تلاش وجود ندارد.');
    }

    /**
     * Default CSS values for the public survey wizard (merged with stored public_theme).
     *
     * @return array<string, string>
     */
    public static function defaultPublicTheme(): array
    {
        return [
            /** @see self::QUESTIONS_DISPLAY_WIZARD | self::QUESTIONS_DISPLAY_SINGLE_PAGE */
            'questions_display_mode' => self::QUESTIONS_DISPLAY_WIZARD,
            'card_bg' => 'rgba(255,255,255,0.96)',
            'card_border' => 'rgba(15,23,42,0.12)',
            'title' => '#0f172a',
            'body' => '#334155',
            'muted' => '#64748b',
            'required_star' => '#0369a1',
            'input_bg' => '#ffffff',
            'input_border' => '#cbd5e1',
            'input_text' => '#0f172a',
            'input_placeholder' => '#94a3b8',
            'option_hover' => 'rgba(15,23,42,0.06)',
            'error_color' => '#b91c1c',
            'rating_wrap_bg' => 'rgba(248,250,252,0.95)',
            'rating_wrap_border' => 'rgba(15,23,42,0.12)',
            'footer_percent' => '#ffffff',
            'track_bg' => 'rgba(255,255,255,0.22)',
            'fill' => 'rgba(255,255,255,0.78)',
            'nav_prev' => 'rgba(13, 116, 133, 0.94)',
            'nav_next' => 'rgba(15, 118, 110, 0.96)',
        ];
    }

    public static function questionsDisplayModeOptions(): array
    {
        return [
            self::QUESTIONS_DISPLAY_WIZARD => 'تک‌به‌تک (با دکمه‌های قبلی و بعدی)',
            self::QUESTIONS_DISPLAY_SINGLE_PAGE => 'همهٔ سوالات در یک صفحه (فرم زیر هم، بدون دکمهٔ قبلی/بعدی)',
        ];
    }

    public static function normalizeQuestionsDisplayMode(?string $mode): string
    {
        return $mode === self::QUESTIONS_DISPLAY_SINGLE_PAGE
            ? self::QUESTIONS_DISPLAY_SINGLE_PAGE
            : self::QUESTIONS_DISPLAY_WIZARD;
    }
}

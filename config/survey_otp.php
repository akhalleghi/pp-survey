<?php

return [

    /*
    |--------------------------------------------------------------------------
    | تنظیمات احراز هویت پیامکی نظرسنجی عمومی
    |--------------------------------------------------------------------------
    */
    'code_length' => (int) env('SURVEY_OTP_CODE_LENGTH', 6),

    'ttl_seconds' => (int) env('SURVEY_OTP_TTL_SECONDS', 300),

    'resend_cooldown_seconds' => (int) env('SURVEY_OTP_RESEND_COOLDOWN', 60),

    'max_verify_attempts' => (int) env('SURVEY_OTP_MAX_VERIFY_ATTEMPTS', 5),

    'send_rate_limit_per_minute' => (int) env('SURVEY_OTP_SEND_RATE_LIMIT', 3),

    'verify_rate_limit_per_minute' => (int) env('SURVEY_OTP_VERIFY_RATE_LIMIT', 10),

];

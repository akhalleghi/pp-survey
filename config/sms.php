<?php

return [

    /*
    |--------------------------------------------------------------------------
    | درایورهای پنل پیامک
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'panel_3300' => [
            'api_url' => env('SMS_3300_API_URL', 'https://sms.3300.ir/api/wsSend.ashx'),
            'life_time' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | محدودیت ارسال تست از پنل تنظیمات
    |--------------------------------------------------------------------------
    */
    'test_rate_limit_per_minute' => (int) env('SMS_TEST_RATE_LIMIT', 5),

    'campaign_max_recipients' => (int) env('SMS_CAMPAIGN_MAX_RECIPIENTS', 500),

    'campaign_rate_limit_per_hour' => (int) env('SMS_CAMPAIGN_RATE_LIMIT_HOUR', 5),

    'campaign_min_confirm_seconds' => (int) env('SMS_CAMPAIGN_MIN_CONFIRM_SECONDS', 10),

    'campaign_send_delay_ms' => (int) env('SMS_CAMPAIGN_SEND_DELAY_MS', 350),

];

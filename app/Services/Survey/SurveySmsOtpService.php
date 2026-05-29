<?php

namespace App\Services\Survey;

use App\Models\Personnel;
use App\Models\Survey;
use App\Services\Sms\SmsPanelService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class SurveySmsOtpService
{
    public function __construct(
        private readonly SmsPanelService $smsPanelService,
    ) {}

    /**
     * @return array{ok: bool, message: string, cooldown_seconds?: int}
     */
    public function sendForPersonnel(Survey $survey, Personnel $personnel): array
    {
        $surveyId = (int) $survey->id;
        $personnelId = (int) $personnel->id;

        $ipKey = $this->sendRateKey($surveyId, request()->ip() ?? 'unknown');
        $maxSend = (int) config('survey_otp.send_rate_limit_per_minute', 3);
        if (RateLimiter::tooManyAttempts($ipKey, $maxSend)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return [
                'ok' => false,
                'message' => 'تعداد درخواست ارسال کد بیش از حد مجاز است. لطفاً '.max(1, $seconds).' ثانیه دیگر تلاش کنید.',
                'cooldown_seconds' => max(1, $seconds),
            ];
        }

        $cooldownKey = $this->cooldownCacheKey($surveyId, $personnelId);
        if (Cache::has($cooldownKey)) {
            $expiresAt = Cache::get($cooldownKey);
            $remaining = is_numeric($expiresAt) ? max(1, (int) $expiresAt - now()->timestamp) : 60;

            return [
                'ok' => false,
                'message' => 'برای درخواست مجدد کد، '.$remaining.' ثانیه صبر کنید.',
                'cooldown_seconds' => $remaining,
            ];
        }

        $mobile = trim((string) $personnel->mobile);
        if ($mobile === '') {
            return [
                'ok' => false,
                'message' => 'شماره موبایل برای این پرسنل ثبت نشده است. لطفاً با واحد منابع انسانی تماس بگیرید.',
            ];
        }

        if (! $this->smsPanelService->activeProvider()) {
            return [
                'ok' => false,
                'message' => 'سرویس پیامک در حال حاضر فعال نیست. لطفاً بعداً تلاش کنید یا با پشتیبانی تماس بگیرید.',
            ];
        }

        $code = $this->generateCode();
        $normalizedMobile = SmsPanelService::normalizeMobile($mobile);
        $ttlMinutes = max(1, (int) ceil((int) config('survey_otp.ttl_seconds', 300) / 60));
        $message = 'کد تایید نظرسنجی «'.$this->sanitizeSmsTitle($survey->title).'»: '.$code."\n"
            .'اعتبار: '.$ttlMinutes.' دقیقه. این کد را در اختیار دیگران قرار ندهید.';

        $result = $this->smsPanelService->sendUsingActive($normalizedMobile, $message);
        if (! $result->success) {
            return [
                'ok' => false,
                'message' => 'ارسال پیامک با خطا مواجه شد. لطفاً چند لحظه بعد دوباره تلاش کنید.',
            ];
        }

        $this->storeOtp($surveyId, $personnelId, $code);

        $cooldown = (int) config('survey_otp.resend_cooldown_seconds', 60);
        Cache::put($cooldownKey, now()->timestamp + $cooldown, $cooldown);

        RateLimiter::hit($ipKey, 60);

        return [
            'ok' => true,
            'message' => 'کد تایید به شماره '.self::maskMobile($mobile).' ارسال شد.',
            'cooldown_seconds' => $cooldown,
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function verify(Survey $survey, Personnel $personnel, string $submittedCode): array
    {
        $surveyId = (int) $survey->id;
        $personnelId = (int) $personnel->id;

        $ipKey = $this->verifyRateKey($surveyId, request()->ip() ?? 'unknown');
        $maxVerify = (int) config('survey_otp.verify_rate_limit_per_minute', 10);
        if (RateLimiter::tooManyAttempts($ipKey, $maxVerify)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return [
                'ok' => false,
                'message' => 'تلاش‌های ناموفق بیش از حد مجاز است. لطفاً '.max(1, $seconds).' ثانیه دیگر تلاش کنید.',
            ];
        }

        $submittedCode = $this->normalizeDigits(trim($submittedCode));
        $expectedLength = (int) config('survey_otp.code_length', 6);
        if (! preg_match('/^\d{'.$expectedLength.'}$/', $submittedCode)) {
            RateLimiter::hit($ipKey, 60);

            return [
                'ok' => false,
                'message' => 'فرمت کد تایید معتبر نیست.',
            ];
        }

        $cacheKey = $this->otpCacheKey($surveyId, $personnelId);
        $payload = Cache::get($cacheKey);
        if (! is_array($payload)) {
            RateLimiter::hit($ipKey, 60);

            return [
                'ok' => false,
                'message' => 'کد تایید منقضی شده یا یافت نشد. لطفاً کد جدید درخواست کنید.',
            ];
        }

        $sessionId = (string) ($payload['session_id'] ?? '');
        if ($sessionId === '' || $sessionId !== session()->getId()) {
            Cache::forget($cacheKey);
            RateLimiter::hit($ipKey, 60);

            return [
                'ok' => false,
                'message' => 'نشست شما منقضی شده است. لطفاً از ابتدا وارد شوید.',
            ];
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        $maxAttempts = (int) config('survey_otp.max_verify_attempts', 5);
        if ($attempts >= $maxAttempts) {
            Cache::forget($cacheKey);

            return [
                'ok' => false,
                'message' => 'تعداد تلاش‌های مجاز برای این کد به پایان رسید. لطفاً کد جدید درخواست کنید.',
            ];
        }

        $storedHash = (string) ($payload['hash'] ?? '');
        $submittedHash = $this->hashCode($submittedCode);
        if (! hash_equals($storedHash, $submittedHash)) {
            $payload['attempts'] = $attempts + 1;
            $ttl = max(1, (int) config('survey_otp.ttl_seconds', 300));
            Cache::put($cacheKey, $payload, $ttl);
            RateLimiter::hit($ipKey, 60);

            $remaining = max(0, $maxAttempts - $payload['attempts']);

            return [
                'ok' => false,
                'message' => $remaining > 0
                    ? 'کد وارد شده صحیح نیست. '.$remaining.' تلاش باقی مانده.'
                    : 'کد وارد شده صحیح نیست. لطفاً کد جدید درخواست کنید.',
            ];
        }

        Cache::forget($cacheKey);
        RateLimiter::clear($ipKey);

        return [
            'ok' => true,
            'message' => 'شماره موبایل با موفقیت تایید شد.',
        ];
    }

    public function resendCooldownRemaining(Survey $survey, Personnel $personnel): int
    {
        $cooldownKey = $this->cooldownCacheKey((int) $survey->id, (int) $personnel->id);
        $expiresAt = Cache::get($cooldownKey);
        if (! is_numeric($expiresAt)) {
            return 0;
        }

        return max(0, (int) $expiresAt - now()->timestamp);
    }

    public static function maskMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', self::normalizeDigitsStatic($mobile)) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        $lastTwo = substr($digits, -2);
        if (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            return '09** *** **'.$lastTwo;
        }
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '09** *** **'.$lastTwo;
        }

        return '*** **'.$lastTwo;
    }

    private function storeOtp(int $surveyId, int $personnelId, string $code): void
    {
        $ttl = max(60, (int) config('survey_otp.ttl_seconds', 300));
        Cache::put($this->otpCacheKey($surveyId, $personnelId), [
            'hash' => $this->hashCode($code),
            'attempts' => 0,
            'session_id' => session()->getId(),
            'created_at' => now()->timestamp,
        ], $ttl);
    }

    private function generateCode(): string
    {
        $length = max(4, min(8, (int) config('survey_otp.code_length', 6)));
        $max = (10 ** $length) - 1;
        $number = random_int(0, $max);

        return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function otpCacheKey(int $surveyId, int $personnelId): string
    {
        return 'survey_otp:'.$surveyId.':'.$personnelId;
    }

    private function cooldownCacheKey(int $surveyId, int $personnelId): string
    {
        return 'survey_otp_cooldown:'.$surveyId.':'.$personnelId;
    }

    private function sendRateKey(int $surveyId, string $ip): string
    {
        return 'survey_otp_send:'.$surveyId.':'.sha1($ip);
    }

    private function verifyRateKey(int $surveyId, string $ip): string
    {
        return 'survey_otp_verify:'.$surveyId.':'.sha1($ip);
    }

    private function sanitizeSmsTitle(string $title): string
    {
        $title = Str::limit(trim(strip_tags($title)), 40, '…');

        return $title !== '' ? $title : 'نظرسنجی';
    }

    private function normalizeDigits(string $value): string
    {
        return self::normalizeDigitsStatic($value);
    }

    private static function normalizeDigitsStatic(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}

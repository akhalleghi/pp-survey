<?php

namespace App\Services\Sms;

use App\Models\AdminUser;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\SmsProvider;
use App\Models\Survey;
use App\Support\SmsTargetingMode;
use App\Support\SurveyAudience;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class SmsCampaignService
{
    public function __construct(
        private readonly SmsRecipientResolver $recipientResolver,
        private readonly SmsPanelService $smsPanelService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     recipients: list<array{id: int|null, mobile: string, name: string|null, personnel_code: string|null, preview_message: string}>,
     *     recipient_count: int,
     *     checksum: string,
     *     sample_message: string,
     *     targeting_label: string,
     *     survey_title: string|null,
     *     public_url: string|null
     * }
     */
    public function preview(array $payload): array
    {
        $this->guardActiveProvider();
        $survey = $this->resolveSurveyForPayload($payload);
        $this->ensureSurveyLinkIfNeeded($survey, $payload);
        $recipients = $this->recipientResolver->resolve($payload);
        $template = $this->validatedMessage($payload);
        $publicUrl = $survey ? $this->publicUrlFor($survey) : '';

        $previewRows = [];
        $sampleMessage = '';

        foreach ($recipients as $index => $row) {
            $message = $row['personnel']
                ? SmsMessageComposer::personalize($template, $row['personnel'], $survey, $publicUrl)
                : SmsMessageComposer::personalizeFree($template, $publicUrl, $survey);

            if ($index === 0) {
                $sampleMessage = $message;
            }

            $previewRows[] = [
                'id' => $row['personnel_id'],
                'mobile' => $this->displayMobile($row['mobile']),
                'name' => $row['name'],
                'personnel_code' => $row['personnel']?->personnel_code,
                'preview_message' => $message,
            ];
        }

        $audienceSummary = null;
        if ($survey && ($payload['targeting_mode'] ?? '') === SmsTargetingMode::SURVEY_ELIGIBLE) {
            $audienceSummary = SurveyAudience::describeFilters($survey->audience_filters ?? []);
        }

        return [
            'recipients' => $previewRows,
            'recipient_count' => count($previewRows),
            'checksum' => SmsRecipientResolver::checksum($recipients),
            'sample_message' => $sampleMessage,
            'targeting_label' => SmsTargetingMode::labels()[$payload['targeting_mode']] ?? '',
            'survey_title' => $survey?->title,
            'public_url' => $publicUrl ?: null,
            'audience_summary' => $audienceSummary,
            'survey_has_filters' => $survey ? SurveyAudience::hasActiveFilters($survey->audience_filters ?? []) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDraftCampaign(AdminUser $admin, array $payload): SmsCampaign
    {
        $this->guardCampaignRateLimit($admin);
        $provider = $this->guardActiveProvider();
        $survey = $this->resolveSurveyForPayload($payload);
        $this->ensureSurveyLinkIfNeeded($survey, $payload);

        $recipients = $this->recipientResolver->resolve($payload);
        $checksum = SmsRecipientResolver::checksum($recipients);
        $template = $this->validatedMessage($payload);
        $publicUrl = $survey ? $this->publicUrlFor($survey) : '';

        if ($checksum !== (string) ($payload['recipients_checksum'] ?? '')) {
            throw ValidationException::withMessages([
                'recipients_checksum' => 'فهرست گیرندگان تغییر کرده است. لطفاً دوباره پیش‌نمایش بگیرید.',
            ]);
        }

        $config = $this->audienceConfigForStorage($payload);

        return DB::transaction(function () use ($admin, $provider, $survey, $payload, $recipients, $checksum, $template, $publicUrl, $config) {
            $campaign = SmsCampaign::query()->create([
                'admin_user_id' => $admin->id,
                'survey_id' => $survey?->id,
                'sms_provider_id' => $provider->id,
                'targeting_mode' => $payload['targeting_mode'],
                'audience_config' => $config,
                'message_template' => $template,
                'send_number' => $provider->config?->send_number,
                'recipient_count' => count($recipients),
                'recipients_checksum' => $checksum,
                'status' => SmsCampaign::STATUS_AWAITING_SEND,
                'confirm_phrase' => $this->buildConfirmPhrase(count($recipients)),
            ]);

            $seenMobiles = [];
            foreach ($recipients as $row) {
                if (isset($seenMobiles[$row['mobile']])) {
                    continue;
                }
                $seenMobiles[$row['mobile']] = true;

                $body = $row['personnel']
                    ? SmsMessageComposer::personalize($template, $row['personnel'], $survey, $publicUrl)
                    : SmsMessageComposer::personalizeFree($template, $publicUrl, $survey);

                SmsMessage::query()->create([
                    'sms_campaign_id' => $campaign->id,
                    'personnel_id' => $row['personnel_id'],
                    'recipient_mobile' => $row['mobile'],
                    'recipient_name' => $row['name'],
                    'sender_number' => $provider->config?->send_number,
                    'message_body' => $body,
                    'sms_provider_id' => $provider->id,
                    'provider_name' => $provider->name,
                    'status' => SmsMessage::STATUS_PENDING,
                ]);
            }

            $campaign->update(['recipient_count' => count($seenMobiles)]);

            return $campaign->fresh(['survey', 'adminUser']);
        });
    }

    public function confirmCampaignForSending(SmsCampaign $campaign, AdminUser $admin, string $confirmPhrase, string $password, bool $acknowledged): void
    {
        if ($campaign->status !== SmsCampaign::STATUS_AWAITING_SEND) {
            throw ValidationException::withMessages(['campaign' => 'این درخواست ارسال دیگر قابل تأیید نیست.']);
        }

        if (! $acknowledged) {
            throw ValidationException::withMessages(['acknowledged' => 'تأیید مطالعهٔ فهرست گیرندگان الزامی است.']);
        }

        $normalizedInput = self::normalizeConfirmPhrase($confirmPhrase);
        $normalizedExpected = self::normalizeConfirmPhrase((string) $campaign->confirm_phrase);

        if ($normalizedInput === '') {
            throw ValidationException::withMessages([
                'confirm_phrase' => 'عبارت تأیید را در مرحلهٔ قبل وارد کنید (مثال: ارسال-۱۲-پیامک).',
            ]);
        }

        if ($normalizedInput !== $normalizedExpected) {
            throw ValidationException::withMessages([
                'confirm_phrase' => 'عبارت تأیید با مقدار درخواستی مطابقت ندارد. از خط تیره انگلیسی (-) و ارقام انگلیسی استفاده کنید یا عبارت را کپی کنید.',
            ]);
        }

        if (! Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages(['admin_password' => 'رمز عبور مدیر صحیح نیست.']);
        }

        $minWait = max(5, (int) config('sms.campaign_min_confirm_seconds', 10));
        if ($campaign->created_at->diffInSeconds(now()) < $minWait) {
            throw ValidationException::withMessages([
                'campaign' => "لطفاً حداقل {$minWait} ثانیه پس از ایجاد پیش‌نویس، ارسال را تأیید کنید.",
            ]);
        }

        DB::transaction(function () use ($campaign) {
            $locked = SmsCampaign::query()->lockForUpdate()->find($campaign->id);
            if (! $locked || $locked->status !== SmsCampaign::STATUS_AWAITING_SEND) {
                throw ValidationException::withMessages([
                    'campaign' => 'این درخواست ارسال قبلاً ثبت شده است.',
                ]);
            }

            $locked->update([
                'status' => SmsCampaign::STATUS_PROCESSING,
                'confirmed_at' => now(),
                'queued_at' => now(),
                'started_at' => now(),
                'sent_count' => 0,
                'failed_count' => 0,
            ]);
        });
    }

    public function buildConfirmPhrase(int $count): string
    {
        return 'ارسال-'.$count.'-پیامک';
    }

    public static function normalizeConfirmPhrase(string $phrase): string
    {
        $phrase = trim($phrase);
        $phrase = strtr($phrase, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $phrase = preg_replace('/[\x{200c}\x{200f}\x{feff}\s]+/u', '', $phrase) ?? $phrase;
        $phrase = preg_replace('/\p{Pd}/u', '-', $phrase) ?? $phrase;

        return $phrase;
    }

    private function guardActiveProvider(): SmsProvider
    {
        $provider = $this->smsPanelService->activeProvider()?->load('config');
        if (! $provider || ! $provider->config) {
            throw ValidationException::withMessages([
                'sms' => 'پنل پیامکی فعال یا پیکربندی نشده است. از تنظیمات سازمان، پنل پیامک را فعال کنید.',
            ]);
        }

        return $provider;
    }

    private function guardCampaignRateLimit(AdminUser $admin): void
    {
        $key = 'sms-campaign-create:'.$admin->id;
        $max = max(1, (int) config('sms.campaign_rate_limit_per_hour', 5));
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'campaign' => "تعداد درخواست ارسال گروهی بیش از حد مجاز است. {$seconds} ثانیه صبر کنید.",
            ]);
        }
        RateLimiter::hit($key, 3600);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function validatedMessage(array $payload): string
    {
        $message = trim((string) ($payload['message'] ?? ''));
        if (mb_strlen($message) < 10) {
            throw ValidationException::withMessages(['message' => 'متن پیامک باید حداقل ۱۰ کاراکتر باشد.']);
        }
        if (mb_strlen($message) > 900) {
            throw ValidationException::withMessages(['message' => 'متن پیامک نباید بیش از ۹۰۰ کاراکتر باشد.']);
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveSurveyForPayload(array $payload): ?Survey
    {
        if (empty($payload['survey_id'])) {
            return null;
        }

        return Survey::query()->find((int) $payload['survey_id']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensureSurveyLinkIfNeeded(?Survey $survey, array $payload): void
    {
        $mode = (string) ($payload['targeting_mode'] ?? '');
        $needsSurvey = in_array($mode, [
            SmsTargetingMode::SURVEY_ELIGIBLE,
        ], true) || ! empty($payload['survey_id']);

        if ($needsSurvey && ! $survey) {
            throw ValidationException::withMessages(['survey_id' => 'انتخاب نظرسنجی الزامی است.']);
        }

        if ($survey && ! $survey->public_token) {
            throw ValidationException::withMessages([
                'survey_id' => 'ابتدا برای این نظرسنجی لینک عمومی ایجاد کنید.',
            ]);
        }
    }

    private function publicUrlFor(Survey $survey): string
    {
        return $survey->publicUrl() ?? '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function audienceConfigForStorage(array $payload): ?array
    {
        if (($payload['targeting_mode'] ?? '') !== SmsTargetingMode::CUSTOM_FILTERS) {
            return null;
        }

        return SurveyAudience::fromRequestInput([
            'modes' => $payload['audience_modes'] ?? [],
            'unit_ids' => $payload['audience_unit_ids'] ?? [],
            'genders' => $payload['audience_genders'] ?? [],
            'position_ids' => $payload['audience_position_ids'] ?? [],
            'company_ids' => $payload['audience_company_ids'] ?? [],
            'personnel_ids' => $payload['audience_personnel_ids'] ?? [],
        ]);
    }

    private function displayMobile(string $mobile): string
    {
        if (strlen($mobile) === 10 && str_starts_with($mobile, '9')) {
            return '0'.$mobile;
        }

        return $mobile;
    }
}

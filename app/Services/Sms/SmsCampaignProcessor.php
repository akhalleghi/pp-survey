<?php

namespace App\Services\Sms;

use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;

final class SmsCampaignProcessor
{
    public function __construct(
        private readonly SmsPanelService $smsPanelService,
    ) {}

    /**
     * @return array{
     *     done: bool,
     *     total: int,
     *     processed: int,
     *     sent: int,
     *     failed: int,
     *     remaining: int,
     *     percent: float,
     *     current: array{name: string|null, mobile: string, status: string, error: string|null}|null,
     *     campaign_status: string
     * }
     */
    public function sendNext(int $campaignId): array
    {
        $campaign = SmsCampaign::query()->find($campaignId);
        if (! $campaign) {
            return $this->emptyResult(0, true, SmsCampaign::STATUS_FAILED);
        }

        if (! in_array($campaign->status, [SmsCampaign::STATUS_PROCESSING, SmsCampaign::STATUS_QUEUED], true)) {
            return $this->buildResult($campaign, null, true);
        }

        if ($campaign->status === SmsCampaign::STATUS_QUEUED) {
            $campaign->update([
                'status' => SmsCampaign::STATUS_PROCESSING,
                'started_at' => $campaign->started_at ?? now(),
            ]);
        }

        $provider = $this->smsPanelService->activeProvider()?->load('config');
        if (! $provider || ! $provider->config) {
            $campaign->update([
                'status' => SmsCampaign::STATUS_FAILED,
                'last_error' => 'پنل پیامکی فعال یا پیکربندی‌شده یافت نشد.',
                'completed_at' => now(),
            ]);

            return $this->buildResult($campaign->fresh(), null, true);
        }

        $campaign->update([
            'sms_provider_id' => $provider->id,
            'send_number' => $provider->config->send_number,
        ]);

        $message = SmsMessage::query()
            ->where('sms_campaign_id', $campaign->id)
            ->where('status', SmsMessage::STATUS_PENDING)
            ->orderBy('id')
            ->first();

        if (! $message) {
            $this->finalize($campaign);

            return $this->buildResult($campaign->fresh(), null, true);
        }

        $result = $this->smsPanelService->sendUsingProvider(
            $provider,
            $message->recipient_mobile,
            $message->message_body
        );

        if ($result->success) {
            $message->update([
                'status' => SmsMessage::STATUS_SENT,
                'sms_provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'sender_number' => $provider->config->send_number,
                'provider_status' => $result->providerStatus,
                'provider_response' => $result->message,
                'sent_at' => now(),
                'error_message' => null,
            ]);
            $campaign->increment('sent_count');
        } else {
            $message->update([
                'status' => SmsMessage::STATUS_FAILED,
                'error_message' => $result->message,
                'provider_status' => $result->providerStatus,
            ]);
            $campaign->increment('failed_count');
            Log::warning('sms.campaign.message_failed', [
                'campaign_id' => $campaign->id,
                'message_id' => $message->id,
                'mobile' => $message->recipient_mobile,
                'error' => $result->message,
            ]);
        }

        $delayMs = max(0, (int) config('sms.campaign_send_delay_ms', 350));
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $campaign->refresh();
        $stillPending = SmsMessage::query()
            ->where('sms_campaign_id', $campaign->id)
            ->where('status', SmsMessage::STATUS_PENDING)
            ->exists();

        if (! $stillPending) {
            $this->finalize($campaign);
            $campaign->refresh();
        }

        return $this->buildResult(
            $campaign,
            [
                'name' => $message->recipient_name,
                'mobile' => $this->displayMobile($message->recipient_mobile),
                'status' => $result->success ? SmsMessage::STATUS_SENT : SmsMessage::STATUS_FAILED,
                'error' => $result->success ? null : $result->message,
            ],
            ! $stillPending
        );
    }

    /** @deprecated Used only by artisan command for recovery */
    public function process(int $campaignId): void
    {
        $campaign = SmsCampaign::query()->find($campaignId);
        if (! $campaign) {
            return;
        }

        if ($campaign->status === SmsCampaign::STATUS_AWAITING_SEND) {
            $campaign->update([
                'status' => SmsCampaign::STATUS_PROCESSING,
                'started_at' => now(),
            ]);
        }

        while (true) {
            $result = $this->sendNext($campaignId);
            if ($result['done']) {
                break;
            }
        }
    }

    private function finalize(SmsCampaign $campaign): void
    {
        $failed = (int) $campaign->failed_count;
        $campaign->update([
            'status' => SmsCampaign::STATUS_COMPLETED,
            'completed_at' => now(),
            'last_error' => $failed > 0 ? "{$failed} پیامک ارسال نشد." : null,
        ]);
    }

    /**
     * @param  array{name: string|null, mobile: string, status: string, error: string|null}|null  $current
     * @return array<string, mixed>
     */
    private function buildResult(SmsCampaign $campaign, ?array $current, bool $done): array
    {
        $total = max(0, (int) $campaign->recipient_count);
        $sent = (int) $campaign->sent_count;
        $failed = (int) $campaign->failed_count;
        $processed = $sent + $failed;
        $remaining = max(0, $total - $processed);
        $percent = $total > 0 ? round(($processed / $total) * 100, 1) : ($done ? 100.0 : 0.0);

        return [
            'done' => $done,
            'total' => $total,
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => $remaining,
            'percent' => $percent,
            'current' => $current,
            'campaign_status' => $campaign->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(int $total, bool $done, string $status): array
    {
        return [
            'done' => $done,
            'total' => $total,
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'remaining' => 0,
            'percent' => $done ? 100.0 : 0.0,
            'current' => null,
            'campaign_status' => $status,
        ];
    }

    private function displayMobile(string $mobile): string
    {
        if (strlen($mobile) === 10 && str_starts_with($mobile, '9')) {
            return '0'.$mobile;
        }

        return $mobile;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\SmsCampaign;
use App\Services\Sms\SmsCampaignProcessor;
use Illuminate\Console\Command;

class ProcessPendingSmsCampaignsCommand extends Command
{
    protected $signature = 'sms:process-pending {--campaign= : Campaign ID}';

    protected $description = 'Process queued SMS campaigns (for stuck pending messages)';

    public function handle(SmsCampaignProcessor $processor): int
    {
        $campaignId = $this->option('campaign');

        $query = SmsCampaign::query()->whereIn('status', [
            SmsCampaign::STATUS_QUEUED,
            SmsCampaign::STATUS_PROCESSING,
        ]);

        if ($campaignId) {
            $query->whereKey((int) $campaignId);
        }

        $campaigns = $query->orderBy('id')->get();

        if ($campaigns->isEmpty()) {
            $this->info('No queued campaigns found.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->info("Processing campaign #{$campaign->id}…");
            $processor->process($campaign->id);
            $campaign->refresh();
            $this->line("  → sent: {$campaign->sent_count}, failed: {$campaign->failed_count}, status: {$campaign->status}");
        }

        return self::SUCCESS;
    }
}

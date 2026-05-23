<?php

namespace App\Jobs;

use App\Services\Sms\SmsCampaignProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSmsCampaignJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $campaignId,
    ) {}

    public function uniqueId(): string
    {
        return 'sms-campaign-'.$this->campaignId;
    }

    public function handle(SmsCampaignProcessor $processor): void
    {
        $processor->process($this->campaignId);
    }
}

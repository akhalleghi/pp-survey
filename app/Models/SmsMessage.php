<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'sms_campaign_id',
        'personnel_id',
        'recipient_mobile',
        'recipient_name',
        'sender_number',
        'message_body',
        'sms_provider_id',
        'provider_name',
        'status',
        'provider_status',
        'provider_response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmsProvider::class, 'sms_provider_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING_SEND = 'awaiting_send';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'admin_user_id',
        'survey_id',
        'sms_provider_id',
        'targeting_mode',
        'audience_config',
        'message_template',
        'send_number',
        'recipient_count',
        'recipients_checksum',
        'status',
        'confirm_phrase',
        'confirmed_at',
        'queued_at',
        'started_at',
        'completed_at',
        'sent_count',
        'failed_count',
        'last_error',
    ];

    protected $casts = [
        'audience_config' => 'array',
        'confirmed_at' => 'datetime',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmsProvider::class, 'sms_provider_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }
}

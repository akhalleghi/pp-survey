<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->foreignId('survey_id')->nullable()->constrained('surveys')->nullOnDelete();
            $table->foreignId('sms_provider_id')->nullable()->constrained('sms_providers')->nullOnDelete();
            $table->string('targeting_mode', 40);
            $table->json('audience_config')->nullable();
            $table->text('message_template');
            $table->string('send_number', 32)->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->string('recipients_checksum', 64);
            $table->string('status', 24)->default('draft');
            $table->string('confirm_phrase', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('admin_user_id');
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained('sms_campaigns')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('recipient_mobile', 20);
            $table->string('recipient_name')->nullable();
            $table->string('sender_number', 32)->nullable();
            $table->text('message_body');
            $table->foreignId('sms_provider_id')->nullable()->constrained('sms_providers')->nullOnDelete();
            $table->string('provider_name')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('provider_status')->nullable();
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sms_campaign_id', 'status']);
            $table->index('recipient_mobile');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_campaigns');
    }
};

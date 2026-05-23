<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('sms_messages')
            ->select('sms_campaign_id', 'recipient_mobile', DB::raw('MIN(id) as keep_id'))
            ->groupBy('sms_campaign_id', 'recipient_mobile')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('sms_messages')
                ->where('sms_campaign_id', $dup->sms_campaign_id)
                ->where('recipient_mobile', $dup->recipient_mobile)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->unique(['sms_campaign_id', 'recipient_mobile'], 'sms_messages_campaign_mobile_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropUnique('sms_messages_campaign_mobile_unique');
        });
    }
};

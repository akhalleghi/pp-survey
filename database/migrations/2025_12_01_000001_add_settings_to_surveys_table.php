<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->timestamp('start_at')->nullable()->after('status');
            $table->timestamp('end_at')->nullable()->after('start_at');
            $table->boolean('is_anonymous')->default(true)->after('end_at');
            $table->boolean('require_auth')->default(true)->after('is_anonymous');
            $table->boolean('allow_partial')->default(true)->after('require_auth');
            $table->boolean('shuffle_questions')->default(false)->after('allow_partial');
            $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
            $table->boolean('show_results_after_submit')->default(false)->after('shuffle_options');
            $table->string('result_visibility')->default('private')->after('show_results_after_submit');
            $table->unsignedSmallInteger('response_edit_window_hours')->nullable()->after('response_window_hours');
            $table->string('thank_you_message')->nullable()->after('description');
            $table->json('notification_emails')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn([
                'start_at',
                'end_at',
                'is_anonymous',
                'require_auth',
                'allow_partial',
                'shuffle_questions',
                'shuffle_options',
                'show_results_after_submit',
                'result_visibility',
                'response_edit_window_hours',
                'thank_you_message',
                'notification_emails',
            ]);
        });
    }
};

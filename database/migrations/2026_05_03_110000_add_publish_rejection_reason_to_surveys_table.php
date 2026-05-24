<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('surveys', 'publish_rejection_reason')) {
            return;
        }

        if (Schema::hasColumn('surveys', 'publish_requested_by_admin_user_id')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->text('publish_rejection_reason')->nullable()->after('publish_requested_by_admin_user_id');
            });

            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $table->text('publish_rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('surveys', 'publish_rejection_reason')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('publish_rejection_reason');
        });
    }
};

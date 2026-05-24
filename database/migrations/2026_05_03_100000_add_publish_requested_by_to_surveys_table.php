<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('surveys', 'publish_requested_by_admin_user_id')) {
            return;
        }

        if (Schema::hasColumn('surveys', 'created_by_admin_user_id')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->foreignId('publish_requested_by_admin_user_id')
                    ->nullable()
                    ->after('created_by_admin_user_id')
                    ->constrained('admin_users')
                    ->nullOnDelete();
            });

            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $table->foreignId('publish_requested_by_admin_user_id')
                ->nullable()
                ->constrained('admin_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('surveys', 'publish_requested_by_admin_user_id')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['publish_requested_by_admin_user_id']);
            $table->dropColumn('publish_requested_by_admin_user_id');
        });
    }
};

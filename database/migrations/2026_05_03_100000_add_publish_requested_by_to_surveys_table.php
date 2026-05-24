<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return list<string> */
    private function surveyColumns(): array
    {
        return Schema::getColumnListing('surveys');
    }

    private function surveyHasColumn(string $column): bool
    {
        return in_array($column, $this->surveyColumns(), true);
    }

    public function up(): void
    {
        if (! $this->surveyHasColumn('created_by_admin_user_id')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->foreignId('created_by_admin_user_id')
                    ->nullable()
                    ->after('unit_id')
                    ->constrained('admin_users')
                    ->nullOnDelete();
            });
        }

        if ($this->surveyHasColumn('publish_requested_by_admin_user_id')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $column = $table->foreignId('publish_requested_by_admin_user_id')
                ->nullable()
                ->constrained('admin_users')
                ->nullOnDelete();

            if ($this->surveyHasColumn('created_by_admin_user_id')) {
                $column->after('created_by_admin_user_id');
            }
        });
    }

    public function down(): void
    {
        if ($this->surveyHasColumn('publish_requested_by_admin_user_id')) {
            Schema::table('surveys', function (Blueprint $table) {
                $table->dropForeign(['publish_requested_by_admin_user_id']);
                $table->dropColumn('publish_requested_by_admin_user_id');
            });
        }
    }
};

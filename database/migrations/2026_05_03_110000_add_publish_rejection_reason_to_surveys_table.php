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
        if ($this->surveyHasColumn('publish_rejection_reason')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $column = $table->text('publish_rejection_reason')->nullable();

            if ($this->surveyHasColumn('publish_requested_by_admin_user_id')) {
                $column->after('publish_requested_by_admin_user_id');
            }
        });
    }

    public function down(): void
    {
        if (! $this->surveyHasColumn('publish_rejection_reason')) {
            return;
        }

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn('publish_rejection_reason');
        });
    }
};

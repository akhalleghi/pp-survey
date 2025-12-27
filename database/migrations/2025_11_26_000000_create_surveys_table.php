<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('questions_count')->default(0);
            $table->unsignedInteger('responses_count')->default(0);
            $table->string('status')->default('draft');
            $table->unsignedSmallInteger('response_window_hours')->default(48);
            $table->unsignedInteger('response_limit')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('track_location')->default(false);
            $table->boolean('prevent_multiple_submissions')->default(true);
            $table->boolean('allow_edit')->default(true);
            $table->json('audience_filters')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};

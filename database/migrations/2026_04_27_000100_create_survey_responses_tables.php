<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
            $table->foreignId('personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('respondent_name')->nullable();
            $table->string('identifier_type', 32)->nullable();
            $table->string('respondent_identifier', 191)->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->string('status', 20)->default('draft'); // draft|submitted
            $table->unsignedInteger('answers_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('meta')->nullable();
            $table->string('edit_token', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['survey_id', 'status']);
            $table->index(['survey_id', 'personnel_id']);
        });

        Schema::create('survey_response_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->constrained('survey_responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('survey_questions')->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('survey_question_options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->decimal('answer_number', 14, 4)->nullable();
            $table->date('answer_date')->nullable();
            $table->json('answer_json')->nullable();
            $table->timestamps();

            $table->unique(['response_id', 'question_id']);
            $table->index(['question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_response_answers');
        Schema::dropIfExists('survey_responses');
    }
};


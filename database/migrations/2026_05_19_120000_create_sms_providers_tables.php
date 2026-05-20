<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('driver', 64);
            $table->string('default_api_url', 512)->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sms_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_provider_id')->unique()->constrained('sms_providers')->cascadeOnDelete();
            $table->text('username_encrypted');
            $table->text('password_encrypted');
            $table->string('send_number', 32);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->foreignId('updated_by_admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_provider_configs');
        Schema::dropIfExists('sms_providers');
    }
};

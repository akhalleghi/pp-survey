<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_login_throttle_states', function (Blueprint $table) {
            $table->id();
            $table->string('username_key', 64)->unique();
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('username', 64);
            $table->string('outcome', 32);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('detail', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'outcome']);
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_login_logs');
        Schema::dropIfExists('admin_login_throttle_states');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_login_throttle_states', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->after('username_key');
        });
    }

    public function down(): void
    {
        Schema::table('admin_login_throttle_states', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};

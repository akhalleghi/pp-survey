<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->string('role', 32)->default('admin')->after('password');
            $table->json('permissions')->nullable()->after('role');
            $table->string('personnel_code', 64)->nullable()->after('permissions');
            $table->boolean('is_active')->default(true)->after('personnel_code');
            $table->index('personnel_code');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->foreignId('created_by_admin_user_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('admin_users')
                ->nullOnDelete();
        });

        Schema::table('unit_supervisors', function (Blueprint $table) {
            $table->dropUnique(['personnel_code']);
        });

        Schema::table('unit_supervisors', function (Blueprint $table) {
            $table->unique(['personnel_code', 'unit_id']);
            $table->foreignId('admin_user_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('admin_users')
                ->nullOnDelete();
        });

        DB::table('admin_users')->update([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('unit_supervisors', function (Blueprint $table) {
            $table->dropForeign(['admin_user_id']);
            $table->dropUnique(['personnel_code', 'unit_id']);
        });

        Schema::table('unit_supervisors', function (Blueprint $table) {
            $table->unique('personnel_code');
        });

        Schema::table('unit_supervisors', function (Blueprint $table) {
            $table->dropColumn('admin_user_id');
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['created_by_admin_user_id']);
            $table->dropColumn('created_by_admin_user_id');
        });

        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropIndex(['personnel_code']);
            $table->dropColumn(['role', 'permissions', 'personnel_code', 'is_active']);
        });
    }
};

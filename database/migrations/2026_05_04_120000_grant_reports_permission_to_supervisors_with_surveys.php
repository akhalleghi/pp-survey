<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ناظرانی که قبلاً با مجوز نظرسنجی به گزارشات دسترسی داشتند، کلید reports را صریحاً دریافت می‌کنند.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_users')
            ->where('role', 'supervisor')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $row) {
                    $perms = json_decode($row->permissions ?? '[]', true);
                    if (! is_array($perms)) {
                        continue;
                    }
                    if (! in_array('surveys', $perms, true) || in_array('reports', $perms, true)) {
                        continue;
                    }
                    $perms[] = 'reports';
                    DB::table('admin_users')->where('id', $row->id)->update([
                        'permissions' => json_encode(array_values(array_unique($perms))),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('admin_users')
            ->where('role', 'supervisor')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $row) {
                    $perms = json_decode($row->permissions ?? '[]', true);
                    if (! is_array($perms)) {
                        continue;
                    }
                    $filtered = array_values(array_filter(
                        $perms,
                        static fn ($p) => $p !== 'reports'
                    ));
                    DB::table('admin_users')->where('id', $row->id)->update([
                        'permissions' => json_encode($filtered),
                    ]);
                }
            });
    }
};

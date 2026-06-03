<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $username = (string) env('SEED_ADMIN_USERNAME', 'admin');
        $configuredPassword = env('SEED_ADMIN_PASSWORD');

        if ($configuredPassword === null || $configuredPassword === '') {
            if (app()->environment('production')) {
                throw new \RuntimeException(
                    'برای محیط production باید SEED_ADMIN_PASSWORD در فایل .env تنظیم شود.'
                );
            }
            $configuredPassword = 'ChangeMe123!';
        }

        $user = AdminUser::query()->firstOrNew(['username' => $username]);
        $user->fill([
            'name' => 'مدیر اصلی',
            'role' => AdminUser::ROLE_ADMIN,
            'permissions' => null,
            'personnel_code' => null,
            'is_active' => true,
        ]);

        if (! $user->exists) {
            $user->password = (string) $configuredPassword;
        } elseif (env('SEED_ADMIN_PASSWORD')) {
            $user->password = (string) $configuredPassword;
        }

        $user->save();

        if (! app()->environment('production') && ! env('SEED_ADMIN_PASSWORD')) {
            $this->command?->warn(
                'حساب مدیر: '.$username.' / ChangeMe123! — حتماً پس از نصب رمز را در پنل تغییر دهید.'
            );
        }
    }
}

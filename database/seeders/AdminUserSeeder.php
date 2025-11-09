<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'مدیر اصلی',
                'password' => Hash::make('ChangeMe123!'),
            ]
        );
    }
}

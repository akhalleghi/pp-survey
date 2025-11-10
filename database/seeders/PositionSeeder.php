<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            'مدیر کل',
            'مدیر منابع انسانی',
            'مدیر مالی',
            'سرپرست عملیات پرواز',
            'کارشناس ارشد پشتیبانی',
        ];

        foreach ($positions as $name) {
            Position::firstOrCreate(['name' => $name]);
        }
    }
}

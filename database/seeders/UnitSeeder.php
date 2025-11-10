<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            'واحد بازرگانی',
            'واحد منابع انسانی',
            'واحد مالی',
            'واحد عملیات پرواز',
            'واحد پشتیبانی مشتریان',
        ];

        foreach ($units as $name) {
            Unit::firstOrCreate(['name' => $name]);
        }
    }
}

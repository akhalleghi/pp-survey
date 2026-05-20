<?php

namespace Database\Seeders;

use App\Models\SmsProvider;
use Illuminate\Database\Seeder;

class SmsProviderSeeder extends Seeder
{
    public function run(): void
    {
        SmsProvider::query()->updateOrCreate(
            ['slug' => 'panel_3300'],
            [
                'name' => 'پنل 3300.ir',
                'driver' => 'panel_3300',
                'default_api_url' => config('sms.drivers.panel_3300.api_url'),
                'is_available' => true,
                'sort_order' => 10,
            ]
        );
    }
}

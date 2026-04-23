<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'MatrimonyTheme'],
            ['key' => 'tagline', 'value' => 'Find Your Perfect Match'],
            ['key' => 'phone', 'value' => '+91 00000 00000'],
            ['key' => 'whatsapp', 'value' => '+91 00000 00000'],
            ['key' => 'email', 'value' => 'info@example.com'],
            ['key' => 'address', 'value' => 'Your City, Your Country'],
            ['key' => 'profile_id_prefix', 'value' => 'MT'],
            ['key' => 'total_members', 'value' => '0'],
            ['key' => 'successful_marriages', 'value' => '0'],
            ['key' => 'years_of_service', 'value' => '1'],
            ['key' => 'copyright_year_start', 'value' => '2024'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::create($setting);
        }
    }
}

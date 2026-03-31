<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Anugraha Matrimony'],
            ['key' => 'tagline', 'value' => 'Find Your Perfect Match'],
            ['key' => 'phone', 'value' => '+91 94816 18143'],
            ['key' => 'whatsapp', 'value' => '+91 94816 18143'],
            ['key' => 'email', 'value' => 'info@anugrahamatrimony.com'],
            ['key' => 'address', 'value' => 'Karnataka, India'],
            ['key' => 'profile_id_prefix', 'value' => 'AM'],
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

<?php

namespace Database\Seeders;

use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

class ThemeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        ThemeSetting::create([
            'site_name' => 'MatrimonyTheme',
            'tagline' => 'Find Your Perfect Match',
            'primary_color' => '#8B1D91',
            'primary_hover' => '#6B1571',
            'primary_light' => '#F3E8F7',
            'secondary_color' => '#00BCD4',
            'secondary_hover' => '#00ACC1',
            'secondary_light' => '#E0F7FA',
            'logo_url' => null,
            'favicon_url' => null,
        ]);
    }
}

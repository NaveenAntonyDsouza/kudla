<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];
    public $logoUpload = null;
    public $faviconUpload = null;

    public function mount(): void
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();
        $theme = ThemeSetting::first();

        $this->form->fill([
            'site_name' => $settings['site_name'] ?? '',
            'tagline' => $settings['tagline'] ?? '',
            'hero_heading' => $settings['hero_heading'] ?? '',
            'hero_subheading' => $settings['hero_subheading'] ?? '',
            'search_heading' => $settings['search_heading'] ?? '',
            'email_verification_enabled' => $settings['email_verification_enabled'] ?? '1',
            'phone_verification_enabled' => $settings['phone_verification_enabled'] ?? '0',
            'auto_approve_profiles' => $settings['auto_approve_profiles'] ?? '1',
            'phone' => $settings['phone'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'email' => $settings['email'] ?? '',
            'address' => $settings['address'] ?? '',
            'profile_id_prefix' => $settings['profile_id_prefix'] ?? 'AM',
            'total_members' => $settings['total_members'] ?? '0',
            'successful_marriages' => $settings['successful_marriages'] ?? '0',
            'years_of_service' => $settings['years_of_service'] ?? '1',
            'copyright_year_start' => $settings['copyright_year_start'] ?? date('Y'),
            'social_facebook' => $settings['social_facebook'] ?? '',
            'social_instagram' => $settings['social_instagram'] ?? '',
            'social_youtube' => $settings['social_youtube'] ?? '',
            'social_twitter' => $settings['social_twitter'] ?? '',
            'current_logo_url' => $theme?->logo_url,
            'current_favicon_url' => $theme?->favicon_url,
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                // General
                Forms\Components\TextInput::make('site_name')
                    ->label('Site Name')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Displayed in header, footer, emails'),

                Forms\Components\TextInput::make('tagline')
                    ->label('Tagline')
                    ->maxLength(200)
                    ->helperText('Shown on homepage hero section'),

                Forms\Components\TextInput::make('hero_heading')
                    ->label('Homepage Hero Heading')
                    ->maxLength(200)
                    ->helperText('Big text on homepage hero. e.g., "Find Your Perfect Match in Coastal Karnataka"'),

                Forms\Components\TextInput::make('hero_subheading')
                    ->label('Homepage Hero Subheading')
                    ->maxLength(200)
                    ->helperText('Smaller text below heading. e.g., "Register Free & Connect with 1000+ Profiles"'),

                Forms\Components\TextInput::make('search_heading')
                    ->label('Search Section Heading')
                    ->maxLength(200)
                    ->helperText('Text above search bar. e.g., "Search for Your Perfect Partner"'),

                // Registration Settings
                Forms\Components\Toggle::make('email_verification_enabled')
                    ->label('Email Verification Required')
                    ->helperText('ON = users must verify email OTP during registration. OFF = skip email verification.'),

                Forms\Components\Toggle::make('phone_verification_enabled')
                    ->label('Phone Verification Required')
                    ->helperText('ON = users must verify phone OTP during registration. OFF = skip phone verification.'),

                Forms\Components\Toggle::make('auto_approve_profiles')
                    ->label('Auto-Approve Profiles')
                    ->helperText('ON = profiles go live immediately. OFF = admin must approve each profile.'),

                // Logo Upload
                Forms\Components\FileUpload::make('logo_upload')
                    ->label('Site Logo')
                    ->image()
                    ->maxSize(2048) // 2MB
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                    ->directory('branding')
                    ->disk('public')
                    ->helperText('Recommended: PNG or SVG, max height 40px. Leave empty to keep current logo.'),

                // Favicon Upload
                Forms\Components\FileUpload::make('favicon_upload')
                    ->label('Favicon')
                    ->image()
                    ->maxSize(512) // 512KB
                    ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml'])
                    ->directory('branding')
                    ->disk('public')
                    ->helperText('Recommended: 32x32 or 64x64 PNG. Leave empty to keep current.'),

                Forms\Components\TextInput::make('profile_id_prefix')
                    ->label('Matri ID Prefix')
                    ->required()
                    ->maxLength(5)
                    ->helperText('e.g., "AM" generates AM100001, AM100002...'),

                // Contact
                Forms\Components\TextInput::make('email')
                    ->label('Contact Email')
                    ->email()
                    ->helperText('Shown on contact page + receives contact form submissions'),

                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number')
                    ->tel()
                    ->helperText('Shown on contact page and footer'),

                Forms\Components\TextInput::make('whatsapp')
                    ->label('WhatsApp Number')
                    ->tel()
                    ->helperText('WhatsApp chat link on contact page'),

                Forms\Components\Textarea::make('address')
                    ->label('Office Address')
                    ->rows(3)
                    ->helperText('Shown on contact page'),

                // Homepage Stats
                Forms\Components\TextInput::make('total_members')
                    ->label('Total Members (display)')
                    ->numeric()
                    ->helperText('Shown on homepage stats section'),

                Forms\Components\TextInput::make('successful_marriages')
                    ->label('Successful Marriages')
                    ->numeric()
                    ->helperText('Shown on homepage stats section'),

                Forms\Components\TextInput::make('years_of_service')
                    ->label('Years of Service')
                    ->numeric()
                    ->helperText('Shown on homepage stats section'),

                Forms\Components\TextInput::make('copyright_year_start')
                    ->label('Copyright Year Start')
                    ->numeric()
                    ->helperText('e.g., 2024 shows "© 2024-2026"'),

                // Social Links
                Forms\Components\TextInput::make('social_facebook')
                    ->label('Facebook Page URL')
                    ->url()
                    ->helperText('e.g., https://facebook.com/anugrahamatrimony'),

                Forms\Components\TextInput::make('social_instagram')
                    ->label('Instagram URL')
                    ->url()
                    ->helperText('e.g., https://instagram.com/anugrahamatrimony'),

                Forms\Components\TextInput::make('social_youtube')
                    ->label('YouTube Channel URL')
                    ->url()
                    ->helperText('e.g., https://youtube.com/@anugrahamatrimony'),

                Forms\Components\TextInput::make('social_twitter')
                    ->label('Twitter / X URL')
                    ->url()
                    ->helperText('e.g., https://x.com/anugrahamatrimony'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Handle logo upload
        $logoUpload = $data['logo_upload'] ?? null;
        $faviconUpload = $data['favicon_upload'] ?? null;

        // Remove upload fields from site_settings save
        unset($data['logo_upload'], $data['favicon_upload'], $data['current_logo_url'], $data['current_favicon_url']);

        // Save text settings to site_settings table
        foreach ($data as $key => $value) {
            SiteSetting::setValue($key, $value ?? '');
        }

        // Save logo/favicon to theme_settings table
        $theme = ThemeSetting::first();
        if ($theme) {
            $updateData = [];

            if ($logoUpload) {
                // $logoUpload is the stored path from FileUpload component
                $updateData['logo_url'] = '/storage/' . $logoUpload;
            }

            if ($faviconUpload) {
                $updateData['favicon_url'] = '/storage/' . $faviconUpload;
            }

            // Also sync site_name and tagline to theme_settings
            $updateData['site_name'] = $data['site_name'] ?? $theme->site_name;
            $updateData['tagline'] = $data['tagline'] ?? $theme->tagline;

            $theme->update($updateData);

            // Clear theme cache so changes appear immediately
            Cache::forget('theme_settings');
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

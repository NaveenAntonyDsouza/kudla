<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\ThemeSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'General Settings';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'General Settings';
    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        $this->form->fill([
            // General
            'site_name' => $settings['site_name'] ?? '',
            'tagline' => $settings['tagline'] ?? '',

            // Contact
            'email' => $settings['email'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'address' => $settings['address'] ?? '',
            'copyright_year_start' => $settings['copyright_year_start'] ?? date('Y'),
            'google_maps_embed_url' => $settings['google_maps_embed_url'] ?? '',
            'google_maps_api_key' => $settings['google_maps_api_key'] ?? '',
            'google_maps_lat' => $settings['google_maps_lat'] ?? '',
            'google_maps_lng' => $settings['google_maps_lng'] ?? '',

            // Registration Settings
            'profile_id_prefix' => $settings['profile_id_prefix'] ?? 'AM',
            'email_verification_enabled' => $settings['email_verification_enabled'] ?? '1',
            'phone_verification_enabled' => $settings['phone_verification_enabled'] ?? '0',
            'mobile_otp_login_enabled' => $settings['mobile_otp_login_enabled'] ?? '1',
            'email_otp_login_enabled' => $settings['email_otp_login_enabled'] ?? '0',
            'auto_approve_profiles' => $settings['auto_approve_profiles'] ?? '1',
            'auto_approve_profile_photos' => $settings['auto_approve_profile_photos'] ?? '1',
            'auto_approve_album_photos' => $settings['auto_approve_album_photos'] ?? '1',
            'auto_approve_family_photos' => $settings['auto_approve_family_photos'] ?? '1',
            'auto_approve_documents' => $settings['auto_approve_documents'] ?? '1',

            // Social Links
            'social_facebook' => $settings['social_facebook'] ?? '',
            'social_instagram' => $settings['social_instagram'] ?? '',
            'social_youtube' => $settings['social_youtube'] ?? '',
            'social_twitter' => $settings['social_twitter'] ?? '',

            // Mobile App
            'app_play_store_url' => $settings['app_play_store_url'] ?? '',
            'app_apple_store_url' => $settings['app_apple_store_url'] ?? '',
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('General')
                    ->description('Basic site identity settings.')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Displayed in header, footer, emails. e.g., "Anugraha Matrimony"'),

                        Forms\Components\TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(200)
                            ->helperText('Shown on homepage hero section. e.g., "Find Your Perfect Match"'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Contact Information')
                    ->description('Shown on contact page, footer, and structured data.')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Contact Email')
                            ->email()
                            ->helperText('Shown on contact page + receives form submissions'),

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
                            ->helperText('Shown on contact page')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('google_maps_embed_url')
                            ->label('Google Maps Embed URL')
                            ->url()
                            ->placeholder('https://www.google.com/maps/embed?pb=...')
                            ->helperText('Paste the iframe src URL from Google Maps "Share > Embed". Shown on Contact Us page.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('google_maps_api_key')
                            ->label('Google Maps API Key (Optional)')
                            ->placeholder('AIzaSy...')
                            ->helperText('For interactive map. Leave blank to use the embed URL above instead.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('google_maps_lat')
                            ->label('Latitude')
                            ->placeholder('12.9141')
                            ->helperText('Required only if using API key'),

                        Forms\Components\TextInput::make('google_maps_lng')
                            ->label('Longitude')
                            ->placeholder('74.8560')
                            ->helperText('Required only if using API key'),

                        Forms\Components\TextInput::make('copyright_year_start')
                            ->label('Copyright Year Start')
                            ->numeric()
                            ->helperText('e.g., 2024 shows "2024-2026"'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make('Registration & Approval')
                    ->description('Control user registration and content approval behavior.')
                    ->schema([
                        Forms\Components\TextInput::make('profile_id_prefix')
                            ->label('Matri ID Prefix')
                            ->required()
                            ->maxLength(5)
                            ->helperText('e.g., "AM" generates AM100001, AM100002...'),

                        Forms\Components\Toggle::make('email_verification_enabled')
                            ->label('Email Verification Required')
                            ->helperText('Require email OTP during registration'),

                        Forms\Components\Toggle::make('phone_verification_enabled')
                            ->label('Phone Verification Required')
                            ->helperText('Require phone OTP during registration'),

                        Forms\Components\Toggle::make('mobile_otp_login_enabled')
                            ->label('Mobile OTP Login')
                            ->helperText('Allow login via mobile + OTP'),

                        Forms\Components\Toggle::make('email_otp_login_enabled')
                            ->label('Email OTP Login')
                            ->helperText('Allow login via email + OTP'),

                        Forms\Components\Toggle::make('auto_approve_profiles')
                            ->label('Auto-Approve Profiles')
                            ->helperText('Profiles go live immediately'),

                        Forms\Components\Toggle::make('auto_approve_profile_photos')
                            ->label('Auto-Approve Profile Photos'),

                        Forms\Components\Toggle::make('auto_approve_album_photos')
                            ->label('Auto-Approve Album Photos'),

                        Forms\Components\Toggle::make('auto_approve_family_photos')
                            ->label('Auto-Approve Family Photos'),

                        Forms\Components\Toggle::make('auto_approve_documents')
                            ->label('Auto-Approve Documents')
                            ->helperText('Horoscope / Baptism Certificate'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Social Links')
                    ->description('Social media URLs shown in footer and contact page.')
                    ->schema([
                        Forms\Components\TextInput::make('social_facebook')
                            ->label('Facebook Page URL')
                            ->url()
                            ->placeholder('https://facebook.com/...'),

                        Forms\Components\TextInput::make('social_instagram')
                            ->label('Instagram URL')
                            ->url()
                            ->placeholder('https://instagram.com/...'),

                        Forms\Components\TextInput::make('social_youtube')
                            ->label('YouTube Channel URL')
                            ->url()
                            ->placeholder('https://youtube.com/@...'),

                        Forms\Components\TextInput::make('social_twitter')
                            ->label('Twitter / X URL')
                            ->url()
                            ->placeholder('https://x.com/...'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Mobile App')
                    ->description('App download links shown on homepage. Leave blank to hide the section.')
                    ->schema([
                        Forms\Components\TextInput::make('app_play_store_url')
                            ->label('Google Play Store URL')
                            ->url()
                            ->placeholder('https://play.google.com/store/apps/details?id=...'),

                        Forms\Components\TextInput::make('app_apple_store_url')
                            ->label('Apple App Store URL')
                            ->url()
                            ->placeholder('https://apps.apple.com/app/...'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $toggleFields = [
            'email_verification_enabled', 'phone_verification_enabled',
            'mobile_otp_login_enabled', 'email_otp_login_enabled',
            'auto_approve_profiles', 'auto_approve_profile_photos',
            'auto_approve_album_photos', 'auto_approve_family_photos',
            'auto_approve_documents',
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $toggleFields)) {
                SiteSetting::setValue($key, $value ? '1' : '0');
            } else {
                SiteSetting::setValue($key, $value ?? '');
            }
        }

        // Sync site_name and tagline to theme_settings table
        $theme = ThemeSetting::first();
        if ($theme) {
            $theme->update([
                'site_name' => $data['site_name'] ?? $theme->site_name,
                'tagline' => $data['tagline'] ?? $theme->tagline,
            ]);
            \Illuminate\Support\Facades\Cache::forget('theme_settings');
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

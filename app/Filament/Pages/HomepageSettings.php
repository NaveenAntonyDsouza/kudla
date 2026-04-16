<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class HomepageSettings extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Homepage Content';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Homepage Content';
    protected string $view = 'filament.pages.homepage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        $this->form->fill([
            // Hero Section
            'hero_heading' => $settings['hero_heading'] ?? '',
            'hero_subheading' => $settings['hero_subheading'] ?? '',
            'search_heading' => $settings['search_heading'] ?? '',
            'current_hero_image' => $settings['hero_image_url'] ?? '',

            // Stats Section
            'total_members' => $settings['total_members'] ?? '0',
            'successful_marriages' => $settings['successful_marriages'] ?? '0',
            'years_of_service' => $settings['years_of_service'] ?? '1',

            // CTA Section
            'cta_title' => $settings['cta_title'] ?? 'Register Free Today',
            'cta_description' => $settings['cta_description'] ?? 'Join thousands of families who found their perfect match.',
            'cta_button_text' => $settings['cta_button_text'] ?? 'Register Now',

            // Why Choose Us Items (stored as JSON)
            'why_choose_us' => json_decode($settings['why_choose_us'] ?? '[]', true) ?: [
                ['title' => 'Verified Profiles', 'description' => 'Every profile is verified to ensure authenticity and safety for our members.', 'icon' => 'check'],
                ['title' => '100% Privacy', 'description' => 'Your personal information is protected. You control who sees your contact details.', 'icon' => 'lock'],
                ['title' => 'Community Focused', 'description' => 'Designed for families seeking meaningful connections within their community and values.', 'icon' => 'heart'],
                ['title' => 'Easy to Use', 'description' => 'Simple registration, powerful search, and instant messaging from your phone or computer.', 'icon' => 'star'],
            ],

            // Announcement Banner
            'announcement_enabled' => $settings['announcement_enabled'] ?? '0',
            'announcement_text' => $settings['announcement_text'] ?? '',
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Hero Banner')
                    ->description('The main banner section at the top of the homepage.')
                    ->schema([
                        Forms\Components\FileUpload::make('hero_image_upload')
                            ->label('Hero Background Image')
                            ->image()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->directory('branding')
                            ->disk('public')
                            ->helperText('Recommended: 1920x800 landscape photo of a couple. Leave empty to keep current or use gradient fallback.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('hero_heading')
                            ->label('Hero Heading')
                            ->maxLength(200)
                            ->helperText('Big text on homepage hero. e.g., "Find Your Perfect Match in Coastal Karnataka"'),

                        Forms\Components\TextInput::make('hero_subheading')
                            ->label('Hero Subheading')
                            ->maxLength(200)
                            ->helperText('Smaller text below heading. Falls back to site tagline if empty.'),

                        Forms\Components\TextInput::make('search_heading')
                            ->label('Search Box Heading')
                            ->maxLength(200)
                            ->helperText('Text above the search form. e.g., "Search for Your Perfect Partner"'),
                    ]),

                \Filament\Schemas\Components\Section::make('Stats Section')
                    ->description('Numbers displayed on the homepage to build trust.')
                    ->schema([
                        Forms\Components\TextInput::make('total_members')
                            ->label('Total Members')
                            ->numeric()
                            ->helperText('Displayed as "X+ Members" on homepage'),

                        Forms\Components\TextInput::make('successful_marriages')
                            ->label('Successful Marriages')
                            ->numeric()
                            ->helperText('Displayed as "X+ Successful Marriages"'),

                        Forms\Components\TextInput::make('years_of_service')
                            ->label('Years of Service')
                            ->numeric()
                            ->helperText('Displayed as "X+ Years of Service"'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make('CTA Banner')
                    ->description('Call-to-action section that encourages visitors to register.')
                    ->schema([
                        Forms\Components\TextInput::make('cta_title')
                            ->label('CTA Title')
                            ->maxLength(200)
                            ->helperText('e.g., "Register Free Today"'),

                        Forms\Components\TextInput::make('cta_description')
                            ->label('CTA Description')
                            ->maxLength(300)
                            ->helperText('Text below the CTA title'),

                        Forms\Components\TextInput::make('cta_button_text')
                            ->label('CTA Button Text')
                            ->maxLength(50)
                            ->helperText('e.g., "Register Now", "Get Started Free"'),
                    ]),

                \Filament\Schemas\Components\Section::make('Why Choose Us')
                    ->description('Feature highlights shown on the homepage. Up to 4 items recommended.')
                    ->schema([
                        Forms\Components\Repeater::make('why_choose_us')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(50),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->rows(2)
                                    ->maxLength(200),

                                Forms\Components\Select::make('icon')
                                    ->label('Icon')
                                    ->options([
                                        'check' => 'Checkmark',
                                        'lock' => 'Lock / Privacy',
                                        'heart' => 'Heart',
                                        'star' => 'Star',
                                        'shield' => 'Shield / Security',
                                        'users' => 'Users / Community',
                                        'phone' => 'Phone / Support',
                                        'globe' => 'Globe / Worldwide',
                                    ])
                                    ->default('check'),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->maxItems(6)
                            ->defaultItems(4)
                            ->reorderable()
                            ->collapsible(),
                    ]),

                \Filament\Schemas\Components\Section::make('Announcement Banner')
                    ->description('Optional banner shown at the top of the site.')
                    ->schema([
                        Forms\Components\Toggle::make('announcement_enabled')
                            ->label('Show Announcement Banner')
                            ->helperText('Display a banner at the top of all pages.'),

                        Forms\Components\TextInput::make('announcement_text')
                            ->label('Announcement Text')
                            ->maxLength(300)
                            ->helperText('e.g., "We are now available in Mangalore! Register today."'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Handle hero image upload
        $heroUpload = $data['hero_image_upload'] ?? null;
        if ($heroUpload) {
            SiteSetting::setValue('hero_image_url', '/storage/' . $heroUpload);
        }

        $toggleFields = ['announcement_enabled'];
        $skipFields = ['hero_image_upload', 'current_hero_image'];

        foreach ($data as $key => $value) {
            if (in_array($key, $skipFields)) {
                continue;
            } elseif ($key === 'why_choose_us') {
                SiteSetting::setValue($key, json_encode($value));
            } elseif (in_array($key, $toggleFields)) {
                SiteSetting::setValue($key, $value ? '1' : '0');
            } else {
                SiteSetting::setValue($key, $value ?? '');
            }
        }

        Notification::make()
            ->title('Homepage content saved successfully')
            ->success()
            ->send();
    }

    public function removeHeroImage(): void
    {
        $currentUrl = SiteSetting::getValue('hero_image_url', '');
        if ($currentUrl) {
            $path = str_replace('/storage/', '', $currentUrl);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            SiteSetting::setValue('hero_image_url', '');

            Notification::make()
                ->title('Hero image removed. Gradient will be used as fallback.')
                ->success()
                ->send();
        }
    }
}

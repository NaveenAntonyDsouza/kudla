<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?int $navigationSort = 10;
    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        $this->form->fill([
            'site_name' => $settings['site_name'] ?? '',
            'tagline' => $settings['tagline'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'whatsapp' => $settings['whatsapp'] ?? '',
            'email' => $settings['email'] ?? '',
            'address' => $settings['address'] ?? '',
            'profile_id_prefix' => $settings['profile_id_prefix'] ?? 'AM',
            'total_members' => $settings['total_members'] ?? '0',
            'successful_marriages' => $settings['successful_marriages'] ?? '0',
            'years_of_service' => $settings['years_of_service'] ?? '1',
            'copyright_year_start' => $settings['copyright_year_start'] ?? date('Y'),
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label('Site Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->helperText('Displayed in header, footer, emails'),

                                Forms\Components\TextInput::make('tagline')
                                    ->label('Tagline')
                                    ->maxLength(200)
                                    ->helperText('Shown on homepage hero section'),

                                Forms\Components\TextInput::make('profile_id_prefix')
                                    ->label('Matri ID Prefix')
                                    ->required()
                                    ->maxLength(5)
                                    ->helperText('e.g., "AM" generates AM100001, AM100002...'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Contact')
                            ->icon('heroicon-o-phone')
                            ->schema([
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
                            ]),

                        Forms\Components\Tabs\Tab::make('Homepage Stats')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\TextInput::make('total_members')
                                    ->label('Total Members (display)')
                                    ->numeric()
                                    ->helperText('Shown on homepage stats section. Can be different from actual DB count.'),

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
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SiteSetting::setValue($key, $value ?? '');
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}

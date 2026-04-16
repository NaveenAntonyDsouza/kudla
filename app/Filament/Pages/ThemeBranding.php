<?php

namespace App\Filament\Pages;

use App\Models\ThemeSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Livewire\WithFileUploads;

class ThemeBranding extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Theme & Branding';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Theme & Branding';
    protected string $view = 'filament.pages.theme-branding';

    public ?array $data = [];

    public function mount(): void
    {
        $theme = ThemeSetting::first();

        $this->form->fill([
            'primary_color' => $theme?->primary_color ?? '#8B1D91',
            'primary_hover' => $theme?->primary_hover ?? '#6B1571',
            'primary_light' => $theme?->primary_light ?? '#F3E8F7',
            'secondary_color' => $theme?->secondary_color ?? '#00BCD4',
            'secondary_hover' => $theme?->secondary_hover ?? '#00ACC1',
            'secondary_light' => $theme?->secondary_light ?? '#E0F7FA',
            'current_logo_url' => $theme?->logo_url,
            'current_favicon_url' => $theme?->favicon_url,
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Logo & Favicon')
                    ->description('Upload your site logo and favicon.')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_upload')
                            ->label('Site Logo')
                            ->image()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                            ->directory('branding')
                            ->disk('public')
                            ->helperText('Recommended: PNG or SVG, max height 40px. Leave empty to keep current logo.'),

                        Forms\Components\FileUpload::make('favicon_upload')
                            ->label('Favicon')
                            ->image()
                            ->maxSize(512)
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml'])
                            ->directory('branding')
                            ->disk('public')
                            ->helperText('Recommended: 32x32 or 64x64 PNG. Leave empty to keep current.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Primary Colors')
                    ->description('Main brand color used for buttons, links, and accents.')
                    ->schema([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->required()
                            ->helperText('Main brand color (buttons, links). Default: #8B1D91'),

                        Forms\Components\ColorPicker::make('primary_hover')
                            ->label('Primary Hover')
                            ->required()
                            ->helperText('Darker shade for hover states. Default: #6B1571'),

                        Forms\Components\ColorPicker::make('primary_light')
                            ->label('Primary Light')
                            ->required()
                            ->helperText('Light tint for backgrounds. Default: #F3E8F7'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make('Secondary Colors')
                    ->description('Accent color used for secondary elements and highlights.')
                    ->schema([
                        Forms\Components\ColorPicker::make('secondary_color')
                            ->label('Secondary Color')
                            ->required()
                            ->helperText('Accent color. Default: #00BCD4'),

                        Forms\Components\ColorPicker::make('secondary_hover')
                            ->label('Secondary Hover')
                            ->required()
                            ->helperText('Darker shade for hover. Default: #00ACC1'),

                        Forms\Components\ColorPicker::make('secondary_light')
                            ->label('Secondary Light')
                            ->required()
                            ->helperText('Light tint for backgrounds. Default: #E0F7FA'),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $theme = ThemeSetting::first();
        if (! $theme) {
            Notification::make()->title('No theme record found. Please run database seeder.')->danger()->send();
            return;
        }

        $updateData = [
            'primary_color' => $data['primary_color'],
            'primary_hover' => $data['primary_hover'],
            'primary_light' => $data['primary_light'],
            'secondary_color' => $data['secondary_color'],
            'secondary_hover' => $data['secondary_hover'],
            'secondary_light' => $data['secondary_light'],
        ];

        // Handle logo upload
        $logoUpload = $data['logo_upload'] ?? null;
        if ($logoUpload) {
            $updateData['logo_url'] = '/storage/' . $logoUpload;
        }

        // Handle favicon upload
        $faviconUpload = $data['favicon_upload'] ?? null;
        if ($faviconUpload) {
            $updateData['favicon_url'] = '/storage/' . $faviconUpload;
        }

        $theme->update($updateData);

        // Clear theme cache
        Cache::forget('theme_settings');

        Notification::make()
            ->title('Theme & branding saved successfully')
            ->success()
            ->send();
    }

    public function removeLogo(): void
    {
        $theme = ThemeSetting::first();
        if ($theme && $theme->logo_url) {
            // Delete file from storage
            $path = str_replace('/storage/', '', $theme->logo_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

            $theme->update(['logo_url' => null]);
            Cache::forget('theme_settings');

            Notification::make()
                ->title('Logo removed successfully')
                ->success()
                ->send();
        }
    }

    public function removeFavicon(): void
    {
        $theme = ThemeSetting::first();
        if ($theme && $theme->favicon_url) {
            $path = str_replace('/storage/', '', $theme->favicon_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);

            $theme->update(['favicon_url' => null]);
            Cache::forget('theme_settings');

            Notification::make()
                ->title('Favicon removed successfully')
                ->success()
                ->send();
        }
    }

    public function resetToDefaults(): void
    {
        $this->form->fill([
            'primary_color' => '#8B1D91',
            'primary_hover' => '#6B1571',
            'primary_light' => '#F3E8F7',
            'secondary_color' => '#00BCD4',
            'secondary_hover' => '#00ACC1',
            'secondary_light' => '#E0F7FA',
        ]);

        Notification::make()
            ->title('Colors reset to defaults. Click "Save" to apply.')
            ->info()
            ->send();
    }
}

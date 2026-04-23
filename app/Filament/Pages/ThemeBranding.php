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

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_site_settings');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_site_settings');
    }

    public function mount(): void
    {
        $theme = ThemeSetting::first();

        // Detect if the current heading/body font is one of the curated options,
        // or if it's a custom font typed by the admin.
        $curatedHeadings = array_column(config('fonts.headings', []), 'family');
        $curatedBody = array_column(config('fonts.body', []), 'family');

        $currentHeading = $theme?->heading_font ?? 'Playfair Display';
        $currentBody = $theme?->body_font ?? 'Inter';

        $headingIsCustom = !in_array($currentHeading, $curatedHeadings, true);
        $bodyIsCustom = !in_array($currentBody, $curatedBody, true);

        $this->form->fill([
            'primary_color' => $theme?->primary_color ?? '#8B1D91',
            'primary_hover' => $theme?->primary_hover ?? '#6B1571',
            'primary_light' => $theme?->primary_light ?? '#F3E8F7',
            'secondary_color' => $theme?->secondary_color ?? '#00BCD4',
            'secondary_hover' => $theme?->secondary_hover ?? '#00ACC1',
            'secondary_light' => $theme?->secondary_light ?? '#E0F7FA',
            'heading_font' => $headingIsCustom ? 'Playfair Display' : $currentHeading,
            'body_font' => $bodyIsCustom ? 'Inter' : $currentBody,
            'custom_heading_font' => $headingIsCustom ? $currentHeading : '',
            'custom_body_font' => $bodyIsCustom ? $currentBody : '',
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

                \Filament\Schemas\Components\Section::make('Typography')
                    ->description('Choose fonts for headings and body text. Pick from curated fonts or enter any Google Font name.')
                    ->schema([
                        Forms\Components\Select::make('heading_font')
                            ->label('Heading Font')
                            ->required()
                            ->options(collect(config('fonts.headings', []))
                                ->mapWithKeys(fn ($f, $key) => [$f['family'] => $f['label']])
                                ->toArray())
                            ->allowHtml()
                            ->searchable()
                            ->helperText('Used for page titles, hero headings, section headers.')
                            ->placeholder('Select a heading font'),

                        Forms\Components\Select::make('body_font')
                            ->label('Body Font')
                            ->required()
                            ->options(collect(config('fonts.body', []))
                                ->mapWithKeys(fn ($f, $key) => [$f['family'] => $f['label']])
                                ->toArray())
                            ->allowHtml()
                            ->searchable()
                            ->helperText('Used for body text, buttons, forms, navigation.')
                            ->placeholder('Select a body font'),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('custom_heading_font')
                                    ->label('Custom Heading Font (advanced)')
                                    ->placeholder('e.g., Montserrat or Crimson Text')
                                    ->helperText('Optional. Type any Google Fonts name to override the curated pick above. Leave blank to use the dropdown selection.'),

                                Forms\Components\TextInput::make('custom_body_font')
                                    ->label('Custom Body Font (advanced)')
                                    ->placeholder('e.g., Roboto or Quicksand')
                                    ->helperText('Optional. Type any Google Fonts name to override the curated pick above.'),
                            ]),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function getViewData(): array
    {
        return [
            'presets' => config('theme_presets.presets', []),
            'activePresetKey' => \App\Models\SiteSetting::getValue('active_theme_preset', ''),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $theme = ThemeSetting::first();
        if (! $theme) {
            Notification::make()->title('No theme record found. Please run database seeder.')->danger()->send();
            return;
        }

        // Font resolution: custom input overrides curated dropdown if filled.
        $headingFont = trim($data['custom_heading_font'] ?? '') !== ''
            ? trim($data['custom_heading_font'])
            : ($data['heading_font'] ?? 'Playfair Display');

        $bodyFont = trim($data['custom_body_font'] ?? '') !== ''
            ? trim($data['custom_body_font'])
            : ($data['body_font'] ?? 'Inter');

        $updateData = [
            'primary_color' => $data['primary_color'],
            'primary_hover' => $data['primary_hover'],
            'primary_light' => $data['primary_light'],
            'secondary_color' => $data['secondary_color'],
            'secondary_hover' => $data['secondary_hover'],
            'secondary_light' => $data['secondary_light'],
            'heading_font' => $headingFont,
            'body_font' => $bodyFont,
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

    /**
     * Apply a preset theme (sets all 6 colors from config/theme_presets.php).
     * Called from the Preset Themes picker in the view.
     */
    public function applyPreset(string $presetKey): void
    {
        $preset = config("theme_presets.presets.{$presetKey}");
        if (!$preset) {
            Notification::make()->title('Unknown preset: ' . $presetKey)->danger()->send();
            return;
        }

        $theme = ThemeSetting::first();
        if (!$theme) {
            Notification::make()->title('No theme record found. Run the seeder first.')->danger()->send();
            return;
        }

        $theme->update([
            'primary_color' => $preset['primary_color'],
            'primary_hover' => $preset['primary_hover'],
            'primary_light' => $preset['primary_light'],
            'secondary_color' => $preset['secondary_color'],
            'secondary_hover' => $preset['secondary_hover'],
            'secondary_light' => $preset['secondary_light'],
        ]);

        // Remember which preset was last applied (for UI active-state)
        \App\Models\SiteSetting::setValue('active_theme_preset', $presetKey);

        Cache::forget('theme_settings');
        \Cache::forget('site_setting.active_theme_preset');

        // Re-fill form state so the color inputs reflect the applied preset
        $this->form->fill([
            'primary_color' => $preset['primary_color'],
            'primary_hover' => $preset['primary_hover'],
            'primary_light' => $preset['primary_light'],
            'secondary_color' => $preset['secondary_color'],
            'secondary_hover' => $preset['secondary_hover'],
            'secondary_light' => $preset['secondary_light'],
            'current_logo_url' => $theme->logo_url,
            'current_favicon_url' => $theme->favicon_url,
        ]);

        Notification::make()
            ->title('Preset applied: ' . $preset['name'])
            ->body('Color palette updated. Save any other changes (logo/favicon) as needed.')
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

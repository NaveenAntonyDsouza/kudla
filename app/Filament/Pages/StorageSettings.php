<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\PhotoStorageService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StorageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Photo Storage';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Photo Storage Driver';
    protected string $view = 'filament.pages.storage-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_site_settings');
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_site_settings');
    }

    public function mount(): void
    {
        $this->form->fill([
            'active_storage_driver' => SiteSetting::getValue('active_storage_driver', PhotoStorageService::DRIVER_LOCAL),
            'photo_watermark_enabled' => SiteSetting::getValue('photo_watermark_enabled', '0') === '1',
            'image_output_format' => SiteSetting::getValue('image_output_format', 'webp'),
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Active Storage Driver')
                    ->description('Choose where NEW photo uploads are stored. Existing photos remain on their original driver (hybrid mode).')
                    ->schema([
                        Forms\Components\Radio::make('active_storage_driver')
                            ->label('')
                            ->options([
                                PhotoStorageService::DRIVER_LOCAL => 'Local Storage (server filesystem) — always available, no external setup',
                                PhotoStorageService::DRIVER_CLOUDINARY => 'Cloudinary — managed CDN with image optimization',
                                PhotoStorageService::DRIVER_R2 => 'Cloudflare R2 — zero-egress object storage (recommended for scale)',
                                PhotoStorageService::DRIVER_S3 => 'AWS S3 — industry-standard object storage (needs AWS_* env vars)',
                            ])
                            ->required()
                            ->inline(false),
                    ])
                    ->columns(1),

                \Filament\Schemas\Components\Section::make('Image Output Format')
                    ->description('Format for generated size variants (thumb, medium, full).')
                    ->schema([
                        Forms\Components\Radio::make('image_output_format')
                            ->label('')
                            ->options([
                                'webp' => 'WebP — 25-35% smaller than JPEG, 99% browser support (recommended)',
                                'jpg' => 'JPEG — universal, slightly larger files',
                            ])
                            ->required()
                            ->inline(false),
                    ])
                    ->columns(1),

                \Filament\Schemas\Components\Section::make('Watermark')
                    ->description('Apply a diagonal repeating site-name watermark to every uploaded photo. Modern matrimony platforms typically have this disabled in favor of UI-level blur for protected photos.')
                    ->schema([
                        Forms\Components\Toggle::make('photo_watermark_enabled')
                            ->label('Enable watermark on all uploaded photos')
                            ->helperText('Watermark text uses the site name from general settings.'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('active_storage_driver', $data['active_storage_driver']);
        SiteSetting::setValue('image_output_format', $data['image_output_format']);
        SiteSetting::setValue('photo_watermark_enabled', $data['photo_watermark_enabled'] ? '1' : '0');

        // Clear the cache so changes apply immediately
        \Cache::forget('site_setting.active_storage_driver');
        \Cache::forget('site_setting.image_output_format');
        \Cache::forget('site_setting.photo_watermark_enabled');

        Notification::make()
            ->title('Storage settings saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    $service = app(PhotoStorageService::class);
                    $driver = $this->data['active_storage_driver'] ?? PhotoStorageService::DRIVER_LOCAL;
                    $result = $service->testConnection($driver);

                    Notification::make()
                        ->title($result['ok'] ? 'Connection OK' : 'Connection failed')
                        ->body($result['message'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();
                }),
        ];
    }

    public function getViewData(): array
    {
        $service = app(PhotoStorageService::class);

        return [
            'drivers' => collect(PhotoStorageService::SUPPORTED_DRIVERS)
                ->map(fn ($d) => [
                    'key' => $d,
                    'label' => $service->driverLabel($d),
                    'configured' => $service->isDriverConfigured($d),
                ])
                ->toArray(),
        ];
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class GatewaySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Email, SMS & Payment';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Email, SMS & Payment Settings';
    protected string $view = 'filament.pages.gateway-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('manage_gateway_settings');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_gateway_settings');
    }

    public function mount(): void
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        $this->form->fill([
            // SMTP
            'mail_driver' => $settings['mail_driver'] ?? config('mail.default'),
            'mail_host' => $settings['mail_host'] ?? config('mail.mailers.smtp.host'),
            'mail_port' => $settings['mail_port'] ?? config('mail.mailers.smtp.port'),
            'mail_username' => $settings['mail_username'] ?? config('mail.mailers.smtp.username'),
            'mail_password' => $settings['mail_password'] ?? '',
            'mail_encryption' => $settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption'),
            'mail_from_address' => $settings['mail_from_address'] ?? config('mail.from.address'),
            'mail_from_name' => $settings['mail_from_name'] ?? config('mail.from.name'),

            // SMS (Fast2SMS)
            'sms_provider' => $settings['sms_provider'] ?? 'fast2sms',
            'sms_api_key' => $settings['sms_api_key'] ?? config('services.fast2sms.api_key', ''),
            'sms_sender_id' => $settings['sms_sender_id'] ?? config('services.fast2sms.sender_id', ''),
            'otp_length' => $settings['otp_length'] ?? '6',
            'otp_expiry_minutes' => $settings['otp_expiry_minutes'] ?? '10',

            // Razorpay
            'razorpay_key_id' => $settings['razorpay_key_id'] ?? config('services.razorpay.key', ''),
            'razorpay_key_secret' => $settings['razorpay_key_secret'] ?? '',
            'razorpay_webhook_secret' => $settings['razorpay_webhook_secret'] ?? '',
            'razorpay_mode' => $settings['razorpay_mode'] ?? 'test',
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Email / SMTP Configuration')
                    ->description('Configure outgoing email settings. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('mail_driver')
                            ->label('Mail Driver')
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'log' => 'Log (for testing)',
                            ])
                            ->required()
                            ->default('smtp'),

                        Forms\Components\TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->placeholder('smtp.hostinger.com')
                            ->helperText('e.g., smtp.hostinger.com, smtp.gmail.com'),

                        Forms\Components\TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->placeholder('465')
                            ->helperText('Common: 465 (SSL), 587 (TLS), 25 (unencrypted)'),

                        Forms\Components\Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'ssl' => 'SSL',
                                'tls' => 'TLS',
                                '' => 'None',
                            ])
                            ->default('ssl'),

                        Forms\Components\TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->placeholder('info@yourdomain.com'),

                        Forms\Components\TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->revealable()
                            ->helperText('Leave empty to keep existing password.'),

                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->placeholder('info@yourdomain.com')
                            ->helperText('The sender email address.'),

                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->placeholder('Your Matrimony Site')
                            ->helperText('The sender display name.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('SMS / OTP Gateway')
                    ->description('Configure SMS provider for OTP verification. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('sms_provider')
                            ->label('SMS Provider')
                            ->options([
                                'fast2sms' => 'Fast2SMS',
                                'twilio' => 'Twilio',
                                'msg91' => 'MSG91',
                                'textlocal' => 'TextLocal',
                            ])
                            ->default('fast2sms'),

                        Forms\Components\TextInput::make('sms_api_key')
                            ->label('API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Leave empty to keep existing key.'),

                        Forms\Components\TextInput::make('sms_sender_id')
                            ->label('Sender ID')
                            ->maxLength(6)
                            ->placeholder('ANUGRA')
                            ->helperText('6-character sender ID for India.'),

                        Forms\Components\Select::make('otp_length')
                            ->label('OTP Length')
                            ->options(['4' => '4 digits', '6' => '6 digits'])
                            ->default('6'),

                        Forms\Components\TextInput::make('otp_expiry_minutes')
                            ->label('OTP Expiry (minutes)')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(30),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Payment Gateway (Razorpay)')
                    ->description('Configure Razorpay payment integration. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('razorpay_mode')
                            ->label('Mode')
                            ->options([
                                'test' => 'Test Mode',
                                'live' => 'Live Mode',
                            ])
                            ->default('test')
                            ->helperText('Use Test mode until you have verified Razorpay live credentials.'),

                        Forms\Components\TextInput::make('razorpay_key_id')
                            ->label('Key ID')
                            ->placeholder('rzp_test_...')
                            ->helperText('Razorpay Key ID (starts with rzp_test_ or rzp_live_)'),

                        Forms\Components\TextInput::make('razorpay_key_secret')
                            ->label('Key Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Leave empty to keep existing secret.'),

                        Forms\Components\TextInput::make('razorpay_webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Optional. Used to verify webhook signatures.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Fields where empty means "keep existing value"
        $passwordFields = ['mail_password', 'sms_api_key', 'razorpay_key_secret', 'razorpay_webhook_secret'];

        foreach ($data as $key => $value) {
            // Don't overwrite secrets with empty values
            if (in_array($key, $passwordFields) && empty($value)) {
                continue;
            }

            SiteSetting::setValue($key, $value ?? '');
        }

        // Clear config cache so new values take effect
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Silently ignore if artisan command fails
        }

        Notification::make()
            ->title('Gateway settings saved successfully')
            ->success()
            ->send();
    }

    /**
     * Send a test email to verify SMTP settings.
     */
    public function sendTestEmail(): void
    {
        $adminEmail = SiteSetting::getValue('email');

        if (!$adminEmail) {
            Notification::make()
                ->title('No admin email configured. Go to General Settings first.')
                ->danger()
                ->send();
            return;
        }

        try {
            // Apply current form values temporarily
            $data = $this->form->getState();
            $this->applyMailConfig($data);

            Mail::raw('This is a test email from ' . config('app.name') . ' admin panel. If you received this, your SMTP settings are working correctly.', function ($message) use ($adminEmail) {
                $message->to($adminEmail)
                    ->subject('Test Email - ' . config('app.name'));
            });

            Notification::make()
                ->title("Test email sent to {$adminEmail}")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Email failed: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    /**
     * Apply mail config from form data for test email.
     */
    protected function applyMailConfig(array $data): void
    {
        if (!empty($data['mail_host'])) {
            config([
                'mail.default' => $data['mail_driver'] ?? 'smtp',
                'mail.mailers.smtp.host' => $data['mail_host'],
                'mail.mailers.smtp.port' => (int) ($data['mail_port'] ?? 465),
                'mail.mailers.smtp.username' => $data['mail_username'] ?? '',
                'mail.mailers.smtp.encryption' => $data['mail_encryption'] ?? 'ssl',
                'mail.from.address' => $data['mail_from_address'] ?? '',
                'mail.from.name' => $data['mail_from_name'] ?? config('app.name'),
            ]);

            // Only override password if provided
            if (!empty($data['mail_password'])) {
                config(['mail.mailers.smtp.password' => $data['mail_password']]);
            } else {
                $storedPassword = SiteSetting::getValue('mail_password');
                if ($storedPassword) {
                    config(['mail.mailers.smtp.password' => $storedPassword]);
                }
            }
        }
    }
}

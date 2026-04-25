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

            // Stripe
            'stripe_key' => $settings['stripe_key'] ?? config('services.stripe.key', ''),
            'stripe_secret' => $settings['stripe_secret'] ?? '',
            'stripe_webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
            'stripe_mode' => $settings['stripe_mode'] ?? 'test',

            // PayPal
            'paypal_client_id' => $settings['paypal_client_id'] ?? config('services.paypal.client_id', ''),
            'paypal_secret' => $settings['paypal_secret'] ?? '',
            'paypal_webhook_id' => $settings['paypal_webhook_id'] ?? config('services.paypal.webhook_id', ''),
            'paypal_mode' => $settings['paypal_mode'] ?? config('services.paypal.mode', 'sandbox'),
            'paypal_currency' => $settings['paypal_currency'] ?? config('services.paypal.currency', 'USD'),

            // Paytm
            'paytm_mid' => $settings['paytm_mid'] ?? config('services.paytm.mid', ''),
            'paytm_key' => $settings['paytm_key'] ?? '',
            'paytm_mode' => $settings['paytm_mode'] ?? config('services.paytm.mode', 'sandbox'),
            'paytm_website' => $settings['paytm_website'] ?? config('services.paytm.website', 'WEBSTAGING'),
            'paytm_industry_type' => $settings['paytm_industry_type'] ?? config('services.paytm.industry_type', 'Retail'),
            'paytm_channel_id' => $settings['paytm_channel_id'] ?? config('services.paytm.channel_id', 'WAP'),

            // PhonePe (V2 Standard Checkout)
            'phonepe_client_id' => $settings['phonepe_client_id'] ?? config('services.phonepe.client_id', ''),
            'phonepe_client_secret' => $settings['phonepe_client_secret'] ?? '',
            'phonepe_client_version' => $settings['phonepe_client_version'] ?? config('services.phonepe.client_version', '1'),
            'phonepe_mode' => $settings['phonepe_mode'] ?? config('services.phonepe.mode', 'sandbox'),
            'phonepe_webhook_username' => $settings['phonepe_webhook_username'] ?? config('services.phonepe.webhook_username', ''),
            'phonepe_webhook_password' => $settings['phonepe_webhook_password'] ?? '',
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

                \Filament\Schemas\Components\Section::make('Payment Gateway (Stripe)')
                    ->description('Configure Stripe payment integration. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('stripe_mode')
                            ->label('Mode')
                            ->options([
                                'test' => 'Test Mode',
                                'live' => 'Live Mode',
                            ])
                            ->default('test')
                            ->helperText('Use Test mode until you have verified Stripe live credentials.'),

                        Forms\Components\TextInput::make('stripe_key')
                            ->label('Publishable Key')
                            ->placeholder('pk_test_...')
                            ->helperText('Stripe publishable key (pk_test_ / pk_live_). Safe to expose to client apps.'),

                        Forms\Components\TextInput::make('stripe_secret')
                            ->label('Secret Key')
                            ->password()
                            ->revealable()
                            ->helperText('Stripe secret key (sk_test_ / sk_live_). Leave empty to keep existing.'),

                        Forms\Components\TextInput::make('stripe_webhook_secret')
                            ->label('Webhook Signing Secret')
                            ->password()
                            ->revealable()
                            ->helperText('whsec_... — used to verify Stripe-Signature header. Leave empty to keep existing.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Payment Gateway (PayPal)')
                    ->description('Configure PayPal payment integration. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('paypal_mode')
                            ->label('Mode')
                            ->options([
                                'sandbox' => 'Sandbox',
                                'live' => 'Live',
                            ])
                            ->default('sandbox')
                            ->helperText('Use Sandbox until you have verified PayPal live credentials.'),

                        Forms\Components\TextInput::make('paypal_currency')
                            ->label('Currency (ISO-4217)')
                            ->placeholder('USD')
                            ->maxLength(3)
                            ->helperText('3-letter currency code. PayPal-India accounts cannot receive INR — default USD.'),

                        Forms\Components\TextInput::make('paypal_client_id')
                            ->label('Client ID')
                            ->helperText('From PayPal Developer dashboard → My Apps & Credentials.'),

                        Forms\Components\TextInput::make('paypal_secret')
                            ->label('Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Leave empty to keep existing secret.'),

                        Forms\Components\TextInput::make('paypal_webhook_id')
                            ->label('Webhook ID')
                            ->helperText('The webhook id PayPal assigns when you register your webhook URL. Required to verify inbound webhook signatures.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Payment Gateway (Paytm)')
                    ->description('Configure Paytm payment integration. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('paytm_mode')
                            ->label('Mode')
                            ->options([
                                'sandbox' => 'Sandbox (securegw-stage)',
                                'production' => 'Production (securegw)',
                            ])
                            ->default('sandbox')
                            ->helperText('Sandbox for testing; switch to Production once Paytm activates your live MID.'),

                        Forms\Components\TextInput::make('paytm_mid')
                            ->label('Merchant ID (MID)')
                            ->helperText('Your Paytm Merchant ID — provided by Paytm at onboarding.'),

                        Forms\Components\TextInput::make('paytm_key')
                            ->label('Merchant Key')
                            ->password()
                            ->revealable()
                            ->helperText('Secret used for AES-128-CBC checksum signing. Leave empty to keep existing.'),

                        Forms\Components\TextInput::make('paytm_website')
                            ->label('Website Name')
                            ->placeholder('WEBSTAGING')
                            ->helperText('Provided by Paytm. WEBSTAGING for sandbox; merchant-specific for live.'),

                        Forms\Components\TextInput::make('paytm_industry_type')
                            ->label('Industry Type')
                            ->placeholder('Retail')
                            ->helperText('Provided by Paytm at onboarding (e.g. Retail).'),

                        Forms\Components\Select::make('paytm_channel_id')
                            ->label('Channel ID')
                            ->options([
                                'WAP' => 'WAP (mobile app SDK)',
                                'WEB' => 'WEB (browser flow)',
                            ])
                            ->default('WAP')
                            ->helperText('WAP for the Flutter SDK flow; WEB for browser-based checkout.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Payment Gateway (PhonePe V2)')
                    ->description('Configure PhonePe Standard Checkout V2. Falls back to .env values if left empty.')
                    ->schema([
                        Forms\Components\Select::make('phonepe_mode')
                            ->label('Mode')
                            ->options([
                                'sandbox' => 'Sandbox (api-preprod)',
                                'production' => 'Production (api.phonepe.com)',
                            ])
                            ->default('sandbox')
                            ->helperText('Sandbox for testing; switch to Production once your PG V2 keys are activated.'),

                        Forms\Components\TextInput::make('phonepe_client_id')
                            ->label('Client ID')
                            ->helperText('From PhonePe Dashboard → Developer Settings → PG V2 keys.'),

                        Forms\Components\TextInput::make('phonepe_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->revealable()
                            ->helperText('PG V2 client secret. Leave empty to keep existing.'),

                        Forms\Components\TextInput::make('phonepe_client_version')
                            ->label('Client Version')
                            ->placeholder('1')
                            ->helperText('Provided by PhonePe — usually 1.'),

                        Forms\Components\TextInput::make('phonepe_webhook_username')
                            ->label('Webhook Username')
                            ->helperText('Configure in PhonePe Dashboard → Webhooks. PhonePe authenticates each callback with SHA256(username:password).'),

                        Forms\Components\TextInput::make('phonepe_webhook_password')
                            ->label('Webhook Password')
                            ->password()
                            ->revealable()
                            ->helperText('Paired with the webhook username above. Leave empty to keep existing.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Fields where empty means "keep existing value"
        $passwordFields = [
            'mail_password',
            'sms_api_key',
            'razorpay_key_secret', 'razorpay_webhook_secret',
            'stripe_secret', 'stripe_webhook_secret',
            'paypal_secret',
            'paytm_key',
            'phonepe_client_secret', 'phonepe_webhook_password',
        ];

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

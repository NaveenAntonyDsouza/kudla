<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppInboxService;
use App\Support\WhatsApp;
use App\Support\WhatsAppCloud;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

/**
 * Settings -> WhatsApp API (Oct 2026): connects the office number to Meta's
 * WhatsApp Cloud API. The ids and secrets come from the Meta app (App ->
 * WhatsApp -> API setup) and the system user token from Business Settings.
 * Secrets are stored encrypted and never shown again; leave a secret field
 * empty to keep the saved one.
 */
class WhatsAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'WhatsApp API';
    protected static \UnitEnum|string|null $navigationGroup = 'Admin & Security';
    protected static ?int $navigationSort = 9;
    protected static ?string $title = 'WhatsApp API';
    protected string $view = 'filament.pages.whatsapp-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('manage_gateway_settings');
    }

    public function mount(): void
    {
        if (WhatsAppCloud::verifyToken() === '') {
            SiteSetting::setValue('wa_cloud_verify_token', Str::random(32));
        }

        $this->form->fill([
            'wa_cloud_enabled' => SiteSetting::getValue('wa_cloud_enabled', '0') === '1',
            'wa_cloud_phone_number_id' => SiteSetting::getValue('wa_cloud_phone_number_id', ''),
            'wa_cloud_waba_id' => SiteSetting::getValue('wa_cloud_waba_id', ''),
            'wa_cloud_access_token' => '',
            'wa_cloud_app_secret' => '',
            'wa_cloud_auto_reply_enabled' => SiteSetting::getValue('wa_cloud_auto_reply_enabled', '1') === '1',
            'wa_cloud_auto_reply' => SiteSetting::getValue('wa_cloud_auto_reply', ''),
            'wa_cloud_photo_reply' => SiteSetting::getValue('wa_cloud_photo_reply', ''),
            'wa_cloud_photo_template' => WhatsAppCloud::photoTemplate(),
            'wa_cloud_templates_enabled' => SiteSetting::getValue('wa_cloud_templates_enabled', '0') === '1',
            'wa_cloud_login_codes_enabled' => SiteSetting::getValue('wa_cloud_login_codes_enabled', '0') === '1',
            'otp_phone_daily_cap' => (string) \App\Services\OtpService::dailyCap(),
            'wa_cloud_alert_enabled' => SiteSetting::getValue('wa_cloud_alert_enabled', '1') === '1',
            'wa_cloud_alert_email' => SiteSetting::getValue('wa_cloud_alert_email', ''),
        ]);
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $saved = fn (string $key) => WhatsAppCloud::hasSecret($key) ? 'Saved. Leave empty to keep it.' : 'Not set yet.';

        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Connection')
                    ->description('From the Meta app: WhatsApp -> API setup. The token is the permanent system user token from Business Settings -> System users.')
                    ->schema([
                        Forms\Components\Toggle::make('wa_cloud_enabled')
                            ->label('Use the WhatsApp API')
                            ->helperText('When on, messages to the office number arrive here, photos are added to profiles for approval, and automatic replies go out.')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('wa_cloud_phone_number_id')
                            ->label('Phone number ID')
                            ->regex('/^\d{6,25}$/')
                            ->validationMessages(['regex' => 'Digits only, as shown in Meta.']),
                        Forms\Components\TextInput::make('wa_cloud_waba_id')
                            ->label('WhatsApp Business Account ID')
                            ->regex('/^\d{6,25}$/')
                            ->validationMessages(['regex' => 'Digits only, as shown in Meta.']),
                        Forms\Components\TextInput::make('wa_cloud_access_token')
                            ->label('Access token')
                            ->password()
                            ->autocomplete('new-password')
                            ->helperText(fn () => $saved('wa_cloud_access_token')),
                        Forms\Components\TextInput::make('wa_cloud_app_secret')
                            ->label('App secret')
                            ->password()
                            ->autocomplete('new-password')
                            ->helperText(fn () => 'Meta app -> App settings -> Basic. Used to check that webhook calls really come from Meta. '.$saved('wa_cloud_app_secret')),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Webhook')
                    ->description('Paste these into the Meta app: WhatsApp -> Configuration -> Webhook, then subscribe to "messages".')
                    ->schema([
                        Forms\Components\Placeholder::make('callback_url')
                            ->label('Callback URL')
                            ->content(fn () => WhatsAppCloud::webhookUrl()),
                        Forms\Components\Placeholder::make('verify_token')
                            ->label('Verify token')
                            ->content(fn () => WhatsAppCloud::verifyToken()),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Email alerts')
                    ->description('An email when a member writes to the WhatsApp number, so nobody has to keep the inbox open.')
                    ->schema([
                        Forms\Components\Toggle::make('wa_cloud_alert_enabled')
                            ->label('Email me about new WhatsApp messages')
                            ->helperText('At most one email per member every '.\App\Services\WhatsAppInboxService::ALERT_QUIET_MINUTES.' minutes, with a link that opens the chat.'),
                        Forms\Components\TextInput::make('wa_cloud_alert_email')
                            ->label('Send alerts to')
                            ->email()
                            ->placeholder(fn () => SiteSetting::getValue('contact_email', '') ?: (string) config('mail.from.address'))
                            ->helperText('Leave empty to use the contact email shown.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Sign-in codes')
                    ->description('Members sign in with a 6-digit code sent to their WhatsApp (Meta\'s fixed wording with a Copy code button; about Rs 0.14 each, charged when delivered). Codes go only to member accounts, at most one a minute and 5 an hour per number, 10 an hour per device, and the daily limit below for the whole site.')
                    ->schema([
                        Forms\Components\Toggle::make('wa_cloud_login_codes_enabled')
                            ->label('Send sign-in codes on WhatsApp')
                            ->helperText('The login page shows a "WhatsApp Code" tab when this is on AND Site Settings -> Mobile OTP Login is on. Also used for the app\'s phone codes.'),
                        Forms\Components\TextInput::make('otp_phone_daily_cap')
                            ->label('Most codes a day (whole site)')
                            ->numeric()->integer()->minValue(1)->maxValue(5000)
                            ->required()
                            ->helperText(fn () => 'Sent today: '.\App\Services\OtpService::sentToday().'. When the limit is reached, members are asked to use their password or email until tomorrow.'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Approved messages (photo requests, "please reply")')
                    ->description('Messages the office starts, sent through Meta. Meta charges for them and refuses them until a payment card is added in WhatsApp Manager -> Payment settings. Replies within 24 hours of a message from the member are free and work either way.')
                    ->schema([
                        Forms\Components\Toggle::make('wa_cloud_templates_enabled')
                            ->label('Send approved messages through the WhatsApp API')
                            ->helperText('Off: Members Without Photo opens WhatsApp on this phone or computer, as before. On: it sends the photo request through the API, and the inbox can ask members to reply.'),
                        Forms\Components\Select::make('wa_cloud_photo_template')
                            ->label('Message sent from Members Without Photo')
                            ->options([
                                WhatsAppCloud::TEMPLATE_PHOTO_MISSING => 'profile_photo_missing: account notice with an "Add photo" button (Utility, about Rs 0.12 each)',
                                WhatsAppCloud::TEMPLATE_PHOTO_REQUEST => 'photo_request: friendlier, with an "Add it myself" button (Marketing, about Rs 0.86 each)',
                            ])
                            ->required()
                            ->helperText('Both are approved in WhatsApp Manager, in English and Kannada.'),
                    ]),

                \Filament\Schemas\Components\Section::make('Automatic replies')
                    ->schema([
                        Forms\Components\Toggle::make('wa_cloud_auto_reply_enabled')
                            ->label('Reply automatically to messages')
                            ->helperText('At most once every '.WhatsAppInboxService::AUTO_REPLY_EVERY_HOURS.' hours per number, so a conversation is not flooded.'),
                        Forms\Components\Textarea::make('wa_cloud_auto_reply')
                            ->label('Automatic reply')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder(WhatsAppInboxService::defaultAutoReply())
                            ->helperText('Leave empty to use the text shown.'),
                        Forms\Components\Textarea::make('wa_cloud_photo_reply')
                            ->label('Reply when a photo is added')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder(WhatsAppInboxService::defaultPhotoReply())
                            ->helperText('{id} becomes the member ID. Leave empty to use the text shown.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('wa_cloud_enabled', ! empty($data['wa_cloud_enabled']) ? '1' : '0');
        SiteSetting::setValue('wa_cloud_auto_reply_enabled', ! empty($data['wa_cloud_auto_reply_enabled']) ? '1' : '0');
        SiteSetting::setValue('wa_cloud_alert_enabled', ! empty($data['wa_cloud_alert_enabled']) ? '1' : '0');
        SiteSetting::setValue('wa_cloud_templates_enabled', ! empty($data['wa_cloud_templates_enabled']) ? '1' : '0');
        SiteSetting::setValue('wa_cloud_login_codes_enabled', ! empty($data['wa_cloud_login_codes_enabled']) ? '1' : '0');
        SiteSetting::setValue('otp_phone_daily_cap', (string) max(1, (int) ($data['otp_phone_daily_cap'] ?? \App\Services\OtpService::PHONE_DAILY_CAP)));
        foreach (['wa_cloud_phone_number_id', 'wa_cloud_waba_id', 'wa_cloud_auto_reply', 'wa_cloud_photo_reply', 'wa_cloud_photo_template', 'wa_cloud_alert_email'] as $key) {
            SiteSetting::setValue($key, trim((string) ($data[$key] ?? '')));
        }
        foreach (WhatsAppCloud::SECRET_KEYS as $key) {
            if (trim((string) ($data[$key] ?? '')) !== '') {
                WhatsAppCloud::storeSecret($key, (string) $data[$key]);
            }
        }

        $this->data['wa_cloud_access_token'] = '';
        $this->data['wa_cloud_app_secret'] = '';

        $missing = collect([
            'phone number ID' => WhatsAppCloud::phoneNumberId(),
            'access token' => WhatsAppCloud::accessToken(),
            'app secret' => WhatsAppCloud::appSecret(),
        ])->filter(fn ($v) => $v === '')->keys();

        Notification::make()
            ->title('WhatsApp settings saved')
            ->body(! empty($data['wa_cloud_enabled']) && $missing->isNotEmpty() ? 'Still needed before it works: '.$missing->join(', ').'.' : null)
            ->{! empty($data['wa_cloud_enabled']) && $missing->isNotEmpty() ? 'warning' : 'success'}()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check')
                ->label('Check connection')
                ->color('gray')
                ->action(function (): void {
                    $s = WhatsAppCloud::status();
                    if (! $s['ok']) {
                        Notification::make()->title('Could not reach WhatsApp')->body($s['error'])->danger()->persistent()->send();

                        return;
                    }

                    $lines = [
                        "{$s['number']} ({$s['name']}): ".strtolower((string) $s['status']).', quality '.strtolower((string) $s['quality']).'.',
                        'Replies within 24 hours of a member\'s message: working.',
                        match ($s['can_start']) {
                            true => 'Messages we start (photo requests, "please reply"): allowed.',
                            false => 'Messages we start (photo requests, "please reply"): BLOCKED by Meta.',
                            default => 'Messages we start: Meta did not say.',
                        },
                        ...$s['problems'],
                    ];

                    Notification::make()
                        ->title($s['can_start'] === false ? 'Connected, with a problem' : 'Connected')
                        ->body(implode('<br><br>', array_map('e', $lines)))
                        ->{$s['can_start'] === false ? 'warning' : 'success'}()
                        ->persistent()
                        ->send();
                }),
            Action::make('test')
                ->label('Send test message')
                ->color('gray')
                ->modalDescription('Sends a short plain message (free). Meta only lets us write first with a paid approved message, so first send any WhatsApp message from that phone to the API number, then send the test within 24 hours.')
                ->form([
                    Forms\Components\TextInput::make('number')
                        ->label('WhatsApp number')
                        ->required()
                        ->placeholder('98450 12345'),
                ])
                ->action(function (array $data): void {
                    $to = WhatsApp::digits($data['number']);
                    if ($to === null) {
                        Notification::make()->title('That does not look like a phone number')->danger()->send();

                        return;
                    }
                    if (! WhatsAppCloud::inServiceWindow($to)) {
                        Notification::make()
                            ->title('Send a WhatsApp from that phone first')
                            ->body("+{$to} has not written to the API number in the last 24 hours, so Meta will not deliver a free message to it. Send any message (\"Hi\") from that phone, then try again.")
                            ->warning()->persistent()->send();

                        return;
                    }

                    $body = 'Test message from the '.SiteSetting::getValue('site_name', config('app.name')).' admin: the WhatsApp API is working.';
                    $result = WhatsAppCloud::sendText($to, $body);
                    WhatsAppMessage::create([
                        'direction' => WhatsAppMessage::OUT,
                        'wa_message_id' => $result['id'],
                        'wa_id' => $to,
                        'type' => 'text',
                        'body' => $body,
                        'status' => $result['ok'] ? 'sent' : 'failed',
                        'note' => $result['ok'] ? 'test' : 'test: '.$result['error'],
                        'sent_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title($result['ok'] ? "Test message sent to +{$to}" : 'Not sent')
                        ->body($result['ok'] ? null : $result['error'])
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->send();
                }),
        ];
    }
}

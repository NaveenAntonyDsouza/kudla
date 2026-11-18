<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * The office number on Meta's WhatsApp Cloud API (Oct 2026, Kudla first).
 *
 * Settings live in Settings -> WhatsApp API. The access token and app secret
 * are stored encrypted with the app key; the page never shows them again.
 * Nothing here runs until the API is switched on and fully configured.
 */
final class WhatsAppCloud
{
    public const GRAPH = 'https://graph.facebook.com/v25.0';

    public const SECRET_KEYS = ['wa_cloud_access_token', 'wa_cloud_app_secret'];

    public static function enabled(): bool
    {
        try {
            return SiteSetting::getValue('wa_cloud_enabled', '0') === '1'
                && self::phoneNumberId() !== ''
                && self::accessToken() !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public static function phoneNumberId(): string
    {
        return trim((string) SiteSetting::getValue('wa_cloud_phone_number_id', ''));
    }

    public static function verifyToken(): string
    {
        return (string) SiteSetting::getValue('wa_cloud_verify_token', '');
    }

    public static function accessToken(): string
    {
        return self::secret('wa_cloud_access_token');
    }

    public static function appSecret(): string
    {
        return self::secret('wa_cloud_app_secret');
    }

    public static function storeSecret(string $key, string $value): void
    {
        SiteSetting::setValue($key, Crypt::encryptString(trim($value)));
    }

    public static function hasSecret(string $key): bool
    {
        return self::secret($key) !== '';
    }

    private static function secret(string $key): string
    {
        $stored = (string) SiteSetting::getValue($key, '');
        if ($stored === '') {
            return '';
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return ''; // saved under a different APP_KEY: treat as not set
        }
    }

    /** Meta signs every webhook with the app secret (X-Hub-Signature-256). */
    public static function validSignature(string $payload, ?string $header): bool
    {
        $secret = self::appSecret();
        if ($secret === '' || ! $header || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $payload, $secret), $header);
    }

    /**
     * Send a plain text message. Meta only delivers free text within 24 hours
     * of the member's last message to us; outside that a template is needed.
     *
     * @return array{ok: bool, id: ?string, error: ?string}
     */
    public static function sendText(string $to, string $body): array
    {
        return self::post([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $body],
        ]);
    }

    /**
     * Send an approved template: the only kind of message Meta allows to a
     * number that has not written to us in the last 24 hours.
     *
     * @return array{ok: bool, id: ?string, error: ?string}
     */
    public static function sendTemplate(string $to, string $name, string $language = 'en_US', array $bodyParams = []): array
    {
        $template = ['name' => $name, 'language' => ['code' => $language]];
        if ($bodyParams) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], array_values($bodyParams)),
            ]];
        }

        return self::post([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => $template,
        ]);
    }

    /**
     * Templates approved in WhatsApp Manager (Oct 2026), in English and
     * Kannada. Body variables: {{1}} first name, then {{2}} member ID.
     */
    public const TEMPLATE_PHOTO_REQUEST = 'photo_request';          // Marketing (about Rs 0.86)
    public const TEMPLATE_PHOTO_MISSING = 'profile_photo_missing';  // Utility (about Rs 0.12), plain account notice
    public const TEMPLATE_REPLY_WAITING = 'reply_waiting';
    public const LANGUAGES = ['en' => 'English', 'kn' => 'Kannada'];

    /**
     * Whether staff may send approved templates (photo requests, "please
     * reply"). Meta charges for these and refuses them until a payment card
     * is added in WhatsApp Manager, so this is a separate switch, off by
     * default (Oct 2026: card planned for Nov). Replies inside the 24-hour
     * window are free and don't depend on it.
     */
    public static function templatesEnabled(): bool
    {
        try {
            return self::enabled() && SiteSetting::getValue('wa_cloud_templates_enabled', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Sign-in codes (Nov 2026): Meta's "authentication" template, fixed
     * wording "<code> is your verification code. For your security, do not
     * share this code. Expires in 10 minutes." + Copy code button, and a
     * 10-minute time to live so a late code is never delivered.
     */
    public const TEMPLATE_LOGIN_CODE = 'login_code';

    /** Send sign-in codes by WhatsApp (Settings -> WhatsApp API). Separate from templatesEnabled(). */
    public static function loginCodesEnabled(): bool
    {
        try {
            return self::enabled() && SiteSetting::getValue('wa_cloud_login_codes_enabled', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Send a one-time sign-in code. Meta wants the code twice: in the body
     * and as the Copy code button's parameter.
     *
     * @return array{ok: bool, id: ?string, error: ?string}
     */
    public static function sendLoginCode(string $to, string $code): array
    {
        return self::post([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => self::TEMPLATE_LOGIN_CODE,
                'language' => ['code' => 'en'],
                'components' => [
                    ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
                    ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $code]]],
                ],
            ],
        ]);
    }

    /** Which approved template asks members for a photo (Settings -> WhatsApp API). */
    public static function photoTemplate(): string
    {
        $chosen = (string) SiteSetting::getValue('wa_cloud_photo_template', self::TEMPLATE_PHOTO_MISSING);

        return in_array($chosen, [self::TEMPLATE_PHOTO_REQUEST, self::TEMPLATE_PHOTO_MISSING], true) ? $chosen : self::TEMPLATE_PHOTO_MISSING;
    }

    /** Kannada for members whose mother tongue is Kannada, English otherwise. */
    public static function languageFor(?\App\Models\Profile $profile): string
    {
        return strcasecmp(trim((string) $profile?->mother_tongue), 'Kannada') === 0 ? 'kn' : 'en';
    }

    /** First word of the name, for "Hi {{1}}". */
    public static function firstName(?string $fullName): string
    {
        return trim((string) strtok(trim((string) $fullName), ' ')) ?: 'there';
    }

    /** @return array{ok: bool, id: ?string, error: ?string} */
    private static function post(array $payload): array
    {
        if (! self::enabled()) {
            return ['ok' => false, 'id' => null, 'error' => 'WhatsApp API is not switched on'];
        }

        try {
            $response = Http::withToken(self::accessToken())->timeout(15)
                ->post(self::GRAPH.'/'.self::phoneNumberId().'/messages', $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'id' => null, 'error' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'id' => null, 'error' => (string) ($response->json('error.message') ?? 'HTTP '.$response->status())];
        }

        return ['ok' => true, 'id' => $response->json('messages.0.id'), 'error' => null];
    }

    /**
     * Download a media file the member sent: Meta first gives a short-lived
     * URL for the media id, which also needs the token.
     *
     * @return array{body: string, mime: string}|null
     */
    public static function downloadMedia(string $mediaId): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $meta = Http::withToken(self::accessToken())->timeout(15)->get(self::GRAPH.'/'.$mediaId);
        $url = $meta->successful() ? $meta->json('url') : null;
        if (! $url) {
            return null;
        }

        $file = Http::withToken(self::accessToken())->timeout(30)->get($url);
        if (! $file->successful() || $file->body() === '') {
            return null;
        }

        return ['body' => $file->body(), 'mime' => (string) ($meta->json('mime_type') ?? $file->header('Content-Type'))];
    }

    /**
     * Active members whose WhatsApp, phone or login number ends with the same
     * 10 digits as the sender. Families often share one number, so callers
     * must handle more than one match.
     *
     * @return Collection<int, Profile>
     */
    public static function profilesForNumber(string $waId): Collection
    {
        $last10 = substr(preg_replace('/\D/', '', $waId), -10);
        if (strlen($last10) < 10) {
            return collect();
        }

        $normalized = fn (string $column) => "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') LIKE ?";
        $like = '%'.$last10;

        return Profile::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->whereNull('staff_role_id'))
            ->where(function ($q) use ($normalized, $like) {
                $q->whereHas('contactInfo', fn ($c) => $c->whereRaw($normalized('whatsapp_number'), [$like])
                    ->orWhereRaw($normalized('primary_phone'), [$like]))
                    ->orWhereHas('user', fn ($u) => $u->whereRaw($normalized('phone'), [$like]));
            })
            ->limit(5)
            ->get();
    }

    /**
     * What Meta says about the number and the account (Settings -> WhatsApp
     * API -> Check connection). `can_start` is false when Meta refuses the
     * messages we start, e.g. no working payment card; null if unknown.
     *
     * @return array{ok: bool, error: ?string, number: ?string, name: ?string, status: ?string, quality: ?string, can_start: ?bool, problems: list<string>}
     */
    public static function status(): array
    {
        $result = ['ok' => false, 'error' => null, 'number' => null, 'name' => null, 'status' => null, 'quality' => null, 'can_start' => null, 'problems' => []];
        if (! self::enabled()) {
            return ['error' => 'WhatsApp API is not switched on, or the phone number ID or access token is missing.'] + $result;
        }

        try {
            $phone = Http::withToken(self::accessToken())->timeout(15)->get(self::GRAPH.'/'.self::phoneNumberId(), [
                'fields' => 'display_phone_number,verified_name,status,quality_rating,health_status',
            ]);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()] + $result;
        }
        if (! $phone->successful()) {
            return ['error' => (string) ($phone->json('error.message') ?? 'HTTP '.$phone->status())] + $result;
        }

        $health = (array) $phone->json('health_status', []);
        // Only what stops messages; Meta also lists unrelated ones (e.g. SIP calling not set up).
        $problems = collect($health['entities'] ?? [])
            ->filter(fn ($e) => ($e['can_send_message'] ?? 'AVAILABLE') !== 'AVAILABLE')
            ->flatMap(fn ($e) => collect($e['errors'] ?? [])->map(fn ($err) => trim(($err['error_description'] ?? '').' '.($err['possible_solution'] ?? ''))))
            ->filter()->unique()->values()->all();

        return [
            'ok' => true,
            'error' => null,
            'number' => $phone->json('display_phone_number'),
            'name' => $phone->json('verified_name'),
            'status' => $phone->json('status'),
            'quality' => $phone->json('quality_rating'),
            'can_start' => isset($health['can_send_message']) ? $health['can_send_message'] !== 'BLOCKED' : null,
            'problems' => $problems,
        ];
    }

    /** The webhook address to paste into Meta (App -> WhatsApp -> Configuration). */
    public static function webhookUrl(): string
    {
        return url('/api/v1/webhooks/whatsapp');
    }

    /** Has this number written to us in the last 24 hours (Meta's free-reply window)? */
    public static function inServiceWindow(string $waId): bool
    {
        return DB::table('whatsapp_messages')->where('wa_id', $waId)->where('direction', 'in')
            ->where('created_at', '>=', now()->subDay())->exists();
    }
}

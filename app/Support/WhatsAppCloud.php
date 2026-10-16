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
    public static function sendTemplate(string $to, string $name, string $language = 'en_US'): array
    {
        return self::post([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => ['name' => $name, 'language' => ['code' => $language]],
        ]);
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

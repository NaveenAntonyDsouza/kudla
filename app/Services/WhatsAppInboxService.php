<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppCloud;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * What happens to a message that reaches the office number (Oct 2026).
 *
 * A photo from a number that belongs to exactly one member is added to that
 * member's profile as a pending photo, for staff to approve as usual, and the
 * member gets a thank-you. Anything else gets the auto-reply (at most once
 * every 12 hours per number) and waits in the inbox for staff.
 */
class WhatsAppInboxService
{
    public const AUTO_REPLY_EVERY_HOURS = 12;
    public const PHOTO_REPLY_EVERY_MINUTES = 10;

    public function __construct(
        private ImageProcessingService $images,
        private PhotoStorageService $storage,
    ) {}

    public static function defaultAutoReply(): string
    {
        $site = SiteSetting::getValue('site_name', 'our office');

        return "Thank you for your message to {$site}. Our team will reply soon. To add a photo to your profile, just send the photo here.";
    }

    public static function defaultPhotoReply(): string
    {
        return 'Thank you, we have received your photo. It will appear on your profile ({id}) once our team has checked it.';
    }

    public function process(WhatsAppMessage $message): void
    {
        $profiles = WhatsAppCloud::profilesForNumber($message->wa_id);
        if ($profiles->count() === 1) {
            $message->profile_id = $profiles->first()->id;
        }

        $isImage = $message->type === 'image'
            || ($message->type === 'document' && str_starts_with((string) $message->media_mime, 'image/'));

        if ($isImage && $message->media_id) {
            if ($profiles->count() === 1) {
                $this->attachPhoto($message, $profiles->first());
            } else {
                $message->note = $profiles->isEmpty()
                    ? 'Photo from a number that is not on any member profile. Find the member and upload it by hand.'
                    : 'Photo from a number shared by '.$profiles->count().' members ('.$profiles->pluck('matri_id')->join(', ').'). Upload it to the right one by hand.';
                $message->save();
                $this->autoReply($message);
            }

            return;
        }

        $message->save();
        $this->autoReply($message);
    }

    private function attachPhoto(WhatsAppMessage $message, Profile $profile): void
    {
        try {
            $photo = $this->addPhoto($message, $profile);
            $message->fill([
                'status' => 'attached',
                'profile_photo_id' => $photo->id,
                'note' => "Added to {$profile->matri_id} as a {$photo->photo_type} photo, waiting for approval in Photo Approval.",
            ])->save();

            $this->photoReply($message, $profile);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp photo not attached', ['message' => $message->id, 'error' => $e->getMessage()]);
            $message->fill(['note' => "Photo not added automatically: {$e->getMessage()}. Upload it by hand."])->save();
            $this->autoReply($message);
        }
    }

    /**
     * Staff add a photo from the inbox to a member they chose. They have seen
     * it, so it goes live straight away (main photo if the member has none).
     */
    public function attachByStaff(WhatsAppMessage $message, Profile $profile, int $staffId): ProfilePhoto
    {
        $photo = $this->addPhoto($message, $profile, $staffId);

        if ($photo->photo_type === 'profile' && ! $profile->profilePhotos()->where('id', '!=', $photo->id)
            ->where('is_primary', true)->where('is_visible', true)->approved()->exists()) {
            $profile->profilePhotos()->where('id', '!=', $photo->id)->update(['is_primary' => false]);
            $photo->update(['is_primary' => true]);
        }

        $message->fill([
            'profile_id' => $message->profile_id ?? $profile->id,
            'status' => 'attached',
            'profile_photo_id' => $photo->id,
            'note' => "Added to {$profile->matri_id} as a {$photo->photo_type} photo by staff.",
        ])->save();

        return $photo;
    }

    /**
     * The photo file a member sent, kept on the server after the first
     * download (Meta's media links expire). Null when it cannot be fetched.
     *
     * @return array{body: string, mime: string}|null
     */
    public static function media(WhatsAppMessage $message): ?array
    {
        if (! $message->media_id) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = "whatsapp-media/{$message->id}";
        if ($disk->exists($path)) {
            return ['body' => $disk->get($path), 'mime' => $message->media_mime ?: 'image/jpeg'];
        }

        $media = WhatsAppCloud::downloadMedia($message->media_id);
        if ($media) {
            $disk->put($path, $media['body']);
            if (! $message->media_mime && $media['mime']) {
                $message->forceFill(['media_mime' => mb_substr($media['mime'], 0, 100)])->save();
            }
        }

        return $media;
    }

    private function addPhoto(WhatsAppMessage $message, Profile $profile, ?int $staffId = null): ProfilePhoto
    {
        $tmp = null;
        try {
            $media = self::media($message);
            if (! $media) {
                throw new \RuntimeException('could not download the photo from WhatsApp');
            }

            $ext = match (strtolower(explode(';', $media['mime'])[0])) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $tmp = tempnam(sys_get_temp_dir(), 'wa').'.'.$ext;
            file_put_contents($tmp, $media['body']);
            if (@getimagesize($tmp) === false) {
                throw new \RuntimeException('the file is not an image');
            }

            $type = $this->photoType($profile);
            if ($type === null) {
                throw new \RuntimeException('the member already has the most photos allowed');
            }

            $driver = $this->storage->getActiveDriver();
            if (! $this->storage->isDriverConfigured($driver)) {
                $driver = PhotoStorageService::DRIVER_LOCAL;
            }
            $paths = $this->images->processUpload(new UploadedFile($tmp, "whatsapp.{$ext}", null, null, true), "photos/{$profile->id}", $driver);

            return ProfilePhoto::create([
                'profile_id' => $profile->id,
                'photo_type' => $type,
                'photo_url' => $paths['full'],
                'thumbnail_url' => $paths['thumb'],
                'medium_url' => $paths['medium'],
                'original_url' => $paths['original'],
                'storage_driver' => $paths['driver'],
                'is_primary' => false,
                'is_visible' => true,
                'display_order' => (int) $profile->profilePhotos()->ofType($type)->max('display_order') + 1,
                // Automatic: anyone can send from a number, so staff check it. By staff: already checked.
                'approval_status' => $staffId ? ProfilePhoto::STATUS_APPROVED : ProfilePhoto::STATUS_PENDING,
                'approved_by' => $staffId,
                'approved_at' => $staffId ? now() : null,
            ]);
        } finally {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /** Main photo when they have none (approved or waiting), else the album while there is room. */
    private function photoType(Profile $profile): ?string
    {
        $hasMain = $profile->profilePhotos()->ofType('profile')->where('is_visible', true)
            ->whereIn('approval_status', [ProfilePhoto::STATUS_APPROVED, ProfilePhoto::STATUS_PENDING])->exists();
        if (! $hasMain) {
            return 'profile';
        }

        $album = $profile->profilePhotos()->ofType('album')->where('is_visible', true)
            ->whereIn('approval_status', [ProfilePhoto::STATUS_APPROVED, ProfilePhoto::STATUS_PENDING])->count();

        return $album < ProfilePhoto::maxForType('album') ? 'album' : null;
    }

    private function photoReply(WhatsAppMessage $message, Profile $profile): void
    {
        $text = trim((string) SiteSetting::getValue('wa_cloud_photo_reply', '')) ?: self::defaultPhotoReply();
        $this->sendOnce($message->wa_id, str_replace('{id}', (string) $profile->matri_id, $text), 'photo-reply', now()->subMinutes(self::PHOTO_REPLY_EVERY_MINUTES));
    }

    private function autoReply(WhatsAppMessage $message): void
    {
        if (SiteSetting::getValue('wa_cloud_auto_reply_enabled', '1') !== '1') {
            return;
        }
        $text = trim((string) SiteSetting::getValue('wa_cloud_auto_reply', '')) ?: self::defaultAutoReply();
        $this->sendOnce($message->wa_id, $text, 'auto-reply', now()->subHours(self::AUTO_REPLY_EVERY_HOURS));
    }

    /** Send an automatic reply unless the same kind went to this number recently. */
    private function sendOnce(string $waId, string $text, string $kind, \DateTimeInterface $since): void
    {
        $recent = WhatsAppMessage::where('wa_id', $waId)->where('direction', WhatsAppMessage::OUT)
            ->where('note', $kind)->where('created_at', '>=', $since)->exists();
        if ($recent) {
            return;
        }

        $this->send($waId, $text, null, $kind);
    }

    /**
     * The approved template wording, kept here only so the inbox can show
     * what the member received. Meta holds the real copy.
     */
    public const TEMPLATE_TEXT = [
        WhatsAppCloud::TEMPLATE_PHOTO_REQUEST => [
            'en' => "Hi {{1}}, this is the Kudla Matrimony team. Your profile {{2}} does not have a photo yet, and profiles with a photo get many more responses from families.\n\nJust reply to this message with one clear, recent photo of yourself and we will add it to your profile for you.",
            'kn' => "ನಮಸ್ಕಾರ {{1}}, ನಾವು ಕುಡ್ಲ ಮ್ಯಾಟ್ರಿಮೋನಿ ತಂಡ. ನಿಮ್ಮ ಪ್ರೊಫೈಲ್ {{2}} ನಲ್ಲಿ ಇನ್ನೂ ಫೋಟೋ ಇಲ್ಲ. ಫೋಟೋ ಇರುವ ಪ್ರೊಫೈಲ್‌ಗಳಿಗೆ ಕುಟುಂಬಗಳಿಂದ ಹೆಚ್ಚು ಪ್ರತಿಕ್ರಿಯೆಗಳು ಬರುತ್ತವೆ.\n\nನಿಮ್ಮ ಒಂದು ಸ್ಪಷ್ಟವಾದ, ಇತ್ತೀಚಿನ ಫೋಟೋವನ್ನು ಈ ಸಂದೇಶಕ್ಕೆ ಉತ್ತರವಾಗಿ ಕಳುಹಿಸಿ. ನಾವೇ ಅದನ್ನು ನಿಮ್ಮ ಪ್ರೊಫೈಲ್‌ಗೆ ಸೇರಿಸುತ್ತೇವೆ.",
        ],
        WhatsAppCloud::TEMPLATE_PHOTO_MISSING => [
            'en' => 'Hi {{1}}, your Kudla Matrimony profile {{2}} has no profile photo. To add one, reply to this message with a clear, recent photo of yourself and the Kudla Matrimony team will add it to your profile.',
            'kn' => 'ನಮಸ್ಕಾರ {{1}}, ನಿಮ್ಮ ಕುಡ್ಲ ಮ್ಯಾಟ್ರಿಮೋನಿ ಪ್ರೊಫೈಲ್ {{2}} ನಲ್ಲಿ ಪ್ರೊಫೈಲ್ ಫೋಟೋ ಇಲ್ಲ. ಫೋಟೋ ಸೇರಿಸಲು, ನಿಮ್ಮ ಒಂದು ಸ್ಪಷ್ಟವಾದ, ಇತ್ತೀಚಿನ ಫೋಟೋವನ್ನು ಈ ಸಂದೇಶಕ್ಕೆ ಉತ್ತರವಾಗಿ ಕಳುಹಿಸಿ. ಕುಡ್ಲ ಮ್ಯಾಟ್ರಿಮೋನಿ ತಂಡ ಅದನ್ನು ನಿಮ್ಮ ಪ್ರೊಫೈಲ್‌ಗೆ ಸೇರಿಸುತ್ತದೆ.',
        ],
        WhatsAppCloud::TEMPLATE_REPLY_WAITING => [
            'en' => 'Hi {{1}}, the Kudla Matrimony team has a reply to your message. Please reply to this message (any text is fine) and we will answer you here straight away.',
            'kn' => 'ನಮಸ್ಕಾರ {{1}}, ನಿಮ್ಮ ಸಂದೇಶಕ್ಕೆ ಕುಡ್ಲ ಮ್ಯಾಟ್ರಿಮೋನಿ ತಂಡದ ಉತ್ತರ ಸಿದ್ಧವಿದೆ. ದಯವಿಟ್ಟು ಈ ಸಂದೇಶಕ್ಕೆ ಉತ್ತರಿಸಿ (ಯಾವುದೇ ಪದ ಸಾಕು), ನಾವು ತಕ್ಷಣ ಇಲ್ಲಿಯೇ ಉತ್ತರಿಸುತ್ತೇವೆ.',
        ],
    ];

    /**
     * Send an approved template (the only message Meta allows outside the
     * 24-hour window) and keep a readable copy in the inbox.
     */
    public function sendTemplate(string $waId, string $template, string $language, array $params, ?int $staffId = null, ?int $profileId = null): WhatsAppMessage
    {
        $language = array_key_exists($language, WhatsAppCloud::LANGUAGES) ? $language : 'en';
        $result = WhatsAppCloud::sendTemplate($waId, $template, $language, $params);

        $body = self::TEMPLATE_TEXT[$template][$language] ?? $template;
        foreach (array_values($params) as $i => $value) {
            $body = str_replace('{{'.($i + 1).'}}', (string) $value, $body);
        }

        return WhatsAppMessage::create([
            'direction' => WhatsAppMessage::OUT,
            'wa_message_id' => $result['id'],
            'wa_id' => $waId,
            'profile_id' => $profileId ?? WhatsAppMessage::where('wa_id', $waId)->whereNotNull('profile_id')->latest('id')->value('profile_id'),
            'type' => 'template',
            'body' => $body,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'note' => $result['ok'] ? "template:{$template}" : "template:{$template}: ".(string) $result['error'],
            'sent_by' => $staffId,
        ]);
    }

    /** Send a text and keep a copy in the inbox. */
    public function send(string $waId, string $text, ?int $staffId = null, ?string $kind = null): WhatsAppMessage
    {
        $result = WhatsAppCloud::sendText($waId, $text);

        return WhatsAppMessage::create([
            'direction' => WhatsAppMessage::OUT,
            'wa_message_id' => $result['id'],
            'wa_id' => $waId,
            'profile_id' => WhatsAppMessage::where('wa_id', $waId)->whereNotNull('profile_id')->latest('id')->value('profile_id'),
            'type' => 'text',
            'body' => $text,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'note' => $result['ok'] ? $kind : trim(($kind ? "{$kind}: " : '').(string) $result['error']),
            'sent_by' => $staffId,
        ]);
    }
}

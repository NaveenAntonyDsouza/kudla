<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\SiteSetting;
use App\Services\InterestService;
use App\Services\ProfileAccessService;

/**
 * Can this member see that member's phone / WhatsApp, and if not, why not —
 * one answer for the profile sidebar, Contact tab, phone action bar, chat
 * page and Chats list (Oct 2026).
 *
 * The rule itself is ProfileAccessService::canViewContact (same as the app):
 * an accepted interest + a paid plan (everyone on free-membership sites), or
 * a target whose plan opens contact to all. Numbers are only ever returned
 * in the OPEN / OWN states, so a locked page never carries them.
 */
final class ContactAccess
{
    public const OWN = 'own';
    public const OPEN = 'open';
    public const NO_INTEREST = 'no_interest';        // send an interest first
    public const SENT_PENDING = 'sent_pending';      // waiting for them to accept
    public const RECEIVED_PENDING = 'received_pending'; // they're interested: accept to connect
    public const DECLINED = 'declined';
    public const NEEDS_PLAN = 'needs_plan';          // accepted, but the viewer's plan doesn't include contacts
    public const UNAVAILABLE = 'unavailable';        // blocked, hidden, same gender…

    /** @return array{state: string, contact: ?array} */
    public static function for(?Profile $viewer, ?Profile $target): array
    {
        if (! $viewer || ! $target) {
            return ['state' => self::UNAVAILABLE, 'contact' => null];
        }

        $key = 'contact_access.'.$viewer->id.'.'.$target->id;
        if (request()->attributes->has($key)) {
            return request()->attributes->get($key);
        }

        $result = ['state' => self::resolveState($viewer, $target), 'contact' => null];
        if (in_array($result['state'], [self::OPEN, self::OWN], true)) {
            $result['contact'] = self::details($viewer, $target);
        }

        request()->attributes->set($key, $result);

        return $result;
    }

    private static function resolveState(Profile $viewer, Profile $target): string
    {
        if ($viewer->id === $target->id) {
            return self::OWN;
        }

        try {
            $access = app(ProfileAccessService::class);
            if ($access->canViewContact($viewer, $target)) {
                return self::OPEN;
            }
            if (! $access->canAccess($viewer, $target)) {
                return self::UNAVAILABLE;
            }

            $status = app(InterestService::class)->getStatus($viewer, $target);
        } catch (\Throwable) {
            return self::UNAVAILABLE;
        }

        return match (true) {
            $status === null => self::NO_INTEREST,
            $status['status'] === 'accepted' => self::NEEDS_PLAN,
            $status['status'] === 'declined' => self::DECLINED,
            $status['direction'] === 'sent' => self::SENT_PENDING,
            default => self::RECEIVED_PENDING,
        };
    }

    /**
     * Phone + WhatsApp as ready-to-tap links. WhatsApp falls back to the
     * mobile number when no separate WhatsApp number was given (most Indian
     * mobiles are on WhatsApp; WhatsApp itself says so if one isn't).
     */
    private static function details(Profile $viewer, Profile $target): array
    {
        $c = $target->contactInfo;
        $phoneRaw = $c?->primary_phone ?: $target->user?->phone;
        // (string): digits(null) means "the site's own number" — never that here
        $phone = WhatsApp::digits((string) $phoneRaw);
        $whatsappOwn = WhatsApp::digits((string) $c?->whatsapp_number);
        $whatsapp = $whatsappOwn ?: $phone;

        $site = SiteSetting::getValue('site_name', config('app.name'));
        $message = "Hello, I'm {$viewer->matri_id} from {$site}. We have accepted each other's interest (your profile {$target->matri_id}). Is this a good time to talk?";

        return [
            'person' => $c?->contact_person,
            'relationship' => $c?->contact_relationship,
            'best_time' => $c?->preferred_call_time,
            'email' => $c?->email,
            'phone_display' => $phone ? self::pretty($phone) : ($phoneRaw ?: null),
            'tel' => $phone ? 'tel:+'.$phone : null,
            'whatsapp_display' => $whatsapp ? self::pretty($whatsapp) : null,
            'whatsapp_is_phone' => ! $whatsappOwn && $whatsapp !== null,
            'whatsapp_link' => $whatsapp ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message) : null,
        ];
    }

    /** 919876543210 → "+91 98765 43210"; other countries: "+<digits>". */
    public static function pretty(string $digits): string
    {
        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+91 '.substr($digits, 2, 5).' '.substr($digits, 7);
        }

        return '+'.$digits;
    }

    /** Short sentence for a locked state (Contact tab, sidebar hint). */
    public static function lockedReason(string $state): string
    {
        return match ($state) {
            self::NO_INTEREST => 'Their phone and WhatsApp open as soon as they accept your interest.',
            self::SENT_PENDING => 'Your interest is waiting for a reply. Their phone and WhatsApp open as soon as they accept — we\'ll let you know.',
            self::RECEIVED_PENDING => 'They\'re interested in you. Accept their interest and you can both see each other\'s phone and WhatsApp.',
            self::DECLINED => 'Contact details aren\'t available for this profile.',
            self::NEEDS_PLAN => 'Your interest is accepted. Upgrade to a paid plan to see their phone and WhatsApp.',
            default => 'Contact details aren\'t available for this profile.',
        };
    }
}

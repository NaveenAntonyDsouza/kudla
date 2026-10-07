<?php

namespace App\Support;

use App\Models\Interest;
use App\Models\Profile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Chats tab (owner's choice, Oct 2026): conversations are still interests —
 * a chat opens only once an interest is accepted — but they get their own
 * list and menu item, apart from the requests in Interests.
 */
class Chats
{
    /** Replies the other side sends that count as "new" in a chat. */
    private const UNREAD_TYPES = ['message', 'accept'];

    private const ACCEPT_TEXTS = [
        'reply_accept_1' => 'Thank you for your interest. We are also interested in your profile and would like to proceed.',
        'reply_accept_2' => 'I am happy to accept your interest. Please feel free to contact me.',
    ];

    /** Accepted conversations, newest activity first, each with unread_count. */
    public static function forProfile(Profile $me, int $perPage = 20): LengthAwarePaginator
    {
        return Interest::query()
            ->select('interests.*')
            ->selectRaw('COALESCE((select max(r.created_at) from interest_replies r where r.interest_id = interests.id), interests.updated_at) as last_activity_at')
            ->selectRaw(
                "(select count(*) from interest_replies r where r.interest_id = interests.id and r.replier_profile_id <> ? and r.reply_type in ('message', 'accept')"
                ." and r.created_at > COALESCE(CASE WHEN interests.sender_profile_id = ? THEN interests.sender_last_read_at ELSE interests.receiver_last_read_at END, '1970-01-01 00:00:00')) as unread_count",
                [$me->id, $me->id]
            )
            ->where('status', 'accepted')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('sender_profile_id', $me->id)->where('is_trashed_by_sender', false))
                ->orWhere(fn ($q) => $q->where('receiver_profile_id', $me->id)->where('is_trashed_by_receiver', false)))
            ->with([
                'senderProfile.primaryPhoto', 'receiverProfile.primaryPhoto', 'latestReply',
                // the Chats list offers WhatsApp / Call where contact is open
                'senderProfile.contactInfo', 'receiverProfile.contactInfo', 'senderProfile.user', 'receiverProfile.user',
            ])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('interests.id')
            ->paginate($perPage);
    }

    /** How many conversations have something this member hasn't read (menu badge). */
    public static function unreadCount(Profile $me): int
    {
        return (int) self::cached('chats_unread.'.$me->id, fn () => DB::table('interest_replies as r')
            ->join('interests as i', 'i.id', '=', 'r.interest_id')
            ->where('i.status', 'accepted')
            ->where('r.replier_profile_id', '<>', $me->id)
            ->whereIn('r.reply_type', self::UNREAD_TYPES)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('i.sender_profile_id', $me->id)->where('i.is_trashed_by_sender', false)
                    ->whereRaw("r.created_at > COALESCE(i.sender_last_read_at, '1970-01-01 00:00:00')"))
                ->orWhere(fn ($q) => $q->where('i.receiver_profile_id', $me->id)->where('i.is_trashed_by_receiver', false)
                    ->whereRaw("r.created_at > COALESCE(i.receiver_last_read_at, '1970-01-01 00:00:00')")))
            ->distinct()
            ->count('r.interest_id'));
    }

    /** Interests waiting for this member's reply (menu badge) — same count as the dashboard. */
    public static function pendingInterestsCount(Profile $me): int
    {
        return (int) self::cached('interests_pending.'.$me->id, fn () => Interest::where('receiver_profile_id', $me->id)
            ->where('status', 'pending')
            ->count());
    }

    /** One line for the Chats list: what was last said, and by whom. */
    public static function preview(Interest $chat, Profile $me, bool $withYouPrefix = true): string
    {
        $reply = $chat->latestReply;
        $text = match ($reply?->reply_type) {
            'message' => (string) $reply->custom_message,
            'accept' => $reply->custom_message ?: (self::ACCEPT_TEXTS[$reply->template_id] ?? 'Interest accepted'),
            default => 'Interest accepted — say hello',
        };
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($withYouPrefix && $reply && $reply->replier_profile_id === $me->id) {
            $text = 'You: '.$text;
        }

        return Str::limit($text, 90);
    }

    /** Per request, and never breaks a page (e.g. a test schema without these tables). */
    private static function cached(string $key, \Closure $count): int
    {
        if (request()->attributes->has($key)) {
            return request()->attributes->get($key);
        }
        try {
            $value = (int) $count();
        } catch (\Throwable) {
            $value = 0;
        }
        request()->attributes->set($key, $value);

        return $value;
    }
}

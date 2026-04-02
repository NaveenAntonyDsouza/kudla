<?php

namespace App\Services;

use App\Models\DailyInterestUsage;
use App\Models\Interest;
use App\Models\InterestReply;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

class InterestService
{
    const DAILY_LIMIT = 5;

    /**
     * Send an interest from one profile to another.
     */
    public function send(Profile $sender, Profile $receiver, ?string $templateId, ?string $customMessage): Interest
    {
        // Validation checks
        if ($sender->id === $receiver->id) {
            throw new \InvalidArgumentException('Cannot send interest to yourself.');
        }

        if ($sender->gender === $receiver->gender) {
            throw new \InvalidArgumentException('Cannot send interest to the same gender.');
        }

        // Check daily limit
        $usage = $this->canSendToday($sender);
        if (! $usage['can_send']) {
            throw new \RuntimeException("Daily interest limit reached ({$usage['limit']}/day). Try again tomorrow.");
        }

        // Check for existing active interest between these two profiles
        $existing = Interest::where('sender_profile_id', $sender->id)
            ->where('receiver_profile_id', $receiver->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existing) {
            throw new \RuntimeException('You already have an active interest with this profile.');
        }

        // Check if declined interest exists and 30 days have passed
        $declined = Interest::where('sender_profile_id', $sender->id)
            ->where('receiver_profile_id', $receiver->id)
            ->where('status', 'declined')
            ->latest()
            ->first();

        if ($declined && $declined->updated_at->diffInDays(now()) < 30) {
            $daysLeft = 30 - $declined->updated_at->diffInDays(now());
            throw new \RuntimeException("You can re-send interest after {$daysLeft} days.");
        }

        // Also check reverse direction — if receiver already sent to sender
        $reverseActive = Interest::where('sender_profile_id', $receiver->id)
            ->where('receiver_profile_id', $sender->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($reverseActive) {
            throw new \RuntimeException('This person has already sent you an interest. Check your received interests.');
        }

        return DB::transaction(function () use ($sender, $receiver, $templateId, $customMessage, $declined) {
            // Remove old declined/cancelled/expired records to satisfy unique constraint
            Interest::where('sender_profile_id', $sender->id)
                ->where('receiver_profile_id', $receiver->id)
                ->whereIn('status', ['declined', 'cancelled', 'expired'])
                ->delete();

            // Create the interest
            $interest = Interest::create([
                'sender_profile_id' => $sender->id,
                'receiver_profile_id' => $receiver->id,
                'template_id' => $templateId,
                'custom_message' => $customMessage,
                'status' => 'pending',
            ]);

            // Increment daily usage
            $usage = DailyInterestUsage::firstOrCreate(
                ['profile_id' => $sender->id, 'usage_date' => today()],
                ['count' => 0]
            );
            $usage->increment('count');

            return $interest;
        });
    }

    /**
     * Accept an interest and create a reply.
     */
    public function accept(Interest $interest, ?string $templateId, ?string $customMessage): InterestReply
    {
        if ($interest->status !== 'pending') {
            throw new \RuntimeException('This interest is no longer pending.');
        }

        return DB::transaction(function () use ($interest, $templateId, $customMessage) {
            $interest->update(['status' => 'accepted']);

            return InterestReply::create([
                'interest_id' => $interest->id,
                'replier_profile_id' => $interest->receiver_profile_id,
                'reply_type' => 'accept',
                'template_id' => $templateId,
                'custom_message' => $customMessage,
            ]);
        });
    }

    /**
     * Decline an interest and create a reply.
     */
    public function decline(Interest $interest, ?string $templateId, ?string $customMessage, bool $silent = false): InterestReply
    {
        if ($interest->status !== 'pending') {
            throw new \RuntimeException('This interest is no longer pending.');
        }

        return DB::transaction(function () use ($interest, $templateId, $customMessage, $silent) {
            $interest->update(['status' => 'declined']);

            return InterestReply::create([
                'interest_id' => $interest->id,
                'replier_profile_id' => $interest->receiver_profile_id,
                'reply_type' => 'decline',
                'template_id' => $templateId,
                'custom_message' => $silent ? null : $customMessage,
                'is_silent_decline' => $silent,
            ]);
        });
    }

    /**
     * Send a chat message in an accepted interest thread.
     */
    public function sendMessage(Interest $interest, Profile $sender, string $message): InterestReply
    {
        if ($interest->status !== 'accepted') {
            throw new \RuntimeException('Messages can only be sent in accepted interests.');
        }

        // Verify sender is part of this interest
        if ($interest->sender_profile_id !== $sender->id && $interest->receiver_profile_id !== $sender->id) {
            throw new \RuntimeException('You are not part of this conversation.');
        }

        return InterestReply::create([
            'interest_id' => $interest->id,
            'replier_profile_id' => $sender->id,
            'reply_type' => 'message',
            'custom_message' => $message,
        ]);
    }

    /**
     * Cancel a previously sent interest.
     */
    public function cancel(Interest $interest): void
    {
        if ($interest->status !== 'pending') {
            throw new \RuntimeException('Only pending interests can be cancelled.');
        }

        $interest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Check if the profile can send interests today.
     *
     * @return array{can_send: bool, remaining: int, limit: int}
     */
    public function canSendToday(Profile $profile): array
    {
        $usage = DailyInterestUsage::where('profile_id', $profile->id)
            ->where('usage_date', today())
            ->first();

        $count = $usage?->count ?? 0;
        $remaining = max(0, self::DAILY_LIMIT - $count);

        return [
            'can_send' => $remaining > 0,
            'remaining' => $remaining,
            'limit' => self::DAILY_LIMIT,
        ];
    }

    /**
     * Get the interest status between two profiles (for UI display).
     */
    public function getStatus(Profile $viewer, Profile $other): ?array
    {
        // Check if viewer sent to other
        $sent = Interest::where('sender_profile_id', $viewer->id)
            ->where('receiver_profile_id', $other->id)
            ->whereIn('status', ['pending', 'accepted', 'declined'])
            ->latest()
            ->first();

        if ($sent) {
            return ['direction' => 'sent', 'status' => $sent->status, 'interest' => $sent];
        }

        // Check if other sent to viewer
        $received = Interest::where('sender_profile_id', $other->id)
            ->where('receiver_profile_id', $viewer->id)
            ->whereIn('status', ['pending', 'accepted', 'declined'])
            ->latest()
            ->first();

        if ($received) {
            return ['direction' => 'received', 'status' => $received->status, 'interest' => $received];
        }

        return null; // No interaction
    }
}

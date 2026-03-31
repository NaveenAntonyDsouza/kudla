<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\InterestReply;
use App\Models\Profile;

class InterestService
{
    /**
     * Send an interest from one profile to another.
     */
    public function send(Profile $sender, Profile $receiver, string $templateId, ?string $customMessage): Interest
    {
        // TODO: Implement in Phase 4
        throw new \RuntimeException('InterestService::send() not yet implemented.');
    }

    /**
     * Accept an interest and create a reply.
     */
    public function accept(Interest $interest, string $templateId, ?string $customMessage): InterestReply
    {
        // TODO: Implement in Phase 4
        throw new \RuntimeException('InterestService::accept() not yet implemented.');
    }

    /**
     * Decline an interest and create a reply.
     */
    public function decline(Interest $interest, string $templateId, ?string $customMessage, bool $silent = false): InterestReply
    {
        // TODO: Implement in Phase 4
        throw new \RuntimeException('InterestService::decline() not yet implemented.');
    }

    /**
     * Cancel a previously sent interest (within the allowed window).
     */
    public function cancel(Interest $interest): void
    {
        // TODO: Implement in Phase 4
        throw new \RuntimeException('InterestService::cancel() not yet implemented.');
    }

    /**
     * Check if the profile can send interests today.
     *
     * @return array{can_send: bool, remaining: int, limit: int}
     */
    public function canSendToday(Profile $profile): array
    {
        // TODO: Implement in Phase 4
        throw new \RuntimeException('InterestService::canSendToday() not yet implemented.');
    }
}

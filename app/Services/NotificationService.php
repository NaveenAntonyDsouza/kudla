<?php

namespace App\Services;

use App\Models\User;

class NotificationService
{
    /**
     * Send an in-app notification to a user.
     *
     * @param  array<string, mixed>  $data  Additional data payload
     */
    public function send(User $user, string $type, string $title, string $message, array $data = []): void
    {
        // TODO: Implement in Phase 4
        throw new \RuntimeException('NotificationService::send() not yet implemented.');
    }
}

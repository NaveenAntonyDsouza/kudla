<?php

namespace App\Mail;

class PasswordResetMail extends DatabaseMailable
{
    protected string $templateSlug = 'password-reset';

    public function __construct(
        public string $userName,
        public string $resetUrl,
        public int $expiryMinutes = 60,
    ) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->userName,
            'ACTION_URL' => $this->resetUrl,
            'EXPIRY_MINUTES' => (string) $this->expiryMinutes,
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Reset Your Password - ' . config('app.name');
    }
}

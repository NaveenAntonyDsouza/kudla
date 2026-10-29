<?php

namespace App\Filament\Auth;

use Filament\Auth\MultiFactor\App\AppAuthentication as FilamentAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Facades\Filament;

/**
 * Filament 5.4's set-up wizard checks the 6-digit code on "Next" and again on
 * "Enable authenticator app", but a code may only be used once (replay
 * protection), so the second check always failed and two-step login could
 * never be switched on (found 29 Oct 2026). During set-up — a signed-in
 * person checking a NEW secret, not the one saved on their account — the
 * code is checked without the used-once rule; signing in keeps it.
 */
class AppAuthentication extends FilamentAppAuthentication
{
    public function verifyCode(string $code, ?string $secret = null): bool
    {
        $user = Filament::auth()->user();
        $settingUp = $secret !== null
            && $user instanceof HasAppAuthentication
            && $secret !== $user->getAppAuthenticationSecret();

        if (! $settingUp) {
            return parent::verifyCode($code, $secret);
        }

        return (bool) $this->google2FA->verifyKey($secret, $code, $this->getCodeWindow());
    }
}

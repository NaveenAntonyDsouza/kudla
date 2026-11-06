<?php

namespace App\Http\Controllers\Admin;

use Filament\Auth\Http\Controllers\LogoutController;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;

/**
 * Admin "Sign out" signs out this browser only (6 Nov 2026). Laravel's logout() replaces
 * the account's remember token, which all of a person's "Remember me" browsers share, so
 * signing out on a phone signed the laptop out too after its 2-hour session (the owner
 * felt "Remember me" did not work). Remembered browsers still end after 7 days
 * (AppServiceProvider::STAFF_REMEMBER_MINUTES), and a password change ends them all.
 */
class SignOutThisBrowser extends LogoutController
{
    public function __invoke(): LogoutResponse
    {
        Filament::auth()->logoutCurrentDevice();

        session()->invalidate();
        session()->regenerateToken();

        return app(LogoutResponse::class);
    }
}

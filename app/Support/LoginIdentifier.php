<?php

namespace App\Support;

use App\Models\Profile;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * LoginIdentifier — password login with email, mobile number or Matri ID.
 *
 * Members mistype their email at registration and then can't log in (or
 * reset their password), so the login box also accepts the mobile number
 * and the Matri ID shown on their profile. Shared by the web login and the
 * app API so both behave the same.
 *
 *   - contains "@"            → email (exact match, as before)
 *   - letters + digits        → Matri ID ("km100097", "KM 100097", "KM-100097")
 *   - 10–13 digits            → mobile; compared on the LAST 10 digits, so
 *                               "+91 98450 12345", "098450…", "9845012345"
 *                               all match however the number was stored
 *
 * A mobile number can belong to more than one account where old data has
 * duplicates (phone is unique for new registrations). The password then
 * decides: exactly one matching account logs in; if several accounts share
 * the number AND the password, the member must use email or Matri ID.
 */
final class LoginIdentifier
{
    public const MATCHED = 'matched';
    public const NO_MATCH = 'no_match';
    public const AMBIGUOUS = 'ambiguous';

    /**
     * Resolve identifier + password to exactly one account.
     *
     * @return array{0: string, 1: ?User}  [status, user]
     */
    public static function authenticate(string $identifier, string $password): array
    {
        $matches = self::candidates($identifier)
            ->filter(fn (User $u) => self::passwordMatches($u, $password))
            ->values();

        return match ($matches->count()) {
            1 => [self::MATCHED, $matches->first()],
            0 => [self::NO_MATCH, null],
            default => [self::AMBIGUOUS, null],
        };
    }

    private static function passwordMatches(User $user, string $password): bool
    {
        if ($user->password === null || $user->password === '') {
            return false; // OTP-only / admin-created account without a password
        }

        try {
            return Hash::check($password, $user->password);
        } catch (\RuntimeException) {
            return false; // hash in an unsupported (legacy) format
        }
    }

    /**
     * Accounts the typed identifier could refer to (before the password check).
     *
     * @return Collection<int, User>
     */
    public static function candidates(string $identifier): Collection
    {
        $input = trim($identifier);
        if ($input === '') {
            return collect();
        }

        if (str_contains($input, '@')) {
            return User::where('email', $input)->get();
        }

        if (preg_match('/^([A-Za-z]{1,6})[\s-]?(\d{3,})$/', $input, $m)) {
            // withTrashed: a deleted member gets the same "account removed"
            // message as when they log in by email.
            $userId = Profile::withTrashed()
                ->where('matri_id', strtoupper($m[1]) . $m[2])
                ->value('user_id');

            return $userId ? User::whereKey($userId)->get() : collect();
        }

        $digits = preg_replace('/\D/', '', $input);
        if (preg_match('/^[\d\s().+-]+$/', $input) && strlen($digits) >= 10 && strlen($digits) <= 13) {
            return User::whereNotNull('phone')
                ->whereRaw(
                    "SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', ''), -10) = ?",
                    [substr($digits, -10)]
                )
                ->get();
        }

        return collect();
    }

    /**
     * What this site calls the member ID — "Matri ID" (matrimony) or
     * e.g. "Member ID" on a dating site. Admin: Site Settings → login.
     */
    public static function memberIdLabel(): string
    {
        $label = trim((string) SiteSetting::getValue('member_id_label', 'Matri ID'));

        return $label !== '' ? $label : 'Matri ID';
    }

    /** Label for the login box, e.g. "Email, Mobile Number or Matri ID". */
    public static function fieldLabel(): string
    {
        return 'Email, Mobile Number or ' . self::memberIdLabel();
    }
}

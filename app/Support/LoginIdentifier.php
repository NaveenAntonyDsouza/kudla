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
 *   - any country's number    → also, where Site Settings → "Phone numbers from
 *                               any country" is on (see internationalMatches)
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
                ->where('matri_id', MatriId::current($m[1] . $m[2]))
                ->value('user_id');

            return $userId ? User::whereKey($userId)->get() : collect();
        }

        $digits = preg_replace('/\D/', '', $input);
        if (! preg_match('/^[\d\s().+-]+$/', $input)) {
            return collect();
        }

        $found = collect();
        if (strlen($digits) >= 10 && strlen($digits) <= 13) {
            $found = User::whereNotNull('phone')
                ->whereRaw(
                    "SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', ''), -10) = ?",
                    [substr($digits, -10)]
                )
                ->get();
        }

        // Sites that take numbers from any country (PhoneNumber::international()) store them as
        // "+447700900123"; members type "+44 7700 900123", "07700 900123" or "412 345 678"
        if (PhoneNumber::international()) {
            $found = $found->merge(self::internationalMatches($input, $digits))->unique('id')->values();
        }

        return $found;
    }

    /**
     * Accounts whose international number is the typed one: exactly, when it was typed with its
     * country code ("+44…", "0044…"); otherwise the stored numbers that end with it after a
     * 1–3 digit country code ("07700 900123" → "+44 7700900123", "0412 345 678" → "+61 412345678").
     *
     * @return Collection<int, User>
     */
    private static function internationalMatches(string $input, string $digits): Collection
    {
        if (str_starts_with($input, '+') || str_starts_with($digits, '00')) {
            $e164 = PhoneNumber::international164(null, $input);

            return $e164 ? User::where('phone', $e164)->get() : collect();
        }

        $national = ltrim($digits, '0');
        if (strlen($national) < 6 || strlen($national) > 14) {
            return collect();
        }

        return User::where('phone', 'like', '+%' . $national)->get()
            ->filter(function (User $user) use ($national) {
                $codeLength = strlen(preg_replace('/\D/', '', (string) $user->phone)) - strlen($national);

                return $codeLength >= 1 && $codeLength <= 3;
            })
            ->values();
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

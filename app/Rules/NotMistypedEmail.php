<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Catches common typos in email domains at sign-up ("gmial.com",
 * "gamil.com", "gmail.con") and suggests the right one. Kudla's bounce list
 * was full of them, and a mistyped address means the member never gets the
 * verification code or any email after it.
 */
class NotMistypedEmail implements ValidationRule
{
    /** Mistyped domain => the domain meant. */
    public const TYPOS = [
        'gmial.com' => 'gmail.com', 'gamil.com' => 'gmail.com', 'gmal.com' => 'gmail.com', 'gmai.com' => 'gmail.com',
        'gmil.com' => 'gmail.com', 'gnail.com' => 'gmail.com', 'gmaill.com' => 'gmail.com', 'gmaul.com' => 'gmail.com',
        'gmali.com' => 'gmail.com', 'gimal.com' => 'gmail.com', 'gmaip.com' => 'gmail.com', 'gmsil.com' => 'gmail.com',
        'gmail.co' => 'gmail.com', 'gmail.con' => 'gmail.com', 'gmail.cm' => 'gmail.com', 'gmail.om' => 'gmail.com',
        'gmail.comm' => 'gmail.com', 'gmail.coms' => 'gmail.com', 'gmail.cpm' => 'gmail.com', 'gmail.cim' => 'gmail.com',
        'gmail.in' => 'gmail.com', 'gmail.co.in' => 'gmail.com', 'gmail.vom' => 'gmail.com', 'gmail.xom' => 'gmail.com',
        'yaho.com' => 'yahoo.com', 'yahooo.com' => 'yahoo.com', 'yhoo.com' => 'yahoo.com', 'yahoo.con' => 'yahoo.com',
        'yahoo.cm' => 'yahoo.com', 'yahoo.co' => 'yahoo.com', 'yaho.in' => 'yahoo.in', 'yahoo.co.on' => 'yahoo.co.in',
        'hotmial.com' => 'hotmail.com', 'hotmal.com' => 'hotmail.com', 'hotmail.con' => 'hotmail.com', 'hotmail.co' => 'hotmail.com',
        'outlok.com' => 'outlook.com', 'outlook.con' => 'outlook.com', 'outlook.co' => 'outlook.com', 'outllok.com' => 'outlook.com',
        'rediffmail.con' => 'rediffmail.com', 'redifmail.com' => 'rediffmail.com', 'rediffmial.com' => 'rediffmail.com',
        'iclod.com' => 'icloud.com', 'icloud.con' => 'icloud.com',
    ];

    /** The address with its domain fixed, or null if it doesn't look mistyped. */
    public static function suggestion(string $email): ?string
    {
        $email = strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return null;
        }
        [$local, $domain] = explode('@', $email, 2);

        if (isset(self::TYPOS[$domain])) {
            return "{$local}@".self::TYPOS[$domain];
        }
        // "name@315gmail.com" — digits that belong before the @
        if (preg_match('/^(\d+)(gmail|yahoo|hotmail|outlook|rediffmail)\.com$/', $domain, $m)) {
            return "{$local}{$m[1]}@{$m[2]}.com";
        }

        return null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $suggestion = is_string($value) ? self::suggestion($value) : null;
        if ($suggestion !== null) {
            $fail("Please check your email address. Did you mean {$suggestion}?");
        }
    }
}

<?php

namespace App\Support;

/**
 * How other members are named in emails: first name and last initial
 * ("Priya D."), always shown with their Matri ID. Full names stay on the
 * website behind login, so a forwarded email reveals less.
 */
final class MemberName
{
    private const TITLES = ['dr', 'mr', 'mrs', 'ms', 'miss', 'prof', 'sri', 'shri', 'shree', 'smt', 'kum', 'kumari', 'fr', 'sr', 'er', 'adv'];

    /** Surname prefixes written as a separate word: "Naveen D Souza" is "Naveen D." like "Naveen D'Souza". */
    private const PREFIXES = ['d', "d'", 'de', 'da', 'dos', 'del', 'della', 'la', 'van', 'von'];

    /**
     * "Priya D.": the given name and the first letter of the surname (20 Nov 2026, from the live names):
     *   Naveen Antony D'Souza → Naveen D.    Naveen D Souza → Naveen D.   Dr. Priya Rao → Priya R.
     *   K. Ramesh / K.Ramesh / K R Ramesh → Ramesh K.   Ramesh K → Ramesh K.   PRIYA RAO / priya rao → Priya R.
     *   Anugraha 1460 (a staff code) → Anugraha 1460.   An email typed as a name is never shown.
     */
    public static function short(?string $fullName): string
    {
        $text = preg_replace('/\S*@\S*/u', ' ', trim((string) $fullName));                 // never an email
        // dots, commas and brackets between words count as spaces: "K.Ramesh", "Priya.Shetty", "Priya (Rao)"
        $text = preg_replace('/[.,;:()\[\]{}\/\\\\|"_]+/u', ' ', $text);
        $words = [];
        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $w) {
            $w = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{M}\p{N}]+$/u', '', $w);           // stray ' - at the edges
            if ($w !== '') {
                $words[] = $w;
            }
        }

        while (count($words) > 1 && in_array(mb_strtolower($words[0]), self::TITLES, true)) {
            array_shift($words);
        }
        if ($words === []) {
            return '';
        }

        $isNumber = fn (string $w) => (bool) preg_match('/^\d+$/', $w);
        $isInitial = fn (string $w) => (bool) preg_match('/^\p{L}\p{M}*$/u', $w);
        $isWord = fn (string $w) => ! $isNumber($w) && ! $isInitial($w);

        $given = null;
        foreach ($words as $i => $w) {
            if ($isWord($w)) {
                $given = $i;
                break;
            }
        }
        if ($given === null) {                                // only initials or numbers: as typed
            return implode(' ', $words);
        }

        // the surname letter: the last word after the given name (or its prefix, "D Souza"),
        // else the first initial before it ("K. Ramesh")
        $letter = null;
        $after = array_values(array_filter(array_slice($words, $given + 1), fn ($w) => ! $isNumber($w)));
        if ($after !== []) {
            $last = count($after) - 1;
            $prefix = $last > 0 && in_array(mb_strtolower($after[$last - 1]), self::PREFIXES, true);
            $letter = mb_substr($after[$prefix ? $last - 1 : $last], 0, 1);
        } elseif ($given > 0) {
            $letter = mb_substr($words[0], 0, 1);
        }
        $numbers = array_filter(array_slice($words, $given + 1), $isNumber);        // a staff code stays

        return trim(self::nameCase($words[$given])
            . ($letter !== null ? ' ' . mb_strtoupper($letter) . '.' : '')
            . ($numbers !== [] ? ' ' . implode(' ', $numbers) : ''));
    }

    /** "PRIYA" and "priya" become "Priya"; a name typed with its own mix of capitals stays as it is. */
    private static function nameCase(string $word): string
    {
        return ($word === mb_strtoupper($word) || $word === mb_strtolower($word))
            ? mb_convert_case(mb_strtolower($word), MB_CASE_TITLE)
            : $word;
    }

    /** "Naveen" from "Naveen Antony D'Souza", for greetings. */
    public static function first(?string $fullName): string
    {
        return trim(strtok(trim((string) $fullName), " \t")) ?: '';
    }

    /**
     * "Priya D. (KM100123)": how another member is named inside an email (Nov 2026, owner: interest
     * emails show the name too). Only the Matri ID when the profile has no usable name. Never use it
     * in a subject or preview line: those show on locked phone screens.
     */
    public static function withId(?\App\Models\Profile $profile): string
    {
        if (! $profile) {
            return '';
        }
        $short = self::short($profile->full_name);

        return $short !== '' && $short !== $profile->matri_id ? "{$short} ({$profile->matri_id})" : (string) $profile->matri_id;
    }
}

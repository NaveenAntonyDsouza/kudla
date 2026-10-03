<?php

namespace App\Support;

/**
 * How other members are named in emails: first name and last initial
 * ("Priya D."), always shown with their Matri ID. Full names stay on the
 * website behind login, so a forwarded email reveals less.
 */
final class MemberName
{
    private const TITLES = ['dr', 'mr', 'mrs', 'ms', 'miss', 'prof'];

    public static function short(?string $fullName): string
    {
        $words = array_values(array_filter(preg_split('/\s+/u', trim((string) $fullName)) ?: [], fn ($w) => $w !== ''));

        while (count($words) > 1 && in_array(mb_strtolower(rtrim($words[0], '.')), self::TITLES, true)) {
            array_shift($words);
        }

        if ($words === []) {
            return '';
        }
        if (count($words) === 1) {
            return $words[0];
        }

        return $words[0] . ' ' . mb_strtoupper(mb_substr(end($words), 0, 1)) . '.';
    }

    /** "Naveen" from "Naveen Antony D'Souza", for greetings. */
    public static function first(?string $fullName): string
    {
        return trim(strtok(trim((string) $fullName), " \t")) ?: '';
    }
}

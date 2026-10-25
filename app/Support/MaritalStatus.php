<?php

namespace App\Support;

/**
 * Marital status spellings that mean the same thing (Oct 2026). Sites have
 * used "Divorced" and "Divorcee", "Widower" and "Widow/Widower", and bulk
 * import stored "never_married" / "widowed"; a partner preference or search
 * for one spelling must find members saved with another.
 */
class MaritalStatus
{
    private const GROUPS = [
        ['Unmarried', 'Never Married', 'never_married', 'Single'],
        ['Divorcee', 'Divorced', 'divorced'],
        ['Widow/Widower', 'Widower', 'Widow', 'Widowed', 'widowed'],
        ['Awaiting Divorce', 'awaiting_divorce'],
        ['Annulled', 'annulled'],
        ['Separated', 'separated'],
    ];

    /** All spellings of the given values (for whereIn / in_array). */
    public static function expand(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            $v = (string) $v;
            if ($v === '' || $v === 'Any') {
                continue;
            }
            $out[] = $v;
            foreach (self::GROUPS as $group) {
                if (in_array(mb_strtolower($v), array_map('mb_strtolower', $group), true)) {
                    array_push($out, ...$group);
                }
            }
        }

        return array_values(array_unique($out));
    }

    public static function same(?string $a, ?string $b): bool
    {
        return $a !== null && $b !== null && in_array($a, self::expand([$b]), true);
    }

    /**
     * The spelling this site's own list uses (e.g. bulk import "divorced" →
     * "Divorcee" on a site that offers Divorcee). Unknown values come back
     * unchanged.
     */
    public static function toSiteValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $list = (array) config('reference_data.marital_status_list', []);
        foreach (self::expand([$value]) as $spelling) {
            if (in_array($spelling, $list, true)) {
                return $spelling;
            }
        }

        return $value;
    }
}

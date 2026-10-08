<?php

namespace App\Support;

use App\Models\Profile;

/**
 * Gotra (father's line, Brahmins) and bari / bali (mother's line, Tulu
 * communities) share one field, religious_info.gotra (Oct 2026). Either
 * way, two people of the same one count as brother and sister, so the site
 * shows a gentle "Same bari / gotra as you" note and members can choose not
 * to see them (partner_preferences.exclude_same_gotra).
 *
 * The list is the admin-editable `gothram` reference data; once bari groups
 * are added it is grouped (['Common Tulu baris' => [...], ...]).
 */
final class GotraBari
{
    public const LABEL = 'Gotra / Bari';

    /** The classic Brahmin gotras — anything else in the field is a bari. */
    public const GOTRAS = ['Atri', 'Bharadvaja', 'Bhrigu', 'Gautam', 'Jamadagni', 'Kashyapa', 'Vasishta', 'Viswamitra', 'Agastya', 'Harita', 'Kaundinya', 'Koushika', 'Moudgalya', 'Sandilya', 'Srivatsa'];

    /** Answers that say nothing about the lineage. */
    private const NOT_A_LINEAGE = ['', "don't know", 'dont know', 'other', "don't wish to specify", 'not sure', 'na', 'n/a', 'none', '-'];

    /** Lower-case, single-spaced; null when it isn't a real gotra / bari. */
    public static function normalize(?string $value): ?string
    {
        $v = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));

        return in_array($v, self::NOT_A_LINEAGE, true) ? null : $v;
    }

    /** 'gotra' for a Brahmin gotra, otherwise 'bari'. */
    public static function kind(string $value): string
    {
        $v = self::normalize($value);

        return $v !== null && in_array($v, array_map('mb_strtolower', self::GOTRAS), true) ? 'gotra' : 'bari';
    }

    /**
     * The gotra / bari two profiles share, as ['value' => …, 'kind' => 'bari'|'gotra'],
     * or null (different, unknown, or the same person).
     */
    public static function shared(?Profile $a, ?Profile $b): ?array
    {
        if (! $a || ! $b || $a->id === $b->id) {
            return null;
        }

        try {
            $mine = $a->religiousInfo?->gotra;
            $theirs = $b->religiousInfo?->gotra;
        } catch (\Throwable) {
            return null;
        }

        $n = self::normalize($mine);
        if ($n === null || $n !== self::normalize($theirs)) {
            return null;
        }

        return ['value' => trim((string) $theirs), 'kind' => self::kind((string) $theirs)];
    }

    /** The list as it is: grouped (['Group' => [...]]) or flat. */
    public static function options(): array
    {
        $list = config('reference_data.gothram_list', []);

        return is_array($list) ? $list : [];
    }

    /** Every option in one flat list (admin forms, validation). */
    public static function flatOptions(): array
    {
        $flat = [];
        foreach (self::options() as $key => $item) {
            foreach ((array) $item as $value) {
                $flat[] = (string) $value;
            }
        }

        return array_values(array_unique($flat));
    }
}

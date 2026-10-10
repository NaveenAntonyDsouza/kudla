<?php

namespace App\Support;

use App\Models\Community;
use App\Models\Profile;
use App\Models\ReligiousInfo;

/**
 * Gotra (father's line, Brahmins) and bari / bali (mother's line, Tulu
 * communities) share one field, religious_info.gotra (Oct 2026). Either
 * way, two people of the same one count as brother and sister, so the site
 * shows a gentle "Same bari / gotra as you" note and members can choose not
 * to see them (partner_preferences.exclude_same_gotra). Only within the same
 * caste: a Bunt Bangera and a Billava Bangera are not the same family line
 * (owner, 10 Oct 2026, after the ChatGPT / Gemini research). All Brahmin
 * communities count as one for gotras.
 *
 * The field is labelled by caste: "Bari" for the Tulu bari castes, "Gotra"
 * for Brahmins and Vishwakarmas, "Gotra / Bari" for everyone else.
 *
 * The list is the admin-editable `gothram` reference data; once bari groups
 * are added it is grouped (['Common Tulu baris' => [...], ...]).
 */
final class GotraBari
{
    public const LABEL = 'Gotra / Bari';

    /** The Brahmin and Vishwakarma gotras — anything else in the field is a bari. */
    public const GOTRAS = ['Atri', 'Bharadvaja', 'Bhrigu', 'Gautam', 'Jamadagni', 'Kashyapa', 'Vasishta', 'Viswamitra', 'Agastya', 'Harita', 'Kaundinya', 'Koushika', 'Moudgalya', 'Sandilya', 'Srivatsa', 'Naidhruva', 'Angirasa',
        'Sanaga', 'Sanatana', 'Abhuvanasa', 'Pratnasa', 'Suparna'];

    /** Castes whose families go by a bari / bali (the mother's line). */
    public const BARI_CASTES = ['Billava', 'Bunts', 'Bunt Jain', 'Mogaveera', 'Kulal', 'Kulala', 'Devadiga (Moily)', 'Devadiga', 'Gowda (Tulu / Arebhashe)', 'Tulu Gowda', 'Kotari', 'Sapaliga', 'Salian'];

    /** Castes that go by a gotra, besides every "… Brahmin" one. */
    public const GOTRA_CASTES = ['Vishwakarma'];

    /** The list groups a caste sees first (lower-case caste => group labels). */
    public const FIRST_GROUPS = [
        'bunts' => ['Bunt balis', 'Nadava balis'],
        'bunt jain' => ['Bunt balis', 'Nadava balis'],
        'gowda (tulu / arebhashe)' => ['Gowda baris'],
        'tulu gowda' => ['Gowda baris'],
        'vishwakarma' => ['Vishwakarma gotras'],
    ];

    /** Answers that say nothing about the lineage. */
    private const NOT_A_LINEAGE = ['', "don't know", 'dont know', 'other', "don't wish to specify", 'not sure', 'na', 'n/a', 'none', '-'];

    /** Lower-case, single-spaced; null when it isn't a real gotra / bari. */
    public static function normalize(?string $value): ?string
    {
        $v = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));

        return in_array($v, self::NOT_A_LINEAGE, true) ? null : $v;
    }

    /** Lower-case caste; null for none or "Other / Not Listed". */
    public static function casteKey(?string $caste): ?string
    {
        if ($caste === null || ReligiousInfo::isOtherCaste(trim($caste))) {
            return null;
        }
        $c = mb_strtolower(trim(preg_replace('/\s+/', ' ', $caste)));

        return $c === '' ? null : $c;
    }

    /** What the same-lineage check compares: the caste, with every Brahmin community as one. */
    public static function matchKey(?string $caste): ?string
    {
        $c = self::casteKey($caste);

        return $c !== null && str_contains($c, 'brahmin') ? 'brahmin' : $c;
    }

    /** 'bari', 'gotra', or null when the caste doesn't tell us. */
    public static function lineage(?string $caste): ?string
    {
        $c = self::casteKey($caste);
        if ($c === null) {
            return null;
        }
        if (str_contains($c, 'brahmin') || in_array($c, array_map('mb_strtolower', self::GOTRA_CASTES), true)) {
            return 'gotra';
        }

        return in_array($c, array_map('mb_strtolower', self::BARI_CASTES), true) ? 'bari' : null;
    }

    /** The field's label for this caste. */
    public static function label(?string $caste): string
    {
        return match (self::lineage($caste)) {
            'bari' => 'Bari',
            'gotra' => 'Gotra',
            default => self::LABEL,
        };
    }

    /** The line under the field. */
    public static function help(?string $caste): string
    {
        return match (self::lineage($caste)) {
            'bari' => "Your bari is your mother's bari, the family line from her side (for example Bangera or Salian). Not sure? Ask an elder, or choose \"Don't Know\".",
            'gotra' => self::casteKey($caste) === 'vishwakarma'
                ? "Your gotra, for example Sanaga or Suparna. Not sure? Choose \"Don't Know\"."
                : "Your gotra, the family line from your father's side (for example Kashyapa or Bharadvaja). Not sure? Choose \"Don't Know\".",
            default => "Bari is your family line from your mother's side (for example Bangera or Salian). Brahmins: choose your gotra.",
        };
    }

    /** The list groups this caste sees first. */
    public static function firstGroups(?string $caste): array
    {
        $c = self::casteKey($caste);
        if ($c === null) {
            return [];
        }

        return str_contains($c, 'brahmin') ? ['Brahmin gotras'] : (self::FIRST_GROUPS[$c] ?? []);
    }

    /**
     * Label, help line and first groups for every caste that has them, keyed
     * by casteKey(), so the picker can follow a caste changed on the page.
     */
    public static function casteHints(?string $current = null): array
    {
        try {
            $castes = Community::active()->pluck('community_name')->all();
        } catch (\Throwable) {
            $castes = [];
        }

        $hints = [];
        foreach (array_merge([(string) $current], $castes, self::BARI_CASTES, self::GOTRA_CASTES, ['Brahmin']) as $caste) {
            $key = self::casteKey($caste);
            if ($key === null || isset($hints[$key]) || (self::lineage($caste) === null && ! self::firstGroups($caste))) {
                continue;
            }
            $hints[$key] = ['label' => self::label($caste), 'help' => self::help($caste), 'first' => self::firstGroups($caste)];
        }

        return $hints;
    }

    /** 'gotra' for a Brahmin or Vishwakarma gotra, otherwise 'bari'. */
    public static function kind(string $value): string
    {
        $v = self::normalize($value);

        return $v !== null && in_array($v, array_map('mb_strtolower', self::GOTRAS), true) ? 'gotra' : 'bari';
    }

    /**
     * The gotra / bari two profiles of the same caste share, as
     * ['value' => …, 'kind' => 'bari'|'gotra'], or null (different, unknown,
     * another caste, or the same person).
     */
    public static function shared(?Profile $a, ?Profile $b): ?array
    {
        if (! $a || ! $b || $a->id === $b->id) {
            return null;
        }

        try {
            $mine = $a->religiousInfo?->gotra;
            $theirs = $b->religiousInfo?->gotra;
            $myCaste = $a->religiousInfo?->caste;
            $theirCaste = $b->religiousInfo?->caste;
        } catch (\Throwable) {
            return null;
        }

        $n = self::normalize($mine);
        if ($n === null || $n !== self::normalize($theirs)) {
            return null;
        }
        $caste = self::matchKey($myCaste);
        if ($caste === null || $caste !== self::matchKey($theirCaste)) {
            return null;
        }

        return ['value' => trim((string) $theirs), 'kind' => self::lineage($theirCaste) ?? self::kind((string) $theirs)];
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

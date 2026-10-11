<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use App\Models\Community;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Kannada Dating's caste list for all of Karnataka (owner, 11 Oct 2026):
 * the big Kannada communities get their sub-castes, Brahmins become one
 * caste with sub-castes (as on Kudla), new communities are added, and the
 * Tulu leftovers copied from Kudla in September are fixed. Sources:
 *
 * - Vokkaliga sub-groups: en.wikipedia.org/wiki/Vokkaliga
 * - Lingayat sub-sects: ThePrint / Deccan Chronicle reporting; en.wikipedia.org/wiki/Panchamsali_Lingayats
 * - Kuruba divisions: en.wikipedia.org/wiki/Kuruba
 * - Idiga / Ediga / Deevaru: en.wikipedia.org/wiki/Idiga
 * - Golla (Kadugolla, Oorugolla, Hanabaru / Krishna Golla, Yadava): en.wikipedia.org/wiki/Golla_(caste)
 * - Bestha / Kabbera / Ambiga / Kabbaliga (Gangakula): en.wikipedia.org/wiki/Bestha, Thurston 1909
 * - Thigala: en.wikipedia.org/wiki/Thigala; Savita Samaj (Nayinda, Hadapada): news reporting
 * - Kannada Brahmins: en.wikipedia.org/wiki/Kannada_Brahmin, Babburkamme_Brahmin, Badaganadu_Brahmin
 * - SC / ST: the official Karnataka lists (Constitution SC / ST Orders)
 * - Tulu communities: as on Kudla (communities:tulu-nadu-update, South Kanara Gazetteer 1973)
 *
 * Runs only on kannadadating.com. Safe to run again: a second run changes nothing.
 */
class UpdateKannadaCommunities extends Command
{
    protected $signature = 'communities:kannada-update {--dry-run : show what would change, change nothing}';

    protected $description = 'Kannada Dating: Karnataka-wide caste and sub-caste list';

    public const GOWDA = 'Gowda (Tulu / Arebhashe)';

    private const OTHER = ['Other / Not Listed', 'Other (not listed)', 'Other'];

    /** New communities => their sub-castes. */
    public const ADD = [
        'Brahmin' => ['Smartha Brahmin', 'Madhwa Brahmin', 'Sri Vaishnava / Iyengar', 'Havyaka Brahmin', 'Hoysala Karnataka Brahmin', 'Babbur Kamme', 'Ulucha Kamme', 'Badaganadu Brahmin', 'Deshastha Brahmin', 'Kota Brahmin', 'Shivalli Brahmin', 'Sthanika Brahmin', 'Koteshwara Brahmin', 'Kandavara Brahmin', 'Chitpavan Brahmin', 'Karhade Brahmin', "Don't wish to specify"],
        'Daivajna Brahmin' => [],
        'Rajapur Saraswat Brahmin (RSB)' => [],
        'Chitrapur Saraswat Brahmin' => [],
        'Golla / Yadava' => ['Golla', 'Yadava', 'Kadugolla', 'Oorugolla', 'Hanabaru / Krishna Golla'],
        'Bestha / Gangamatha' => ['Bestha', 'Gangamatha', 'Kabbaliga', 'Ambiga', 'Kabbera'],
        'Savita Samaj' => ['Nayinda', 'Hadapada'],
        'Thigala' => [],
        'Kharvi' => [],
        'Sapaliga' => [],
        'Kudubi' => [],
    ];

    /** Existing communities => their sub-caste list from now on. */
    public const SUBS = [
        'Vokkaliga' => ['Gangadikara', 'Kunchitiga', 'Morasu', 'Hallikar', 'Namadhari', 'Reddy', 'Sadar', 'Nonaba', "Don't wish to specify"],
        'Lingayat / Veerashaiva' => ['Panchamasali', 'Banajiga', 'Jangama', 'Sadar', 'Ganiga', 'Reddy', 'Nonaba', 'Gowda Lingayat', "Don't wish to specify"],
        'Kuruba' => ['Hande', 'Unnikankan', 'Hattikankan', "Don't wish to specify"],
        'Idiga / Ediga' => ['Idiga', 'Ediga', 'Deevaru'],
        'Bunts' => ['Bunt', 'Masadika Bunt', 'Nadava', 'Parivara Bunt', 'Jain Bunt', 'Shetty'],
        'Billava' => ['Poojary', 'Billava', 'Halepaika'],
        'Mogaveera' => ['Mogaveera', 'Tulu Mogaveera', 'Kannada Mogaveera'],
        'Devadiga (Moily)' => ['Devadiga', 'Sherigar'],
        'Kulala' => ['Kumbara', 'Kulala', 'Kulal', 'Moolya'],
        'Vishwakarma' => ['Achari', 'Vishwakarma', 'Akkasale (Goldsmith)', 'Kammara (Blacksmith)', 'Badagi (Carpenter)', 'Shilpi (Sculptor)', 'Kanchugara (Bronzesmith)'],
        'Padmashali' => ['Padmashali', 'Shettigar'],
        'Madiwala' => ['Madiwala', 'Agasa'],
        self::GOWDA => ['Tulu Gowda', 'Arebhashe Gowda', "Don't wish to specify"],
        'Scheduled Caste' => ['Adi Karnataka', 'Adi Dravida', 'Adi Andhra', 'Holeya', 'Madiga', 'Banjara / Lambani', 'Bhovi', 'Korama', 'Koracha', 'Chalavadi', 'Samagara', 'Bhambi / Mochi', 'Mala', 'Mundala', 'Bakuda', 'Pambada', 'Paravan', 'Nalike', 'Mera / Muggera', "Don't wish to specify"],
        'Scheduled Tribe' => ['Nayaka / Valmiki / Beda', 'Jenu Kuruba', 'Kadu Kuruba / Betta Kuruba', 'Soliga', 'Koraga', 'Siddi', 'Hakkipikki', 'Iruliga', 'Yerava', 'Gond', 'Gowdalu', 'Marati / Marati Naik', 'Malekudiya / Malaikudi', 'Kudiya', 'Hasalaru', "Don't wish to specify"],
    ];

    /**
     * Communities that become another one (members and partner preferences
     * move): old name => [new name, sub-caste for the members or null].
     */
    public const MERGE = [
        'Smartha Brahmin' => ['Brahmin', 'Smartha Brahmin'],
        'Madhwa Brahmin' => ['Brahmin', 'Madhwa Brahmin'],
        'Havyaka Brahmin' => ['Brahmin', 'Havyaka Brahmin'],
        'Shivalli Brahmin' => ['Brahmin', 'Shivalli Brahmin'],
        'Kota Brahmin' => ['Brahmin', 'Kota Brahmin'],
        'Sthanika Brahmin' => ['Brahmin', 'Sthanika Brahmin'],
        'Hoysala Karnataka Brahmin' => ['Brahmin', 'Hoysala Karnataka Brahmin'],
        'Deshastha Brahmin' => ['Brahmin', 'Deshastha Brahmin'],
        'Kunchitiga' => ['Vokkaliga', 'Kunchitiga'],
        'Tulu Gowda' => [self::GOWDA, null],
        'Devadiga' => ['Devadiga (Moily)', null],
    ];

    /** Not communities: switched off (a member who chose one keeps it). */
    public const OFF = ['Salian', 'Konkani'];

    private array $report = [];

    public function handle(): int
    {
        if (! str_contains((string) config('app.url'), 'kannadadating')) {
            $this->error('This is for kannadadating.com only — nothing changed.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        DB::transaction(function () use ($dry) {
            $this->renameOrAdd($dry);
            $this->setSubCastes($dry);
            $this->moveMembers($dry);
            $this->switchOff($dry);
        });

        if (! $dry) {
            foreach (array_merge(['_all'], Community::query()->distinct()->pluck('religion')->map(fn ($r) => "_{$r}")->all()) as $suffix) {
                Cache::forget('community_caste_list'.$suffix);
                Cache::forget('community_sub_caste_list'.$suffix);
            }
            AdminActivityLog::create(['action' => 'communities_kannada_update', 'model_type' => Community::class, 'changes' => $this->report]);
        }

        foreach ($this->report as $line => $n) {
            $this->line(($dry ? '[dry run] ' : '').$line.': '.(is_array($n) ? json_encode($n) : $n));
        }

        return self::SUCCESS;
    }

    private function hindu()
    {
        return Community::where('religion', 'Hindu');
    }

    /** Renamed communities keep their row; new ones go in before "Other / Not Listed" (sort_order ≤ 255). */
    private function renameOrAdd(bool $dry): void
    {
        $renamed = [];
        foreach (self::MERGE as $from => [$to, $sub]) {
            if ($sub !== null) {
                continue;   // merged into another community, not a plain rename
            }
            $old = $this->hindu()->where('community_name', $from)->first();
            if ($old && ! $this->hindu()->where('community_name', $to)->exists()) {
                $renamed[] = "{$from} → {$to}";
                if (! $dry) {
                    $old->update(['community_name' => $to]);
                }
            }
        }
        $this->report['renamed'] = $renamed;

        $order = (int) $this->hindu()->whereNotIn('community_name', self::OTHER)->max('sort_order');
        $added = [];
        foreach (self::ADD as $name => $subs) {
            if ($this->hindu()->where('community_name', $name)->exists()) {
                continue;
            }
            $added[] = $name;
            if (! $dry) {
                Community::create(['religion' => 'Hindu', 'community_name' => $name, 'sub_communities' => $subs, 'is_active' => true, 'sort_order' => min(255, ++$order)]);
            }
        }
        $this->report['communities added'] = $added;
        if (! $dry && $added) {
            $this->hindu()->whereIn('community_name', self::OTHER)->update(['sort_order' => min(255, $order + 1)]);
        }
    }

    private function setSubCastes(bool $dry): void
    {
        $changed = [];
        foreach (self::SUBS as $name => $subs) {
            $c = $this->hindu()->where('community_name', $name)->first()
                ?? ($dry ? $this->hindu()->whereIn('community_name', array_keys(array_filter(self::MERGE, fn ($m) => $m[0] === $name && $m[1] === null)))->first() : null);
            if (! $c || ($c->sub_communities ?? []) === $subs) {
                continue;
            }
            $changed[] = $name;
            if (! $dry) {
                $c->update(['sub_communities' => $subs]);
            }
        }
        $this->report['sub-caste lists updated'] = $changed;
    }

    private function moveMembers(bool $dry): void
    {
        $moved = 0;
        foreach (self::MERGE as $from => [$to, $sub]) {
            $q = fn () => DB::table('religious_info')->where('caste', $from);
            if ($n = $q()->count()) {
                $moved += $n;
                $this->report["members {$from} → {$to}"] = $n;
                if (! $dry) {
                    $q()->update(array_filter(['caste' => $to, 'sub_caste' => $sub, 'updated_at' => now()], fn ($v) => $v !== null));
                }
            }
        }

        $prefs = DB::table('partner_preferences')->whereNotNull('caste')->get(['id', 'caste']);
        $n = 0;
        foreach ($prefs as $row) {
            $list = json_decode((string) $row->caste, true);
            if (! is_array($list)) {
                continue;
            }
            $new = array_values(array_unique(array_map(fn ($c) => self::MERGE[$c][0] ?? $c, $list)));
            if ($new !== $list) {
                $n++;
                if (! $dry) {
                    DB::table('partner_preferences')->where('id', $row->id)->update(['caste' => json_encode($new), 'updated_at' => now()]);
                }
            }
        }
        $this->report['partner preferences updated'] = $n;
    }

    /** Merged communities and non-communities: off for new members. */
    private function switchOff(bool $dry): void
    {
        $names = array_merge(self::OFF, array_keys(array_filter(self::MERGE, fn ($m) => $m[1] !== null)));
        $off = $this->hindu()->whereIn('community_name', $names)->where('is_active', true)->pluck('community_name')->all();
        $this->report['switched off'] = $off;
        if (! $dry && $off) {
            $this->hindu()->whereIn('community_name', $off)->update(['is_active' => false]);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Mail\CommunityUpdateRequestMail;
use App\Models\AdminActivityLog;
use App\Models\Community;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Kudla caste list, from the Tulu Nadu research of 8 Oct 2026 (owner
 * approved; notes in D:\matrimony\reports\tulu-nadu-castes-research.md).
 *
 *  - adds communities members typed into "Other" or that are recognised in
 *    Tulu Nadu, adds sub-castes, fixes "Nadoja" → "Nadava";
 *  - moves members (and partner preferences) still on old names, so nobody
 *    drops out of caste searches;
 *  - switches off "Salian" (a bari) and "Konkani" (a language) for new
 *    members, and with --ask asks the members who chose them to pick their
 *    community (in-app + email, once each).
 *
 * Kudla only (the other sites keep their own lists): refuses to run where
 * the Tulu list isn't present. Safe to run again — every step checks first.
 */
class UpdateTuluNaduCommunities extends Command
{
    protected $signature = 'communities:tulu-nadu-update
        {--dry-run : show what would change, change nothing}
        {--ask : ask members whose caste is Salian / Konkani to choose their community}';

    protected $description = 'Apply the Tulu Nadu caste-list update (kudla, Oct 2026)';

    public const GOWDA = 'Gowda (Tulu / Arebhashe)';

    private const OTHER = ['Other / Not Listed', 'Other (not listed)', 'Other'];

    /** New Hindu communities => sub-castes. */
    public const ADD = [
        'Thiyya / Ezhava' => ['Thiyya', 'Ezhava'],
        'Gatti' => [],
        'Kotari' => [],
        'Jogi' => [],
        'Ramakshatriya' => [],
        'Lingayat / Veerashaiva' => [],
        'Vokkaliga' => [],
        'Yadava (Maniyani)' => [],
        'Nair' => [],
        'Devanga' => [],
        'Kharvi' => [],
        'Kudubi' => [],
        'Daivajna Brahmin' => [],
        'Rajapur Saraswat Brahmin (RSB)' => [],
        'Chitrapur Saraswat Brahmin' => [],
    ];

    /** Existing communities => their sub-caste list from now on. */
    public const SUBS = [
        self::GOWDA => ['Tulu Gowda', 'Arebhashe Gowda', "Don't wish to specify"],
        'Bunts' => ['Bunt', 'Nadava', 'Parivara Bunt', 'Jain Bunt', 'Shetty'],
        'Mogaveera' => ['Mogaveera', 'Tulu Mogaveera', 'Kannada Mogaveera'],
        'Brahmin' => ['Havyaka Brahmin', 'Kota Brahmin', 'Shivalli Brahmin', 'Sthanika Brahmin', 'Koteshwara Brahmin', 'Kandavara Brahmin', 'Chitpavan Brahmin', 'Karhade Brahmin'],
        'Kulal' => ['Kulal', 'Kulala', 'Moolya', 'Kumbara'],
        'Devadiga (Moily)' => ['Devadiga', 'Sapaliga', 'Sherigar'],
        'Padmashali' => ['Padmashali', 'Shettigar'],
        'Vishwakarma' => ['Achari', 'Vishwakarma', 'Akkasale (Goldsmith)', 'Kammara (Blacksmith)', 'Badagi (Carpenter)', 'Shilpi (Sculptor)'],
        'Scheduled Caste (SC)' => ['Adi Dravida', 'Adi Karnataka', 'Mundala', 'Mera / Muggera', 'Manser / Mansa', 'Samagara', 'Bakuda', 'Raneyar', 'Chalavadi', 'Madiga', 'Bhovi', 'Holeya', 'Pambada', 'Paravan', 'Nalike', "Don't wish to specify"],
    ];

    /** Members' caste names that changed => current name (also in partner preferences). */
    public const RENAMES = [
        'Gowda - Tulu' => self::GOWDA,
        'Tulu Gowda' => self::GOWDA,
        'Devadiga' => 'Devadiga (Moily)',
        'Bhandari' => 'Bhandary',
    ];

    /** What members typed into "Other" (lower-case) => [caste, sub-caste]. */
    public const TYPED = [
        'gatty' => ['Gatti', null], 'gatty (2a)' => ['Gatti', null], 'gatti' => ['Gatti', null],
        'sc' => ['Scheduled Caste (SC)', null],
        'thiyya' => ['Thiyya / Ezhava', 'Thiyya'], 'ezhava' => ['Thiyya / Ezhava', 'Ezhava'],
        'poojari' => ['Billava', 'Poojary'], 'poojary' => ['Billava', 'Poojary'],
        'lingayath' => ['Lingayat / Veerashaiva', null], 'lingayat' => ['Lingayat / Veerashaiva', null],
        'lingyath' => ['Lingayat / Veerashaiva', null], 'veerashaiva lingayat' => ['Lingayat / Veerashaiva', null],
        'lingayat, mali, malagar' => ['Lingayat / Veerashaiva', null],
        'nair' => ['Nair', null],
        'ramakshatriya' => ['Ramakshatriya', null], 'rama kshtriya' => ['Ramakshatriya', null], 'rama kshatriya' => ['Ramakshatriya', null],
        'jogi' => ['Jogi', null],
        'arekannada gowdas' => [self::GOWDA, 'Arebhashe Gowda'],
        'bhovi' => ['Scheduled Caste (SC)', 'Bhovi'],
        'madivala tulu rajaka' => ['Madiwala', null],
        'kottari' => ['Kotari', null], 'kottary' => ['Kotari', null], 'kotari' => ['Kotari', null],
        'yadav' => ['Yadava (Maniyani)', null], 'yadava' => ['Yadava (Maniyani)', null], 'maniyani yadava' => ['Yadava (Maniyani)', null],
        'vokkaliga gowda' => ['Vokkaliga', null],
        'devnaga' => ['Devanga', null], 'devanga' => ['Devanga', null], 'devanga chettiyar' => ['Devanga', null],
    ];

    /** [caste, sub-caste as typed (lower-case)] => sub-caste from the list. */
    public const SUB_FIXES = [
        ['Brahmin', 'koteshwara', 'Koteshwara Brahmin'],
        ['Padmashali', 'shettigar', 'Shettigar'],
        ['Padmashali', 'padma shali shettigar', 'Shettigar'],
        ['Vishwakarma', 'gold smith', 'Akkasale (Goldsmith)'],
        ['Kulal', 'moolya', 'Moolya'],
    ];

    /** Not communities: switched off for new members; --ask asks who chose them. */
    public const ASK = ['Salian', 'Konkani'];

    private array $report = [];

    public function handle(NotificationService $notifications): int
    {
        if (! Community::where('religion', 'Hindu')->where('community_name', 'Billava')->exists()) {
            $this->error('This site does not have the Tulu Nadu caste list — nothing changed.');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        DB::transaction(function () use ($dry) {
            $this->updateCommunities($dry);
            $this->moveMembers($dry);
            $this->movePartnerPreferences($dry);
        });

        if (! $dry) {
            foreach (array_merge(['_all'], Community::query()->distinct()->pluck('religion')->map(fn ($r) => "_{$r}")->all()) as $suffix) {
                Cache::forget('community_caste_list'.$suffix);
                Cache::forget('community_sub_caste_list'.$suffix);
            }
            AdminActivityLog::create(['action' => 'communities_tulu_nadu_update', 'model_type' => Community::class, 'changes' => $this->report]);
        }

        foreach ($this->report as $line => $n) {
            $this->line(($dry ? '[dry run] ' : '').$line.': '.(is_array($n) ? json_encode($n) : $n));
        }

        if ($this->option('ask')) {
            $this->askMembers($dry, $notifications);
        }

        return self::SUCCESS;
    }

    private function updateCommunities(bool $dry): void
    {
        $hindu = fn () => Community::where('religion', 'Hindu');

        // Gowda: one community for Tulu and Arebhashe Gowdas
        $old = $hindu()->where('community_name', 'Gowda - Tulu')->first();
        if ($old && ! $hindu()->where('community_name', self::GOWDA)->exists()) {
            $this->report['renamed Gowda - Tulu'] = 1;
            if (! $dry) {
                $old->update(['community_name' => self::GOWDA]);
            }
        }

        // New communities, kept before "Other / Not Listed" (sort_order is a tinyint: ≤ 255)
        $order = (int) $hindu()->whereNotIn('community_name', self::OTHER)->max('sort_order');
        $added = [];
        foreach (self::ADD as $name => $subs) {
            if ($hindu()->where('community_name', $name)->exists()) {
                continue;
            }
            $added[] = $name;
            if (! $dry) {
                Community::create(['religion' => 'Hindu', 'community_name' => $name, 'sub_communities' => $subs, 'is_active' => true, 'sort_order' => min(255, ++$order)]);
            }
        }
        $this->report['communities added'] = $added;
        if (! $dry && $added) {
            $hindu()->whereIn('community_name', self::OTHER)->update(['sort_order' => min(255, $order + 1)]);
        }

        // Sub-castes
        $changed = [];
        foreach (self::SUBS as $name => $subs) {
            $c = $hindu()->where('community_name', $name)->first()
                ?? ($dry && $name === self::GOWDA ? $hindu()->where('community_name', 'Gowda - Tulu')->first() : null);
            if (! $c || ($c->sub_communities ?? []) === $subs) {
                continue;
            }
            $changed[] = $name;
            if (! $dry) {
                $c->update(['sub_communities' => $subs]);
            }
        }
        $this->report['sub-caste lists updated'] = $changed;

        // Not communities: off for new members (existing members keep the value until they choose)
        $off = $hindu()->whereIn('community_name', self::ASK)->where('is_active', true)->pluck('community_name')->all();
        $this->report['switched off'] = $off;
        if (! $dry) {
            $hindu()->whereIn('community_name', self::ASK)->update(['is_active' => false]);
        }
    }

    private function moveMembers(bool $dry): void
    {
        $ri = fn () => DB::table('religious_info');

        foreach (self::RENAMES as $from => $to) {
            $n = $ri()->where('caste', $from)->count();
            if ($n) {
                $this->report["members {$from} → {$to}"] = $n;
                if (! $dry) {
                    $ri()->where('caste', $from)->update(['caste' => $to, 'updated_at' => now()]);
                }
            }
        }

        // Typed into "Other"
        $typed = $ri()->where(fn ($q) => $q->whereIn('caste', self::OTHER)->orWhereNull('caste')->orWhere('caste', ''))
            ->whereNotNull('other_caste_name')->where('other_caste_name', '!=', '')
            ->get(['id', 'other_caste_name', 'sub_caste']);
        $moved = 0;
        foreach ($typed as $row) {
            $map = self::TYPED[mb_strtolower(trim($row->other_caste_name))] ?? null;
            if (! $map) {
                continue;
            }
            $moved++;
            if (! $dry) {
                $ri()->where('id', $row->id)->update([
                    'caste' => $map[0],
                    'sub_caste' => $map[1] ?? ($row->sub_caste ?: null),
                    'other_caste_name' => null,
                    'religion' => DB::raw("COALESCE(NULLIF(religion, ''), 'Hindu')"),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->report['typed "Other" castes moved'] = $moved;

        // "Other / Not Listed" + an SC sub-caste
        foreach (['mundala' => 'Mundala', 'adi dravida' => 'Adi Dravida'] as $sub => $proper) {
            $q = fn () => $ri()->whereIn('caste', self::OTHER)->whereRaw('LOWER(TRIM(sub_caste)) = ?', [$sub]);
            if ($n = $q()->count()) {
                $this->report["Other + {$proper} → Scheduled Caste (SC)"] = $n;
                if (! $dry) {
                    $q()->update(['caste' => 'Scheduled Caste (SC)', 'sub_caste' => $proper, 'updated_at' => now()]);
                }
            }
        }

        foreach (self::SUB_FIXES as [$caste, $sub, $proper]) {
            $q = fn () => $ri()->where('caste', $caste)->whereRaw('LOWER(TRIM(sub_caste)) = ?', [$sub])->where('sub_caste', '!=', $proper);
            if ($n = $q()->count()) {
                $this->report["{$caste}: sub-caste {$sub} → {$proper}"] = $n;
                if (! $dry) {
                    $q()->update(['sub_caste' => $proper, 'updated_at' => now()]);
                }
            }
        }

        // "Konkani" members who entered a GSB name
        $gsb = Community::where('religion', 'Hindu')->where('community_name', 'like', 'GSB%')->value('community_name');
        if ($gsb) {
            foreach (['prabhu' => 'Prabhu', 'gowda sarswath bramhin' => null] as $sub => $keep) {
                $q = fn () => $ri()->where('caste', 'Konkani')->whereRaw('LOWER(TRIM(sub_caste)) = ?', [$sub]);
                if ($n = $q()->count()) {
                    $this->report["Konkani + {$sub} → {$gsb}"] = $n;
                    if (! $dry) {
                        $q()->update(['caste' => $gsb, 'sub_caste' => $keep, 'updated_at' => now()]);
                    }
                }
            }
        }

        // Religion typed as "Muslim" under "Other"
        $q = fn () => $ri()->where('religion', 'Other')->whereRaw('LOWER(TRIM(other_religion_name)) = ?', ['muslim']);
        if ($n = $q()->count()) {
            $this->report['religion Other/Muslim → Muslim'] = $n;
            if (! $dry) {
                $q()->update(['religion' => 'Muslim', 'other_religion_name' => null, 'updated_at' => now()]);
            }
        }
    }

    private function movePartnerPreferences(bool $dry): void
    {
        $rows = DB::table('partner_preferences')->whereNotNull('caste')
            ->where(function ($q) {
                foreach (array_keys(self::RENAMES) as $from) {
                    $q->orWhere('caste', 'like', '%"'.$from.'"%');
                }
            })->get(['id', 'caste']);

        $n = 0;
        foreach ($rows as $row) {
            $list = json_decode($row->caste, true);
            if (! is_array($list)) {
                continue;
            }
            $new = array_values(array_unique(array_map(fn ($c) => self::RENAMES[$c] ?? $c, $list)));
            if ($new !== $list) {
                $n++;
                if (! $dry) {
                    DB::table('partner_preferences')->where('id', $row->id)->update(['caste' => json_encode($new), 'updated_at' => now()]);
                }
            }
        }
        $this->report['partner preferences updated'] = $n;
    }

    /** Once per member: in-app note + email asking them to choose their community. */
    private function askMembers(bool $dry, NotificationService $notifications): void
    {
        $members = User::query()
            ->whereHas('profile', fn ($q) => $q->whereNull('deleted_at'))
            ->whereHas('profile.religiousInfo', fn ($q) => $q->whereIn('caste', self::ASK))
            ->whereNull('staff_role_id')
            ->with('profile.religiousInfo')
            ->get();

        $asked = 0;
        foreach ($members as $user) {
            $already = DB::table('notifications')->where('user_id', $user->id)->where('type', 'community_update_request')->exists();
            if ($already) {
                continue;
            }
            $value = $user->profile->religiousInfo->caste;
            $asked++;
            if ($dry) {
                continue;
            }

            $notifications->send(
                $user,
                'community_update_request',
                'Please check your community',
                "Your caste / community is \"{$value}\", which isn't a community on its own. Please choose yours from the updated list in your profile.",
                null,
                ['url' => route('profile.show', ['section' => 'religious'])],
            );

            if ($user->email && ! $user->email_undeliverable_at) {
                try {
                    Mail::to($user->email)->send(new CommunityUpdateRequestMail($user, $value));
                } catch (\Throwable $e) {
                    $this->warn("Email to user {$user->id} failed: ".$e->getMessage());
                }
            }
        }

        $this->line(($dry ? '[dry run] ' : '')."members asked to choose their community: {$asked}");
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Community;
use App\Models\ReligiousInfo;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class UnlistedCommunities extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Unlisted Caste / Religion';
    protected static \UnitEnum|string|null $navigationGroup = 'Members';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Unlisted Caste / Religion entries';
    protected string $view = 'filament.pages.unlisted-communities';

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ReligiousInfo::query()
            ->whereHas('profile')
            ->where(function ($w) {
                foreach (['other_caste_name', 'other_religion_name', 'other_denomination_name'] as $col) {
                    $w->orWhere(fn ($o) => $o->whereNotNull($col)->where($col, '!=', ''));
                }
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    /** Group live members' free-text entries in $column by the typed value. */
    private function grouped(string $column): Collection
    {
        return ReligiousInfo::query()
            ->whereNotNull($column)->where($column, '!=', '')
            ->whereHas('profile')
            ->with('profile:id,matri_id,full_name')
            ->get()
            ->groupBy($column)
            ->map(fn ($g) => (object) [
                'value' => $g->first()->{$column},
                'count' => $g->count(),
                'members' => $g->pluck('profile')->filter()->values(),
            ])
            ->sortByDesc('count')
            ->values();
    }

    public function getUnlistedCastes(): Collection { return $this->grouped('other_caste_name'); }

    public function getUnlistedReligions(): Collection { return $this->grouped('other_religion_name'); }

    public function getUnlistedDenominations(): Collection { return $this->grouped('other_denomination_name'); }

    /**
     * Gotras / baris members typed that aren't in the list (Oct 2026: the
     * bari list grows from what members write), with the castes they're in.
     */
    public function getUnlistedGotras(): Collection
    {
        $known = array_map(fn ($v) => \App\Support\GotraBari::normalize($v), \App\Support\GotraBari::flatOptions());

        return ReligiousInfo::query()
            ->whereNotNull('gotra')->where('gotra', '!=', '')
            ->whereHas('profile')
            ->with('profile:id,matri_id,full_name')
            ->get()
            ->filter(function ($ri) use ($known) {
                $v = \App\Support\GotraBari::normalize($ri->gotra);

                return $v !== null && ! in_array($v, $known, true);
            })
            ->groupBy(fn ($ri) => \App\Support\GotraBari::normalize($ri->gotra))
            ->map(fn ($g) => (object) [
                'value' => trim($g->first()->gotra),
                'count' => $g->count(),
                'members' => $g->pluck('profile')->filter()->values(),
                'castes' => $g->map(fn ($ri) => $ri->display_caste)->filter()->unique()->values()->all(),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /** Sub-castes typed by members that aren't in ANY community's sub_communities. */
    public function getUnlistedSubCastes(): Collection
    {
        $known = Community::active()->get()
            ->flatMap(fn ($c) => $c->sub_communities ?? [])
            ->map(fn ($s) => mb_strtolower(trim($s)))
            ->unique()->all();
        $sentinels = ['other', 'other (not listed)', 'other / not listed'];
        $skip = array_merge($known, $sentinels);

        return ReligiousInfo::query()
            ->whereNotNull('sub_caste')->where('sub_caste', '!=', '')
            ->whereHas('profile')
            ->with('profile:id,matri_id,full_name')
            ->get()
            ->filter(fn ($ri) => ! in_array(mb_strtolower(trim($ri->sub_caste)), $skip, true))
            ->groupBy('sub_caste')
            ->map(fn ($g) => (object) [
                'value' => $g->first()->sub_caste,
                'count' => $g->count(),
                'members' => $g->pluck('profile')->filter()->values(),
            ])
            ->sortByDesc('count')
            ->values();
    }
}

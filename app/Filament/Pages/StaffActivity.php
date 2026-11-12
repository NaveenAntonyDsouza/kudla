<?php

namespace App\Filament\Pages;

use App\Support\Permissions;
use App\Support\StaffActivity as Activity;
use Filament\Pages\Page;

/**
 * Insights → Staff Activity (12 Nov 2026): calls, WhatsApp and other contacts
 * from the Notes popup, follow-ups, member work, per staff member and per day
 * (App\Support\StaffActivity). Whoever may read the Activity Log sees every
 * staff member; everyone else sees only their own figures ("My Activity").
 */
class StaffActivity extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.staff-activity';

    public string $range = 'today';
    public ?string $from = null;
    public ?string $to = null;
    /** The staff member whose days and notes are shown below the table. */
    public ?int $staffId = null;

    public static function seesEveryone(): bool
    {
        return Permissions::can('view_activity_log');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->staff_role_id !== null || $user->role === 'admin');
    }

    public static function getNavigationLabel(): string
    {
        return static::seesEveryone() ? 'Staff Activity' : 'My Activity';
    }

    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    public function mount(): void
    {
        $this->from = now(Activity::tz())->startOfMonth()->toDateString();
        $this->to = now(Activity::tz())->toDateString();
        if (! static::seesEveryone()) {
            $this->staffId = auth()->id();
        }
    }

    public function pick(?int $staffId): void
    {
        $this->staffId = static::seesEveryone() ? $staffId : auth()->id();
    }

    protected function getViewData(): array
    {
        if (! array_key_exists($this->range, Activity::RANGES)) {
            $this->range = 'today';
        }
        // whatever the browser sends, someone without the right sees only themselves
        $only = static::seesEveryone() ? null : (int) auth()->id();
        $selected = $only ?? $this->staffId;

        [$from, $to] = Activity::range($this->range, $this->from, $this->to);
        $rows = Activity::summary($from, $to, $only);
        $names = collect($rows)->pluck('name', 'staff_id');

        return [
            'everyone' => $only === null,
            'ranges' => Activity::RANGES,
            'periodLabel' => $from->isSameDay($to) ? $from->format('D j M Y') : $from->format('j M').' – '.$to->format('j M Y'),
            'rows' => $rows,
            'totals' => $this->totals($rows),
            'selected' => $selected && $names->has($selected) ? $selected : null,
            'selectedName' => $selected ? $names->get($selected) : null,
            'days' => $selected && $names->has($selected) ? Activity::daily($selected, $from, $to) : [],
            'notes' => $selected && $names->has($selected) ? Activity::notes($selected, $from, $to) : collect(),
            'noteTypes' => \App\Models\ProfileNote::NOTE_TYPES,
            'memberUrl' => fn (int $profileId) => \App\Filament\Resources\UserResource::getUrl('view', ['record' => $profileId]),
        ];
    }

    private function totals(array $rows): array
    {
        $sum = fn (string $k) => array_sum(array_column($rows, $k));

        return [
            'calls' => $sum('calls'),
            'whatsapp' => $sum('whatsapp'),
            'other_contacts' => $sum('other_contacts'),
            'members_contacted' => $sum('members_contacted'),
            'followups_done' => $sum('followups_done'),
            'followups_late' => $sum('followups_late'),
            'details_checked' => $sum('details_checked'),
        ];
    }
}

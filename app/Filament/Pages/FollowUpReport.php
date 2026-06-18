<?php

namespace App\Filament\Pages;

use App\Models\Profile;
use App\Models\ProfileNote;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class FollowUpReport extends Page
{
    protected static BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Follow-up Report';
    protected static \UnitEnum|string|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Follow-up Report';
    protected string $view = 'filament.pages.follow-up-report';

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_member');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ProfileNote::pendingFollowUp()
            ->whereHas('profile') // exclude follow-ups whose member was deleted
            ->where('follow_up_date', '<=', today())
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public function getOverdueFollowUps()
    {
        return ProfileNote::query()
            ->pendingFollowUp()
            ->whereHas('profile') // exclude follow-ups whose member was deleted
            ->where('follow_up_date', '<', today())
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser'])
            ->orderBy('follow_up_date')
            ->get();
    }

    public function getTodayFollowUps()
    {
        return ProfileNote::query()
            ->pendingFollowUp()
            ->whereHas('profile') // exclude follow-ups whose member was deleted
            ->whereDate('follow_up_date', today())
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser'])
            ->orderBy('follow_up_date')
            ->get();
    }

    public function getUpcomingFollowUps()
    {
        return ProfileNote::query()
            ->pendingFollowUp()
            ->whereHas('profile') // exclude follow-ups whose member was deleted
            ->where('follow_up_date', '>', today())
            ->where('follow_up_date', '<=', today()->addDays(7))
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser'])
            ->orderBy('follow_up_date')
            ->get();
    }

    /**
     * Follow-ups marked done today — shown in a collapsed section so staff/
     * managers can see what was handled and Undo a mistake. Resets each day.
     */
    public function getCompletedTodayFollowUps()
    {
        return ProfileNote::query()
            ->whereNotNull('follow_up_completed_at')
            ->whereDate('follow_up_completed_at', today())
            ->whereHas('profile') // exclude follow-ups whose member was deleted
            ->with(['profile.primaryPhoto', 'profile.user', 'adminUser', 'completedBy'])
            ->orderByDesc('follow_up_completed_at')
            ->get();
    }

    /**
     * "Mark Done" — stamps the follow-up complete and, in one step, lets staff
     * log what happened (a new interaction-log note) and schedule the next
     * follow-up. Mirrors the call → outcome → next-callback cycle the Leads
     * call-log already uses. The note id is passed as an argument when the
     * button is rendered per-row in the blade.
     */
    public function markDoneAction(): Action
    {
        return Action::make('markDone')
            ->label('Done')
            ->icon('heroicon-o-check')
            ->color('success')
            ->size('sm')
            ->modalHeading('Complete Follow-up')
            ->modalDescription('Log what happened and (optionally) schedule the next follow-up.')
            // Show the member's recent notes above the form so staff have context
            // while writing the outcome (chosen design: notes inside Done too).
            // Same shared history view as the members-list / View-page Notes popup.
            ->modalContent(fn (array $arguments) => view('filament.components.profile-notes-history', [
                'notes' => $this->notesFor($arguments['note'] ?? null, 5),
                'compact' => true,
            ]))
            ->modalSubmitActionLabel('Mark Done')
            ->form([
                Select::make('note_type')
                    ->label('Type')
                    ->options(ProfileNote::NOTE_TYPES)
                    ->default('call')
                    ->required(),
                Textarea::make('outcome')
                    ->label('Outcome / what happened')
                    ->rows(3)
                    ->placeholder('e.g. Spoke to member, will send 3 profiles by email.'),
                DatePicker::make('next_follow_up_date')
                    ->label('Next follow-up (optional)')
                    ->minDate(today())
                    ->helperText('Leave empty if no further follow-up is needed.'),
            ])
            ->action(function (array $arguments, array $data): void {
                $note = ProfileNote::find($arguments['note'] ?? null);
                if (! $note) {
                    return;
                }

                // Everything is one logical unit: stamp the original follow-up
                // done AND record the outcome / next follow-up. Wrap so a failure
                // can't leave it half-done.
                DB::transaction(function () use ($note, $data) {
                    $note->update([
                        'follow_up_completed_at' => now(),
                        'follow_up_completed_by' => auth()->id(),
                    ]);

                    $outcome = trim((string) ($data['outcome'] ?? ''));
                    $nextDate = $data['next_follow_up_date'] ?? null;

                    // Only write a new interaction-log note if the staff member
                    // actually logged something or scheduled a next follow-up.
                    if ($outcome !== '' || ! empty($nextDate)) {
                        ProfileNote::create([
                            'profile_id' => $note->profile_id,
                            'admin_user_id' => auth()->id(),
                            'note_type' => $data['note_type'] ?? 'call',
                            'note' => $outcome !== '' ? $outcome : 'Follow-up completed.',
                            'follow_up_date' => $nextDate ?: null,
                        ]);
                    }
                });
            })
            ->successNotificationTitle('Follow-up completed');
    }

    /**
     * "Undo" — reopen a follow-up marked done by mistake. Just clears the
     * completion stamp; the note returns to its date-based bucket. Does NOT
     * remove any outcome note that "Mark Done" may have created (that's a real
     * logged interaction).
     */
    public function undoFollowUpAction(): Action
    {
        return Action::make('undoFollowUp')
            ->label('Undo')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->size('sm')
            ->link()
            ->requiresConfirmation()
            ->modalHeading('Reopen follow-up?')
            ->modalDescription('This puts the follow-up back on the pending list.')
            ->action(function (array $arguments): void {
                $note = ProfileNote::find($arguments['note'] ?? null);
                if (! $note) {
                    return;
                }
                $note->update([
                    'follow_up_completed_at' => null,
                    'follow_up_completed_by' => null,
                ]);
            })
            ->successNotificationTitle('Follow-up reopened');
    }

    /**
     * "View Notes" — read-only popup of the member's note history. Rendered
     * per-row with the member id:
     * {{ ($this->notesAction)(['profile' => $note->profile_id]) }}.
     * Logging happens in "Done" (see markDoneAction), so this stays read-only;
     * the add-capable shared popup still lives on the members list / profile
     * page (App\Filament\Actions\ProfileNotesAction).
     */
    public function notesAction(): Action
    {
        // READ-ONLY on the follow-up report (owner preference): "View Notes"
        // just shows the member's note history — logging lives in "Done" (which
        // records the outcome and schedules the next follow-up). Read-only here
        // matches the "View" label and keeps a single add-form on the page.
        // (The members list / profile-page "Notes" button stays add-capable.)
        return Action::make('notes')
            ->label(function (array $arguments): string {
                $count = ProfileNote::where('profile_id', $arguments['profile'] ?? 0)->count();

                return 'View Notes'.($count > 0 ? ' · '.$count : '');
            })
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            // Blue (info), distinct from the orange add-capable "Notes" button on
            // the members list / profile page — signals this one is read-only.
            ->color('info')
            ->button()
            ->size('sm')
            ->modalHeading(fn (array $arguments): string => 'Notes — '
                .(Profile::find($arguments['profile'] ?? null)?->full_name ?? 'Member'))
            ->modalContent(fn (array $arguments) => view('filament.components.profile-notes-history', [
                'notes' => ProfileNote::where('profile_id', $arguments['profile'] ?? 0)
                    ->with('adminUser')->latest()->get(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * All notes for the member that owns the given note (newest first). The
     * report rows key off a single ProfileNote, so we resolve its profile_id
     * then pull that member's whole interaction log. $limit > 0 trims it for
     * the compact context shown inside the Done dialog.
     */
    private function notesFor($noteId, int $limit = 0)
    {
        $note = ProfileNote::find($noteId);
        if (! $note) {
            return collect();
        }

        $query = ProfileNote::where('profile_id', $note->profile_id)
            ->with('adminUser')
            ->orderByDesc('created_at');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }
}

<?php

namespace App\Filament\Actions;

use App\Models\Profile;
use App\Models\ProfileNote;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

/**
 * Single source of truth for the member "Notes" interaction-log popup:
 * full history on top + an append form (Type, Note, optional Follow-up date).
 *
 * Reused by ALL three places a member's notes are opened so they can never
 * drift apart again:
 *   - members list row action (UserResource)
 *   - member View page header action (ViewUser)
 *   - the Follow-up Report rows (FollowUpReport::notesAction)
 *
 * Member resolution works in every context:
 *   - On the members table / View page, the Action gets its Profile from the
 *     Livewire component automatically (getDefaultActionRecord → row / page
 *     record), injected as $record.
 *   - The Follow-up Report is a custom Page with no record binding, so $record
 *     is null there; that caller passes the member id as a `profile` argument
 *     when rendering the button. resolveProfile() handles both.
 *
 * Previously the View-page popup was a separate, thinner definition missing the
 * Type field, so notes logged there silently saved as "General" — a data bug
 * this consolidation fixes.
 */
class ProfileNotesAction
{
    public static function make(string $name = 'notes'): Action
    {
        return Action::make($name)
            ->label(function (?Profile $record, array $arguments): string {
                $profile = self::resolveProfile($record, $arguments);
                if (! $profile) {
                    return 'Notes';
                }

                // profile_notes_count is preloaded on the members table
                // (withCount); elsewhere (View page, Follow-up Report) it isn't,
                // so fall back to a count() there (one cheap query).
                $count = $profile->profile_notes_count ?? $profile->profileNotes()->count();

                return 'Notes'.($count > 0 ? ' · '.$count : '');
            })
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            ->color('warning')
            ->button()
            ->size('sm')
            ->modalHeading(fn (?Profile $record, array $arguments): string => 'Notes — '
                .(self::resolveProfile($record, $arguments)?->full_name ?? 'Member'))
            ->modalContent(function (?Profile $record, array $arguments) {
                $profile = self::resolveProfile($record, $arguments);

                return view('filament.components.profile-notes-history', [
                    'notes' => $profile
                        ? $profile->profileNotes()->with('adminUser')->latest()->get()
                        : collect(),
                ]);
            })
            ->form([
                Select::make('note_type')
                    ->label('Type')
                    ->options(ProfileNote::NOTE_TYPES)
                    ->required()
                    ->placeholder('Call / WhatsApp / …'),
                Textarea::make('note')
                    ->label('Note')
                    ->required()
                    ->rows(3)
                    ->placeholder('What happened on the call / chat? Any commitments or next steps?'),
                DatePicker::make('follow_up_date')
                    ->label('Follow-up date (optional)')
                    ->minDate(today()),
            ])
            ->modalSubmitActionLabel('Add Note')
            ->action(function (?Profile $record, array $arguments, array $data): void {
                $profile = self::resolveProfile($record, $arguments);
                if (! $profile) {
                    return;
                }

                ProfileNote::create([
                    'profile_id' => $profile->id,
                    'admin_user_id' => auth()->id(),
                    'note_type' => $data['note_type'] ?? 'general',
                    'note' => $data['note'],
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                ]);
            })
            ->successNotificationTitle('Note added');
    }

    /**
     * The member this popup is about — the injected record when the host
     * component provides one (table row / View page), otherwise the `profile`
     * id passed as a render argument (the Follow-up Report's custom page).
     */
    private static function resolveProfile(?Profile $record, array $arguments): ?Profile
    {
        if ($record instanceof Profile) {
            return $record;
        }

        $id = $arguments['profile'] ?? null;

        return $id ? Profile::find($id) : null;
    }
}

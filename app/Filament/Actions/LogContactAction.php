<?php

namespace App\Filament\Actions;

use App\Filament\Resources\UserResource;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Support\MemberContacts;
use App\Support\StaffActivity;
use App\Support\VerificationCalls;
use Closure;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View as SchemaView;

/**
 * The one box for writing down a contact with a member (simpler staff screens, owner
 * 25 Nov 2026): "Log call" and "Log WhatsApp" on the member page, "Notes" on All Members
 * and the View page, "Done" on the Follow-up Report. What it was (call, WhatsApp, email,
 * met in person, other), what happened, the call checklist on a new member's verification
 * call, a note and one "call again / follow up on" day. The rules: App\Support\MemberContacts.
 * Nothing is sent to the member from here.
 */
class LogContactAction
{
    /**
     * @param  string|null  $type  what it was, picked when the box opens ('call', 'whatsapp'), or null
     * @param  bool  $history  the member's notes above the form (the lists, the Follow-up Report)
     * @param  Closure|null  $before  runs first; the member page saves its form there and returns a warning when it could not
     * @param  bool  $followUpDone  "mark the follow-up done" ticked when the box opens (the Follow-up Report)
     */
    public static function make(string $name = 'notes', ?string $type = null, bool $history = true, ?Closure $before = null, bool $followUpDone = false): Action
    {
        return Action::make($name)
            ->label(function (?Profile $record, array $arguments): string {
                $profile = self::profile($record, $arguments);
                $count = $profile ? ($profile->profile_notes_count ?? $profile->profileNotes()->count()) : 0;

                return 'Notes'.($count > 0 ? ' · '.$count : '');
            })
            ->icon('heroicon-o-chat-bubble-bottom-center-text')
            ->color('warning')
            ->button()
            ->modalWidth('2xl')
            ->modalHeading(function (?Profile $record, array $arguments) use ($type): string {
                $profile = self::profile($record, $arguments);
                $who = $profile ? ($profile->matri_id.($profile->full_name ? ' · '.$profile->full_name : '')) : 'Member';

                return match ($type) {
                    'call' => 'Log call · '.$who,
                    'whatsapp' => 'Log WhatsApp · '.$who,
                    default => 'Notes · '.$who,
                };
            })
            ->modalDescription(fn (): ?string => $type === 'whatsapp' ? 'Send the message from your WhatsApp first, then save what you sent here. Nothing is sent from here.' : null)
            ->modalContent(function (?Profile $record, array $arguments) use ($history) {
                $profile = self::profile($record, $arguments);
                if (! $history || ! $profile || ! ProfileNotesAction::canView()) {
                    return null;
                }

                return view('filament.components.profile-notes-history', [
                    'notes' => $profile->profileNotes()->with('adminUser')->latest()->get(),
                ]);
            })
            ->fillForm(fn (): array => ['type' => $type, 'complete_follow_up' => $followUpDone])
            ->form(fn (?Profile $record, array $arguments): array => ProfileNotesAction::canAdd() ? self::schema(self::profile($record, $arguments)) : [])
            ->modalSubmitActionLabel($type === 'whatsapp' ? 'I sent it: save' : 'Save')
            ->modalSubmitAction(fn ($action) => ProfileNotesAction::canAdd() ? $action : false)
            ->visible(fn (): bool => ProfileNotesAction::canView() || ProfileNotesAction::canAdd())
            ->action(function (?Profile $record, array $arguments, array $data, Action $action) use ($before): void {
                $profile = self::profile($record, $arguments);
                $staff = auth()->user();
                if (! $profile || ! $staff || ! ProfileNotesAction::canAdd()) {
                    return;
                }
                $wasOnList = ($data['type'] ?? null) === 'call' && MemberContacts::workableCall($staff, $profile) !== null;
                $warning = $before ? $before() : null;

                try {
                    $message = MemberContacts::log($staff, $profile, $data);
                } catch (DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                    $action->halt();   // the box stays open with what was typed

                    return;
                }

                $done = Notification::make()->title($message)->success();
                if ($wasOnList && ($next = self::nextCallUrl($profile))) {
                    $done->persistent()->actions([
                        Action::make('nextCall')->label('Next call →')->button()->url($next),
                    ]);
                }
                $done->send();
                if ($warning) {
                    Notification::make()->title('The call is saved, but your changes to the profile are not')
                        ->body($warning)->warning()->persistent()->send();
                }
            });
    }

    /** The member: the row or page record, or the id the Follow-up Report passes ('profile' or 'note'). */
    public static function profile(?Profile $record, array $arguments): ?Profile
    {
        if ($record instanceof Profile) {
            return $record;
        }
        if (! empty($arguments['note'])) {
            return ProfileNote::find($arguments['note'])?->profile;
        }

        return ProfileNotesAction::resolveProfile(null, $arguments);
    }

    /** The next call due on this staff member's own list, after this member. */
    private static function nextCallUrl(Profile $profile): ?string
    {
        $staff = auth()->user();
        if (! VerificationCalls::isCaller($staff)) {
            return null;
        }
        $next = VerificationCalls::tab($staff, 'mine')
            ->where('verification_calls.profile_id', '!=', $profile->getKey())
            ->where('verification_calls.due_at', '<=', now())
            ->with('profile')
            ->first();

        return $next?->profile ? UserResource::profileWorkUrl($next->profile) : null;
    }

    private static function schema(?Profile $profile): array
    {
        if (! $profile) {
            return [];
        }
        $staff = auth()->user();
        $onList = MemberContacts::workableCall($staff, $profile) !== null;
        $open = MemberContacts::openCall($profile);
        $followUp = MemberContacts::pendingFollowUp($profile);
        $isCall = fn (Get $get): bool => $get('type') === 'call';
        $outcome = fn (Get $get): ?string => $get('type') === 'call' ? $get('outcome') : null;

        return [
            ToggleButtons::make('type')
                ->label('What was it?')
                ->options(MemberContacts::TYPES)
                ->inline()
                ->required()
                ->live(),

            // a verification call someone else makes: the note is kept, the call stays theirs
            SchemaView::make('filament.members.contact-box')
                ->viewData(['notice' => $open?->caller ? 'This member\'s verification call is given to '.$open->caller->name.'. Your note is saved, but the call stays on their list.' : null])
                ->visible(fn (Get $get): bool => $isCall($get) && $open !== null && ! $onList),

            ToggleButtons::make('outcome')
                ->label('What happened?')
                ->options(MemberContacts::outcomesFor($staff, $profile))
                ->inline()
                ->required($isCall)
                ->visible($isCall)
                ->live(),

            CheckboxList::make('tasks')
                ->label('On this call I')
                ->options(VerificationCalls::tasks())
                ->descriptions(MemberContacts::taskHints($profile))
                ->helperText('Nothing is ticked for you. The call counts as verified only when the first item is ticked; without it, it comes back on your list.')
                ->visible(fn (Get $get): bool => $onList && $outcome($get) === 'spoke'),

            // the link for "Sent them the search or community link"
            SchemaView::make('filament.members.contact-box')
                ->viewData(['link' => VerificationCalls::linkFor($profile)])
                ->visible(fn (Get $get): bool => $onList && $outcome($get) === 'spoke'),

            Checkbox::make('details_checked')
                ->label('Went through their details with them')
                ->visible(fn (Get $get): bool => ! $onList && $outcome($get) === 'spoke'),

            DateTimePicker::make('at')
                ->label('Call back on')
                ->seconds(false)
                ->native(false)
                ->timezone(StaffActivity::tz())
                ->minDate(now())
                ->required(fn (Get $get): bool => $outcome($get) === 'call_back')
                ->visible(fn (Get $get): bool => $outcome($get) === 'call_back'),

            Checkbox::make('sent_whatsapp')
                ->label('I sent them the "we tried to call you" message on WhatsApp')
                ->visible(fn (Get $get): bool => $outcome($get) === 'no_answer'),

            ToggleButtons::make('whatsapp_kind')
                ->label('What did you send?')
                ->options(MemberContacts::whatsappKinds())
                ->inline()
                ->required(fn (Get $get): bool => $get('type') === 'whatsapp')
                ->visible(fn (Get $get): bool => $get('type') === 'whatsapp')
                ->live(),

            // the number and the ready-made text, to copy into WhatsApp
            SchemaView::make('filament.members.contact-box')
                ->viewData(fn (Get $get): array => [
                    'whatsapp' => true,
                    'number' => MemberContacts::whatsappNumber($profile),
                    'text' => MemberContacts::readyText($profile, $get('type') === 'whatsapp' ? $get('whatsapp_kind') : 'missed'),
                ])
                ->visible(fn (Get $get): bool => $get('type') === 'whatsapp' || $outcome($get) === 'no_answer'),

            Textarea::make('note')
                ->label(fn (Get $get): string => match (true) {
                    $outcome($get) === 'mismatch' => 'What did not match?',
                    $outcome($get) === 'not_looking' => 'What did they say?',
                    $get('type') === 'whatsapp' && $get('whatsapp_kind') === 'other' => 'What did you send?',
                    in_array($get('type'), ['email', 'meeting', 'general'], true) => 'What happened?',
                    default => 'Anything else (optional)',
                })
                ->placeholder('What they said, what to do next…')
                ->rows(3)
                ->maxLength(MemberContacts::NOTE_MAX)
                ->required(fn (Get $get): bool => in_array($outcome($get), ['mismatch', 'not_looking'], true)
                    || in_array($get('type'), ['email', 'meeting', 'general'], true)
                    || ($get('type') === 'whatsapp' && $get('whatsapp_kind') === 'other')),

            DatePicker::make('date')
                ->label(fn (Get $get): string => $isCall($get) && $onList ? 'Call again on (optional)' : 'Follow up on (optional)')
                ->helperText(fn (Get $get): ?string => $isCall($get) && $onList
                    ? 'While the call is still open, this is when it comes back on the list. Once it is finished, it becomes a follow-up.'
                    : 'Shows on the Follow-up Report on that day.')
                ->native(false)
                ->minDate(today())
                ->visible(fn (Get $get): bool => filled($get('type')) && $outcome($get) !== 'call_back'),

            Checkbox::make('complete_follow_up')
                ->label($followUp ? 'Mark the follow-up of '.$followUp->follow_up_date->format('j M Y').' done' : 'Mark the follow-up done')
                ->visible($followUp !== null),
        ];
    }
}

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AdminActivityLog;
use App\Models\ContactInfo;
use App\Models\EducationDetail;
use App\Models\FamilyDetail;
use App\Models\LifestyleInfo;
use App\Models\LocationInfo;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ProfileNote;
use App\Models\ReligiousInfo;
use App\Models\SocialMediaLink;
use App\Models\VerificationCall;
use App\Services\ProfileCompletionService;
use App\Support\Permissions;
use App\Support\StaffActivity;
use App\Support\VerificationCalls;
use App\Traits\LogsAdminActivity;
use DomainException;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
| Staff spend most of their working time on this page, often with the member on
| the phone, checking and updating details during the call (member edit page
| review, 5 Nov 2026). So: a member card above the tabs (who this is, how to
| reach them, what is still empty, the last call); the status changes as header
| buttons, each with a confirm and its own staff right (they were switches inside
| the form that anyone who could edit could flip, unlogged); Save always in view,
| and staying on this page; a warning before leaving with unsaved changes; and
| every save logged with the fields it changed. 6 Nov 2026: "Save and mark checked
| on call" next to Save records that the details were gone through with the
| member, also when nothing needed changing.
*/
class EditUser extends EditRecord
{
    use LogsAdminActivity;

    protected static string $resource = UserResource::class;

    public static bool $formActionsAreSticky = true;

    protected ?bool $hasUnsavedDataChangesAlert = true;

    /** The profile's parts as named in the edit log ("Basic" = the profile itself, "Login" = the account). */
    private const LOGGED_PARTS = [
        'religiousInfo' => 'Religion',
        'educationDetail' => 'Education & job',
        'familyDetail' => 'Family',
        'locationInfo' => 'Location',
        'contactInfo' => 'Contact',
        'differentlyAbledInfo' => 'Differently abled',
        'lifestyleInfo' => 'Lifestyle',
        'socialMediaLink' => 'Social media',
        'partnerPreference' => 'Partner preferences',
    ];

    /** Parts and fields logged only as "changed": contact details, health details and documents stay out of the log. */
    private const LOGGED_WITHOUT_VALUES = ['Login', 'Contact', 'Social media', 'Differently abled'];

    private const LOGGED_WITHOUT_VALUES_FIELDS = ['Basic: creator contact number', 'Religion: jathakam upload url'];

    /**
     * Profile completion sections → [the tab where staff fill them in (null: the Photos list below
     * the tabs), what the completion rule looks for there (ProfileCompletionService::sectionDoneMap)].
     * 6 Nov 2026: the card said "Family" for a member whose Family tab was full except the
     * father's name, the one field the rule checks.
     */
    private const SECTION_TABS = [
        'basic_info' => ['Basic', 'name, gender or date of birth'],
        'contact' => ['Contact', 'contact person or WhatsApp'],
        'religious' => ['Religion & Community', 'religion'],
        'education' => ['Education & Job', 'education or job'],
        'family_details' => ['Family', "father's name"],
        'location' => ['Location', 'country'],
        'partner_preferences' => ['Partner Preferences', 'age range or religion'],
        'lifestyle' => ['Lifestyle', 'diet or hobbies'],
        'photo' => [null, 'Photo (Photos list below)'],
    ];

    /** @var array<string, mixed> the values before Save (see logChangedFields) */
    private array $valuesBeforeSave = [];

    /** @var array<string, array<string, mixed>> per part (LOGGED_PARTS key), the values afterSave() wrote */
    private array $writtenValues = [];

    /** True while saveAndMarkChecked() saves: afterSave() then records the check, once everything is saved. */
    private bool $markCheckedOnSave = false;

    /** After a check was recorded: how many fields that save changed. */
    private ?int $checkedWithChanges = null;

    /** This request's verification call row ([] before it is looked up). */
    private array $verificationRow = [];

    public function getHeading(): string
    {
        return $this->record->full_name ?: (string) $this->record->matri_id;
    }

    /** The member card sits between the header and the tabs. */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('filament.members.edit-card')->viewData(fn (): array => $this->memberCard()),
            $this->getFormContentComponent(),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // log the call while it happens: the shared Notes popup (history, Type, Note, Follow-up)
            \App\Filament\Actions\ProfileNotesAction::make('notes'),

            Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(function (): ?string {
                    $phone = preg_replace('/[^0-9]/', '', (string) $this->record->user?->phone);
                    if (strlen($phone) === 10) {
                        $phone = '91' . $phone;
                    }

                    return $phone !== '' ? 'https://wa.me/' . $phone : null;
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => (bool) $this->record->user?->phone),

            // the end of a verification call that went through: tick what was done, add
            // anything else, and the call is recorded and closed in one go
            Actions\Action::make('vc_done')
                ->label('Call done')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->button()
                ->modalHeading(fn (): string => 'What did you do on this call? — '.$this->record->matri_id)
                ->modalDescription('Tick only what you actually did, however you did it — a photo you asked for on your own phone counts. Save any changes to the details first.')
                ->modalSubmitActionLabel('Record the call')
                ->visible(fn (): bool => ($row = $this->verificationRow()) !== null && $row->isOpen() && VerificationCalls::mayWork(auth()->user(), $row))
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('tasks')
                        ->label('On this call I')
                        ->options(VerificationCalls::TASKS)
                        ->bulkToggleable(),
                    Textarea::make('notes')
                        ->label('Anything else (optional)')
                        ->placeholder('What they said, what to do next…')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data): void {
                    $this->runVerification(fn (VerificationCall $row) => VerificationCalls::finishCall(
                        auth()->user(),
                        $row,
                        (array) ($data['tasks'] ?? []),
                        $data['notes'] ?? null,
                    ));
                }),

            // a new member's verification call (Today → Verification Calls): what happened on
            // it, when it was not "Save and mark checked on call"
            Actions\ActionGroup::make([
                $this->callResult('no_answer', 'heroicon-o-phone-x-mark')
                    ->requiresConfirmation()
                    ->modalDescription('Records a try. The call comes back on the list tomorrow at 10; after ' . VerificationCalls::MAX_NO_ANSWER . ' tries without an answer it closes as "could not reach". Send the "we tried to call you" WhatsApp message from the member card too.'),
                $this->callResult('call_back', 'heroicon-o-clock')
                    ->form([
                        DateTimePicker::make('at')->label('Call back on')->required()->seconds(false)->native(false)
                            ->timezone(StaffActivity::tz())->minDate(now()),
                        Textarea::make('note')->label('Note (optional)')->rows(2)->maxLength(500),
                    ]),
                $this->callResult('wrong_number', 'heroicon-o-no-symbol')
                    ->form([Textarea::make('note')->label('Note (optional)')->rows(2)->maxLength(500)]),
                $this->callResult('mismatch', 'heroicon-o-exclamation-triangle')
                    ->modalDescription('The call closes and a manager sees it under "Needs a check". The profile itself stays as it is.')
                    ->form([Textarea::make('note')->label('What did not match?')->required()->rows(3)->maxLength(500)]),
                $this->callResult('not_looking', 'heroicon-o-hand-raised')
                    ->form([Textarea::make('note')->label('What did they say?')->placeholder('For example: already settled')->required()->rows(2)->maxLength(500)]),
                Actions\Action::make('vc_reopen')
                    ->label('Put back on the calls list')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => ($row = $this->verificationRow()) !== null && ! $row->isOpen() && $row->status !== 'verified')
                    ->action(function (): void {
                        $this->runVerification(fn (VerificationCall $row) => VerificationCalls::reopen(auth()->user(), $row) ?? 'Back on the calls list.');
                    }),
            ])
                ->label('Call result')
                ->icon('heroicon-m-phone')
                ->iconPosition('after')
                ->button()
                ->color('warning')
                ->visible(fn (): bool => ($row = $this->verificationRow()) !== null && VerificationCalls::mayWork(auth()->user(), $row)),

            // the most common status change has its own button; the rest are under Status
            $this->statusAction('approve', 'Approve', 'heroicon-o-check-badge', 'success', ['is_approved' => true], 'profile_approved', 'Profile approved',
                'Other members will see this profile, and the member gets the "profile approved" email.')
                ->visible(fn (): bool => ! $this->record->is_approved && Permissions::can('approve_member')),

            Actions\ActionGroup::make([
                $this->statusAction('unapprove', 'Mark as pending (unapprove)', 'heroicon-o-clock', 'warning', ['is_approved' => false], 'profile_unapproved', 'Marked as pending',
                    'Other members stop seeing this profile until it is approved again.')
                    ->visible(fn (): bool => $this->record->is_approved && Permissions::can('unapprove_member')),
                $this->statusAction('hide', 'Hide profile', 'heroicon-o-eye-slash', 'gray', ['is_hidden' => true], 'profile_hidden', 'Profile hidden',
                    'This is the member\'s own "Hide my profile" setting: other members stop seeing the profile. The member can still sign in, and can unhide it.')
                    ->visible(fn (): bool => ! $this->record->is_hidden && Permissions::can('toggle_active')),
                $this->statusAction('unhide', 'Unhide profile', 'heroicon-o-eye', 'gray', ['is_hidden' => false], 'profile_unhidden', 'Profile unhidden',
                    'Turns off the member\'s "Hide my profile" setting: other members can see the profile again (if it is active and approved).')
                    ->visible(fn (): bool => (bool) $this->record->is_hidden && Permissions::can('toggle_active')),
                $this->statusAction('idVerified', 'Mark ID proof verified', 'heroicon-o-identification', 'success', ['id_proof_verified' => true], 'profile_id_verified', 'Marked ID proof verified',
                    'Records that staff checked this member\'s ID proof.')
                    ->visible(fn (): bool => ! $this->record->id_proof_verified && Permissions::can('id_proof_approval')),
                $this->statusAction('idUnverified', 'Remove ID proof verified', 'heroicon-o-identification', 'gray', ['id_proof_verified' => false], 'profile_id_unverified', 'ID proof verified mark removed',
                    'The profile is no longer marked as ID-proof verified.')
                    ->visible(fn (): bool => (bool) $this->record->id_proof_verified && Permissions::can('id_proof_approval')),
                $this->statusAction('deactivate', 'Deactivate', 'heroicon-o-x-circle', 'danger', ['is_active' => false], 'profile_deactivated', 'Profile deactivated',
                    'Other members stop seeing this profile. The member can still sign in.')
                    ->visible(fn (): bool => $this->record->is_active && Permissions::can('toggle_active')),
                $this->statusAction('activate', 'Activate', 'heroicon-o-check-circle', 'success', ['is_active' => true], 'profile_activated', 'Profile activated',
                    fn (): string => $this->record->account_deleted_at
                        ? 'The member deleted this account; activating it cancels the deletion and the profile shows to other members again.'
                        : 'Other members can see this profile again (if it is approved, not hidden and not suspended).')
                    ->visible(fn (): bool => ! $this->record->is_active && Permissions::can('toggle_active')),
            ])
                ->label('Status')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition('after')
                ->button()
                ->color('gray'),

            // send a pending profile back with a reason (pending profiles only)
            \App\Filament\Actions\RequestProfileChangesAction::make(),

            // see the site as this member sees it (view only, recorded)
            \App\Filament\Actions\LoginAsMemberAction::make(),

            Actions\ViewAction::make(),
        ];
    }

    /**
     * Save, and the button for the end of a call: it saves what was corrected and records
     * that the details were checked with the member, also when nothing needed changing.
     * A plain Livewire call like Save, so a field that does not validate shows its error
     * on the form and nothing is marked.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Actions\Action::make('saveChecked')
                ->label('Save and mark checked on call')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->tooltip('After going through the details with the member or their family on a call: saves any changes, and records that the details were checked today. For a new member, this is their verification call: verified.')
                ->action('saveAndMarkChecked'),
            $this->getCancelFormAction(),
        ];
    }

    public function saveAndMarkChecked(): void
    {
        $this->markCheckedOnSave = true;
        $this->checkedWithChanges = null;

        try {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
        } finally {
            $this->markCheckedOnSave = false;
        }

        if ($this->checkedWithChanges !== null) {
            Notification::make()
                ->success()
                ->title($this->checkedWithChanges === 0
                    ? 'Marked as checked on a call. Nothing needed changing.'
                    : 'Saved, and marked as checked on a call.')
                ->send();
        }
    }

    /**
     * "Details checked on a call": when and by whom on the profile (the member card, the
     * members list and its filter), and every check in the Activity Log. Written straight
     * to the table at the end of a save that went through: a check is not an edit, so the
     * profile's updated time and its model hooks stay as they are.
     */
    private function markDetailsChecked(int $fieldsChanged): void
    {
        $mark = ['details_checked_at' => now(), 'details_checked_by' => auth()->id()];
        DB::table('profiles')->where('id', $this->record->getKey())->update($mark);
        $this->record->forceFill($mark)->syncOriginalAttributes(array_keys($mark));

        self::logActivity('member_details_checked', $this->record, ['fields_changed' => $fieldsChanged]);
        $this->checkedWithChanges = $fieldsChanged;

        // a new member's verification call: checked on a call = verified
        VerificationCalls::checkedOnCall(auth()->user(), $this->record);
        $this->verificationRow = [];
    }

    /**
     * One status change: a confirm that says what happens, the change through the model
     * (so its hooks run: the approval email, cancelling a deletion), and the activity log.
     * Unsaved form edits stay on the page.
     */
    private function statusAction(string $name, string $label, string $icon, string $color, array $change, string $logAction, string $done, string | \Closure $description): Actions\Action
    {
        return Actions\Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->modalHeading(fn (): string => "{$label}: {$this->record->full_name} ({$this->record->matri_id})?")
            ->modalDescription($description)
            ->modalSubmitActionLabel($label)
            ->action(function () use ($change, $logAction): void {
                $this->record->update($change);
                self::logActivity($logAction, $this->record);
            })
            ->successNotificationTitle($done);
    }

    /** This member's verification call, when they are on the list (looked up once per request). */
    private function verificationRow(): ?VerificationCall
    {
        if (! array_key_exists('row', $this->verificationRow)) {
            $this->verificationRow['row'] = VerificationCalls::enabled()
                ? VerificationCall::with('caller')->firstWhere('profile_id', $this->record->getKey())?->setRelation('profile', $this->record)
                : null;
        }

        return $this->verificationRow['row'];
    }

    /** One "Call result" item: records it through VerificationCalls::record(). */
    private function callResult(string $result, string $icon): Actions\Action
    {
        return Actions\Action::make('vc_' . $result)
            ->label(VerificationCalls::RESULTS[$result])
            ->icon($icon)
            ->modalHeading(fn (): string => VerificationCalls::RESULTS[$result] . ': ' . $this->record->matri_id)
            ->visible(fn (): bool => (bool) $this->verificationRow()?->isOpen())
            ->action(function (array $data) use ($result): void {
                $this->runVerification(fn (VerificationCall $row) => VerificationCalls::record(
                    auth()->user(),
                    $row,
                    $result,
                    $data['note'] ?? null,
                    filled($data['at'] ?? null) ? \Illuminate\Support\Carbon::parse($data['at'], config('app.timezone')) : null,
                ));
            });
    }

    private function runVerification(callable $work): void
    {
        $row = $this->verificationRow();
        if (! $row) {
            return;
        }
        try {
            $message = $work($row);
            Notification::make()->title(is_string($message) ? $message : 'Noted.')->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
        $this->verificationRow = [];
    }

    /** A WhatsApp message on the member card was opened (missed, photo, matches): note it in the history. */
    public function verificationWhatsApp(string $kind): void
    {
        try {
            VerificationCalls::logWhatsApp(auth()->user(), $this->record, $kind);
            Notification::make()->title('Noted in the member\'s history.')->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /** Facts for the member card (resources/views/filament/members/edit-card.blade.php). */
    private function memberCard(): array
    {
        $profile = $this->record;
        $profile->loadMissing(['user', 'primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo', 'contactInfo']);
        $user = $profile->user;
        $rel = $profile->religiousInfo;
        $loc = $profile->locationInfo;
        $cont = $profile->contactInfo;

        $phone = (string) ($user?->phone ?? '');
        $digits = fn (?string $number): string => substr(preg_replace('/[^0-9]/', '', (string) $number), -10);
        $whatsapp = $cont?->whatsapp_number;

        // places say what they are (6 Nov 2026: "Mangalore, Bangalore" was native place + native
        // district, read as where he lives; labelled, a mixed-up entry shows on the call)
        $edu = $profile->educationDetail;
        $from = collect([$loc?->native_place, $loc?->native_district])->filter()->unique()->implode(', ');
        $worksIn = collect([
            $edu?->working_city ?: $edu?->working_district,
            filled($edu?->working_country) && $edu->working_country !== 'India' ? $edu->working_country : null,
        ])->filter()->unique()->implode(', ');
        $livesIn = filled($loc?->residing_country) && $loc->residing_country !== 'India' && ! str_contains($worksIn, $loc->residing_country)
            ? $loc->residing_country : null;

        $membership = $user?->activeMembership();
        $pills = [
            $profile->is_active ? ['Active', 'success'] : ['Inactive', 'danger'],
            $profile->is_approved ? ['Approved', 'success'] : ['Pending approval', 'warning'],
        ];
        if ($profile->is_hidden) {
            $pills[] = ['Hidden', 'warning'];
        }
        if (in_array($profile->suspension_status, ['suspended', 'banned'], true)) {
            $pills[] = [ucfirst($profile->suspension_status), 'danger'];
        }
        if ($profile->account_deleted_at) {
            $pills[] = ['Account deleted ' . $profile->account_deleted_at->displayTz()->format('j M Y'), 'danger'];
        }
        $pills[] = $profile->id_proof_verified ? ['ID proof verified', 'success'] : ['ID proof not verified', 'gray'];
        $pills[] = $membership
            ? [($membership->plan?->plan_name ?? 'Paid') . ($membership->ends_at ? ' till ' . $membership->ends_at->format('j M Y') : ''), 'primary']
            : ['Free plan', 'gray'];
        $pct = (int) $profile->profile_completion_pct;
        $pills[] = ["Profile {$pct}% filled", $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger')];

        // what is still empty, in the order of the tabs
        $missing = app(ProfileCompletionService::class)->detectMissingSections($profile);
        $ask = [];
        foreach (self::SECTION_TABS as $section => [$tab, $what]) {
            if (isset($missing[$section])) {
                $ask[] = [
                    'label' => $tab ? "{$tab}: {$what}" : $what,
                    'tab' => $tab ? UserResource::tabKey($tab) : null,
                ];
            }
        }

        $lastCall = ProfileNote::where('profile_id', $profile->id)->where('note_type', 'call')->with('adminUser')->latest()->first();
        $followUp = ProfileNote::where('profile_id', $profile->id)->whereNotNull('follow_up_date')->whereNull('follow_up_completed_at')
            ->orderBy('follow_up_date')->first();
        $lastEdit = AdminActivityLog::where('action', 'member_profile_updated')->where('model_type', 'Profile')->where('model_id', $profile->id)
            ->with('admin')->latest()->first();
        $checkedAt = $profile->details_checked_at?->displayTz();

        // what the member typed after "Other", marked as typed (6 Nov 2026: the card said
        // "Hindu, Other / Not Listed" for a member who had typed "Shetty")
        $typed = fn (?string $pick, ?string $typedValue, bool $isOther): ?string => $isOther && filled($typedValue) ? $typedValue . ' (typed in)' : $pick;
        $religion = $typed($rel?->religion, $rel?->other_religion_name, $rel?->religion === 'Other');
        $community = $typed($rel?->caste, $rel?->other_caste_name, ReligiousInfo::isOtherCaste($rel?->caste))
            ?: $typed($rel?->denomination, $rel?->other_denomination_name, $rel?->denomination === 'Other')
            ?: $rel?->muslim_community;

        return [
            'photo' => $profile->primaryPhoto?->thumb_url,
            'matriId' => $profile->matri_id,
            'facts' => collect([
                $profile->age ? $profile->age . ' yrs' : null,
                $profile->gender ? ucfirst($profile->gender) : null,
                \App\Support\MaritalStatus::toSiteValue($profile->marital_status),
                collect([$religion, $community])->filter()->implode(', ') ?: null,
                $from !== '' ? 'from ' . $from : null,
                $edu?->occupation,
                $worksIn !== '' ? 'works in ' . $worksIn : null,
                $livesIn ? 'lives in ' . $livesIn : null,
            ])->filter()->values()->all(),
            'pills' => $pills,
            'phone' => $phone !== '' ? $phone : null,
            'phoneHref' => $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : null,
            'whatsapp' => filled($whatsapp) && $digits($whatsapp) !== $digits($phone) ? $whatsapp : null,
            // "Ramesh (Father)", or just "Self" when there is no name
            'contactPerson' => filled($cont?->contact_person)
                ? trim($cont->contact_person . (filled($cont->contact_relationship) ? ' (' . $cont->contact_relationship . ')' : ''))
                : ($cont?->contact_relationship ?: null),
            'callTime' => $cont?->preferred_call_time,
            'createdBy' => collect([filled($profile->created_by) ? ucfirst($profile->created_by) : null, $profile->creator_name])->filter()->unique()->implode(', ') ?: null,
            'lastLogin' => $user?->last_login_at ? $user->last_login_at->diffForHumans() : 'never',
            'ask' => $ask,
            'lastCall' => $lastCall ? [
                'when' => $lastCall->created_at->displayTz()->format('j M Y, g:i A'),
                'by' => $lastCall->adminUser?->name,
                'note' => Str::limit((string) $lastCall->note, 90),
            ] : null,
            'followUp' => $followUp ? [
                'date' => $followUp->follow_up_date->format('j M Y'),
                'late' => $followUp->follow_up_date->lt(today()),
            ] : null,
            'lastEdit' => $lastEdit ? [
                'when' => $lastEdit->created_at->displayTz()->format('j M Y, g:i A'),
                'by' => $lastEdit->admin?->name,
            ] : null,
            // on Today → Verification Calls: where the call stands, and the WhatsApp messages
            'verification' => ($verification = VerificationCalls::card(auth()->user(), $profile))
                ? $verification + ['listUrl' => \App\Filament\Pages\VerificationCalls::getUrl()]
                : null,
            // "Save and mark checked on call" (markDetailsChecked)
            'checked' => $checkedAt ? [
                'when' => $checkedAt->format('j M Y'),
                'ago' => $checkedAt->isToday() ? 'today' : $checkedAt->diffForHumans(),
                'by' => $profile->details_checked_by ? \App\Models\User::whereKey($profile->details_checked_by)->value('name') : null,
            ] : null,
        ];
    }

    /**
     * Load all related data into form fields.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $profile = $this->record;

        // User contact
        $user = $profile->user;
        $data['user_email'] = $user?->email;
        $data['user_phone'] = $user?->phone;

        // Religious info
        $rel = $profile->religiousInfo;
        $data['rel_religion'] = $rel?->religion;
        $data['rel_denomination'] = $rel?->denomination;
        $data['rel_other_denomination_name'] = $rel?->other_denomination_name;
        $data['rel_diocese'] = $rel?->diocese;
        $data['rel_diocese_name'] = $rel?->diocese_name;
        $data['rel_parish'] = $rel?->parish_name_place;
        $data['rel_caste'] = $rel?->caste;
        $data['rel_other_caste_name'] = $rel?->other_caste_name;
        $data['rel_sub_caste'] = $rel?->sub_caste;
        $data['rel_gotra'] = $rel?->gotra;
        $data['rel_nakshatra'] = $rel?->nakshatra;
        $data['rel_rashi'] = $rel?->rashi;
        $data['rel_manglik'] = $rel?->dosh;
        $data['rel_muslim_sect'] = $rel?->muslim_sect;
        $data['rel_muslim_community'] = $rel?->muslim_community;
        $data['rel_jain_sect'] = $rel?->jain_sect;
        $data['rel_religious_observance'] = $rel?->religious_observance;
        $data['rel_other_religion_name'] = $rel?->other_religion_name;
        $data['rel_time_of_birth'] = $rel?->time_of_birth;
        $data['rel_place_of_birth'] = $rel?->place_of_birth;
        $data['rel_jathakam'] = $rel?->jathakam_upload_url;

        // Education
        $edu = $profile->educationDetail;
        $data['edu_highest_education'] = $edu?->highest_education;
        $data['edu_education_level'] = $edu?->education_level;
        $data['edu_education_detail'] = $edu?->education_detail;
        $data['edu_college_name'] = $edu?->college_name;
        $data['edu_occupation'] = $edu?->occupation;
        $data['edu_occupation_detail'] = $edu?->occupation_detail;
        $data['edu_employment_category'] = $edu?->employment_category;
        $data['edu_employer_name'] = $edu?->employer_name;
        $data['edu_annual_income'] = $edu?->annual_income;
        $data['edu_working_country'] = $edu?->working_country;
        $data['edu_working_state'] = $edu?->working_state;
        $data['edu_working_district'] = $edu?->working_district;
        // typed twins for a country other than India (UserResource::stateDistrictFields)
        $data['edu_working_state_text'] = $edu?->working_state;
        $data['edu_working_district_text'] = $edu?->working_district;
        $data['edu_working_city'] = $edu?->working_city;

        // Family
        $fam = $profile->familyDetail;
        $data['fam_father_name'] = $fam?->father_name;
        $data['fam_father_occupation'] = $fam?->father_occupation;
        $data['fam_father_house_name'] = $fam?->father_house_name;
        $data['fam_father_native_place'] = $fam?->father_native_place;
        $data['fam_mother_name'] = $fam?->mother_name;
        $data['fam_mother_occupation'] = $fam?->mother_occupation;
        $data['fam_mother_house_name'] = $fam?->mother_house_name;
        $data['fam_mother_native_place'] = $fam?->mother_native_place;
        $data['fam_family_status'] = $fam?->family_status;
        $data['fam_brothers_married'] = $fam?->brothers_married;
        $data['fam_brothers_unmarried'] = $fam?->brothers_unmarried;
        $data['fam_brothers_priest'] = $fam?->brothers_priest;
        $data['fam_sisters_married'] = $fam?->sisters_married;
        $data['fam_sisters_unmarried'] = $fam?->sisters_unmarried;
        $data['fam_sisters_nun'] = $fam?->sisters_nun;
        $data['fam_candidate_asset_details'] = $fam?->candidate_asset_details;
        $data['fam_about_family'] = $fam?->about_candidate_family;

        // Location
        $loc = $profile->locationInfo;
        $data['loc_residing_country'] = $loc?->residing_country;
        $data['loc_native_country'] = $loc?->native_country;
        $data['loc_native_state'] = $loc?->native_state;
        $data['loc_native_district'] = $loc?->native_district;
        $data['loc_native_state_text'] = $loc?->native_state;
        $data['loc_native_district_text'] = $loc?->native_district;
        $data['loc_native_place'] = $loc?->native_place;
        $data['loc_residency_status'] = $loc?->residency_status;
        $data['loc_pin_zip_code'] = $loc?->pin_zip_code;
        $data['loc_outstation_from'] = $loc?->outstation_leave_date_from;
        $data['loc_outstation_to'] = $loc?->outstation_leave_date_to;

        // Contact Info
        $cont = $profile->contactInfo;
        $data['cont_whatsapp'] = $cont?->whatsapp_number;
        $data['cont_custodian_name'] = $cont?->contact_person;
        $data['cont_custodian_relation'] = $cont?->contact_relationship;
        $data['cont_preferred_call_time'] = $cont?->preferred_call_time;
        $data['cont_communication_address'] = $cont?->communication_address;
        $data['cont_pin_zip_code'] = $cont?->pincode ?? $cont?->present_pin_zip_code;
        $data['cont_reference_name'] = $cont?->reference_name;
        $data['cont_reference_mobile'] = $cont?->reference_mobile;
        $data['cont_secondary_phone'] = $cont?->secondary_phone;
        $data['cont_residential_phone_number'] = $cont?->residential_phone_number;
        $data['cont_alternate_email'] = $cont?->alternate_email;
        $data['cont_reference_relationship'] = $cont?->reference_relationship;
        $data['cont_present_address'] = $cont?->present_address;
        $data['cont_present_pin_zip_code'] = $cont?->present_pin_zip_code;
        $data['cont_permanent_address'] = $cont?->permanent_address;
        $data['cont_permanent_pin_zip_code'] = $cont?->permanent_pin_zip_code;

        // Differently-abled details (as on the member's own page)
        $da = $profile->differentlyAbledInfo;
        $data['da_category'] = $da?->category;
        $data['da_specify'] = $da?->specify;
        $data['da_description'] = $da?->description;

        // Lifestyle
        $life = $profile->lifestyleInfo;
        $data['life_diet'] = $life?->diet;
        $data['life_smoking'] = $life?->smoking;
        $data['life_drinking'] = $life?->drinking;
        $data['life_cultural_background'] = $life?->cultural_background;
        $data['life_hobbies'] = $life?->hobbies ?? [];
        $data['life_interests'] = $life?->interests ?? [];
        $data['life_languages_known'] = $life?->languages_known ?? [];
        $data['life_favorite_music'] = $life?->favorite_music ?? [];
        $data['life_preferred_books'] = $life?->preferred_books ?? [];
        $data['life_preferred_movies'] = $life?->preferred_movies ?? [];
        $data['life_sports_fitness_games'] = $life?->sports_fitness_games ?? [];
        $data['life_favorite_cuisine'] = $life?->favorite_cuisine ?? [];

        // Social Media
        $social = $profile->socialMediaLink;
        $data['social_instagram'] = $social?->instagram_url;
        $data['social_facebook'] = $social?->facebook_url;
        $data['social_linkedin'] = $social?->linkedin_url;
        $data['social_youtube'] = $social?->youtube_url;
        $data['social_website'] = $social?->website_url;

        // Partner Preferences
        $pp = $profile->partnerPreference;
        $data['pp_age_from'] = $pp?->age_from;
        $data['pp_age_to'] = $pp?->age_to;
        $data['pp_height_from'] = $pp?->height_from_cm;
        $data['pp_height_to'] = $pp?->height_to_cm;
        $data['pp_marital_status'] = $pp?->marital_status ?? [];
        $data['pp_complexion'] = $pp?->complexion ?? [];
        $data['pp_body_type'] = $pp?->body_type ?? [];
        $data['pp_physical_status'] = $pp?->physical_status ?? [];
        $data['pp_family_status'] = $pp?->family_status ?? [];
        $data['pp_religions'] = $pp?->religions ?? [];
        $data['pp_mother_tongues'] = $pp?->mother_tongues ?? [];
        $data['pp_education_levels'] = $pp?->education_levels ?? [];
        $data['pp_occupations'] = $pp?->occupations ?? [];
        $data['pp_working_countries'] = $pp?->working_countries ?? [];
        $data['pp_working_states'] = $pp?->working_states ?? [];
        $data['pp_working_districts'] = $pp?->working_districts ?? [];
        $data['pp_native_districts'] = $pp?->native_districts ?? [];
        $data['pp_native_countries'] = $pp?->native_countries ?? [];
        $data['pp_educational_qualifications'] = $pp?->educational_qualifications ?? [];
        $data['pp_employment_status'] = $pp?->employment_status ?? [];
        $data['pp_languages_known'] = $pp?->languages_known ?? [];
        $data['pp_income_range'] = $pp?->income_range ?? [];
        $data['pp_children_status'] = $pp?->children_status ?? [];
        $data['pp_manglik'] = $pp?->manglik ?? [];
        $data['pp_da_category'] = $pp?->da_category ?? [];
        // Partner religion sub-preferences.
        $data['pp_denomination'] = $pp?->denomination ?? [];
        $data['pp_diocese'] = $pp?->diocese ?? [];
        $data['pp_caste'] = $pp?->caste ?? [];
        $data['pp_sub_caste'] = $pp?->sub_caste ?? [];
        $data['pp_muslim_sect'] = $pp?->muslim_sect ?? [];
        $data['pp_muslim_community'] = $pp?->muslim_community ?? [];
        $data['pp_jain_sect'] = $pp?->jain_sect ?? [];
        $data['pp_about_partner'] = $pp?->about_partner;

        return $data;
    }

    /**
     * Save all related data from form fields.
     */
    protected function afterSave(): void
    {
        $data = $this->form->getState();
        $profile = $this->record;

        // Update user email/phone
        $profile->user->update([
            'email' => $data['user_email'] ?? $profile->user->email,
            'phone' => $data['user_phone'] ?? $profile->user->phone,
        ]);

        // Update religious info. Clear fields that don't belong to the chosen
        // religion, so changing a profile's religion never leaves stale data
        // behind (and the View page never shows mismatched fields).
        $relData = [
            'religion' => $data['rel_religion'] ?? null,
            'denomination' => $data['rel_denomination'] ?? null,
            'other_denomination_name' => $data['rel_other_denomination_name'] ?? null,
            'diocese' => $data['rel_diocese'] ?? null,
            'diocese_name' => $data['rel_diocese_name'] ?? null,
            'parish_name_place' => $data['rel_parish'] ?? null,
            'caste' => $data['rel_caste'] ?? null,
            'other_caste_name' => $data['rel_other_caste_name'] ?? null,
            'sub_caste' => $data['rel_sub_caste'] ?? null,
            'gotra' => $data['rel_gotra'] ?? null,
            'nakshatra' => $data['rel_nakshatra'] ?? null,
            'rashi' => $data['rel_rashi'] ?? null,
            'dosh' => $data['rel_manglik'] ?? null,
            'muslim_sect' => $data['rel_muslim_sect'] ?? null,
            'muslim_community' => $data['rel_muslim_community'] ?? null,
            'jain_sect' => $data['rel_jain_sect'] ?? null,
            'religious_observance' => $data['rel_religious_observance'] ?? null,
            'other_religion_name' => $data['rel_other_religion_name'] ?? null,
            'time_of_birth' => $data['rel_time_of_birth'] ?? null,
            'place_of_birth' => $data['rel_place_of_birth'] ?? null,
            'jathakam_upload_url' => $data['rel_jathakam'] ?? null,
        ];
        if (filled($data['rel_jathakam'] ?? null)) {
            \App\Support\PublicFolderRules::ensure('jathakam', \App\Support\PublicFolderRules::DOCUMENTS); // never indexed
        }
        $relData = ReligiousInfo::clearFieldsForReligion($relData, $data['rel_religion'] ?? null);
        $this->savePart('religiousInfo', ReligiousInfo::class, $relData);

        // Update education
        $this->savePart('educationDetail', EducationDetail::class,
            [
                'highest_education' => $data['edu_highest_education'] ?? null,
                'education_level' => $data['edu_education_level'] ?? null,
                'education_detail' => $data['edu_education_detail'] ?? null,
                'college_name' => $data['edu_college_name'] ?? null,
                'occupation' => $data['edu_occupation'] ?? null,
                'occupation_detail' => $data['edu_occupation_detail'] ?? null,
                'employment_category' => $data['edu_employment_category'] ?? null,
                'employer_name' => $data['edu_employer_name'] ?? null,
                'annual_income' => $data['edu_annual_income'] ?? null,
                'working_country' => $data['edu_working_country'] ?? null,
                'working_state' => \App\Filament\Resources\UserResource::stateDistrictFromForm($data, 'edu_working')[0],
                'working_district' => \App\Filament\Resources\UserResource::stateDistrictFromForm($data, 'edu_working')[1],
                'working_city' => $data['edu_working_city'] ?? null,
            ]
        );

        // Update family details
        $this->savePart('familyDetail', FamilyDetail::class,
            [
                'father_name' => $data['fam_father_name'] ?? null,
                'father_occupation' => $data['fam_father_occupation'] ?? null,
                'father_house_name' => $data['fam_father_house_name'] ?? null,
                'father_native_place' => $data['fam_father_native_place'] ?? null,
                'mother_name' => $data['fam_mother_name'] ?? null,
                'mother_occupation' => $data['fam_mother_occupation'] ?? null,
                'mother_house_name' => $data['fam_mother_house_name'] ?? null,
                'mother_native_place' => $data['fam_mother_native_place'] ?? null,
                'family_status' => $data['fam_family_status'] ?? null,
                'brothers_married' => $data['fam_brothers_married'] ?? null,
                'brothers_unmarried' => $data['fam_brothers_unmarried'] ?? null,
                'brothers_priest' => $data['fam_brothers_priest'] ?? null,
                'sisters_married' => $data['fam_sisters_married'] ?? null,
                'sisters_unmarried' => $data['fam_sisters_unmarried'] ?? null,
                'sisters_nun' => $data['fam_sisters_nun'] ?? null,
                'candidate_asset_details' => $data['fam_candidate_asset_details'] ?? null,
                'about_candidate_family' => $data['fam_about_family'] ?? null,
            ]
        );

        // Update location
        $this->savePart('locationInfo', LocationInfo::class,
            [
                'residing_country' => $data['loc_residing_country'] ?? null,
                'native_country' => $data['loc_native_country'] ?? null,
                'native_state' => \App\Filament\Resources\UserResource::stateDistrictFromForm($data, 'loc_native')[0],
                'native_district' => \App\Filament\Resources\UserResource::stateDistrictFromForm($data, 'loc_native')[1],
                'native_place' => $data['loc_native_place'] ?? null,
                'residency_status' => $data['loc_residency_status'] ?? null,
                'pin_zip_code' => $data['loc_pin_zip_code'] ?? null,
                'outstation_leave_date_from' => $data['loc_outstation_from'] ?? null,
                'outstation_leave_date_to' => $data['loc_outstation_to'] ?? null,
            ]
        );

        // Update contact info
        $this->savePart('contactInfo', ContactInfo::class,
            [
                'whatsapp_number' => $data['cont_whatsapp'] ?? null,
                'contact_person' => $data['cont_custodian_name'] ?? null,
                'contact_relationship' => $data['cont_custodian_relation'] ?? null,
                'preferred_call_time' => $data['cont_preferred_call_time'] ?? null,
                'communication_address' => $data['cont_communication_address'] ?? null,
                'pincode' => $data['cont_pin_zip_code'] ?? null,
                'reference_name' => $data['cont_reference_name'] ?? null,
                'reference_mobile' => $data['cont_reference_mobile'] ?? null,
                'secondary_phone' => $data['cont_secondary_phone'] ?? null,
                'residential_phone_number' => $data['cont_residential_phone_number'] ?? null,
                'alternate_email' => $data['cont_alternate_email'] ?? null,
                'reference_relationship' => $data['cont_reference_relationship'] ?? null,
                'present_address' => $data['cont_present_address'] ?? null,
                'present_pin_zip_code' => $data['cont_present_pin_zip_code'] ?? null,
                'permanent_address' => $data['cont_permanent_address'] ?? null,
                'permanent_pin_zip_code' => $data['cont_permanent_pin_zip_code'] ?? null,
            ]
        );

        // Differently-abled details: saved while the status is "Differently Abled", kept
        // otherwise (the member's own page does the same)
        if (($data['physical_status'] ?? $profile->physical_status) === 'Differently Abled') {
            $raw = $this->form->getRawState();
            $this->savePart('differentlyAbledInfo', \App\Models\DifferentlyAbledInfo::class,
                [
                    'category' => $raw['da_category'] ?? null,
                    'specify' => ($raw['da_category'] ?? '') === 'Other' ? ($raw['da_specify'] ?? null) : null,
                    'description' => $raw['da_description'] ?? null,
                ]
            );
        }

        // Update lifestyle
        $this->savePart('lifestyleInfo', LifestyleInfo::class,
            [
                'diet' => $data['life_diet'] ?? null,
                'smoking' => $data['life_smoking'] ?? null,
                'drinking' => $data['life_drinking'] ?? null,
                'cultural_background' => $data['life_cultural_background'] ?? null,
                'hobbies' => $data['life_hobbies'] ?? null,
                'interests' => $data['life_interests'] ?? null,
                'languages_known' => $data['life_languages_known'] ?? null,
                'favorite_music' => $data['life_favorite_music'] ?? null,
                'preferred_books' => $data['life_preferred_books'] ?? null,
                'preferred_movies' => $data['life_preferred_movies'] ?? null,
                'sports_fitness_games' => $data['life_sports_fitness_games'] ?? null,
                'favorite_cuisine' => $data['life_favorite_cuisine'] ?? null,
            ]
        );

        // Update social media
        $this->savePart('socialMediaLink', SocialMediaLink::class,
            [
                'instagram_url' => $data['social_instagram'] ?? null,
                'facebook_url' => $data['social_facebook'] ?? null,
                'linkedin_url' => $data['social_linkedin'] ?? null,
                'youtube_url' => $data['social_youtube'] ?? null,
                'website_url' => $data['social_website'] ?? null,
            ]
        );

        // Update partner preferences
        $this->savePart('partnerPreference', PartnerPreference::class,
            // an empty list (or "Any") is stored as null, the same as the member's own
            // Partner Preferences page (PartnerPreference::listOrNull, Nov 2026)
            array_map(fn ($v) => is_array($v) ? PartnerPreference::listOrNull($v) : $v, [
                'age_from' => $data['pp_age_from'] ?? null,
                'age_to' => $data['pp_age_to'] ?? null,
                'height_from_cm' => $data['pp_height_from'] ?? null,
                'height_to_cm' => $data['pp_height_to'] ?? null,
                'marital_status' => $data['pp_marital_status'] ?? [],
                'complexion' => $data['pp_complexion'] ?? [],
                'body_type' => $data['pp_body_type'] ?? [],
                'physical_status' => $data['pp_physical_status'] ?? [],
                'family_status' => $data['pp_family_status'] ?? [],
                'religions' => $ppReligions = $data['pp_religions'] ?? [],
                'mother_tongues' => $data['pp_mother_tongues'] ?? [],
                'education_levels' => $data['pp_education_levels'] ?? [],
                'educational_qualifications' => $data['pp_educational_qualifications'] ?? [],
                'occupations' => $data['pp_occupations'] ?? [],
                'employment_status' => $data['pp_employment_status'] ?? [],
                'working_countries' => $data['pp_working_countries'] ?? [],
                'working_states' => $data['pp_working_states'] ?? [],
                'working_districts' => $data['pp_working_districts'] ?? [],
                'native_countries' => $data['pp_native_countries'] ?? [],
                'native_districts' => $data['pp_native_districts'] ?? [],
                'languages_known' => $data['pp_languages_known'] ?? [],
                'income_range' => $data['pp_income_range'] ?? [],
                'children_status' => $data['pp_children_status'] ?? [],
                'manglik' => $data['pp_manglik'] ?? [],
                'da_category' => $data['pp_da_category'] ?? [],
                // Religion-gated partner sub-preferences (mirrors onboarding).
                'denomination' => in_array('Christian', (array) $ppReligions, true) ? ($data['pp_denomination'] ?? []) : [],
                'diocese' => in_array('Christian', (array) $ppReligions, true) ? ($data['pp_diocese'] ?? []) : [],
                'caste' => array_intersect(['Hindu', 'Jain'], (array) $ppReligions) ? ($data['pp_caste'] ?? []) : [],
                'sub_caste' => array_intersect(['Hindu', 'Jain'], (array) $ppReligions) ? ($data['pp_sub_caste'] ?? []) : [],
                'muslim_sect' => in_array('Muslim', (array) $ppReligions, true) ? ($data['pp_muslim_sect'] ?? []) : [],
                'muslim_community' => in_array('Muslim', (array) $ppReligions, true) ? ($data['pp_muslim_community'] ?? []) : [],
                'jain_sect' => in_array('Jain', (array) $ppReligions, true) ? ($data['pp_jain_sect'] ?? []) : [],
                'about_partner' => $data['pp_about_partner'] ?? null,
            ])
        );

        // the completion % follows staff edits too, as on the member's own pages (5 Nov 2026:
        // it stayed as it was, so members staff had completed stayed in the "Incomplete" list)
        app(ProfileCompletionService::class)->recalculate($profile);

        $changed = $this->logChangedFields();

        if ($this->markCheckedOnSave) {
            $this->markDetailsChecked(count($changed));
        }
    }

    /** Saves one part of the profile (a LOGGED_PARTS relation) and remembers what was written, for the edit log. */
    private function savePart(string $relation, string $model, array $values): void
    {
        $this->writtenValues[$relation] = $values;
        $model::updateOrCreate(['profile_id' => $this->record->getKey()], $values);
    }

    protected function beforeSave(): void
    {
        $this->valuesBeforeSave = $this->storedValues();
        $this->writtenValues = [];
    }

    /** Staff stay on this page after Save (often still on the call). */
    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    /**
     * Logs who changed which fields ("member_profile_updated" in the Activity Log, and
     * "Last edited" on the member card). Contact details, health details and documents
     * are logged only as "changed", never with their values. Returns what changed.
     *
     * @return array<string, string>
     */
    private function logChangedFields(): array
    {
        $before = $this->valuesBeforeSave;
        $after = $this->storedValues();
        $changed = [];

        // A part saved for the first time (no row before): only what staff filled in. The new
        // row's own values are not edits (6 Nov 2026: "Partner preferences: exclude same gotra
        // (empty) → 0" and the sibling counts 0 were logged on members who had no row yet).
        $partsBefore = array_unique(array_map(fn (string $field): string => Str::before($field, ':'), array_keys($before)));
        $firstSaved = [];
        foreach ($this->writtenValues as $relation => $values) {
            $part = self::LOGGED_PARTS[$relation];
            if (! in_array($part, $partsBefore, true)) {
                $firstSaved[$part] = array_map(fn (string $column): string => $part . ': ' . str_replace('_', ' ', $column),
                    array_keys(array_filter($values, fn ($value): bool => filled($value))));
            }
        }

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
            $old = $this->loggedValue($before[$field] ?? null);
            $new = $this->loggedValue($after[$field] ?? null);
            if ($old === $new) {
                continue;
            }

            $part = Str::before($field, ':');
            if (isset($firstSaved[$part]) && ! in_array($field, $firstSaved[$part], true)) {
                continue;
            }
            $changed[$field] = in_array($part, self::LOGGED_WITHOUT_VALUES, true) || in_array($field, self::LOGGED_WITHOUT_VALUES_FIELDS, true)
                ? 'changed'
                : ($old ?? '(empty)') . ' → ' . ($new ?? '(empty)');
        }

        if ($changed !== []) {
            self::logActivity('member_profile_updated', $this->record, ['changed' => $changed]);
        }

        return $changed;
    }

    /** The profile's stored values, read fresh: ["Basic: marital status" => "Unmarried", "Religion: caste" => …]. */
    private function storedValues(): array
    {
        $profile = Profile::withTrashed()->with(array_merge(['user'], array_keys(self::LOGGED_PARTS)))->find($this->record->getKey());
        if (! $profile) {
            return [];
        }

        $values = [];
        $add = function (string $part, $model) use (&$values): void {
            foreach ($model?->getAttributes() ?? [] as $column => $value) {
                // the completion % is worked out from the rest, so a Save that changes nothing logs nothing
                if (! in_array($column, ['id', 'profile_id', 'user_id', 'created_at', 'updated_at', 'profile_completion_pct'], true)) {
                    $values[$part . ': ' . str_replace('_', ' ', $column)] = $value;
                }
            }
        };

        $add('Basic', $profile);
        $values['Login: email'] = $profile->user?->email;
        $values['Login: phone'] = $profile->user?->phone;
        foreach (self::LOGGED_PARTS as $relation => $part) {
            $add($part, $profile->{$relation});
        }

        return $values;
    }

    /** A stored value as it reads in the log: lists joined, long text shortened, empty as null. */
    private function loggedValue(mixed $value): ?string
    {
        if (is_string($value) && str_starts_with($value, '[')) {
            $list = json_decode($value, true);
            if (is_array($list)) {
                $value = implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item), $list));
            }
        }
        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 80);
    }
}

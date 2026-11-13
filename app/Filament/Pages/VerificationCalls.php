<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCall;
use App\Support\StaffActivity;
use App\Support\VerificationCalls as Calls;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Today → Verification Calls (12 Nov 2026): every new member gets one call, shared out
 * in turn to the staff who take the calls. Each staff member works from "My calls";
 * calls nobody made for 2 days open to everyone; managers see all of them, move them,
 * and see who did what this week. The call itself happens on the member's edit page
 * (the member card, "Save and mark checked on call" = verified, "Call result" for the
 * rest). Rules: App\Support\VerificationCalls.
 */
class VerificationCalls extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static \UnitEnum|string|null $navigationGroup = 'Today';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Verification Calls';
    protected static ?string $title = 'Verification Calls';
    protected string $view = 'filament.pages.verification-calls';

    #[Url]
    public string $tab = '';

    /** Newest members first, or the ones waiting longest (kept in the address). */
    #[Url]
    public string $sort = Calls::SORT_DEFAULT;

    /** Managers: the calls ticked on the list, and who to give them to ('' = anyone). */
    public array $picked = [];
    public string $giveTo = '';

    public static function canAccess(): bool
    {
        return Calls::canUse(auth()->user());
    }

    public static function getNavigationBadge(): ?string
    {
        $due = Calls::dueCount(auth()->user());

        return $due > 0 ? (string) $due : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        Calls::sync();
        if (! array_key_exists($this->tab, $this->tabs())) {
            $this->tab = array_key_first($this->tabs());
        }
        if (! array_key_exists($this->sort, Calls::SORTS)) {
            $this->sort = Calls::SORT_DEFAULT;
        }
    }

    /** tab => label, for this staff member */
    private function tabs(): array
    {
        $user = auth()->user();
        $tabs = [];
        if (Calls::isCaller($user)) {
            $tabs['mine'] = 'My calls';
        }
        $tabs['anyone'] = 'Anyone can call';
        if (Calls::managesAll($user)) {
            $tabs['all'] = 'Everyone\'s calls';
            $tabs['check'] = 'Needs a check';
        }
        $tabs['done'] = 'Done (30 days)';

        return $tabs;
    }

    private function run(callable $work): void
    {
        try {
            $message = $work();
            Notification::make()->title(is_string($message) ? $message : 'Done.')->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    private function row(int $id): ?VerificationCall
    {
        return Calls::rows(auth()->user())->with(['profile.user', 'profile.contactInfo', 'caller'])->find($id);
    }

    public function noAnswer(int $id): void
    {
        if ($row = $this->row($id)) {
            $this->run(fn () => Calls::record(auth()->user(), $row, 'no_answer'));
        }
    }

    public function reopen(int $id): void
    {
        if ($row = $this->row($id)) {
            $this->run(function () use ($row) {
                Calls::reopen(auth()->user(), $row);

                return 'Back on the calls list.';
            });
        }
    }

    public function assign(int $id, string $to): void
    {
        if ($row = $this->row($id)) {
            $this->run(function () use ($row, $to) {
                Calls::assign(auth()->user(), $row, $to === '' ? null : (int) $to);

                return 'Moved.';
            });
        }
    }

    /** Another tab, another list: nothing stays ticked. */
    public function updatedTab(): void
    {
        $this->picked = [];
    }

    /** Give the ticked calls to one caller, or to anyone. */
    public function givePicked(): void
    {
        $picked = count($this->picked);
        $this->run(function () use ($picked) {
            $moved = Calls::assignMany(auth()->user(), $this->picked, $this->giveTo === '' ? null : (int) $this->giveTo);
            $this->picked = [];

            return $moved === $picked
                ? 'Moved '.$moved.' '.($moved === 1 ? 'call' : 'calls').'.'
                : "Moved {$moved} of {$picked}. The rest are already with that person, finished, or from another branch.";
        });
    }

    /** The "we tried to call you" WhatsApp link was opened from a row: note it in the member's history. */
    public function loggedWhatsApp(int $id): void
    {
        if ($row = $this->row($id)) {
            $this->run(function () use ($row) {
                Calls::logWhatsApp(auth()->user(), $row->profile, 'missed');

                return 'Noted in the member\'s history.';
            });
        }
    }

    protected function getHeaderActions(): array
    {
        $callers = fn (): array => Calls::callersFor(auth()->user())->pluck('name', 'id')->all();

        return [
            Action::make('moveCalls')
                ->label('Move someone\'s calls')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->visible(fn (): bool => Calls::managesAll(auth()->user()))
                ->modalDescription('For a staff member on leave or who left: their open calls go to one person, or are shared out among the others.')
                ->form([
                    Select::make('from')->label('Calls of')->options($callers)->required(),
                    Select::make('to')->label('Move to')->options($callers)->placeholder('Share out among the others'),
                ])
                ->action(function (array $data): void {
                    $this->run(fn () => 'Moved '.Calls::moveAll(auth()->user(), (int) $data['from'], filled($data['to'] ?? null) ? (int) $data['to'] : null).' calls.');
                }),
            Action::make('away')
                ->label('Away / back')
                ->icon('heroicon-o-moon')
                ->color('gray')
                ->visible(fn (): bool => Calls::managesAll(auth()->user()))
                ->modalHeading('Mark a staff member away, or back')
                ->modalDescription('While someone is away, new members are not given to them. They are shared among the others. On the return day they are back in the sharing by themselves.')
                ->form([
                    Select::make('staff')->label('Staff member')->options($callers)->required(),
                    Select::make('state')->label('Is')->options(['away' => 'Away', 'back' => 'Back at work'])->default('away')->required()->live(),
                    DatePicker::make('until')->label('Away until (last day away)')->native(false)
                        ->minDate(now(StaffActivity::tz())->toDateString())
                        ->helperText('Leave empty if you don\'t know: they stay away until you mark them back.')
                        ->visible(fn ($get): bool => $get('state') === 'away'),
                    \Filament\Forms\Components\Toggle::make('share')->label('Give their open calls to the others now')->default(true)
                        ->helperText('Off: their open calls stay with them, and open to anyone after 2 days.')
                        ->visible(fn ($get): bool => $get('state') === 'away'),
                ])
                ->action(function (array $data): void {
                    $this->run(function () use ($data) {
                        $away = ($data['state'] ?? 'away') === 'away';
                        $moved = Calls::setAway(auth()->user(), (int) $data['staff'], $away, $away ? ($data['until'] ?? null) : null, $away && ($data['share'] ?? false));

                        return $away ? 'Marked away.'.($moved ? " {$moved} open calls went to the others." : '') : 'Marked back at work.';
                    });
                }),
            Action::make('startDate')
                ->label('List starts from')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->visible(fn (): bool => Calls::managesAll(auth()->user()))
                ->modalDescription('Members who joined from this day on are on the list. Moving it earlier adds the older members (they are shared out too); moving it later takes nobody off.')
                ->fillForm(fn (): array => ['from' => Calls::startDate()->displayTz()->toDateString()])
                ->form([
                    DatePicker::make('from')->label('Members who joined from')->required()->native(false)->maxDate(now(StaffActivity::tz())->toDateString()),
                ])
                ->action(function (array $data): void {
                    $this->run(fn () => 'The list now starts from '.Calls::setStartDate(auth()->user(), (string) $data['from'])->displayTz()->format('j M Y').'.');
                }),
        ];
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $tabs = $this->tabs();
        $counts = [];
        foreach (array_keys($tabs) as $key) {
            $counts[$key] = Calls::tab($user, $key)->count();
        }
        $sort = array_key_exists($this->sort, Calls::SORTS) ? $this->sort : Calls::SORT_DEFAULT;
        $rows = Calls::tab($user, $this->tab, $sort)
            ->with(['profile.user', 'profile.contactInfo', 'profile.primaryPhoto', 'caller', 'doneBy'])
            ->limit(200)
            ->get();
        $manages = Calls::managesAll($user);

        return [
            'tabs' => $tabs,
            'counts' => $counts,
            'rows' => $rows,
            'sorts' => Calls::SORTS,
            'sort' => $sort,
            // the order only applies to the lists of calls still to make
            'sortable' => in_array($this->tab, ['mine', 'anyone', 'all'], true),
            'manages' => $manages,
            'isCaller' => Calls::isCaller($user),
            // for the "Given to" menu: [id => [name, branch]]; a call goes only to its member's branch
            'callers' => $manages ? Calls::callersFor($user)->mapWithKeys(fn (User $c) => [$c->id => [$c->name, $c->branch_id]])->all() : [],
            // ticking several calls: on the lists of calls still to make
            'canPick' => $manages && in_array($this->tab, ['anyone', 'all'], true),
            'report' => $manages ? Calls::report($user) : null,
            'callUrl' => fn (VerificationCall $row) => UserResource::profileWorkUrl($row->profile),
            'missedUrl' => function (VerificationCall $row): ?string {
                $digits = MembersWithoutPhoto::whatsappDigits($row->profile);

                return $digits ? Calls::waUrl($digits, Calls::message($row->profile, 'missed')) : null;
            },
            'mayWork' => fn (VerificationCall $row) => Calls::mayWork($user, $row),
            'forAnyone' => fn (VerificationCall $row) => Calls::isForAnyone($row),
            'isLate' => fn (VerificationCall $row) => Calls::isLate($row),
            'anyoneDays' => Calls::ANYONE_AFTER_DAYS,
            'maxNoAnswer' => Calls::MAX_NO_ANSWER,
            'from' => Calls::startDate(),
        ];
    }
}

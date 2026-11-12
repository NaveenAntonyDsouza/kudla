<?php

namespace App\Filament\Pages;

use App\Models\AdminRecommendation;
use App\Models\AssistedEvent;
use App\Models\Interest;
use App\Models\Profile;
use App\Models\User;
use App\Support\Assisted;
use App\Support\MatriId;
use App\Support\ProfileVisibility;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

/**
 * Members → Assisted Members (12 Nov 2026). The relationship manager's work
 * list: every member on an assisted plan (or only "mine"), how many matches they
 * were sent this week against the target, what waits for the member and what
 * waits for staff. Opening one member gives the work view: suggest matches, send
 * the interests the member said yes to, answer received interests on the
 * member's instruction, note meetings. All rules are in App\Support\Assisted.
 */
class AssistedMembers extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static \UnitEnum|string|null $navigationGroup = 'Members';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Assisted Members';
    protected static ?string $title = 'Assisted Members';
    protected string $view = 'filament.pages.assisted-members';

    #[Url(as: 'member')]
    public ?int $memberId = null;
    public bool $mine = false;

    public string $suggestId = '';
    public string $suggestNote = '';
    public ?string $newManagerId = null;
    /** keyed by interest id */
    public array $replyHow = [];
    public array $replySaid = [];
    /** keyed by suggestion id */
    public array $meetingOn = [];
    public array $meetingNote = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->staff_role_id !== null || $user->role === 'admin') && Assisted::offered();
    }

    public function mount(): void
    {
        $this->mine = ! Assisted::seesAll();
    }

    public function open(?int $id): void
    {
        $this->memberId = $id;
        $this->reset(['suggestId', 'suggestNote', 'newManagerId', 'replyHow', 'replySaid', 'meetingOn', 'meetingNote']);
    }

    /** The open member, when this staff member may see them. */
    private function member(): ?Profile
    {
        if (! $this->memberId) {
            return null;
        }
        $member = Profile::with(['user', 'relationshipManager'])->find($this->memberId);
        if (! $member) {
            return null;
        }
        if (! Assisted::seesAll() && $member->relationship_manager_id !== auth()->id()) {
            abort(403);
        }

        return $member;
    }

    private function run(callable $work, string $done): void
    {
        try {
            $work();
            Notification::make()->title($done)->success()->send();
        } catch (DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function changeManager(): void
    {
        $member = $this->member();
        if (! $member || ! Assisted::seesAll()) {
            return;
        }
        $manager = $this->newManagerId ? User::find($this->newManagerId) : null;
        if ($this->newManagerId && (! $manager || ! array_key_exists($manager->id, Assisted::staffOptions()))) {
            Notification::make()->title('Choose an active staff member.')->danger()->send();

            return;
        }
        Assisted::setManager($member, $manager);
        $this->newManagerId = null;
        Notification::make()->title($manager ? "{$manager->name} is now the relationship manager." : 'Manager removed.')->success()->send();
    }

    public function suggest(?int $profileId = null): void
    {
        $member = $this->member();
        if (! $member) {
            return;
        }
        $other = $profileId
            ? Profile::find($profileId)
            : (trim($this->suggestId) !== '' ? Profile::where('matri_id', MatriId::current($this->suggestId))->first() : null);
        if (! $other) {
            Notification::make()->title('No profile with that Matri ID.')->danger()->send();

            return;
        }
        $this->run(function () use ($member, $other) {
            Assisted::suggest(auth()->user(), $member, $other, $this->suggestNote);
            $this->reset(['suggestId', 'suggestNote']);
        }, $member->assisted_auto_send ? "Suggested {$other->matri_id}, and the interest was sent (the member lets you send without asking)." : "Suggested {$other->matri_id}. The member says yes or no on their Assisted page.");
    }

    public function sendInterest(int $recId): void
    {
        $rec = $this->rec($recId);
        if ($rec) {
            $this->run(fn () => Assisted::sendApproved(auth()->user(), $rec), 'Interest sent in the member\'s name.');
        }
    }

    public function reply(int $interestId, bool $accept): void
    {
        $member = $this->member();
        $interest = $member ? Interest::where('receiver_profile_id', $member->id)->find($interestId) : null;
        if (! $interest) {
            return;
        }
        $this->run(function () use ($member, $interest, $accept) {
            Assisted::reply(auth()->user(), $member, $interest, $accept, (string) ($this->replyHow[$interest->id] ?? ''), (string) ($this->replySaid[$interest->id] ?? ''));
            unset($this->replyHow[$interest->id], $this->replySaid[$interest->id]);
        }, $accept ? 'Accepted for the member.' : 'Declined for the member.');
    }

    public function arrangeMeeting(int $recId): void
    {
        $rec = $this->rec($recId);
        if (! $rec) {
            return;
        }
        $on = (string) ($this->meetingOn[$recId] ?? '');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $on)) {
            Notification::make()->title('Pick the meeting date.')->danger()->send();

            return;
        }
        $this->run(fn () => Assisted::meeting(auth()->user(), $rec, $on, $this->meetingNote[$recId] ?? null), 'Meeting noted. The member was told.');
    }

    private function rec(int $id): ?AdminRecommendation
    {
        $member = $this->member();

        return $member ? AdminRecommendation::where('for_profile_id', $member->id)->with(['forProfile', 'recommendedProfile', 'interest'])->find($id) : null;
    }

    protected function getViewData(): array
    {
        $member = $this->member();

        return $member ? $this->workView($member) : $this->listView();
    }

    private function listView(): array
    {
        $members = Profile::query()
            ->where(fn ($q) => $q->whereNotNull('relationship_manager_id')
                ->orWhereHas('user.userMemberships', fn ($m) => $m->where('is_active', true)
                    ->where(fn ($e) => $e->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                    ->whereHas('plan', fn ($p) => $p->where('is_assisted', true))))
            // whatever the browser sends, staff who can't see everyone get only their own
            ->when($this->mine || ! Assisted::seesAll(), fn ($q) => $q->where('relationship_manager_id', auth()->id()))
            ->with(['user', 'relationshipManager'])
            ->orderBy('full_name')
            ->limit(300)
            ->get();
        $ids = $members->pluck('id');
        $weekStart = now(\App\Support\StaffActivity::tz())->startOfWeek()->setTimezone(config('app.timezone'));

        $count = fn ($query) => $query->whereIn('for_profile_id', $ids)->groupBy('for_profile_id')
            ->selectRaw('for_profile_id, count(*) as n')->pluck('n', 'for_profile_id');
        $week = $count(AdminRecommendation::query()->where('created_at', '>=', $weekStart));
        $waitingMember = $count(AdminRecommendation::query()->whereNull('member_decision'));
        $toSend = $count(AdminRecommendation::query()->whereIn('member_decision', ['yes', 'auto'])->whereNull('interest_id'));
        $toAnswer = Interest::query()->waitingForReply()->whereIn('receiver_profile_id', $ids)
            ->groupBy('receiver_profile_id')->selectRaw('receiver_profile_id, count(*) as n')->pluck('n', 'receiver_profile_id');
        $lastNote = DB::table('profile_notes')->whereIn('profile_id', $ids)->groupBy('profile_id')
            ->selectRaw('profile_id, max(created_at) as at')->pluck('at', 'profile_id');

        $rows = $members->map(function (Profile $p) use ($week, $waitingMember, $toSend, $toAnswer, $lastNote) {
            $membership = $p->user?->activeMembership();

            return [
                'profile' => $p,
                'plan' => $membership?->plan?->is_assisted ? $membership->plan->plan_name : null,
                'ends' => $membership?->plan?->is_assisted ? $membership->ends_at : null,
                'consent' => $p->assisted_consent_at,
                'week' => (int) ($week[$p->id] ?? 0),
                'waiting_member' => (int) ($waitingMember[$p->id] ?? 0),
                'to_send' => (int) ($toSend[$p->id] ?? 0),
                'to_answer' => (int) ($toAnswer[$p->id] ?? 0),
                'last_note' => isset($lastNote[$p->id]) ? \Illuminate\Support\Carbon::parse($lastNote[$p->id]) : null,
            ];
        })
            // on an assisted plan first, those furthest behind the weekly target first
            ->sortBy(fn ($r) => [$r['plan'] ? 0 : 1, $r['week'], $r['profile']->full_name])
            ->values();

        return [
            'mode' => 'list',
            'rows' => $rows,
            'seesAll' => Assisted::seesAll(),
            'target' => Assisted::WEEKLY_TARGET,
        ];
    }

    private function workView(Profile $member): array
    {
        $recs = AdminRecommendation::where('for_profile_id', $member->id)
            ->with(['recommendedProfile' => fn ($q) => $q->withTrashed(), 'interest', 'adminUser'])
            ->latest()->get();

        $received = Interest::query()->waitingForReply()->where('receiver_profile_id', $member->id)
            ->with('senderProfile')->latest()->limit(50)->get();

        // Best matches by score that the member may see, not suggested yet and with no interest either way
        $taken = $recs->pluck('recommended_profile_id')
            ->merge(Interest::where('sender_profile_id', $member->id)->pluck('receiver_profile_id'))
            ->merge(Interest::where('receiver_profile_id', $member->id)->pluck('sender_profile_id'))
            ->unique()->all();
        $candidates = $member->gender
            ? ProfileVisibility::forMember(Profile::query(), $member)
                ->join('match_scores', 'match_scores.matched_profile_id', '=', 'profiles.id')
                ->where('match_scores.profile_id', $member->id)
                ->whereNotIn('profiles.id', $taken ?: [0])
                ->select('profiles.*', 'match_scores.score')
                ->orderByDesc('match_scores.score')
                ->limit(12)->get()
            : collect();

        return [
            'mode' => 'work',
            'member' => $member,
            'plan' => Assisted::plan($member),
            'ends' => Assisted::plan($member) ? $member->user?->activeMembership()?->ends_at : null,
            'refusal' => Assisted::refusal(auth()->user(), $member),
            'seesAll' => Assisted::seesAll(),
            'staff' => Assisted::staffOptions(),
            'recs' => $recs,
            'stages' => AdminRecommendation::STAGES,
            'received' => $received,
            'candidates' => $candidates,
            'how' => Assisted::HOW,
            'history' => AssistedEvent::where('profile_id', $member->id)->with(['admin', 'otherProfile'])->latest('id')->limit(40)->get(),
            'describe' => fn (AssistedEvent $e) => Assisted::describe($e),
            'memberUrl' => fn (int $id) => \App\Filament\Resources\UserResource::getUrl('view', ['record' => $id]),
            'thisWeek' => $recs->where('created_at', '>=', now(\App\Support\StaffActivity::tz())->startOfWeek())->count(),
            'target' => Assisted::WEEKLY_TARGET,
        ];
    }
}

<?php

namespace App\Mail;

use App\Models\Interest;
use App\Models\User;
use App\Support\InterestReplyRows;
use App\Support\MemberName;
use Illuminate\Support\Collection;

/**
 * "Members are waiting for your reply": interests a member received and
 * hasn't answered for 3+ days, up to five listed, each with Accept / Decline
 * links that open the interest page with that reply chosen.
 */
class InterestReminderMail extends DatabaseMailable
{
    protected string $templateSlug = 'interest-reminder';

    protected ?string $unsubscribePreference = 'email_interest';

    /**
     * @param  Collection<int, Interest>  $interests  up to five, oldest first
     * @param  int  $total  all pending interests waiting for this member
     */
    public function __construct(public User $user, public Collection $interests, public int $total) {}

    protected function recipient(): ?User
    {
        return $this->user;
    }

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: 'there',
            'WAITING_LINE' => $this->waitingLine(),
            'PENDING_LIST_HTML' => $this->listHtml(),
            'INBOX_URL' => route('interests.inbox'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_interest'),
        ];
    }

    protected function waitingLine(): string
    {
        if ($this->total === 1) {
            return ($this->interests->first()?->senderProfile?->matri_id ?? 'A member') . ' is waiting for your reply';
        }

        return "{$this->total} members are waiting for your reply";
    }

    /** One row per interest. Every value is escaped here: this is *_HTML. */
    protected function listHtml(): string
    {
        $rows = InterestReplyRows::html($this->interests);

        $more = $this->total - $this->interests->count();

        return '<table style="width:100%;border-collapse:collapse;margin:8px 0 16px;">' . $rows . '</table>'
            . ($more > 0 ? '<p style="color:#6b7280;">And ' . e($more === 1 ? '1 more' : "{$more} more") . ' in your inbox.</p>' : '');
    }

    protected function fallbackSubject(): string
    {
        return $this->waitingLine();
    }
}

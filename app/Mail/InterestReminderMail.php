<?php

namespace App\Mail;

use App\Models\Interest;
use App\Models\User;
use App\Support\MemberName;
use App\Support\MemberSummary;
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
        $rows = $this->interests->map(function (Interest $interest) {
            $page = route('interests.show', $interest);
            $sender = $interest->senderProfile;
            $summary = MemberSummary::line($sender);
            $days = (int) $interest->created_at?->diffInDays(now());

            return '<tr><td style="padding:12px 0;border-bottom:1px solid #e5e7eb;">'
                . '<strong>' . e($sender?->matri_id) . '</strong>'
                . ($summary !== '' ? ' <span style="color:#6b7280;">· ' . e($summary) . '</span>' : '')
                . '<br><span style="font-size:13px;color:#6b7280;">Sent ' . e($days === 1 ? '1 day' : "{$days} days") . ' ago</span>'
                . '</td><td style="padding:12px 0;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap;">'
                . '<a href="' . e($page . '?reply=accept#reply') . '" style="color:#15803d;font-weight:600;text-decoration:none;margin-right:12px;">Accept</a>'
                . '<a href="' . e($page . '?reply=decline#reply') . '" style="color:#b91c1c;font-weight:600;text-decoration:none;">Decline</a>'
                . '</td></tr>';
        })->implode('');

        $more = $this->total - $this->interests->count();

        return '<table style="width:100%;border-collapse:collapse;margin:8px 0 16px;">' . $rows . '</table>'
            . ($more > 0 ? '<p style="color:#6b7280;">And ' . e($more === 1 ? '1 more' : "{$more} more") . ' in your inbox.</p>' : '');
    }

    protected function fallbackSubject(): string
    {
        return $this->waitingLine();
    }
}

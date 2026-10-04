<?php

namespace App\Mail;

use App\Models\Profile;
use App\Models\User;
use App\Support\MemberName;
use App\Support\MemberSummary;
use App\Support\PhotoVisibility;
use Illuminate\Support\Collection;

/**
 * Weekly "N members viewed your profile" (engagement:send-profile-views-digest).
 * Members with full access (paid, or free-membership mode) see who — first
 * name + last initial, Matri ID, photo only if the website would show it to
 * them; others see the count and an upgrade link, like the "Viewed me" page.
 */
class ProfileViewsDigestMail extends DatabaseMailable
{
    protected string $templateSlug = 'profile-views-digest';

    protected ?string $unsubscribePreference = 'email_views';

    public function __construct(public User $user, public int $viewCount, public Collection $viewers) {}

    protected function recipient(): ?User
    {
        return $this->user;
    }

    protected function templateVariables(): array
    {
        $canSee = $this->user->isPremium();

        return [
            'USER_NAME' => MemberName::first($this->user->name) ?: 'there',
            'MATRI_ID' => $this->user->profile?->matri_id ?? '',
            'VIEW_HEADLINE' => $this->viewCount === 1 ? '1 member viewed your profile this week' : "{$this->viewCount} members viewed your profile this week",
            'VIEWER_CARDS_HTML' => $canSee ? $this->cards() : $this->upgradeBlock(),
            'VIEWS_URL' => route('views.index'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_views'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Members viewed your profile this week';
    }

    private function cards(): string
    {
        return $this->viewers->map(function (Profile $viewer) {
            $name = e((MemberName::short($viewer->full_name) ?: 'Member').' · '.$viewer->matri_id);
            $details = e(MemberSummary::line($viewer));
            $photo = $this->photoUrl($viewer);
            $photoHtml = $photo
                ? '<img src="'.e($photo).'" alt="'.e((string) $viewer->matri_id).'" style="width:64px;height:64px;border-radius:50%;object-fit:cover;display:block;">'
                : '<div style="width:64px;height:64px;border-radius:50%;background:#e5e7eb;text-align:center;line-height:64px;color:#6b7280;font-size:1.5rem;">&#128100;</div>';

            return '<table style="width:100%;border-collapse:collapse;margin-bottom:0.75rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff;"><tr>'
                .'<td style="padding:0.75rem;width:76px;vertical-align:middle;">'.$photoHtml.'</td>'
                .'<td style="padding:0.75rem 0.75rem 0.75rem 0;vertical-align:middle;"><div style="font-weight:600;color:#111827;">'.$name.'</div>'
                .'<div style="font-size:0.85rem;color:#6b7280;margin-top:2px;">'.$details.'</div></td>'
                .'<td style="padding:0.75rem;text-align:right;vertical-align:middle;"><a href="'.e(route('profile.view', $viewer)).'" style="display:inline-block;padding:8px 14px;background:'.e($this->getPrimaryColor()).';color:#fff;text-decoration:none;border-radius:6px;font-size:0.85rem;font-weight:600;">View profile</a></td>'
                .'</tr></table>';
        })->implode('');
    }

    private function upgradeBlock(): string
    {
        return '<p style="padding:1rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;color:#374151;text-align:center;">'
            .'See exactly who viewed your profile — and contact them — with a membership plan.<br>'
            .'<a href="'.e(route('membership.index')).'" style="display:inline-block;margin-top:10px;font-weight:600;color:'.e($this->getPrimaryColor()).';">See membership plans</a></p>';
    }

    private function photoUrl(Profile $viewer): ?string
    {
        try {
            return PhotoVisibility::url($viewer, $this->user->profile);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

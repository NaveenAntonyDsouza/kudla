<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * WeeklyMatchSuggestionsMail — weekly digest email showing top N matches.
 *
 * Template: `weekly-match-suggestions` (seeded in EmailTemplateSeeder).
 * Variables: USER_NAME, MATCH_COUNT, MATCH_CARDS_HTML, MATCHES_URL, UNSUBSCRIBE_URL, SITE_NAME.
 *
 * The match cards are rendered to HTML here (rather than Blade partials in the template)
 * so the template stays editable in the admin panel as a single HTML field.
 */
class WeeklyMatchSuggestionsMail extends DatabaseMailable
{
    protected string $templateSlug = 'weekly-match-suggestions';

    public function __construct(public User $user, public Collection $matches) {}

    protected function templateVariables(): array
    {
        return [
            'USER_NAME' => $this->user->name,
            'MATCH_COUNT' => (string) $this->matches->count(),
            'MATCH_CARDS_HTML' => $this->renderMatchCards(),
            'MATCHES_URL' => url('/matches'),
            'UNSUBSCRIBE_URL' => $this->user->unsubscribeUrl('email_weekly_matches'),
        ];
    }

    protected function fallbackSubject(): string
    {
        return 'Your top ' . $this->matches->count() . ' matches this week';
    }

    /**
     * Render each match as an email-friendly card (HTML).
     * Uses inline styles only (email clients strip <style> blocks).
     */
    protected function renderMatchCards(): string
    {
        $cards = [];

        foreach ($this->matches as $match) {
            $photoUrl = $this->photoUrlFor($match);
            $name = htmlspecialchars($match->full_name ?? 'Member');
            $age = $match->date_of_birth
                ? (int) \Carbon\Carbon::parse($match->date_of_birth)->diffInYears(now())
                : null;
            $location = trim(collect([
                optional($match->locationInfo)->native_district,
                optional($match->locationInfo)->native_state,
            ])->filter()->implode(', '));

            $education = htmlspecialchars($match->educationDetail->highest_education ?? '');
            $occupation = htmlspecialchars($match->educationDetail->occupation ?? '');

            $badge = $match->match_badge ?? 'partial';
            $badgeText = match ($badge) {
                'great' => '✨ Great Match',
                'good' => '👍 Good Match',
                default => '🔸 Partial Match',
            };
            $badgeColor = match ($badge) {
                'great' => '#10b981',
                'good' => '#3b82f6',
                default => '#f59e0b',
            };

            $profileUrl = url('/profiles/' . $match->matri_id);

            $details = [];
            if ($age) $details[] = "$age yrs";
            if ($location) $details[] = htmlspecialchars($location);
            if ($education) $details[] = $education;
            if ($occupation) $details[] = $occupation;

            $photoHtml = $photoUrl
                ? '<img src="' . $photoUrl . '" alt="' . $name . '" style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block;">'
                : '<div style="width:80px;height:80px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:2rem;">👤</div>';

            $cards[] = '<table style="width:100%;border-collapse:collapse;margin-bottom:1rem;border:1px solid #e5e7eb;border-radius:8px;background:white;">
                <tr>
                    <td style="padding:1rem;vertical-align:top;width:96px;">' . $photoHtml . '</td>
                    <td style="padding:1rem 1rem 1rem 0;vertical-align:top;">
                        <div style="font-weight:600;font-size:1.125rem;color:#111827;margin-bottom:0.25rem;">' . $name . '</div>
                        <div style="font-size:0.875rem;color:#6b7280;margin-bottom:0.5rem;">' . implode(' · ', $details) . '</div>
                        <span style="display:inline-block;padding:0.25rem 0.625rem;background:' . $badgeColor . ';color:white;border-radius:9999px;font-size:0.75rem;font-weight:600;">' . $badgeText . '</span>
                    </td>
                    <td style="padding:1rem;vertical-align:middle;text-align:right;">
                        <a href="' . $profileUrl . '" style="display:inline-block;padding:8px 16px;background:#8b1d91;color:white;text-decoration:none;border-radius:6px;font-size:0.875rem;font-weight:600;">View Profile</a>
                    </td>
                </tr>
            </table>';
        }

        return implode("\n", $cards);
    }

    /**
     * Safely extract a photo URL for a match profile (public, approved, visible primary photo).
     * Returns null if no suitable photo available.
     */
    protected function photoUrlFor($match): ?string
    {
        $photo = $match->primaryPhoto ?? null;
        if (!$photo) return null;

        // Use full_url accessor if available, else fallback to photo_url
        try {
            $url = $photo->full_url ?? null;
            if ($url) return $url;
        } catch (\Throwable $e) {
            // Accessor may fail if storage misconfigured
        }

        // Fallback: try to build URL from photo_url column
        $rawUrl = $photo->photo_url ?? null;
        if (!$rawUrl) return null;

        return str_starts_with($rawUrl, 'http') ? $rawUrl : asset('storage/' . $rawUrl);
    }
}

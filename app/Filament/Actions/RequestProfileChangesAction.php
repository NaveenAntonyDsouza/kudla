<?php

namespace App\Filament\Actions;

use App\Models\Profile;
use App\Services\MemberEmailService;
use App\Services\NotificationService;
use App\Support\Permissions;
use App\Traits\LogsAdminActivity;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;

/**
 * "Request changes" on a pending profile — the counterpart to Approve.
 *
 * The admin picks a reason (plus an optional note). The profile stays
 * unapproved and nothing is deleted; the member gets an in-app notice and
 * the admin-editable "profile-rejected" email ("Profile update required")
 * carrying the reason, so they know what to fix. The action is logged.
 *
 * Shared by the members list row actions (UserResource) and the member View
 * page (ViewUser) so the two can't drift — same pattern as ProfileNotesAction.
 */
class RequestProfileChangesAction
{
    use LogsAdminActivity;

    public const REASONS = [
        'incomplete' => 'Some profile details are incomplete',
        'photo' => 'The profile photo is unclear or not suitable',
        'inconsistent' => 'Some information looks incorrect or inconsistent',
        'contact_in_text' => 'Please remove phone numbers, emails or links from your profile text',
        'verification' => 'We need to verify some of your details — please contact us',
    ];

    public static function make(string $name = 'requestChanges'): Action
    {
        return Action::make($name)
            ->label('Request changes')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->button()
            ->size('sm')
            ->modalHeading('Request changes')
            ->modalDescription(fn (Profile $record) => "The profile stays unapproved. {$record->full_name} ({$record->matri_id}) will get an in-app notice and an email with the reason.")
            ->modalSubmitActionLabel('Send to member')
            ->form([
                Select::make('reason')
                    ->label('Reason')
                    ->options(self::REASONS)
                    ->required(),
                Textarea::make('note')
                    ->label('Additional note (optional)')
                    ->rows(2)
                    ->placeholder('Shown to the member after the reason'),
            ])
            ->action(function (Profile $record, array $data): void {
                $reason = self::REASONS[$data['reason']] ?? $data['reason'];
                if (filled($data['note'] ?? null)) {
                    $reason .= ' — ' . trim($data['note']);
                }

                $user = $record->user;
                if ($user) {
                    app(NotificationService::class)->send(
                        $user,
                        'profile_changes_requested',
                        'Profile update required',
                        "Please update your profile before it can be approved. Reason: {$reason}",
                    );
                    DB::afterCommit(fn () => app(MemberEmailService::class)->profileChangesRequested($user, $reason));
                }

                self::logActivity('profile_changes_requested', $record, ['reason' => $reason]);
            })
            ->visible(fn (Profile $record): bool => ! $record->is_approved && Permissions::can('approve_member'))
            ->successNotificationTitle('Sent to the member');
    }
}

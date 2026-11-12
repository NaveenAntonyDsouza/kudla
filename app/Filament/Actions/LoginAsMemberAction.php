<?php

namespace App\Filament\Actions;

use App\Models\Profile;
use App\Support\LoginAs;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/**
 * "Login as member" (11 Nov 2026): opens the member site as this member
 * sees it — view only, for a limited time, recorded with the reason typed
 * here. All the rules live in App\Support\LoginAs; this is only the button.
 *
 * Shared by the members list ("More") and the member View / Edit pages so
 * they can't drift — same pattern as ProfileNotesAction.
 */
class LoginAsMemberAction
{
    public static function make(string $name = 'loginAsMember'): Action
    {
        return Action::make($name)
            ->label('Login as member')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->modalHeading(fn (Profile $record) => "Login as {$record->matri_id}")
            ->modalDescription(function (Profile $record): string {
                $refused = LoginAs::refusal(auth('admin')->user(), $record);

                return $refused
                    ?? "You will see the site exactly as {$record->full_name} sees it, including their chats and the phone numbers they have unlocked. "
                    .'View only: nothing can be changed, sent or paid for, and the member is not shown as active. '
                    .'It ends after '.LoginAs::MINUTES.' minutes or when you press Exit. Every visit is recorded in the Audit Trail with your name, the reason and the pages you open.';
            })
            ->form(fn (Profile $record): array => LoginAs::refusal(auth('admin')->user(), $record) ? [] : [
                Textarea::make('reason')
                    ->label('Why are you opening this account?')
                    ->placeholder('e.g. Member phoned: cannot see the photo she uploaded')
                    ->rows(2)
                    ->required()
                    ->minLength(5)
                    ->maxLength(500),
            ])
            ->modalSubmitActionLabel('Open member site')
            ->modalSubmitAction(fn ($action, Profile $record) => LoginAs::refusal(auth('admin')->user(), $record) ? false : $action)
            ->action(function (Profile $record, array $data) {
                try {
                    LoginAs::start(auth('admin')->user(), $record, (string) ($data['reason'] ?? ''));
                } catch (\DomainException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return null;
                }

                return redirect('/dashboard');
            })
            // shown to everyone with the right, also before two-step sign-in is set
            // up: the popup then says what to do first
            ->visible(fn (Profile $record): bool => ! $record->trashed() && LoginAs::allowed(auth('admin')->user()));
    }
}

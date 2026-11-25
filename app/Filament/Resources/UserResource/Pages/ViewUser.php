<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    use \App\Traits\LogsAdminActivity;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        // simpler staff screens (owner, 25 Nov 2026): the member page and Notes; Approve and
        // Request changes only for a pending profile; the rest, the red Deactivate too, in More
        if (\App\Support\AdminModules::simpleStaffScreens()) {
            return [
                Actions\EditAction::make()->label('Open member page'),
                \App\Filament\Actions\LogContactAction::make('notes'),
                $this->approveAction(),
                \App\Filament\Actions\RequestProfileChangesAction::make(),
                Actions\ActionGroup::make([
                    Actions\Action::make('openWhatsApp')
                        ->label('Open in WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn (): ?string => ($number = \App\Support\MemberContacts::whatsappNumber($this->record)) ? 'https://wa.me/'.ltrim($number, '+') : null)
                        ->openUrlInNewTab()
                        ->visible(fn (): bool => \App\Support\MemberContacts::whatsappNumber($this->record) !== null),
                    $this->toggleActiveAction(),
                    \App\Filament\Actions\LoginAsMemberAction::make(),
                ])->label('More')->icon('heroicon-m-ellipsis-horizontal')->button()->color('gray'),
            ];
        }

        return [
            Actions\EditAction::make(),

            // WhatsApp
            Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(function (): ?string {
                    $phone = $this->record->user?->phone;
                    if (!$phone) return null;
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (strlen($phone) === 10) $phone = '91' . $phone;
                    return 'https://wa.me/' . $phone;
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => (bool) $this->record->user?->phone),

            // Quick Approve — needs the Approve Member right and is logged, as on the members
            // list and the edit page (6 Nov 2026: anyone who could view a member could approve here, unlogged)
            $this->approveAction(),

            // Request changes — shared with the members list.
            \App\Filament\Actions\RequestProfileChangesAction::make(),

            // Notes — the SAME shared popup as the members list (history + Type +
            // Note + Follow-up). Replaces the old stripped-down "Add Note" that
            // was missing the Type field and the history timeline.
            \App\Filament\Actions\ProfileNotesAction::make('notes'),

            // Toggle Active — needs the Activate/Deactivate right and is logged (6 Nov 2026, as above)
            $this->toggleActiveAction(),

            // see the site as this member sees it (view only, recorded)
            \App\Filament\Actions\LoginAsMemberAction::make(),
        ];
    }

    private function approveAction(): Actions\Action
    {
        return Actions\Action::make('quickApprove')
            ->label('Approve')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Other members will see this profile, and the member gets the "profile approved" email.')
            ->action(function (): void {
                $this->record->update(['is_approved' => true]);
                self::logActivity('profile_approved', $this->record);
            })
            ->visible(fn (): bool => ! $this->record->is_approved && \App\Support\Permissions::can('approve_member'))
            ->successNotificationTitle('Profile approved');
    }

    private function toggleActiveAction(): Actions\Action
    {
        return Actions\Action::make('toggleActive')
            ->label(fn () => $this->record->is_active ? 'Deactivate' : 'Activate')
            ->icon(fn () => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->color(fn () => $this->record->is_active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->action(function (): void {
                $wasActive = $this->record->is_active;
                $this->record->update(['is_active' => ! $wasActive]);
                self::logActivity($wasActive ? 'profile_deactivated' : 'profile_activated', $this->record);
            })
            ->visible(fn (): bool => \App\Support\Permissions::can('toggle_active'));
    }
}

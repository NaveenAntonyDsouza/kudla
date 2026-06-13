<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
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

            // Quick Approve
            Actions\Action::make('quickApprove')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_approved' => true]))
                ->visible(fn (): bool => !$this->record->is_approved)
                ->successNotificationTitle('Profile approved'),

            // Notes — the SAME shared popup as the members list (history + Type +
            // Note + Follow-up). Replaces the old stripped-down "Add Note" that
            // was missing the Type field and the history timeline.
            \App\Filament\Actions\ProfileNotesAction::make('notes'),

            // Toggle Active
            Actions\Action::make('toggleActive')
                ->label(fn () => $this->record->is_active ? 'Deactivate' : 'Activate')
                ->icon(fn () => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->is_active ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_active' => !$this->record->is_active])),
        ];
    }
}

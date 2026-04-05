<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('toggleActive')
                ->label(fn() => $this->record->is_active ? 'Deactivate User' : 'Activate User')
                ->color(fn() => $this->record->is_active ? 'danger' : 'success')
                ->icon(fn() => $this->record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_active' => !$this->record->is_active]);
                    $this->refreshFormData(['is_active']);
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

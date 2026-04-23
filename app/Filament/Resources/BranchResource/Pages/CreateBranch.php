<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    /**
     * Enforce the "only one head office" invariant before saving.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['is_head_office'])) {
            $existingHO = Branch::where('is_head_office', true)->first();

            if ($existingHO) {
                Notification::make()
                    ->title('Head Office already exists')
                    ->body("'{$existingHO->name}' is already marked as Head Office. Unmark it first or uncheck this option.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }
}

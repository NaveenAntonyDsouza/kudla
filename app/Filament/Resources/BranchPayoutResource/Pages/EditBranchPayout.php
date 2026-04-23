<?php

namespace App\Filament\Resources\BranchPayoutResource\Pages;

use App\Filament\Resources\BranchPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranchPayout extends EditRecord
{
    protected static string $resource = BranchPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Convert form rupees back to paise for storage.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['gross_revenue_paise'])) {
            $data['gross_revenue_paise'] = (int) round($data['gross_revenue_paise'] * 100);
        }
        if (isset($data['payout_amount_paise'])) {
            $data['payout_amount_paise'] = (int) round($data['payout_amount_paise'] * 100);
        }
        return $data;
    }
}

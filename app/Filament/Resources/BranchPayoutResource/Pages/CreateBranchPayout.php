<?php

namespace App\Filament\Resources\BranchPayoutResource\Pages;

use App\Filament\Resources\BranchPayoutResource;
use App\Models\BranchPayout;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBranchPayout extends CreateRecord
{
    protected static string $resource = BranchPayoutResource::class;

    /**
     * Convert form rupees to paise for storage; check for duplicate (branch + period_start).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert rupee inputs to paise
        if (isset($data['gross_revenue_paise'])) {
            $data['gross_revenue_paise'] = (int) round($data['gross_revenue_paise'] * 100);
        }
        if (isset($data['payout_amount_paise'])) {
            $data['payout_amount_paise'] = (int) round($data['payout_amount_paise'] * 100);
        }

        // Stamp creator
        $data['created_by_user_id'] = auth()->id();

        // Duplicate check (DB enforces but better UX with friendly message)
        $existing = BranchPayout::where('branch_id', $data['branch_id'])
            ->whereDate('period_start', $data['period_start'])
            ->first();

        if ($existing) {
            Notification::make()
                ->title('Duplicate payout')
                ->body('A payout already exists for this branch in this period.')
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }

        return $data;
    }
}

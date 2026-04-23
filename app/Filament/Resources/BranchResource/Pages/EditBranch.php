<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Resources\BranchResource;
use App\Models\Branch;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_qr')
                ->label('Download QR (PNG)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    $svc = app(\App\Services\QrCodeService::class);
                    $url = $svc->shortAffiliateUrl($this->record->code);
                    $png = $svc->generatePng($url, 600);
                    $filename = 'affiliate-qr-' . $this->record->code . '.png';

                    return response()->streamDownload(
                        fn () => print($png),
                        $filename,
                        ['Content-Type' => 'image/png']
                    );
                }),

            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->is_head_office) {
                        Notification::make()
                            ->title('Cannot delete the Head Office branch.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
        ];
    }

    /**
     * Enforce the "only one head office" invariant when promoting a branch.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['is_head_office']) && !$this->record->is_head_office) {
            $existingHO = Branch::where('is_head_office', true)
                ->where('id', '!=', $this->record->id)
                ->first();

            if ($existingHO) {
                Notification::make()
                    ->title('Head Office already exists')
                    ->body("'{$existingHO->name}' is already marked as Head Office.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }
}

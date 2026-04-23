<?php

namespace App\Filament\Resources\BulkImportResource\Pages;

use App\Filament\Resources\BulkImportResource;
use App\Models\BulkImport;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class PreviewBulkImport extends Page
{
    protected static string $resource = BulkImportResource::class;
    protected string $view = 'filament.resources.bulk-import.preview';

    public BulkImport $record;

    public function mount(BulkImport $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Preview: ' . $this->record->original_filename;
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->record,
            'validationErrors' => $this->record->validation_errors ?? [],
            'errorsSummary' => $this->buildErrorsSummary(),
        ];
    }

    /**
     * Group errors by column for the summary panel.
     */
    private function buildErrorsSummary(): array
    {
        $summary = [];
        foreach ($this->record->validation_errors ?? [] as $row => $errors) {
            foreach ($errors as $col => $msg) {
                $summary[$col][] = ['row' => $row, 'message' => $msg];
            }
        }
        return $summary;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->record->canBeExecuted()) {
            $actions[] = Actions\Action::make('approve_import')
                ->label('Approve & Import (' . $this->record->valid_rows . ' rows)')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve and import?')
                ->modalDescription("This will create {$this->record->valid_rows} new member profiles. Invalid rows ({$this->record->invalid_rows}) will be skipped. This action cannot be undone.")
                ->action(function () {
                    try {
                        $updated = app(\App\Services\BulkImportExecutor::class)->execute($this->record);
                        $this->record = $updated;
                        $this->record->refresh();

                        if ($updated->status === 'completed') {
                            Notification::make()
                                ->title('Import completed')
                                ->body($updated->summary)
                                ->success()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import failed')
                                ->body($updated->summary ?? 'See row outcomes for details.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Import error')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                });
        }

        if ($this->record->invalid_rows > 0) {
            $actions[] = Actions\Action::make('download_errors')
                ->label('Download Errors CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action(function () {
                    $csv = "\xEF\xBB\xBF"; // BOM
                    $csv .= "Row,Column,Error Message\n";
                    foreach ($this->record->validation_errors ?? [] as $row => $errors) {
                        foreach ($errors as $col => $msg) {
                            $csv .= "$row,\"" . str_replace('"', '""', $col) . '","' . str_replace('"', '""', $msg) . "\"\n";
                        }
                    }
                    return response()->streamDownload(
                        fn () => print($csv),
                        'import-errors-' . $this->record->id . '.csv',
                        ['Content-Type' => 'text/csv']
                    );
                });
        }

        if ($this->record->canBeCancelled()) {
            $actions[] = Actions\Action::make('cancel_import')
                ->label('Cancel Import')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => BulkImport::STATUS_CANCELLED]);
                    Notification::make()->title('Import cancelled')->success()->send();
                    $this->redirect(BulkImportResource::getUrl('index'));
                });
        }

        return $actions;
    }
}

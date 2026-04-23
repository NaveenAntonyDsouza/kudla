<?php

namespace App\Filament\Resources\BulkImportResource\Pages;

use App\Filament\Resources\BulkImportResource;
use App\Services\BulkImportSchema;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBulkImports extends ListRecords
{
    protected static string $resource = BulkImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_template')
                ->label('Download CSV Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $headers = BulkImportSchema::columnNames();
                    $example = BulkImportSchema::exampleRow();

                    $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
                    $csv .= implode(',', $headers) . "\n";
                    $csv .= '"' . implode('","', array_map(fn($v) => str_replace('"', '""', (string) $v), $example)) . '"' . "\n";

                    return response()->streamDownload(
                        fn () => print($csv),
                        'bulk-import-template.csv',
                        ['Content-Type' => 'text/csv']
                    );
                }),

            Actions\Action::make('download_reference')
                ->label('Download Reference Data')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->action(function () {
                    $rows = BulkImportSchema::referenceDataRows();

                    $csv = "\xEF\xBB\xBF"; // BOM
                    $csv .= "Column,Valid Value\n";
                    foreach ($rows as $row) {
                        $csv .= '"' . str_replace('"', '""', $row[0]) . '","' . str_replace('"', '""', $row[1]) . '"' . "\n";
                    }

                    return response()->streamDownload(
                        fn () => print($csv),
                        'bulk-import-reference-data.csv',
                        ['Content-Type' => 'text/csv']
                    );
                }),

            Actions\CreateAction::make()
                ->label('Upload CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn () => BulkImportResource::getUrl('create')),
        ];
    }
}

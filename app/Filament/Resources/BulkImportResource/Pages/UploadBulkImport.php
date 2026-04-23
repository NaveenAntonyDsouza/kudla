<?php

namespace App\Filament\Resources\BulkImportResource\Pages;

use App\Filament\Resources\BulkImportResource;
use App\Models\Branch;
use App\Models\BulkImport;
use App\Services\BulkImportValidator;
use App\Services\CsvParser;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class UploadBulkImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BulkImportResource::class;
    protected string $view = 'filament.resources.bulk-import.upload';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'send_welcome_email' => true,
            'default_branch_id' => auth()->user()->branch_id ?? Branch::getHeadOffice()?->id,
        ]);
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Upload CSV')
                    ->description('Upload a CSV file with member data. Use the "Download CSV Template" button on the list page to get the correct format. After upload, you\'ll see a preview with validation errors before any imports happen.')
                    ->schema([
                        Forms\Components\FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->required()
                            ->acceptedFileTypes(['text/csv', 'application/csv', 'application/vnd.ms-excel'])
                            ->maxSize(5120) // 5 MB
                            ->disk('local')
                            ->directory('bulk-imports')
                            ->visibility('private')
                            ->helperText('Maximum 5 MB. Up to 1000 rows. UTF-8 encoded.'),
                    ])
                    ->columns(1),

                \Filament\Schemas\Components\Section::make('Default Settings')
                    ->description('These apply to rows that don\'t override individually.')
                    ->schema([
                        Forms\Components\Select::make('default_branch_id')
                            ->label('Default Branch')
                            ->options(
                                Branch::active()
                                    ->orderByDesc('is_head_office')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->required()
                            ->searchable()
                            ->helperText('Branch attribution for rows where branch_code column is blank.'),

                        Forms\Components\Toggle::make('send_welcome_email')
                            ->label('Send Welcome Email')
                            ->default(true)
                            ->helperText('Send each new member a welcome email with their temporary password. Turn off if migrating old data.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function upload(): void
    {
        $data = $this->form->getState();

        // FileUpload returns the stored path under disk
        $relativePath = $data['csv_file'];
        if (is_array($relativePath)) {
            $relativePath = reset($relativePath);
        }

        $absolutePath = Storage::disk('local')->path($relativePath);

        // Parse the CSV
        $parser = app(CsvParser::class);
        $parsed = $parser->parseFile($absolutePath);

        if (!empty($parsed['errors'])) {
            Notification::make()
                ->title('Cannot read CSV')
                ->body(implode("\n", $parsed['errors']))
                ->danger()
                ->send();
            return;
        }

        if ($parsed['total_rows'] === 0) {
            Notification::make()
                ->title('CSV has no data rows')
                ->body('The file contains a header row but no data.')
                ->danger()
                ->send();
            return;
        }

        if ($parsed['total_rows'] > 1000) {
            Notification::make()
                ->title('Too many rows')
                ->body("This file has {$parsed['total_rows']} data rows. Maximum 1000 per upload.")
                ->danger()
                ->send();
            return;
        }

        // Validate header structure
        $headerErrors = $parser->validateHeaders($parsed['headers']);
        if (!empty($headerErrors)) {
            Notification::make()
                ->title('CSV header errors')
                ->body(implode("\n", $headerErrors))
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        // Run validation across all rows
        $rowsToValidate = collect($parsed['rows'])->pluck('data')->toArray();
        $validator = app(BulkImportValidator::class);
        $validation = $validator->validateBatch($rowsToValidate);

        // Persist BulkImport record
        $bulkImport = BulkImport::create([
            'uploader_user_id' => auth()->id(),
            'default_branch_id' => $data['default_branch_id'],
            'original_filename' => basename($relativePath),
            'file_path' => $relativePath,
            'status' => BulkImport::STATUS_VALIDATED,
            'total_rows' => $parsed['total_rows'],
            'valid_rows' => $validation['valid_count'],
            'invalid_rows' => $validation['invalid_count'],
            'settings' => [
                'send_welcome_email' => (bool) $data['send_welcome_email'],
            ],
            'validation_errors' => $this->compactErrors($validation['rows'], $parsed['rows']),
        ]);

        Notification::make()
            ->title('CSV uploaded and validated')
            ->body("{$validation['valid_count']} valid, {$validation['invalid_count']} invalid rows. Review on the next page.")
            ->success()
            ->send();

        // Redirect to preview
        $this->redirect(BulkImportResource::getUrl('preview', ['record' => $bulkImport->id]));
    }

    /**
     * Convert validator output into a compact JSON-friendly format for storage.
     */
    private function compactErrors(array $validatedRows, array $parsedRows): array
    {
        $compact = [];
        foreach ($validatedRows as $idx => $result) {
            $rowNumber = $parsedRows[$idx]['_row_number'] ?? ($idx + 2);
            if (!$result['valid']) {
                $compact[$rowNumber] = $result['errors'];
            }
        }
        return $compact;
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('upload')
                ->label('Upload & Validate')
                ->submit('upload')
                ->color('primary'),
            \Filament\Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(BulkImportResource::getUrl('index')),
        ];
    }
}

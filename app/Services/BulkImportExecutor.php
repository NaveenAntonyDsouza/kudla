<?php

namespace App\Services;

use App\Mail\StaffCreatedMemberWelcomeMail;
use App\Models\BulkImport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * BulkImportExecutor — runs the actual member creation pass for a validated BulkImport.
 *
 * Flow:
 *   1. Status: validated → processing
 *   2. Re-parse the CSV (so we have fresh data, and can re-validate in case DB state changed)
 *   3. For each valid row, call MemberCreationService
 *   4. Track per-row outcome (created / skipped / failed) in row_outcomes JSON
 *   5. Optionally queue welcome email per successful creation
 *   6. Update counts (imported, skipped, failed)
 *   7. Status: processing → completed (or failed if fatal error)
 *
 * The validator re-runs at execution time because the DB state may have changed
 * between upload and execute (e.g., someone else registered an email).
 */
class BulkImportExecutor
{
    public function __construct(
        protected CsvParser $parser,
        protected BulkImportValidator $validator,
        protected MemberCreationService $creator,
    ) {}

    /**
     * Execute the import.
     *
     * @param  BulkImport  $import  Must be in 'validated' status with a file path
     * @return BulkImport  The updated record
     */
    public function execute(BulkImport $import): BulkImport
    {
        if (!$import->canBeExecuted()) {
            $import->update([
                'status' => BulkImport::STATUS_FAILED,
                'summary' => 'Import not in executable state',
                'completed_at' => now(),
            ]);
            return $import;
        }

        $import->update([
            'status' => BulkImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $absolutePath = Storage::disk('local')->path($import->file_path);
        if (!file_exists($absolutePath)) {
            $import->update([
                'status' => BulkImport::STATUS_FAILED,
                'summary' => 'Uploaded file not found',
                'completed_at' => now(),
            ]);
            return $import;
        }

        try {
            $parsed = $this->parser->parseFile($absolutePath);
            $rowsData = collect($parsed['rows'])->pluck('data')->toArray();

            // Re-validate with fresh DB state
            $validation = $this->validator->validateBatch($rowsData);

            $settings = $import->settings ?? [];
            $sendWelcome = (bool) ($settings['send_welcome_email'] ?? false);
            $defaultBranchId = $import->default_branch_id;

            $importedCount = 0;
            $skippedCount = 0;
            $failedCount = 0;
            $outcomes = [];
            $welcomeEmailsToSend = [];

            foreach ($validation['rows'] as $idx => $rowResult) {
                $rowNumber = $parsed['rows'][$idx]['_row_number'] ?? ($idx + 2);

                if (!$rowResult['valid']) {
                    $outcomes[$rowNumber] = 'skipped';
                    $skippedCount++;
                    continue;
                }

                // Determine branch: row-specific branch_code wins; else default
                $branchId = $rowResult['normalized']['_resolved_branch_id'] ?? $defaultBranchId;

                try {
                    $result = $this->creator->create(
                        $rowResult['normalized'],
                        [
                            'branch_id' => $branchId,
                            'created_by_staff_id' => $import->uploader_user_id,
                            'is_approved' => true,
                        ]
                    );

                    $outcomes[$rowNumber] = 'created:' . $result['user']->id;
                    $importedCount++;

                    if ($sendWelcome && $result['user']->email) {
                        $welcomeEmailsToSend[] = [
                            'user' => $result['user'],
                            'profile' => $result['profile'],
                            'temp_password' => $result['temp_password'],
                        ];
                    }
                } catch (\Throwable $e) {
                    $outcomes[$rowNumber] = 'failed: ' . substr($e->getMessage(), 0, 200);
                    $failedCount++;
                }
            }

            // Send welcome emails (best-effort)
            foreach ($welcomeEmailsToSend as $item) {
                try {
                    Mail::to($item['user']->email)
                        ->send(new StaffCreatedMemberWelcomeMail(
                            $item['user'],
                            $item['temp_password']
                        ));
                } catch (\Throwable $e) {
                    // Log to row outcomes but don't fail the import
                    report($e);
                }
            }

            // Audit log
            try {
                \App\Models\AdminActivityLog::create([
                    'admin_user_id' => $import->uploader_user_id,
                    'action' => 'bulk_import_completed',
                    'model_type' => BulkImport::class,
                    'model_id' => $import->id,
                    'changes' => json_encode([
                        'total' => $import->total_rows,
                        'imported' => $importedCount,
                        'skipped' => $skippedCount,
                        'failed' => $failedCount,
                    ]),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            $import->update([
                'status' => BulkImport::STATUS_COMPLETED,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'failed_count' => $failedCount,
                'row_outcomes' => $outcomes,
                'summary' => sprintf(
                    "Imported %d, skipped %d, failed %d of %d rows. Welcome emails: %s.",
                    $importedCount,
                    $skippedCount,
                    $failedCount,
                    $import->total_rows,
                    $sendWelcome ? count($welcomeEmailsToSend) . ' sent' : 'skipped by user'
                ),
                'completed_at' => now(),
            ]);

            return $import->refresh();
        } catch (\Throwable $e) {
            report($e);
            $import->update([
                'status' => BulkImport::STATUS_FAILED,
                'summary' => 'Fatal error: ' . substr($e->getMessage(), 0, 500),
                'completed_at' => now(),
            ]);
            return $import;
        }
    }
}

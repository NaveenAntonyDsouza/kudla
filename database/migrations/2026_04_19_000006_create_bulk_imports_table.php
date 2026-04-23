<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log for every bulk-import attempt.
     *
     * Lifecycle:
     *   draft       — file uploaded, not yet validated
     *   validating  — running validation (transient)
     *   validated   — preview ready (admin to approve / cancel)
     *   processing  — executing import (transient)
     *   completed   — finished (some rows may have failed; see counts)
     *   failed      — fatal error during processing
     *   cancelled   — admin cancelled before execution
     */
    public function up(): void
    {
        Schema::create('bulk_imports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uploader_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('default_branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->string('original_filename');
            $table->string('file_path')->nullable(); // relative path on storage disk

            $table->enum('status', [
                'draft', 'validating', 'validated', 'processing',
                'completed', 'failed', 'cancelled',
            ])->default('draft');

            // Counts
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            // Settings (JSON: send_welcome_email bool, etc.)
            $table->json('settings')->nullable();

            // Per-row metadata (JSON: { row_number: [error_messages] } and { row_number: 'created'|'skipped'|'failed' })
            $table->json('validation_errors')->nullable();
            $table->json('row_outcomes')->nullable();

            $table->text('summary')->nullable(); // human-readable summary

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('uploader_user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_imports');
    }
};

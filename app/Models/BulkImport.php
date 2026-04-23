<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BulkImport extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATING = 'validating';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uploader_user_id',
        'default_branch_id',
        'original_filename',
        'file_path',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_count',
        'skipped_count',
        'failed_count',
        'settings',
        'validation_errors',
        'row_outcomes',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'imported_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
            'settings' => 'array',
            'validation_errors' => 'array',
            'row_outcomes' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------
     |  Relationships
     | ------------------------------------------------------------------ */

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }

    public function defaultBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    /* ------------------------------------------------------------------
     |  Scopes
     | ------------------------------------------------------------------ */

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VALIDATED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /* ------------------------------------------------------------------
     |  Helpers
     | ------------------------------------------------------------------ */

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_VALIDATING => 'Validating',
            self::STATUS_VALIDATED => 'Ready to Import',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_DRAFT => 'gray',
            self::STATUS_VALIDATING => 'info',
            self::STATUS_VALIDATED => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'gray',
        ];
    }

    /**
     * Whether this import can be cancelled (only draft/validated states).
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_VALIDATED], true);
    }

    /**
     * Whether this import can be approved/executed (must be in validated state).
     */
    public function canBeExecuted(): bool
    {
        return $this->status === self::STATUS_VALIDATED && $this->valid_rows > 0;
    }
}

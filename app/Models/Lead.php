<?php

namespace App\Models;

use App\Models\Concerns\BranchScopable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BranchScopable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'gender',
        'age',
        'source',
        'status',
        'assigned_to_staff_id',
        'created_by_staff_id',
        'branch_id',
        'notes',
        'follow_up_date',
        'profile_id',
        'converted_at',
        'converted_by_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'follow_up_date' => 'date',
            'converted_at' => 'datetime',
        ];
    }

    // Relationships

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_staff_id');
    }

    public function createdByStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_staff_id');
    }

    public function convertedByStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_staff_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class)->latest('called_at');
    }

    // Scopes

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to_staff_id', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to_staff_id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<=', today())
            ->whereNotIn('status', ['registered', 'lost', 'not_interested']);
    }

    // Accessors

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->follow_up_date) {
            return false;
        }

        if (in_array($this->status, ['registered', 'lost', 'not_interested'], true)) {
            return false;
        }

        return $this->follow_up_date->lte(today());
    }

    // Static lookups

    /**
     * Status list with label + badge color.
     */
    public static function statuses(): array
    {
        return [
            'new' => ['label' => 'New', 'color' => 'gray'],
            'contacted' => ['label' => 'Contacted', 'color' => 'info'],
            'interested' => ['label' => 'Interested', 'color' => 'success'],
            'not_interested' => ['label' => 'Not Interested', 'color' => 'danger'],
            'registered' => ['label' => 'Registered', 'color' => 'success'],
            'lost' => ['label' => 'Lost', 'color' => 'danger'],
        ];
    }

    public static function statusOptions(): array
    {
        return collect(self::statuses())->mapWithKeys(fn ($s, $k) => [$k => $s['label']])->toArray();
    }

    public static function sources(): array
    {
        return [
            'walk_in' => 'Walk-in',
            'phone' => 'Phone Inquiry',
            'website' => 'Website Form',
            'referral' => 'Referral',
            'whatsapp' => 'WhatsApp',
            'social_media' => 'Social Media',
            'advertisement' => 'Advertisement',
            'other' => 'Other',
        ];
    }
}

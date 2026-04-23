<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * BranchScopable — adds branch-level query scoping to a model.
 *
 * Apply this trait to any model that has a `branch_id` column. The trait adds:
 *
 *  1. `scopeForUserBranch($query, ?User $user = null)` — filters records to
 *     those matching the given user's branch_id. Returns the query unchanged if:
 *      - The user is NULL (unauthenticated background jobs / tinker)
 *      - The user is Super Admin
 *      - The user has no branch_id (head office staff with global access)
 *
 *  2. `bootBranchScopable()` — registers a `creating` event that auto-stamps
 *     `branch_id` on new records. Behavior:
 *      - If the record already has branch_id set (e.g., explicit assignment by
 *        Super Admin via form), the existing value is preserved.
 *      - Otherwise, branch_id is set from auth()->user()->branch_id.
 *      - If no auth user (e.g., seeders, jobs), branch_id stays NULL — the
 *        backfill migration / explicit assignment handles that case.
 *
 * Usage:
 *
 *     class Lead extends Model
 *     {
 *         use BranchScopable;
 *         // ...
 *     }
 *
 *     // In a Resource:
 *     return parent::getEloquentQuery()->forUserBranch();
 */
trait BranchScopable
{
    /**
     * Scope to records belonging to the given user's branch.
     * Pass null to use the currently authenticated user.
     *
     * @param  bool  $includeGlobal  If true, also include records where branch_id IS NULL.
     *                               Use this for "global" records like coupons that are
     *                               shared across all branches when branch_id is NULL.
     */
    public function scopeForUserBranch(Builder $query, ?User $user = null, bool $includeGlobal = false): Builder
    {
        $user = $user ?? auth()->user();

        // No user (background jobs, tinker) — show everything
        if (!$user) {
            return $query;
        }

        // Super Admin sees everything across all branches
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $query;
        }

        // Head office staff (no branch_id) see all branches
        if ($user->branch_id === null) {
            return $query;
        }

        $table = $this->getTable();

        // Branch-bound staff: their branch's records, optionally plus global (NULL)
        if ($includeGlobal) {
            return $query->where(function ($q) use ($user, $table) {
                $q->where("$table.branch_id", $user->branch_id)
                  ->orWhereNull("$table.branch_id");
            });
        }

        return $query->where("$table.branch_id", $user->branch_id);
    }

    /**
     * Boot the trait — registers the auto-stamp creating event.
     * Laravel's Model::bootTraits() automatically calls this.
     *
     * Logic: if branch_id is present in the model's attribute array (even as NULL,
     * which happens when a form explicitly sends "Global"/empty), respect it.
     * Only auto-stamp when branch_id wasn't part of the input at all (e.g.,
     * tinker calls, factories, or forms that don't include the field).
     */
    public static function bootBranchScopable(): void
    {
        static::creating(function ($model) {
            // If branch_id was explicitly set (even to NULL, e.g., for global coupons), respect it
            if (array_key_exists('branch_id', $model->getAttributes())) {
                return;
            }

            $user = auth()->user();
            if (!$user || $user->branch_id === null) {
                return;
            }

            // Auto-stamp from the creating user's branch
            $model->branch_id = $user->branch_id;
        });
    }
}

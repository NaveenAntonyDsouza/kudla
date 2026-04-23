<?php

namespace App\Traits;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsAdminActivity
{
    /**
     * Log an admin action to the activity log.
     *
     * @param string     $action  Short action label (e.g., 'profile_approved', 'settings_saved')
     * @param Model|null $model   The model affected (optional)
     * @param array|null $changes Additional context data (optional)
     */
    protected static function logActivity(
        string $action,
        ?Model $model = null,
        ?array $changes = null
    ): void {
        try {
            AdminActivityLog::create([
                'admin_user_id' => auth()->id(),
                'action' => $action,
                'model_type' => $model ? class_basename($model) : null,
                'model_id' => $model?->getKey(),
                'changes' => $changes,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail — logging should never break admin actions
        }
    }
}

<?php

namespace App\Support;

/**
 * Helper for permission-based navigation & action visibility in Filament.
 *
 * Usage in a Resource/Page:
 *     public static function shouldRegisterNavigation(): bool
 *     {
 *         return Permissions::can('manage_coupons');
 *     }
 *
 * Or in a row action:
 *     ->visible(fn () => Permissions::can('approve_member'))
 */
class Permissions
{
    /**
     * Check if the currently authenticated user can perform the given permission.
     * Super Admin always returns true.
     * Unauthenticated users always return false.
     */
    public static function can(?string $permissionKey): bool
    {
        if ($permissionKey === null) {
            return true;
        }

        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        // Legacy fallback — users with role='admin' but no staff_role_id
        if ($user->role === 'admin' && $user->staff_role_id === null) {
            return true;
        }

        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permissionKey);
        }

        return false;
    }
}

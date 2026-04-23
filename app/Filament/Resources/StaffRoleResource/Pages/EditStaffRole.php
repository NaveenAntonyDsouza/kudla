<?php

namespace App\Filament\Resources\StaffRoleResource\Pages;

use App\Filament\Resources\StaffRoleResource;
use App\Models\StaffRolePermission;
use App\Traits\LogsAdminActivity;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStaffRole extends EditRecord
{
    use LogsAdminActivity;

    protected static string $resource = StaffRoleResource::class;

    /**
     * Fill form with existing permissions prefixed by "perm_"
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $permissions = $record->permissions->pluck('scope', 'permission_key')->toArray();

        $permissionConfig = config('permissions.permissions');
        foreach ($permissionConfig as $key => $def) {
            $defaultScope = $def['type'] === 'scoped' ? 'none' : 'no';
            $data["perm_{$key}"] = $permissions[$key] ?? $defaultScope;
        }

        return $data;
    }

    /**
     * Before saving, strip out the perm_ fields from the role data.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Block edits on Super Admin
        if ($this->getRecord()->isSuperAdmin()) {
            Notification::make()
                ->title('Super Admin cannot be edited')
                ->danger()
                ->send();
            $this->halt();
        }

        // Pull permission fields out and store them separately for afterSave
        $permissions = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'perm_')) {
                $permissions[substr($key, 5)] = $value;
                unset($data[$key]);
            }
        }

        $this->pendingPermissions = $permissions;

        return $data;
    }

    /**
     * Used to stash permission data between mutateFormDataBeforeSave and afterSave.
     */
    protected array $pendingPermissions = [];

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Capture old state for activity log
        $oldPermissions = $record->permissions()->pluck('scope', 'permission_key')->toArray();

        // Upsert each permission
        $changes = [];
        foreach ($this->pendingPermissions as $key => $scope) {
            $old = $oldPermissions[$key] ?? null;
            if ($old !== $scope) {
                $changes[$key] = ['from' => $old, 'to' => $scope];
            }

            StaffRolePermission::updateOrCreate(
                ['staff_role_id' => $record->id, 'permission_key' => $key],
                ['scope' => $scope]
            );
        }

        // Log the change
        if (!empty($changes)) {
            self::logActivity('role_permissions_updated', $record, [
                'role' => $record->slug,
                'changes' => $changes,
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(StaffRolePermission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'staff_role_id');
    }

    /**
     * Check if this role has a given permission.
     * Super Admin always returns true.
     */
    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $scope = $this->permissionScope($key);

        return in_array($scope, ['yes', 'all', 'own'], true);
    }

    /**
     * Return the scope value for a permission. Defaults to 'no' if not set.
     */
    public function permissionScope(string $key): string
    {
        if ($this->isSuperAdmin()) {
            $type = config("permissions.permissions.{$key}.type");
            return $type === 'scoped' ? 'all' : 'yes';
        }

        $permission = $this->permissions->firstWhere('permission_key', $key);

        return $permission?->scope ?? 'no';
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super_admin';
    }
}

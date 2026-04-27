<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-facing User shape for the mobile API.
 *
 * Only exposes fields the Flutter client needs. Password + remember_token
 * are never included (they're $hidden on the model, but documenting it
 * explicitly here as defense in depth).
 *
 * Timestamps serialized as ISO 8601 strings (Flutter DateTime.parse friendly).
 *
 * Design reference: docs/mobile-app/design/02-auth-api.md
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'role'              => $this->role,
            'branch_id'         => $this->branch_id,
            'is_active'         => (bool) $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'last_login_at'     => $this->last_login_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}

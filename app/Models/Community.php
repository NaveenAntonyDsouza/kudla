<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $fillable = [
        'religion',
        'community_name',
        'sub_communities',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sub_communities' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByReligion(Builder $query, string $religion): Builder
    {
        return $query->where('religion', $religion);
    }
}

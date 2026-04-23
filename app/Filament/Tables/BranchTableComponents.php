<?php

namespace App\Filament\Tables;

use App\Models\Branch;
use Filament\Tables;

/**
 * Reusable Branch table components — column + filter.
 *
 * Usage in a Resource's table():
 *
 *     ->columns([
 *         // ... other columns
 *         BranchTableComponents::column(),
 *     ])
 *     ->filters([
 *         // ... other filters
 *         BranchTableComponents::filter(),
 *     ])
 */
class BranchTableComponents
{
    /**
     * Branch column. Toggleable, hidden by default to keep tables clean.
     * Branch users see this column but it's redundant for them (everything is their branch).
     */
    public static function column(string $field = 'branch.name'): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($field)
            ->label('Branch')
            ->badge()
            ->color('info')
            ->placeholder('Global')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Branch filter. Visible only to users who can choose between branches
     * (Super Admin and HO Manager — i.e., users with NO branch_id).
     */
    public static function filter(string $field = 'branch_id'): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make($field)
            ->label('Branch')
            ->options(
                Branch::active()
                    ->orderByDesc('is_head_office')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray()
            )
            ->searchable()
            ->visible(function () {
                $user = auth()->user();
                // Hide filter from branch users — they only see their branch anyway
                return !$user || $user->isSuperAdmin() || $user->branch_id === null;
            });
    }
}

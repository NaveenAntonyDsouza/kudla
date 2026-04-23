<?php

namespace App\Filament\Forms;

use App\Models\Branch;
use Filament\Forms;

/**
 * Reusable Branch select field for any Resource form.
 *
 * Behavior:
 * - Super Admin / HO Manager (no branch_id): can pick ANY active branch
 * - Branch Manager / Branch Staff: pre-filled with their branch, disabled (read-only)
 * - For "global" coupons etc., pass $allowGlobal=true to add a "(Global / All branches)" option
 *
 * Usage:
 *
 *     BranchFormField::make()  // for required branch
 *     BranchFormField::make(allowGlobal: true)  // for optional/global (e.g., Coupons)
 *     BranchFormField::make(label: 'Registered through Branch')
 */
class BranchFormField
{
    public static function make(
        string $name = 'branch_id',
        string $label = 'Branch',
        bool $allowGlobal = false,
        ?string $helperText = null,
    ): Forms\Components\Select {
        $user = auth()->user();
        $isBranchUser = $user && !$user->isSuperAdmin() && $user->branch_id !== null;

        $options = Branch::active()
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($b) => [$b->id => $b->name . ' (' . $b->code . ')'])
            ->toArray();

        $field = Forms\Components\Select::make($name)
            ->label($label)
            ->options($options)
            ->searchable()
            ->preload()
            ->default(function () use ($isBranchUser, $user) {
                // Pre-fill with user's branch_id, fallback to HO
                if ($isBranchUser) {
                    return $user->branch_id;
                }
                return Branch::getHeadOffice()?->id;
            });

        if ($allowGlobal) {
            $field->placeholder('Global (all branches)');
            $field->helperText($helperText ?? 'Leave blank for "global" — visible to all branches.');
        } else {
            $field->required();
            if ($helperText) {
                $field->helperText($helperText);
            }
        }

        // Branch users can't change their assignment — lock the field
        if ($isBranchUser) {
            $field->disabled()->dehydrated();
        }

        return $field;
    }
}

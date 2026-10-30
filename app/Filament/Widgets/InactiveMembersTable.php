<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource;
use App\Models\Profile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Insights → Members Overview: live members not seen for 30+ days, most recently seen first (worth a call). */
class InactiveMembersTable extends TableWidget
{
    protected static ?string $heading = 'Not seen for 30 days or more';
    protected static ?int $sort = 8;
    protected static bool $isLazy = true;
    protected static bool $isDiscovered = false;
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return \App\Support\Permissions::can('view_user_reports');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Profile::query()
                    ->whereNotNull('full_name')
                    ->where('is_active', true)
                    ->whereHas('user', fn (Builder $q) => $q->whereNull('staff_role_id')
                        ->where(fn ($w) => $w->where('last_login_at', '<', now()->subDays(30))->orWhereNull('last_login_at')))
                    ->with('user')
                    ->orderByDesc(\App\Models\User::select('last_login_at')->whereColumn('users.id', 'profiles.user_id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('matri_id')->label('Matri ID')->weight('bold')->color('primary')
                    ->url(fn (Profile $record) => UserResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('full_name')->label('Name')->limit(22),
                Tables\Columns\TextColumn::make('user.last_login_at')->label('Last seen')->since()->placeholder('Never'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}

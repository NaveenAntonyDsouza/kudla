<?php

namespace App\Filament\Pages;

use App\Models\Notification;
use App\Models\Profile;
use App\Models\ReligiousInfo;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class BroadcastNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Broadcast Notification';
    protected static \UnitEnum|string|null $navigationGroup = 'Interests & Reports';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Broadcast Notification';
    protected string $view = 'filament.pages.broadcast-notification';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('send_broadcast');
    }

    /**
     * Block direct URL access for users without permission.
     * Without this, hidden navigation can be bypassed by typing the URL.
     */
    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('send_broadcast');
    }

    public function form(\Filament\Schemas\Schema|Forms\Form $form): \Filament\Schemas\Schema|Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Compose Notification')
                    ->description('Send an in-app notification to a group of users.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Notification Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., New Feature Available!'),

                        Forms\Components\Textarea::make('message')
                            ->label('Notification Message')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('e.g., We have launched a new matching feature...'),
                    ]),

                \Filament\Schemas\Components\Section::make('Target Audience')
                    ->description('Choose who should receive this notification.')
                    ->schema([
                        Forms\Components\Select::make('audience')
                            ->label('Send To')
                            ->options([
                                'all' => 'All Users',
                                'male' => 'All Male Users',
                                'female' => 'All Female Users',
                                'free' => 'Free Users Only',
                                'paid' => 'Paid Users Only',
                                'inactive' => 'Inactive Users (30+ days)',
                            ])
                            ->required()
                            ->default('all')
                            ->live(),

                        Forms\Components\Select::make('religion_filter')
                            ->label('Filter by Religion (optional)')
                            ->options(fn () => ReligiousInfo::distinct('religion')
                                ->whereNotNull('religion')
                                ->pluck('religion', 'religion')
                                ->toArray()
                            )
                            ->placeholder('All religions')
                            ->searchable(),

                        Forms\Components\Select::make('state_filter')
                            ->label('Filter by State (optional)')
                            ->options(fn () => DB::table('location_info')
                                ->distinct()
                                ->whereNotNull('native_state')
                                ->where('native_state', '!=', '')
                                ->pluck('native_state', 'native_state')
                                ->toArray()
                            )
                            ->placeholder('All states')
                            ->searchable(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $query = Profile::whereNotNull('full_name')
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true));

        // Audience filter
        match ($data['audience']) {
            'male' => $query->where('gender', 'male'),
            'female' => $query->where('gender', 'female'),
            'free' => $query->whereDoesntHave('user.userMemberships', fn ($q) =>
                $q->where('is_active', true)->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ),
            'paid' => $query->whereHas('user.userMemberships', fn ($q) =>
                $q->where('is_active', true)->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ),
            'inactive' => $query->whereHas('user', fn ($q) =>
                $q->where(fn ($q2) => $q2->where('last_login_at', '<', now()->subDays(30))->orWhereNull('last_login_at'))
            ),
            default => null, // all
        };

        // Religion filter
        if (! empty($data['religion_filter'])) {
            $query->whereHas('religiousInfo', fn ($q) => $q->where('religion', $data['religion_filter']));
        }

        // State filter
        if (! empty($data['state_filter'])) {
            $query->whereHas('locationInfo', fn ($q) => $q->where('native_state', $data['state_filter']));
        }

        $profiles = $query->with('user')->get();
        $count = 0;

        foreach ($profiles as $profile) {
            if ($profile->user) {
                Notification::create([
                    'user_id' => $profile->user->id,
                    'type' => 'admin_broadcast',
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'data' => ['broadcast' => true],
                    'is_read' => false,
                ]);
                $count++;
            }
        }

        $this->form->fill();

        FilamentNotification::make()
            ->title("Notification sent to {$count} users")
            ->success()
            ->send();
    }

    public function preview(): void
    {
        $data = $this->form->getState();

        $query = Profile::whereNotNull('full_name')
            ->where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('is_active', true));

        match ($data['audience']) {
            'male' => $query->where('gender', 'male'),
            'female' => $query->where('gender', 'female'),
            'free' => $query->whereDoesntHave('user.userMemberships', fn ($q) =>
                $q->where('is_active', true)->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ),
            'paid' => $query->whereHas('user.userMemberships', fn ($q) =>
                $q->where('is_active', true)->where(fn ($q2) => $q2->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ),
            'inactive' => $query->whereHas('user', fn ($q) =>
                $q->where(fn ($q2) => $q2->where('last_login_at', '<', now()->subDays(30))->orWhereNull('last_login_at'))
            ),
            default => null,
        };

        if (! empty($data['religion_filter'])) {
            $query->whereHas('religiousInfo', fn ($q) => $q->where('religion', $data['religion_filter']));
        }

        if (! empty($data['state_filter'])) {
            $query->whereHas('locationInfo', fn ($q) => $q->where('native_state', $data['state_filter']));
        }

        $count = $query->count();

        FilamentNotification::make()
            ->title("This notification will be sent to {$count} users")
            ->info()
            ->send();
    }
}

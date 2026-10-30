<?php

namespace App\Filament\Pages;

use App\Support\MemberFunnel;
use App\Support\Permissions;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Insights → Sign-up Sources: the "How did you hear about us?" answers from
 * registration, with how members from each source got on — the question was
 * asked since launch but never shown (admin audit, Oct 2026).
 */
class SignupSources extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Sign-up Sources';
    protected static ?string $title = 'Sign-up Sources';
    protected static \UnitEnum|string|null $navigationGroup = 'Insights';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.signup-sources';

    /** Period in days: 30, 90 or 365. */
    #[Url]
    public int $days = 90;

    public static function canAccess(): bool
    {
        return Permissions::can('view_engagement_reports');
    }

    protected function getViewData(): array
    {
        $days = in_array($this->days, [30, 90, 365], true) ? $this->days : 90;

        return [
            'days' => $days,
            'sources' => MemberFunnel::sources(now()->subDays($days), now()),
            'devices' => MemberFunnel::devices(now()->subDays($days), now()),
        ];
    }
}

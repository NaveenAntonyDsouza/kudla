<?php

namespace App\Filament\Pages;

use App\Services\EmailActivityReport;
use Filament\Pages\Page;

/**
 * Reports → Email Activity: what the site emailed, what failed, who
 * unsubscribed, and how much of the mailbox's daily sending limit is used.
 */
class EmailActivity extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Email Activity';
    protected static \UnitEnum|string|null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Email Activity';
    protected string $view = 'filament.pages.email-activity';

    /** Period shown: 1, 7 or 30 days (today included). */
    public int $days = 7;

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    public static function canAccess(): bool
    {
        return \App\Support\Permissions::can('view_engagement_reports');
    }

    public function getViewData(): array
    {
        $days = in_array($this->days, [1, 7, 30], true) ? $this->days : 7;

        return ['report' => app(EmailActivityReport::class)->build($days), 'days' => $days];
    }
}

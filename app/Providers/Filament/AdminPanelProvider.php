<?php

namespace App\Providers\Filament;

use App\Models\ThemeSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Set the display timezone for ALL Filament date/time columns,
     * entries, and pickers. Storage stays in UTC (config/app.php
     * timezone = 'UTC'); this only affects how times are rendered +
     * how date-picker input is interpreted in the admin panel.
     *
     * Driven by config('app.display_timezone') so a white-label buyer
     * in another region can change it via APP_DISPLAY_TIMEZONE in .env
     * without editing code. Defaults to Asia/Kolkata.
     *
     * NOTE: this does NOT reach custom formatStateUsing closures that
     * call ->format() on a raw Carbon — those convert explicitly with
     * ->timezone(config('app.display_timezone')). See UserResource
     * "Registered" / "Last Login" columns.
     */
    public function boot(): void
    {
        \Filament\Support\Facades\FilamentTimezone::set(
            config('app.display_timezone', 'Asia/Kolkata')
        );

        // Raise the searchable-Select option cap (Filament's default is 50) so
        // long reference lists scroll fully instead of cutting off — e.g.
        // countries (195), occupations (203), education (192), dioceses (132),
        // weight (103), height (80). Applies to every admin dropdown; a per-field
        // ->optionsLimit() still overrides this. (Only affects searchable
        // selects; non-searchable ones already render all options.)
        \Filament\Forms\Components\Select::configureUsing(
            fn (\Filament\Forms\Components\Select $select) => $select->optionsLimit(300)
        );
    }

    public function panel(Panel $panel): Panel
    {
        // Load branding from database (white-label)
        $theme = $this->getTheme();

        return $panel
            ->default()
            ->id('admin')
            ->path(config('app.admin_path', 'admin'))
            ->login()
            ->brandName(($theme?->site_name ?? 'Matrimony') . ' Admin')
            ->colors([
                'primary' => $this->hexToFilamentColor($theme?->primary_color ?? '#8B1D91'),
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard')->icon('heroicon-o-home')->collapsed(),
                NavigationGroup::make('User Management')->icon('heroicon-o-users')->collapsed(),
                NavigationGroup::make('Verification')->icon('heroicon-o-shield-check')->collapsed(),
                NavigationGroup::make('Membership & Payments')->icon('heroicon-o-credit-card')->collapsed(),
                NavigationGroup::make('Interests & Reports')->icon('heroicon-o-heart')->collapsed(),
                NavigationGroup::make('Content Management')->icon('heroicon-o-document-text')->collapsed(),
                NavigationGroup::make('Reports')->icon('heroicon-o-chart-bar')->collapsed(),
                NavigationGroup::make('Settings')->icon('heroicon-o-cog-6-tooth')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->renderHook('panels::head.end', fn () => <<<'HTML'
                <style>
                    /* Reduce spacing between navigation groups */
                    .fi-sidebar-nav .fi-sidebar-group { margin-top: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important; }
                    .fi-sidebar-nav .fi-sidebar-group + .fi-sidebar-group { border-top: 1px solid rgba(0,0,0,0.05); }
                    .fi-sidebar-group > ul { gap: 0 !important; }
                    /* Reduce group header padding */
                    .fi-sidebar-group-button { padding-block: 0.4rem !important; }
                    /* Reduce item padding */
                    .fi-sidebar-item a { padding-block: 0.3rem !important; }
                    /* Remove extra gap in nav */
                    .fi-sidebar-nav > ul { gap: 0 !important; }
                </style>
            HTML)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Get theme settings from database, with fallback for fresh installs.
     */
    private function getTheme(): ?ThemeSetting
    {
        try {
            return ThemeSetting::getTheme();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Convert hex color to Filament Color array.
     */
    private function hexToFilamentColor(string $hex): array
    {
        return Color::hex($hex);
    }
}

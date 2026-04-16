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
    public function panel(Panel $panel): Panel
    {
        // Load branding from database (white-label)
        $theme = $this->getTheme();

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
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

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
            // Own sign-in, separate from members' (config/auth.php): staff can open the
            // public site in the same browser and see it as a visitor.
            ->authGuard('admin')
            // "My Profile" in the avatar menu — admins change their own password here.
            ->profile(\App\Filament\Pages\Auth\EditAdminProfile::class, isSimple: false)
            // Two-step login: each person sets up an authenticator app under My Profile
            // (8 one-time recovery codes for a lost phone). Site Settings → Alerts & admin
            // security can make it required: everyone without it is sent to set it up first.
            ->multiFactorAuthentication(
                [\App\Filament\Auth\AppAuthentication::make()->recoverable()],
                isRequired: function (): bool {
                    try {
                        return \App\Models\SiteSetting::getValue('admin_two_step_required', '0') === '1';
                    } catch (\Throwable) {
                        return false; // no settings table yet (fresh install, tests)
                    }
                },
            )
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
                // Seven groups, one job each (admin audit, Oct 2026). Today — the work
                // waiting for someone — is open; the rest start folded.
                NavigationGroup::make('Today')->icon('heroicon-o-inbox-stack'),
                NavigationGroup::make('Members')->icon('heroicon-o-users')->collapsed(),
                NavigationGroup::make('Engagement')->icon('heroicon-o-heart')->collapsed(),
                NavigationGroup::make('Money')->icon('heroicon-o-credit-card')->collapsed(),
                NavigationGroup::make('Site & Content')->icon('heroicon-o-document-text')->collapsed(),
                NavigationGroup::make('Insights')->icon('heroicon-o-chart-bar')->collapsed(),
                NavigationGroup::make('Admin & Security')->icon('heroicon-o-lock-closed')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            // one menu entry with tabs: Audit Trail, Lists & Options
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([])
            // Photos members send on WhatsApp, for the inbox (needs the Meta token).
            ->authenticatedRoutes(function () {
                \Illuminate\Support\Facades\Route::get('whatsapp-media/{message}', \App\Http\Controllers\Admin\WhatsAppMediaController::class)
                    ->name('whatsapp-media');
            })
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

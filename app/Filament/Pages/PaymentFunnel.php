<?php

namespace App\Filament\Pages;

use App\Support\AdminModules;
use App\Support\PaymentFunnel as Funnel;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Money → Payment Funnel (18 Nov 2026, owner: "are you collecting enough metrics
 * regarding payment?"). From the plans page to a paid plan, step by step; payment
 * gateways; why payments failed; coupon codes typed; which paid feature stopped free
 * members; and the members worth a call. Numbers in App\Support\PaymentFunnel.
 *
 * Needs the "View Revenue Reports" right, and the Staff tools switch "Payment funnel".
 */
class PaymentFunnel extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Payment Funnel';
    protected static \UnitEnum|string|null $navigationGroup = 'Money';
    protected static ?int $navigationSort = 9;
    protected static ?string $title = 'Payment Funnel';
    protected string $view = 'filament.pages.payment-funnel';

    #[Url]
    public int $days = 7;

    public static function canAccess(): bool
    {
        return AdminModules::paymentInsights() && \App\Support\Permissions::can('view_revenue_reports');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getViewData(): array
    {
        $this->days = in_array($this->days, [1, 7, 30, 90], true) ? $this->days : 7;
        $f = new Funnel($this->days);

        return [
            'steps' => $f->steps(),
            'visitorViews' => $f->visitorPlanViews(),
            'gateways' => $f->byGateway(),
            'reasons' => $f->failureReasons(),
            'coupons' => $f->coupons(),
            'paywall' => $f->paywall(),
            'toCall' => $f->toCall(),
        ];
    }
}

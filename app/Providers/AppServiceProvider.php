<?php

namespace App\Providers;

use App\Services\Payment\PayPalService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaytmService;
use App\Services\Payment\PhonePeService;
use App\Services\Payment\RazorpayService;
use App\Services\Payment\StripeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Payment-gateway registry. Bound as a singleton so the same
        // instance services every controller call. New gateways
        // (Stripe, PayPal, Paytm, PhonePe) are registered here in
        // their respective steps — purely additive, no refactor of
        // existing code.
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $manager = new PaymentGatewayManager();
            $manager->register($app->make(RazorpayService::class));
            $manager->register($app->make(StripeService::class));
            $manager->register($app->make(PayPalService::class));
            $manager->register($app->make(PaytmService::class));
            $manager->register($app->make(PhonePeService::class));

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Carbon ->displayTz() macro: convert a UTC-stored timestamp to
        // the configured display timezone (Asia/Kolkata by default) for
        // rendering. Storage stays UTC; this is display-only.
        //
        // Usage in Blade:  {{ $model->created_at->displayTz()->format('d M Y, h:i A') }}
        //
        // copy() so we never mutate the model's underlying attribute
        // (which other code may read assuming UTC). Returns a new
        // instance in the display timezone. Registered on Illuminate's
        // Carbon (what Eloquent date casts return).
        Carbon::macro('displayTz', function () {
            /** @var Carbon $this */
            return $this->copy()->timezone(config('app.display_timezone', 'Asia/Kolkata'));
        });

        // Carbon ->toUtcIso() macro: serialize a timestamp as UTC ISO-8601
        // (e.g. 2026-05-22T13:44:59+00:00) regardless of the app's display
        // timezone. The mobile/JSON API uses this so its timestamp contract
        // stays UTC and timezone-agnostic — the Flutter client localises to
        // the device. Without this, after the app switched to Asia/Kolkata
        // the API's ->toIso8601String() started emitting +05:30, silently
        // changing the contract the Phase 2a app was built against.
        //
        // copy() so we never mutate the model attribute; utc() converts the
        // instant to UTC; the +00:00 offset matches exactly what the API
        // emitted before the IST switch.
        Carbon::macro('toUtcIso', function () {
            /** @var Carbon $this */
            return $this->copy()->utc()->toIso8601String();
        });
    }
}

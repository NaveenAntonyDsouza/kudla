<?php

namespace App\Providers;

use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\RazorpayService;
use App\Services\Payment\StripeService;
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

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

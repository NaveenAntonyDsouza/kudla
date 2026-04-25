<?php

namespace App\Services\Payment;

/**
 * Registry that resolves PaymentGatewayInterface instances by slug.
 *
 * Bound as a singleton in App\Providers\AppServiceProvider.
 * Concrete gateway services (RazorpayService, StripeService, ...)
 * are registered at boot time. Adding a new gateway later is a
 * single line in the provider's boot — no controller / route /
 * test changes required.
 *
 * Manager intentionally has no business logic. Resolution by slug,
 * filtering by isConfigured(), and listing available gateways are
 * its only responsibilities.
 */
class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> Indexed by slug. */
    private array $gateways = [];

    /**
     * Register a gateway. Called from a service provider's boot().
     * Last-write-wins on slug collision (lets tests swap implementations).
     */
    public function register(PaymentGatewayInterface $gateway): self
    {
        $this->gateways[$gateway->getSlug()] = $gateway;

        return $this;
    }

    /**
     * Resolve a gateway by slug. Returns null when unknown OR when the
     * gateway exists but isn't configured (admin disabled or credentials
     * missing). Controller treats both cases as 404 / 422 so the API
     * doesn't leak which slugs exist server-side.
     */
    public function forSlug(string $slug): ?PaymentGatewayInterface
    {
        return $this->gateways[$slug] ?? null;
    }

    /**
     * All registered gateways, regardless of configured state. Used
     * by admin tooling that needs to display the full list.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAll(): array
    {
        return $this->gateways;
    }

    /**
     * All registered AND configured gateways — the set surfaceable
     * to end users.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getConfigured(): array
    {
        return array_filter(
            $this->gateways,
            fn (PaymentGatewayInterface $g) => $g->isConfigured(),
        );
    }

    /**
     * Replace all registered gateways. Test-only helper — production
     * code uses register().
     */
    public function reset(): void
    {
        $this->gateways = [];
    }
}

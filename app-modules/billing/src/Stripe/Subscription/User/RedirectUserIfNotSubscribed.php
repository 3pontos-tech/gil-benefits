<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use App\Models\Users\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Price;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

readonly class RedirectUserIfNotSubscribed
{
    public function __construct(
        private PlanRepository $planRepository,
        private BillingManager $billingManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Company|Filament $tenant */
        $tenant = Filament::getTenant();

        if ($tenant->hasActivePlan()) {
            return $next($request);
        }

        // TODO: when the company cancels the subscription, the user needs a page to understand what do next
        // TODO: ask the team which kind of page to add here

        $hasActiveSubscription = collect(BillingProviderEnum::activeCases())
            ->contains(fn (BillingProviderEnum $provider): bool => $this->billingManager
                ->getDriver($provider)
                ->hasActiveSubscription($tenant)
            );

        if ($tenant->slug === 'flamma-company') {
            $hasActiveSubscription = true;
        }

        abort_unless($hasActiveSubscription, 403);
        $employee = auth()->user();

        // TODO: Employee needs to pick a plan to continue
        // TODO: the plan is already settled up (by pila) so, let them continue
        /** @var Collection<string, PlanEntity> $availableEmployeesPlans */
        $availableEmployeesPlans = $this->planRepository->getPlansFor('user');

        collect(BillingProviderEnum::checkoutCases())
            ->each(fn (BillingProviderEnum $provider) => $this->billingManager->getDriver($provider)->ensureCustomerExists($employee));

        $hasValidSubscription = collect(BillingProviderEnum::activeCases())
            ->contains(function (BillingProviderEnum $provider) use ($employee, $availableEmployeesPlans): bool {
                $driver = $this->billingManager->getDriver($provider);

                foreach ($availableEmployeesPlans as $plan) {
                    if ($driver->isSubscribed($employee, $plan->slug)) {
                        return true;
                    }
                }

                return false;
            });

        if ($tenant->slug === 'flamma-company') {
            $hasValidSubscription = $this->hasFlammaPriceSubscription($employee);
        }

        if ($hasValidSubscription) {
            return $next($request);
        }

        $route = 'filament.app.pages.available-subscriptions';

        if (request()->routeIs($route)) {
            return $next($request);
        }

        return to_route($route, ['tenant' => $tenant->slug]);
    }

    private function hasFlammaPriceSubscription(User $employee): bool
    {
        $flammaPriceIds = Price::query()
            ->whereJsonContains('metadata->tenant', 'flamma-company')
            ->pluck('provider_price_id');

        if ($flammaPriceIds->isEmpty()) {
            return false;
        }

        return Subscription::query()
            ->where('subscriptionable_type', $employee->getMorphClass())
            ->where('subscriptionable_id', $employee->getKey())
            ->where('stripe_status', 'active')
            ->whereIn('stripe_price', $flammaPriceIds)
            ->exists();
    }
}

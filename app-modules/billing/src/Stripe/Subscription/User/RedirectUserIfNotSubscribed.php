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
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
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
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        if ($tenant->hasActivePlan()) {
            return $next($request);
        }

        $hasActiveSubscription = collect(BillingProviderEnum::activeCases())
            ->contains(fn (BillingProviderEnum $provider): bool => $this->billingManager
                ->getDriver($provider)
                ->hasActiveSubscription($tenant)
            );

        if (! $tenant->subsidizesEmployees()) {
            $hasActiveSubscription = true;
        }

        // Company never paid or cancelled its plan: the member cannot fix the
        // billing themselves, so instead of a bare 403 we send them to a page
        // that explains the situation (ÉPICO 56).
        if (! $hasActiveSubscription) {
            $inactiveRoute = 'filament.app.pages.company-plan-inactive';

            if ($request->routeIs($inactiveRoute)) {
                return $next($request);
            }

            return to_route($inactiveRoute, ['tenant' => $tenant->slug]);
        }

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

        // Sem empregador, a assinatura só vale se foi comprada pelo valor
        // cheio — quem entrou por um preço subsidiado não pode ficar aqui.
        if (! $tenant->subsidizesEmployees()) {
            $hasValidSubscription = $this->hasStandalonePriceSubscription($employee);
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

    private function hasStandalonePriceSubscription(User $employee): bool
    {
        $standalonePriceIds = Price::query()
            ->where('audience', PriceAudienceEnum::Standalone)
            ->pluck('provider_price_id');

        if ($standalonePriceIds->isEmpty()) {
            return false;
        }

        return Subscription::query()
            ->where('subscriptionable_type', $employee->getMorphClass())
            ->where('subscriptionable_id', $employee->getKey())
            ->where('stripe_status', 'active')
            ->whereIn('stripe_price', $standalonePriceIds)
            ->exists();
    }
}

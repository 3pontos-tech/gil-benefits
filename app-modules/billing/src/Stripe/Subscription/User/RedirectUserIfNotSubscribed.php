<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Stripe\Collection;
use TresPontosTech\Billing\Core\Entities\PlanEntity;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

class RedirectUserIfNotSubscribed
{
    public function __construct(private readonly PlanRepository $planRepository) {}

    public function handle(Request $request, Closure $next)
    {
        /** @var Company|Filament $tenant */
        $tenant = Filament::getTenant();
        Cashier::useCustomerModel(User::class);

        if ($tenant->hasStripeId() === false) {
            $tenant->createAsStripeCustomer();
        }

        // TODO: when the company cancels the subscription, the user needs a page to understand what do next
        // TODO: ask the team which kind of page to add here
        abort_unless($tenant->subscribed('company'), 401);

        $employee = auth()->user();

        // TODO: Employee needs to pick a plan to continue
        // TODO: the plan is already settled up (by pila) so, let them continue

        /** @var Collection<string, PlanEntity> $availableEmployeesPlans */
        $availableEmployeesPlans = $this->planRepository->getPlansFor('user');
        foreach ($availableEmployeesPlans as $plan) {
            if ($employee->subscribed($plan->type)) {
                return $next($request);
            }
        }

        $route = 'filament.app.pages.available-subscriptions';

        if (request()->routeIs($route)) {
            return $next($request);
        }

        return to_route($route, ['tenant' => $tenant->slug]);

    }
}

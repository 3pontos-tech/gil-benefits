<?php

namespace TresPontosTech\PanelCompany\Filament\Actions;

use Filament\Actions\Action;
use Laravel\Cashier\Subscription;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;

class TenantSeatsCounterAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tenant-seats-counter';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabled();
        $this->outlined();
        $this->label(fn (): string => $this->countSeats());

    }

    private function countSeats(): string
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        $employeesCount = $tenant->employees()->wherePivot('active', true)->count();

        /** @var CompanyPlan|null $contractualPlan */
        $contractualPlan = $tenant->activeContractualPlan();

        if ($contractualPlan !== null) {
            return sprintf(__('panel-company::resources.actions.seats_counter.label'), $employeesCount, $contractualPlan->seats);
        }

        /** @var Subscription $activeSubscription */
        $activeSubscription = $tenant->subscriptions()->where('stripe_status', '=', 'active')->first();

        return sprintf(__('panel-company::resources.actions.seats_counter.label'), $employeesCount, $activeSubscription->quantity);
    }
}

<?php

namespace TresPontosTech\PanelCompany\Filament\Actions;

use Filament\Actions\Action;
use Laravel\Cashier\Subscription;
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

        /** @var Subscription $activeSubscription */
        $activeSubscription = $tenant->subscriptions()->where('stripe_status', '=', 'active')->first();

        $employeesCount = $tenant->employees()->wherePivot('active', true)->count();

        return sprintf('Assentos: %s/%s', $employeesCount, $activeSubscription->quantity);
    }
}

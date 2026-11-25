<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportErrorDTO;

final readonly class CheckImportSeatLimitAction
{
    public function execute(Company $company, int $joiningCount): ?ImportErrorDTO
    {
        $available = $this->availableSeats($company);

        if ($available === null || $joiningCount <= $available) {
            return null;
        }

        return new ImportErrorDTO(
            row: 0,
            email: 'N/A',
            message: __('user::actions.import_users.errors.seat_limit', ['available' => $available, 'requested' => $joiningCount]),
        );
    }

    private function availableSeats(Company $company): ?int
    {
        $currentActiveCount = $company->employees()->wherePivot('active', true)->count();

        $contractualPlan = $company->activeContractualPlan();

        if ($contractualPlan instanceof CompanyPlan) {
            return $contractualPlan->seats - $currentActiveCount;
        }

        $activeSubscription = $company->subscriptions()->where('stripe_status', 'active')->first();

        if ($activeSubscription === null) {
            return null;
        }

        return $activeSubscription->quantity - $currentActiveCount;
    }
}

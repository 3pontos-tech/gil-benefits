<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\ImportErrorDTO;

class CheckImportSeatLimitAction
{
    /**
     * @param  int  $joiningCount  people created, linked or reactivated by the import; skipped
     *                             rows and rows that need support take no seat
     */
    public function execute(Company $company, int $joiningCount): ?ImportErrorDTO
    {
        $available = $this->availableSeats($company);

        if ($available === null || $joiningCount <= $available) {
            return null;
        }

        return new ImportErrorDTO(
            row: 0,
            email: 'N/A',
            message: sprintf('Limite de assentos excedido. Disponíveis: %d, solicitados: %d.', $available, $joiningCount),
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

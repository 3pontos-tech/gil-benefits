<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

/**
 * When a company changes owner, move the company-account credits from the
 * previous owner to the new one, so pool/ownership queries (which key off
 * company.user_id) keep recognising them. Credits an employee bought for
 * themselves (owner = employee) are left untouched.
 */
final readonly class MigrateCompanyCreditsOwner
{
    public function handle(Company $company, string $previousOwnerId): void
    {
        $newOwnerId = (string) $company->user_id;

        if ($previousOwnerId === $newOwnerId) {
            return;
        }

        DB::transaction(function () use ($company, $previousOwnerId, $newOwnerId): void {
            $companyCredits = UserCredit::query()
                ->where('company_id', $company->getKey())
                ->where('owner_id', $previousOwnerId);

            // Credits still in the previous owner's pool move with the ownership.
            (clone $companyCredits)
                ->where('holder_id', $previousOwnerId)
                ->update(['owner_id' => $newOwnerId, 'holder_id' => $newOwnerId]);

            // Already-distributed company credits only change owner; the employee keeps them.
            (clone $companyCredits)->update(['owner_id' => $newOwnerId]);
        });
    }
}

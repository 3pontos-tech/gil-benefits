<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Observers;

use TresPontosTech\Billing\Core\Jobs\MigrateCompanyCreditsOwnerJob;
use TresPontosTech\Company\Models\Company;

/**
 * Billing-side reactions to company changes. When a company changes owner, the
 * company-account credits must follow the new owner — done off the request via
 * a job so a failure retries instead of breaking the company update.
 */
class CompanyCreditsObserver
{
    public function updated(Company $company): void
    {
        if (! $company->wasChanged('user_id')) {
            return;
        }

        /** @var string $previousOwnerId */
        $previousOwnerId = $company->getOriginal('user_id');

        dispatch(new MigrateCompanyCreditsOwnerJob($company, $previousOwnerId));
    }
}

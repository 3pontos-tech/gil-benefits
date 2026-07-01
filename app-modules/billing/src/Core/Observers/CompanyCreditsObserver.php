<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Observers;

use TresPontosTech\Billing\Core\Actions\Credit\MigrateCompanyCreditsOwner;
use TresPontosTech\Company\Models\Company;

/**
 * Billing-side reactions to company changes. When a company changes owner, the
 * company-account credits must follow the new owner.
 */
class CompanyCreditsObserver
{
    public function __construct(private readonly MigrateCompanyCreditsOwner $migrateCompanyCreditsOwner) {}

    public function updated(Company $company): void
    {
        if (! $company->wasChanged('user_id')) {
            return;
        }

        /** @var string $previousOwnerId */
        $previousOwnerId = $company->getOriginal('user_id');

        $this->migrateCompanyCreditsOwner->handle($company, $previousOwnerId);
    }
}

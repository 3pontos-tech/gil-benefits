<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Company\Events\CompanyOwnershipTransferred;
use TresPontosTech\Credits\Jobs\MigrateCompanyCreditsOwnerJob;

/**
 * The company-account credits follow the new responsible. Runs off the request
 * via a job so a failure retries instead of breaking the transfer.
 */
class MigrateCreditsOnCompanyOwnershipTransferred
{
    public function handle(CompanyOwnershipTransferred $event): void
    {
        dispatch(new MigrateCompanyCreditsOwnerJob($event->company, (string) $event->previousOwner->getKey()));
    }
}

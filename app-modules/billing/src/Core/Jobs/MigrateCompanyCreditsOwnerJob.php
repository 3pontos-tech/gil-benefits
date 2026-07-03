<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Billing\Core\Actions\Credit\MigrateCompanyCreditsOwner;
use TresPontosTech\Company\Models\Company;

/**
 * Moves the company-account credits to the new owner after a company changes
 * hands. Runs off the request so a failure retries on the queue instead of
 * breaking the company update.
 */
class MigrateCompanyCreditsOwnerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly Company $company,
        public readonly string $previousOwnerId,
    ) {}

    public function handle(MigrateCompanyCreditsOwner $action): void
    {
        $action->handle($this->company, $this->previousOwnerId);
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Billing\Core\Actions\Credit\RevokeCreditsFromEmployees;
use TresPontosTech\Company\Models\Company;

class RevokeCreditsFromEmployeesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly Company $company) {}

    public function handle(RevokeCreditsFromEmployees $action): void
    {
        $action->handle($this->company);
    }
}

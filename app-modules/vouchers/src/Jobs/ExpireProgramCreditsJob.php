<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\Actions\ExpireProgramCredits;

class ExpireProgramCreditsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(ExpireProgramCredits $expireProgramCredits): void
    {
        CompanyPlan::query()
            ->where('kind', CompanyPlanKindEnum::CreditsOnly)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->each(fn (CompanyPlan $plan): int => $expireProgramCredits->handle($plan));
    }
}

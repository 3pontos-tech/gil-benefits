<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\CreditTotals;

/**
 * Current-snapshot credit counts (available / in use / used) for a tenant.
 */
final class GetCreditTotals
{
    use BuildsMetricsCacheKey;

    public function handle(Company $tenant): CreditTotals
    {
        $cacheKey = $this->metricsCacheKey('credit_totals', $tenant);

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant): CreditTotals {
            $byStatus = UserCredit::forCompany($tenant)->ownedBy($tenant)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $available = (int) ($byStatus[UserCreditStatusEnum::Available->value] ?? 0);
            $inUse = (int) ($byStatus[UserCreditStatusEnum::InUse->value] ?? 0);
            $used = (int) ($byStatus[UserCreditStatusEnum::Used->value] ?? 0);

            return new CreditTotals($available, $inUse, $used, $available + $inUse + $used);
        });
    }
}

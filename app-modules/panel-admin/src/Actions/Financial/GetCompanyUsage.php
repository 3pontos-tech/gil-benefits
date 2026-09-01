<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementFunnel;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\DTOs\EngagementFunnelRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\CompanyUsageRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Cruzamento de utilização por empresa (STORY-241).
 *
 * Contratados, cadastrados e quem usou no período vêm do `GetEngagementFunnel`,
 * a mesma fonte da STORY-235 — as três telas precisam concordar sobre o que é
 * utilização. O que o funil não responde é "nunca utilizou", que é all-time e
 * portanto independe da janela escolhida.
 */
final class GetCompanyUsage
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'company_usage';

    public function __construct(private readonly GetEngagementFunnel $funnel) {}

    /**
     * @return Collection<int, CompanyUsageRow>
     */
    public function handle(FinancialFilters $filters): Collection
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): Collection => $this->build($filters),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    /**
     * @return Collection<int, CompanyUsageRow>
     */
    private function build(FinancialFilters $filters): Collection
    {
        $rows = $this->funnel->handle(new EngagementFilters(
            start: $filters->period->start,
            end: $filters->period->end,
            companyIds: $filters->companyIds,
        ));

        $neverUsed = $this->neverUsedByCompany();

        $bucketIds = Company::query()->where('slug', Company::DEFAULT_SLUG)->pluck('id')->all();

        return $rows
            // O funil de engajamento devolve toda empresa, inclusive a
            // empresa-balde dos avulsos, que não é cliente (D-11).
            ->reject(fn (EngagementFunnelRow $row): bool => in_array($row->companyId, $bucketIds, strict: true))
            ->map(fn (EngagementFunnelRow $row): CompanyUsageRow => new CompanyUsageRow(
                companyId: $row->companyId,
                companyName: $row->companyName,
                seats: $row->seats,
                registered: $row->registered,
                usedInPeriod: $row->withCompletedAppointment,
                neverUsed: max(0, $row->registered - (int) ($neverUsed[$row->companyId] ?? 0)),
            ))
            ->values();
    }

    /**
     * Quantos colaboradores ativos de cada empresa já realizaram ao menos uma
     * consultoria, em qualquer momento.
     *
     * O complemento disso sobre os cadastrados é o "nunca utilizaram" da story.
     *
     * @return array<string, int>
     */
    private function neverUsedByCompany(): array
    {
        /** @var array<string, int> $counts */
        $counts = DB::table('company_employees')
            ->join('appointments', function ($join): void {
                $join->on('appointments.user_id', '=', 'company_employees.user_id')
                    ->on('appointments.company_id', '=', 'company_employees.company_id');
            })
            ->where('company_employees.active', true)
            ->where('appointments.status', AppointmentStatus::Completed->value)
            ->whereNull('appointments.deleted_at')
            ->groupBy('company_employees.company_id')
            ->selectRaw('company_employees.company_id as company_id, COUNT(DISTINCT company_employees.user_id) as total')
            ->pluck('total', 'company_id')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();

        return $counts;
    }
}

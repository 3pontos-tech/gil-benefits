<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ExtraCreditsRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;

/**
 * Créditos utilizados por empresa, separados por origem (STORY-240).
 *
 * A origem de cada crédito é uma coluna de `user_credits`: `credit_order_id`
 * quando foi comprado, `grant_id` quando foi cortesia, nenhum dos dois quando
 * veio do plano. O consumo é a consultoria que o gastou — daí o vínculo com
 * `appointments`, cuja data delimita o mês.
 */
final class GetExtraCredits
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'extra_credits';

    /**
     * @return Collection<int, ExtraCreditsRow>
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
     * @return Collection<int, ExtraCreditsRow>
     */
    private function build(FinancialFilters $filters): Collection
    {
        $rows = DB::table('user_credits')
            ->join('appointments', 'appointments.id', '=', 'user_credits.appointment_id')
            ->leftJoin('credit_orders', 'credit_orders.id', '=', 'user_credits.credit_order_id')
            ->whereNull('user_credits.deleted_at')
            ->whereNull('appointments.deleted_at')
            ->whereBetween('appointments.appointment_at', [$filters->period->start, $filters->period->end])
            ->when(
                $filters->isFilteredByCompany(),
                fn ($query) => $query->whereIn('user_credits.company_id', $filters->companyIds),
            )
            ->groupBy('user_credits.company_id')
            ->selectRaw('user_credits.company_id as company_id')
            ->selectRaw('SUM(CASE WHEN user_credits.credit_order_id IS NULL AND user_credits.grant_id IS NULL THEN 1 ELSE 0 END) as from_plan')
            ->selectRaw('SUM(CASE WHEN user_credits.credit_order_id IS NOT NULL THEN 1 ELSE 0 END) as purchased')
            ->selectRaw('SUM(CASE WHEN user_credits.grant_id IS NOT NULL THEN 1 ELSE 0 END) as granted')
            // Valor proporcional: o pedido guarda o total, e cada crédito vale a
            // fatia dele. Somar `amount_cents` por crédito multiplicaria o valor
            // do pedido pela quantidade comprada.
            ->selectRaw('COALESCE(SUM(credit_orders.amount_cents * 1.0 / NULLIF(credit_orders.quantity, 0)), 0) as purchased_value')
            ->get();

        $names = $this->companyNames($rows->pluck('company_id')->all());

        return $rows
            ->map(fn (object $row): ExtraCreditsRow => new ExtraCreditsRow(
                companyId: (string) $row->company_id,
                companyName: $names[$row->company_id] ?? '',
                fromPlan: (int) $row->from_plan,
                purchased: (int) $row->purchased,
                granted: (int) $row->granted,
                purchasedValueCents: (int) round((float) $row->purchased_value),
            ))
            ->filter(fn (ExtraCreditsRow $row): bool => $row->companyName !== '')
            ->sortByDesc(fn (ExtraCreditsRow $row): int => $row->purchasedValueCents)
            ->values();
    }

    /**
     * Nomes das empresas em uma consulta, já sem a empresa-balde: os avulsos não
     * são cliente e não têm plano contratado para extrapolar (D-11).
     *
     * @param  array<int, mixed>  $companyIds
     * @return array<string, string>
     */
    private function companyNames(array $companyIds): array
    {
        if ($companyIds === []) {
            return [];
        }

        /** @var array<string, string> $names */
        $names = Company::query()
            ->withoutDefault()
            ->whereIn('id', $companyIds)
            ->pluck('name', 'id')
            ->all();

        return $names;
    }
}

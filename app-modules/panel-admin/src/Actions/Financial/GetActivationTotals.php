<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ActivationTotals;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialPeriod;

/**
 * Utilização agregada da base de beneficiários (STORY-242).
 *
 * O universo são os colaboradores ativos das empresas, não todo usuário do
 * banco: contar administradores e consultores como "inativos" deprimiria a taxa
 * de ativação sem dizer nada sobre o produto.
 *
 * O mês anterior é reconstruído com as datas que existem — `created_at` do
 * vínculo e `email_verified_at` do usuário —, então a variação não depende de
 * snapshot nenhum.
 */
final class GetActivationTotals
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'activation_totals';

    /**
     * @return array{current: ActivationTotals, previous: ActivationTotals|null}
     */
    public function handle(FinancialFilters $filters): array
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            function () use ($filters): array {
                $current = $this->totalsFor($filters->period, $filters->companyIds);
                $previous = $this->totalsFor($filters->period->previous(), $filters->companyIds);

                return [
                    'current' => $current,
                    'previous' => $previous->total > 0 ? $previous : null,
                ];
            },
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    /**
     * @param  array<int, string>  $companyIds
     */
    private function totalsFor(FinancialPeriod $period, array $companyIds): ActivationTotals
    {
        $base = DB::table('company_employees')
            ->join('users', 'users.id', '=', 'company_employees.user_id')
            ->join('companies', 'companies.id', '=', 'company_employees.company_id')
            // Todo usuário criado é anexado à empresa-balde pelo observer, então
            // sem este corte a taxa de ativação incluiria admins, consultores e
            // assinantes avulsos — e sairia diluída sem dizer nada sobre o
            // produto vendido às empresas (D-11).
            ->where('companies.slug', '!=', Company::DEFAULT_SLUG)
            ->whereNull('companies.deleted_at')
            ->where('company_employees.active', true)
            ->whereNull('users.deleted_at')
            ->where('company_employees.created_at', '<=', $period->end)
            ->when($companyIds !== [], fn ($query) => $query->whereIn('company_employees.company_id', $companyIds));

        $total = (clone $base)->distinct()->count('company_employees.user_id');

        // Os três grupos precisam particionar a base, senão o total não fecha e
        // "inativos" some sem ninguém perceber. A ordem é: quem usou é ativo
        // (fato), depois quem nunca verificou o e-mail, e o resto é inativo — a
        // mesma ordem do detalhe por colaborador, para as duas telas não
        // classificarem a mesma pessoa de formas diferentes.
        $active = (clone $base)
            ->join('appointments', function ($join): void {
                $join->on('appointments.user_id', '=', 'company_employees.user_id')
                    ->on('appointments.company_id', '=', 'company_employees.company_id');
            })
            ->where('appointments.status', AppointmentStatus::Completed->value)
            ->whereNull('appointments.deleted_at')
            ->whereBetween('appointments.appointment_at', [$period->start, $period->end])
            ->distinct()
            ->count('company_employees.user_id');

        // Sem acesso é relativo ao mês: quem verificou depois do fim do período
        // não tinha acesso naquele mês. Quem usou já foi contado como ativo.
        $withoutAccess = (clone $base)
            ->where(fn ($query) => $query
                ->whereNull('users.email_verified_at')
                ->orWhere('users.email_verified_at', '>', $period->end))
            ->whereNotExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('appointments')
                ->whereColumn('appointments.user_id', 'company_employees.user_id')
                ->whereColumn('appointments.company_id', 'company_employees.company_id')
                ->where('appointments.status', AppointmentStatus::Completed->value)
                ->whereNull('appointments.deleted_at')
                ->whereBetween('appointments.appointment_at', [$period->start, $period->end]))
            ->distinct()
            ->count('company_employees.user_id');

        return new ActivationTotals(
            total: $total,
            active: $active,
            inactive: max(0, $total - $withoutAccess - $active),
            withoutAccess: $withoutAccess,
        );
    }
}

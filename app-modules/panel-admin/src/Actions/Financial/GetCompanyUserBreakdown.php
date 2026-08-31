<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\PanelAdmin\DTOs\Financial\CompanyUserUsage;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialPeriod;

/**
 * Colaboradores de uma empresa com o status de utilização de cada um
 * (STORY-241, cenário 3).
 *
 * Sem cache: é o detalhe de uma empresa só, aberto sob demanda num modal, e
 * guardar isso encheria o cache com uma entrada por empresa visitada.
 */
final class GetCompanyUserBreakdown
{
    /**
     * @return Collection<int, CompanyUserUsage>
     */
    public function handle(string $companyId, FinancialPeriod $period): Collection
    {
        $usedInPeriod = $this->usersWithConsultancy($companyId, $period, withinPeriod: true);
        $usedEver = $this->usersWithConsultancy($companyId, $period, withinPeriod: false);

        return DB::table('company_employees')
            ->join('users', 'users.id', '=', 'company_employees.user_id')
            ->where('company_employees.company_id', $companyId)
            ->where('company_employees.active', true)
            ->whereNull('users.deleted_at')
            ->orderBy('users.name')
            ->selectRaw('users.id as id, users.name as name, users.email as email, users.email_verified_at as email_verified_at')
            ->get()
            ->map(function (object $row) use ($usedInPeriod, $usedEver): CompanyUserUsage {
                /** @var array<string, mixed> $attributes */
                $attributes = (array) $row;

                [$label, $color] = $this->statusOf($attributes, $usedInPeriod, $usedEver);

                return new CompanyUserUsage(
                    name: (string) ($attributes['name'] ?? ''),
                    email: (string) ($attributes['email'] ?? ''),
                    statusLabel: $label,
                    statusColor: $color,
                );
            })
            ->values();
    }

    /**
     * A ordem das checagens é a mesma da agregação da STORY-242, e por isso
     * importa: uso é fato e vence o proxy de e-mail verificado. Assim uma
     * pessoa nunca aparece classificada de um jeito no card e de outro aqui.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $usedInPeriod
     * @param  array<string, bool>  $usedEver
     * @return array{0: string, 1: string}
     */
    private function statusOf(array $attributes, array $usedInPeriod, array $usedEver): array
    {
        $id = (string) ($attributes['id'] ?? '');

        if (isset($usedInPeriod[$id])) {
            return [__('panel-admin::widgets.financial.usage.status_active'), 'success'];
        }

        if (($attributes['email_verified_at'] ?? null) === null) {
            return [__('panel-admin::widgets.financial.usage.status_no_access'), 'gray'];
        }

        if (isset($usedEver[$id])) {
            return [__('panel-admin::widgets.financial.usage.status_lapsed'), 'warning'];
        }

        return [__('panel-admin::widgets.financial.usage.status_never'), 'danger'];
    }

    /**
     * @return array<string, bool>
     */
    private function usersWithConsultancy(string $companyId, FinancialPeriod $period, bool $withinPeriod): array
    {
        /** @var array<string, bool> $ids */
        $ids = DB::table('appointments')
            ->where('company_id', $companyId)
            ->where('status', AppointmentStatus::Completed->value)
            ->whereNull('deleted_at')
            ->when(
                $withinPeriod,
                fn ($query) => $query->whereBetween('appointment_at', [$period->start, $period->end]),
            )
            ->distinct()
            ->pluck('user_id')
            ->mapWithKeys(fn (mixed $id): array => [(string) $id => true])
            ->all();

        return $ids;
    }
}

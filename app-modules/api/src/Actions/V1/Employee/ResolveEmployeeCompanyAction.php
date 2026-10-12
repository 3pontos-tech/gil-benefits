<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use Filament\Facades\Filament;
use TresPontosTech\Api\DTOs\EmployeeCompanyAccess;
use TresPontosTech\Billing\Core\Actions\ResolveBenefitAccess;
use TresPontosTech\Company\Models\Company;

final readonly class ResolveEmployeeCompanyAction
{
    public function __construct(private ResolveBenefitAccess $resolveBenefitAccess) {}

    /**
     * Escolhe a empresa do app, que no painel é a aberta na tela: a primeira empresa com vínculo
     * ativo em que a pessoa tem o benefício (ResolveBenefitAccess), a empregadora antes da padrão
     * e, entre empregadoras, o vínculo mais antigo. Sem nenhuma liberada, devolve o motivo da
     * primeira.
     *
     * A escolhida vira o tenant da requisição (sem disparar eventos), porque cota, crédito e
     * agendamento já perguntam a empresa por `ResolveQuotaAllowance::companyIdFor()`, que lê o
     * tenant. Fora de um painel, o escopo de tenancy do Filament não filtra consulta nenhuma.
     */
    public function handle(User $user): EmployeeCompanyAccess
    {
        $companies = $user->companies()
            ->wherePivot('active', true)
            ->orderByRaw('CASE WHEN companies.slug = ? THEN 1 ELSE 0 END', [Company::DEFAULT_SLUG])
            ->oldest('company_employees.created_at')
            ->orderBy('companies.id')
            ->get();

        $denial = null;

        foreach ($companies as $company) {
            $access = $this->resolveBenefitAccess->for($user, $company);

            if ($access->isGranted()) {
                Filament::setTenant($company, isQuiet: true);

                return new EmployeeCompanyAccess($company);
            }

            $denial ??= $access;
        }

        return new EmployeeCompanyAccess(null, $denial);
    }
}

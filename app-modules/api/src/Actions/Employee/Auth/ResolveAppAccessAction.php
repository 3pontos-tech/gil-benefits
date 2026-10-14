<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Auth;

use App\Models\Users\User;
use TresPontosTech\Api\DTOs\Employee\Auth\AppAccess;
use TresPontosTech\Api\Enums\Employee\Auth\AppAccessDenial;
use TresPontosTech\Billing\Core\Actions\ResolveBenefitAccess;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

final readonly class ResolveAppAccessAction
{
    public function __construct(private ResolveBenefitAccess $resolveBenefitAccess) {}

    /**
     * A regra inteira de quem usa o app, num lugar só e sem efeito colateral: consultor nunca entra
     * (todo cadastro, consultor inclusive, ganha vínculo com a empresa padrão); de resto, a primeira
     * empresa com vínculo ativo onde a pessoa tem o benefício pago, pela régua do painel, com a
     * empregadora antes da padrão e o vínculo mais antigo primeiro. Sem nenhuma, o motivo é o da
     * primeira da ordem. Quem fixa a empresa na requisição é quem chama (middleware e login).
     */
    public function handle(User $user): AppAccess
    {
        if ($user->hasRole(Roles::Consultant->value)) {
            return AppAccess::denied(AppAccessDenial::NotAudience);
        }

        $companies = $user->companies()
            ->wherePivot('active', true)
            ->orderByRaw('CASE WHEN companies.slug = ? THEN 1 ELSE 0 END', [Company::DEFAULT_SLUG])
            ->oldest('company_employees.created_at')
            ->orderBy('companies.id')
            ->get();

        $denial = null;

        foreach ($companies as $company) {
            $benefit = $this->resolveBenefitAccess->for($user, $company);

            if ($benefit->isGranted()) {
                return AppAccess::granted($company);
            }

            $denial ??= AppAccessDenial::fromBenefit($benefit);
        }

        return AppAccess::denied($denial ?? AppAccessDenial::NotAudience);
    }
}

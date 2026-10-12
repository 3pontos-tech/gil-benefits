<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use TresPontosTech\Api\DTOs\EmployeeProfile;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Tenant\Models\TenantMember;

final readonly class BuildEmployeeProfileAction
{
    public function __construct(private ResolveQuotaAllowance $resolveQuotaAllowance) {}

    /**
     * Junta o que o `MeResource` precisa.
     *
     * A empresa é a do app (ResolveEmployeeCompanyAction), lida pelo mesmo `companyIdFor()` que
     * cota e créditos usam: a empregadora com o benefício pago, ou a padrão para assinante
     * individual e voucher. "Membro desde" é a entrada nessa empresa; sem vínculo, a data do
     * cadastro.
     */
    public function handle(User $user): EmployeeProfile
    {
        $user->loadMissing(['detail', 'anamnese', 'media']);

        $company = $user->companies()
            ->withPivot('department_id')
            ->whereKey($this->resolveQuotaAllowance->companyIdFor($user))
            ->with('media')
            ->first();

        /** @var TenantMember|null $membership */
        $membership = $company?->getRelation('pivot');

        return new EmployeeProfile(
            user: $user,
            company: $company,
            department: $this->departmentOf($membership),
            memberSince: $membership->created_at ?? $user->created_at,
        );
    }

    private function departmentOf(?TenantMember $membership): ?Department
    {
        if ($membership?->department_id === null) {
            return null;
        }

        return Department::query()->find($membership->department_id);
    }
}

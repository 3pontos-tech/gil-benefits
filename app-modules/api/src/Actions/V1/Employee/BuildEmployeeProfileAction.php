<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\V1\Employee;

use App\Models\Users\User;
use TresPontosTech\Api\DTOs\EmployeeProfile;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\Tenant\Models\TenantMember;

final readonly class BuildEmployeeProfileAction
{
    /**
     * Junta o que o `MeResource` precisa.
     *
     * A empresa é a empregadora (`employerCompanyId()`): a de verdade para quem tem uma,
     * a padrão para assinante individual e voucher. Sem tenant Filament na API, é o mesmo
     * contexto que cota e créditos já usam. "Membro desde" é a entrada nessa empresa;
     * sem vínculo, a data do cadastro.
     */
    public function handle(User $user): EmployeeProfile
    {
        $user->loadMissing(['detail', 'anamnese', 'media']);

        $company = $user->companies()
            ->withPivot('department_id')
            ->whereKey($user->employerCompanyId())
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

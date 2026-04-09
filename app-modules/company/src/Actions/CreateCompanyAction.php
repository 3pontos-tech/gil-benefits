<?php

namespace TresPontosTech\Company\Actions;

use App\Models\Users\User;
use TresPontosTech\Company\DTOs\CompanyDTO;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

final class CreateCompanyAction
{
    public function execute(CompanyDTO $dto): Company
    {
        $user = User::query()->where('id', $dto->userId)->firstOrFail();
        $company = Company::query()->create($dto->jsonSerialize());

        $user->companies()->attach($company);
        $user->assignRole(Roles::CompanyOwner);

        return $company;
    }
}

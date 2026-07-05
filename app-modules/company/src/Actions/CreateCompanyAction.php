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

        // Owner role lives in the pivot (and is also derived from companies.user_id).
        $user->companies()->attach($company, ['role' => Roles::CompanyOwner->value]);

        return $company;
    }
}

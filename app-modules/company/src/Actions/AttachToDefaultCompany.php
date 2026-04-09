<?php

namespace TresPontosTech\Company\Actions;

use App\Models\Users\User;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

final class AttachToDefaultCompany
{
    public function execute(User $user, Roles $role): void
    {
        $company = Company::query()->firstOrCreate(
            [
                'slug' => 'flamma-company',
            ],
            [
                'name' => 'Flamma',
                'user_id' => User::query()->first()->getKey(),
                'integration_access_key' => Uuid::uuid4(),
                'tax_id' => config('company.tax_id'),
            ]
        );

        $company->employees()->syncWithoutDetaching($user);
        $user->assignRole($role->value);
    }
}

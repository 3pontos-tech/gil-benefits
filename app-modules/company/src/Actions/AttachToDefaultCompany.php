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
                'slug' => Company::DEFAULT_SLUG,
            ],
            [
                'name' => 'Flamma',
                'user_id' => User::query()->first()->getKey(),
                'integration_access_key' => Uuid::uuid4(),
                'tax_id' => config('company.tax_id'),
            ]
        );

        // Everyone (including consultants) joins the shared company; the company
        // role lives in the pivot as employee.
        $company->employees()->syncWithoutDetaching([
            $user->getKey() => ['role' => Roles::Employee->value],
        ]);

        // Global identity role: consultants keep theirs, everyone else is a baseline user.
        $user->assignRole(($role === Roles::Consultant ? Roles::Consultant : Roles::User)->value);
    }
}

<?php

namespace TresPontosTech\Consultants\Observers;

use App\Models\Users\User;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Permissions\Roles;

class ConsultantObserver
{
    public function created(Consultant $consultant): void
    {
        $user = User::query()->firstOrCreate([
            'email' => $consultant->email,
        ], [
            'name' => $consultant->name,
            'password' => $consultant->email,
        ]);

        $consultant->user()->associate($user)->save();
        $user->assignRole(Roles::Consultant);

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
    }
}

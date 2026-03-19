<?php

namespace TresPontosTech\Tenant\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\DTOs\UserDTO;

class CreateExternalUserAction
{
    public function execute(UserDTO $userDTO): void
    {
        $company = Company::query()->findOrFail($userDTO->tenant_id);

        DB::transaction(function () use ($company, $userDTO): void {
            $user = User::query()->create([
                'name' => $userDTO->name,
                'email' => $userDTO->email,
                'password' => $userDTO->password,
                'external_id' => $userDTO->external_id,
                'crm_id' => $userDTO->crm_id,
            ]);

            $company->employees()->save($user);
        });
    }
}

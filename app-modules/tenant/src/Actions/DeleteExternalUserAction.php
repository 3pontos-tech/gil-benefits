<?php

namespace TresPontosTech\Tenant\Actions;

use TresPontosTech\Company\Models\Company;

class DeleteExternalUserAction
{
    public function execute(string $tenant, string $userId): void
    {
        $company = Company::query()->findOrFail($tenant);
        $user = $company->employees()->where('user_id', $userId)->firstOrFail();

        $user->delete();
        $company->employees()->detach($user);

    }
}

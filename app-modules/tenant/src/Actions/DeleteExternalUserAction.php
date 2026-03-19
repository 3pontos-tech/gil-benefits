<?php

namespace TresPontosTech\Tenant\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;

class DeleteExternalUserAction
{
    public function execute(string $tenant, string $userId): void
    {
        $company = Company::query()->findOrFail($tenant);
        $user = $company->employees()->where('user_id', $userId)->firstOrFail();

        DB::transaction(function () use ($company, $user): void {
            $company->employees()->detach($user);
            $user->delete();
        });
    }
}

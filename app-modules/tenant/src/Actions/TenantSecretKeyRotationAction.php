<?php

namespace TresPontosTech\Tenant\Actions;

use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;

class TenantSecretKeyRotationAction
{
    public function generate(Company $company): string
    {
        $key = Uuid::uuid4();
        $company->generateToken($key);

        return $key;
    }
}

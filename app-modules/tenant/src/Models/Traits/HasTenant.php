<?php

namespace TresPontosTech\Tenant\Models\Traits;

use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait HasTenant
{

    public function canAccessTenant(Model $tenant): bool
    {
        $tenantKey = $this->getTenantRelationship();

        return $this->{$tenantKey}()->whereKey($tenant)->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        $tenant = $this->getTenantRelationship();

        return $this->{$tenant};
    }

    public function getTenantRelationship(): string
    {
        return 'companies';
    }

}

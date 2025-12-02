<?php

namespace TresPontosTech\Tenant\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Company\Models\Company;

class CompanyPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any companies.
     */
    protected function canViewAny(User $user): bool
    {
        // All authenticated users can view companies (filtered by tenant isolation)
        return true;
    }

    /**
     * Determine whether the user can view the company.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Users can view companies they belong to
        return $user->canAccessTenant($model);
    }

    /**
     * Determine whether the user can create companies.
     */
    protected function canCreate(User $user): bool
    {
        // All authenticated users can create companies
        return true;
    }

    /**
     * Determine whether the user can update the company.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Only owners and managers can update companies
        if (!$this->isOwnerOrManager($user)) {
            return false;
        }

        // Check if user belongs to this company
        return $user->canAccessTenant($model);
    }

    /**
     * Determine whether the user can delete the company.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Only owners can delete companies
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if user is owner of this specific company
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;
            
        return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can restore the company.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Only owners can restore companies
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if user was owner of this specific company
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;
            
        return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can permanently delete the company.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Only owners can force delete companies
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if user is owner of this specific company
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;
            
        return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
    }
}

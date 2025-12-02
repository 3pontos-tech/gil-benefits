<?php

namespace TresPontosTech\Tenant\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Tenant\Models\TenantMember;

class TenantMemberPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any tenant members.
     */
    protected function canViewAny(User $user): bool
    {
        // Only owners and managers can view tenant members
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can view the tenant member.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var TenantMember $model */
        
        // Users can view their own membership
        if ($model->user_id === $user->id) {
            return true;
        }

        // Owners and managers can view members in their companies
        if ($this->isOwnerOrManager($user)) {
            $userCompanyIds = $user->companies->pluck('id');
            return $userCompanyIds->contains($model->company_id);
        }

        return false;
    }

    /**
     * Determine whether the user can create tenant members.
     */
    protected function canCreate(User $user): bool
    {
        // Only owners and managers can add members to companies
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can update the tenant member.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var TenantMember $model */
        
        // Only owners can update member roles
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if the member belongs to user's company
        $userCompanyIds = $user->companies->pluck('id');
        return $userCompanyIds->contains($model->company_id);
    }

    /**
     * Determine whether the user can delete the tenant member.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var TenantMember $model */
        
        // Users can leave companies (delete their own membership)
        if ($model->user_id === $user->id) {
            return true;
        }

        // Only owners can remove members from companies
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if the member belongs to user's company
        $userCompanyIds = $user->companies->pluck('id');
        return $userCompanyIds->contains($model->company_id);
    }

    /**
     * Determine whether the user can restore the tenant member.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Only owners can restore tenant members
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the tenant member.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Only owners can force delete tenant members
        return $this->isOwner($user);
    }
}
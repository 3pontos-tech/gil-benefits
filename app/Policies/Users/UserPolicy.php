<?php

namespace App\Policies\Users;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any users.
     */
    protected function canViewAny(User $user): bool
    {
        // Only owners and managers can view all users
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can view the user.
     */
    protected function canView(User $user, Model $model): bool
    {
        // Users can view their own profile
        if ($user->is($model)) {
            return true;
        }

        // Owners and managers can view users in their companies
        if ($this->isOwnerOrManager($user)) {
            // Check if the target user belongs to any of the current user's companies
            $userCompanyIds = $user->companies->pluck('id');
            $modelCompanyIds = $model->companies->pluck('id');
            
            return $userCompanyIds->intersect($modelCompanyIds)->isNotEmpty();
        }

        return false;
    }

    /**
     * Determine whether the user can create users.
     */
    protected function canCreate(User $user): bool
    {
        // Only owners and managers can create users
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can update the user.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        // Users can update their own profile
        if ($user->is($model)) {
            return true;
        }

        // Only owners can update other users
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can delete the user.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        // Users cannot delete themselves
        if ($user->is($model)) {
            return false;
        }

        // Only owners can delete users
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the user.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Only owners can restore users
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Only owners can force delete users
        return $this->isOwner($user);
    }
}

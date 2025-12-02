<?php

namespace App\Policies\Users;

use App\Models\Users\Detail;
use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class DetailPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any details.
     */
    protected function canViewAny(User $user): bool
    {
        // Only owners and managers can view all details
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can view the detail.
     */
    protected function canView(User $user, Model $model): bool
    {
        // Users can view their own details
        if ($model->user_id === $user->id) {
            return true;
        }

        // Owners and managers can view details of users in their companies
        if ($this->isOwnerOrManager($user)) {
            $detailUser = $model->user;
            if ($detailUser) {
                $userCompanyIds = $user->companies->pluck('id');
                $detailUserCompanyIds = $detailUser->companies->pluck('id');
                
                return $userCompanyIds->intersect($detailUserCompanyIds)->isNotEmpty();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create details.
     */
    protected function canCreate(User $user): bool
    {
        // All authenticated users can create their own details
        return true;
    }

    /**
     * Determine whether the user can update the detail.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        // Users can update their own details
        if ($model->user_id === $user->id) {
            return true;
        }

        // Only owners can update other users' details
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can delete the detail.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        // Users can delete their own details
        if ($model->user_id === $user->id) {
            return true;
        }

        // Only owners can delete other users' details
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the detail.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Only owners can restore details
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the detail.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Only owners can force delete details
        return $this->isOwner($user);
    }
}

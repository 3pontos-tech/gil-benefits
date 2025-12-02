<?php

namespace TresPontosTech\Billing\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Billing\Core\Models\Price;

class PricePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any prices.
     */
    protected function canViewAny(User $user): bool
    {
        // All authenticated users can view prices
        return true;
    }

    /**
     * Determine whether the user can view the price.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var Price $model */
        
        // All authenticated users can view price details
        return true;
    }

    /**
     * Determine whether the user can create prices.
     */
    protected function canCreate(User $user): bool
    {
        // Partner collaborators cannot create prices
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can create prices
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can update the price.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var Price $model */
        
        // Partner collaborators cannot update prices
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can update prices
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can delete the price.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var Price $model */
        
        // Partner collaborators cannot delete prices
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can delete prices
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the price.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        /** @var Price $model */
        
        // Partner collaborators cannot restore prices
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can restore prices
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the price.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        /** @var Price $model */
        
        // Partner collaborators cannot force delete prices
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can force delete prices
        return $this->isOwner($user);
    }
}
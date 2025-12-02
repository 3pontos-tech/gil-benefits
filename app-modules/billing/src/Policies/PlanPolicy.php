<?php

namespace TresPontosTech\Billing\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Billing\Core\Models\Plan;

class PlanPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any plans.
     */
    protected function canViewAny(User $user): bool
    {
        // All authenticated users can view available plans
        return true;
    }

    /**
     * Determine whether the user can view the plan.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var Plan $model */
        
        // All authenticated users can view plan details
        return true;
    }

    /**
     * Determine whether the user can create plans.
     */
    protected function canCreate(User $user): bool
    {
        // Partner collaborators cannot create plans
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can create plans
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can update the plan.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var Plan $model */
        
        // Partner collaborators cannot update plans
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can update plans
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can delete the plan.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var Plan $model */
        
        // Partner collaborators cannot delete plans
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can delete plans
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the plan.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        /** @var Plan $model */
        
        // Partner collaborators cannot restore plans
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can restore plans
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the plan.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        /** @var Plan $model */
        
        // Partner collaborators cannot force delete plans
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can force delete plans
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can subscribe to the plan.
     */
    public function subscribe(User $user, Plan $plan): bool
    {
        // Partner collaborators cannot manage subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can manage company subscriptions
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can view plan pricing.
     */
    public function viewPricing(User $user, Plan $plan): bool
    {
        // All authenticated users can view plan pricing
        return true;
    }

    /**
     * Determine whether the user can manage plan features.
     */
    public function manageFeatures(User $user, Plan $plan): bool
    {
        // Partner collaborators cannot manage plan features
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only system administrators (owners) can manage plan features
        return $this->isOwner($user);
    }
}
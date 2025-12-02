<?php

namespace TresPontosTech\Billing\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Billing\Core\Models\Subscriptions\SubscriptionItem;

class SubscriptionItemPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any subscription items.
     */
    protected function canViewAny(User $user): bool
    {
        // Partner collaborators cannot view subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners and managers can view subscription items
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can view the subscription item.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var SubscriptionItem $model */
        
        // Partner collaborators cannot view subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Check if subscription item belongs to user's subscription
        if ($model->subscription) {
            return app(\TresPontosTech\Billing\Policies\SubscriptionPolicy::class)
                ->view($user, $model->subscription);
        }

        return false;
    }

    /**
     * Determine whether the user can create subscription items.
     */
    protected function canCreate(User $user): bool
    {
        // Partner collaborators cannot create subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can create subscription items
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can update the subscription item.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var SubscriptionItem $model */
        
        // Partner collaborators cannot update subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can update subscription items
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if subscription item belongs to user's subscription
        if ($model->subscription) {
            return app(\TresPontosTech\Billing\Policies\SubscriptionPolicy::class)
                ->update($user, $model->subscription);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the subscription item.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        // Partner collaborators cannot delete subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can delete subscription items
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can restore the subscription item.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Partner collaborators cannot restore subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can restore subscription items
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the subscription item.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Partner collaborators cannot force delete subscription items
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can force delete subscription items
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can manage subscription item quantities.
     */
    public function manageQuantity(User $user, SubscriptionItem $subscriptionItem): bool
    {
        // Partner collaborators cannot manage quantities
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can manage quantities
        return $this->canUpdate($user, $subscriptionItem);
    }

    /**
     * Determine whether the user can view subscription item usage.
     */
    public function viewUsage(User $user, SubscriptionItem $subscriptionItem): bool
    {
        // Partner collaborators cannot view usage
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Owners and managers can view usage
        if (!$this->isOwnerOrManager($user)) {
            return false;
        }

        // Check if subscription item belongs to user's subscription
        if ($subscriptionItem->subscription) {
            return app(\TresPontosTech\Billing\Policies\SubscriptionPolicy::class)
                ->view($user, $subscriptionItem->subscription);
        }

        return false;
    }
}
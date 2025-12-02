<?php

namespace TresPontosTech\Billing\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     */
    protected function canViewAny(User $user): bool
    {
        // Partner collaborators cannot view subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners and managers can view subscriptions
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can view the subscription.
     */
    protected function canView(User $user, Model $model): bool
    {
        // Partner collaborators cannot view subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Check if subscription belongs to user's company
        if (method_exists($model, 'company') && $model->company) {
            return $user->companies->contains($model->company);
        }

        // For user subscriptions, check if it belongs to the user
        if (isset($model->user_id)) {
            return $model->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create subscriptions.
     */
    protected function canCreate(User $user): bool
    {
        // Partner collaborators cannot create subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can create subscriptions
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can update the subscription.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        // Partner collaborators cannot update subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can update subscriptions
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if subscription belongs to user's company
        if (method_exists($model, 'company') && $model->company) {
            return $user->companies->contains($model->company);
        }

        // For user subscriptions, check if it belongs to the user
        if (isset($model->user_id)) {
            return $model->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the subscription.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        // Partner collaborators cannot delete subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can cancel subscriptions
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can restore the subscription.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Partner collaborators cannot restore subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can restore subscriptions
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Partner collaborators cannot force delete subscriptions
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can force delete subscriptions
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can manage subscription billing.
     */
    public function manageBilling(User $user, Model $model): bool
    {
        // Partner collaborators cannot manage billing
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can manage billing
        return $this->canUpdate($user, $model);
    }

    /**
     * Determine whether the user can view subscription invoices.
     */
    public function viewInvoices(User $user, Model $model): bool
    {
        // Partner collaborators cannot view invoices
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Owners and managers can view invoices
        if (!$this->isOwnerOrManager($user)) {
            return false;
        }

        // Check if subscription belongs to user's company
        if (method_exists($model, 'company') && $model->company) {
            return $user->companies->contains($model->company);
        }

        // For user subscriptions, check if it belongs to the user
        if (isset($model->user_id)) {
            return $model->user_id === $user->id;
        }

        return false;
    }
}
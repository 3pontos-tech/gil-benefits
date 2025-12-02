<?php

namespace TresPontosTech\Consultants\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any consultants.
     */
    protected function canViewAny(User $user): bool
    {
        // All authenticated users can view consultants (for booking appointments)
        return true;
    }

    /**
     * Determine whether the user can view the consultant.
     */
    protected function canView(User $user, Model $model): bool
    {
        /** @var Consultant $model */
        
        // All authenticated users can view consultant profiles (for booking)
        return true;
    }

    /**
     * Determine whether the user can create consultants.
     */
    protected function canCreate(User $user): bool
    {
        // Only owners and managers can create consultants
        return $this->isOwnerOrManager($user);
    }

    /**
     * Determine whether the user can update the consultant.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var Consultant $model */
        
        // Consultants can update their own profile
        if ($model->user_id === $user->id) {
            return true;
        }

        // Owners and managers can update consultants in their companies
        if ($this->isOwnerOrManager($user)) {
            $consultantUser = $model->user;
            if ($consultantUser) {
                $userCompanyIds = $user->companies->pluck('id');
                $consultantUserCompanyIds = $consultantUser->companies->pluck('id');
                
                return $userCompanyIds->intersect($consultantUserCompanyIds)->isNotEmpty();
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the consultant.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var Consultant $model */
        
        // Only owners can delete consultants
        if (!$this->isOwner($user)) {
            return false;
        }

        // Check if consultant belongs to user's company
        $consultantUser = $model->user;
        if ($consultantUser) {
            $userCompanyIds = $user->companies->pluck('id');
            $consultantUserCompanyIds = $consultantUser->companies->pluck('id');
            
            return $userCompanyIds->intersect($consultantUserCompanyIds)->isNotEmpty();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the consultant.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        // Only owners can restore consultants
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the consultant.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        // Only owners can force delete consultants
        return $this->isOwner($user);
    }
}

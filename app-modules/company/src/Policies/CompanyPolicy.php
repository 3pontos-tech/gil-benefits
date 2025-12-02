<?php

namespace TresPontosTech\Company\Policies;

use App\Models\Users\User;
use App\Policies\BasePolicy;
use Illuminate\Database\Eloquent\Model;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
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
        
        // Partner collaborators can only view their partner company
        if ($user->isPartnerCollaborator()) {
            $partnerCompany = $user->getPartnerCompany();
            return $partnerCompany && $partnerCompany->is($model);
        }

        // Users can view companies they belong to
        return $user->companies->contains($model);
    }

    /**
     * Determine whether the user can create companies.
     */
    protected function canCreate(User $user): bool
    {
        // Partner collaborators cannot create companies
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only users without existing companies or system admins can create companies
        return $user->companies->isEmpty() || $this->isOwner($user);
    }

    /**
     * Determine whether the user can update the company.
     */
    protected function canUpdate(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Partner collaborators cannot update companies
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can update company details
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;

        return $userRole === CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can delete the company.
     */
    protected function canDelete(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Partner collaborators cannot delete companies
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can delete companies
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;

        return $userRole === CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can restore the company.
     */
    protected function canRestore(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Partner collaborators cannot restore companies
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can restore companies
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;

        return $userRole === CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can permanently delete the company.
     */
    protected function canForceDelete(User $user, Model $model): bool
    {
        /** @var Company $model */
        
        // Partner collaborators cannot force delete companies
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can force delete companies
        $userRole = $user->companies()
            ->where('companies.id', $model->id)
            ->first()?->pivot?->role;

        return $userRole === CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can manage company members.
     */
    public function manageMembers(User $user, Company $company): bool
    {
        // Partner collaborators cannot manage members
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners and managers can manage members
        $userRole = $user->companies()
            ->where('companies.id', $company->id)
            ->first()?->pivot?->role;

        return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
    }

    /**
     * Determine whether the user can manage company settings.
     */
    public function manageSettings(User $user, Company $company): bool
    {
        // Partner collaborators cannot manage settings
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can manage settings
        $userRole = $user->companies()
            ->where('companies.id', $company->id)
            ->first()?->pivot?->role;

        return $userRole === CompanyRoleEnum::Owner;
    }

    /**
     * Determine whether the user can view company analytics.
     */
    public function viewAnalytics(User $user, Company $company): bool
    {
        // Partner collaborators cannot view analytics
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Owners and managers can view analytics
        $userRole = $user->companies()
            ->where('companies.id', $company->id)
            ->first()?->pivot?->role;

        return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
    }

    /**
     * Determine whether the user can access billing information.
     */
    public function accessBilling(User $user, Company $company): bool
    {
        // Partner collaborators cannot access billing
        if ($user->isPartnerCollaborator()) {
            return false;
        }

        // Only owners can access billing information
        $userRole = $user->companies()
            ->where('companies.id', $company->id)
            ->first()?->pivot?->role;

        return $userRole === CompanyRoleEnum::Owner;
    }
}
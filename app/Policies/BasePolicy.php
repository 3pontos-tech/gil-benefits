<?php

namespace App\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $this->logAuthorizationAttempt($user, 'viewAny', null);
        
        $granted = $this->canViewAny($user);
        $this->logAuthorizationDecision($user, 'viewAny', null, $granted);
        
        return $granted;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $model): bool
    {
        $this->logAuthorizationAttempt($user, 'view', $model);
        
        // Check tenant isolation first
        if (!$this->canAccessTenant($user, $model)) {
            $this->logAuthorizationDecision($user, 'view', $model, false, 'Tenant isolation violation');
            return false;
        }
        
        $granted = $this->canView($user, $model);
        $this->logAuthorizationDecision($user, 'view', $model, $granted);
        
        return $granted;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $this->logAuthorizationAttempt($user, 'create', null);
        
        $granted = $this->canCreate($user);
        $this->logAuthorizationDecision($user, 'create', null, $granted);
        
        return $granted;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        $this->logAuthorizationAttempt($user, 'update', $model);
        
        // Check tenant isolation first
        if (!$this->canAccessTenant($user, $model)) {
            $this->logAuthorizationDecision($user, 'update', $model, false, 'Tenant isolation violation');
            return false;
        }
        
        $granted = $this->canUpdate($user, $model);
        $this->logAuthorizationDecision($user, 'update', $model, $granted);
        
        return $granted;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool
    {
        $this->logAuthorizationAttempt($user, 'delete', $model);
        
        // Check tenant isolation first
        if (!$this->canAccessTenant($user, $model)) {
            $this->logAuthorizationDecision($user, 'delete', $model, false, 'Tenant isolation violation');
            return false;
        }
        
        $granted = $this->canDelete($user, $model);
        $this->logAuthorizationDecision($user, 'delete', $model, $granted);
        
        return $granted;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Model $model): bool
    {
        $this->logAuthorizationAttempt($user, 'restore', $model);
        
        // Check tenant isolation first
        if (!$this->canAccessTenant($user, $model)) {
            $this->logAuthorizationDecision($user, 'restore', $model, false, 'Tenant isolation violation');
            return false;
        }
        
        $granted = $this->canRestore($user, $model);
        $this->logAuthorizationDecision($user, 'restore', $model, $granted);
        
        return $granted;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        $this->logAuthorizationAttempt($user, 'forceDelete', $model);
        
        // Check tenant isolation first
        if (!$this->canAccessTenant($user, $model)) {
            $this->logAuthorizationDecision($user, 'forceDelete', $model, false, 'Tenant isolation violation');
            return false;
        }
        
        $granted = $this->canForceDelete($user, $model);
        $this->logAuthorizationDecision($user, 'forceDelete', $model, $granted);
        
        return $granted;
    }

    /**
     * Check if user can access the tenant (company) associated with the model.
     */
    protected function canAccessTenant(User $user, Model $model): bool
    {
        // If the model doesn't have a company relationship, allow access
        if (!method_exists($model, 'company') && !isset($model->company_id)) {
            return true;
        }

        // For partner collaborators, restrict access to their partner company only
        if ($user->isPartnerCollaborator()) {
            $partnerCompany = $user->getPartnerCompany();
            
            if (!$partnerCompany) {
                return false;
            }

            // Check if model belongs to the partner company
            if (method_exists($model, 'company')) {
                return $model->company && $model->company->is($partnerCompany);
            }
            
            if (isset($model->company_id)) {
                return $model->company_id === $partnerCompany->id;
            }
        }

        // For non-partner collaborators, check if they have access to the company
        if (method_exists($model, 'company') && $model->company) {
            return $user->canAccessTenant($model->company);
        }
        
        if (isset($model->company_id)) {
            $company = \TresPontosTech\Company\Models\Company::find($model->company_id);
            return $company && $user->canAccessTenant($company);
        }

        return true;
    }

    /**
     * Check if user has the required role for the action.
     */
    protected function hasRole(User $user, \TresPontosTech\Company\Enums\CompanyRoleEnum|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];
        
        // Check if user has any of the required roles in any company
        foreach ($user->companies as $company) {
            $userRole = $company->pivot->role ?? null;
            if ($userRole && in_array($userRole, $roles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user is owner or manager of any company.
     */
    protected function isOwnerOrManager(User $user): bool
    {
        return $this->hasRole($user, [
            \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
            \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
        ]);
    }

    /**
     * Check if user is owner of any company.
     */
    protected function isOwner(User $user): bool
    {
        return $this->hasRole($user, \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner);
    }

    /**
     * Log authorization attempts for audit purposes.
     */
    protected function logAuthorizationAttempt(User $user, string $action, ?Model $model): void
    {
        // Log basic attempt
        Log::channel('security')->info('Authorization attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'is_partner_collaborator' => $user->isPartnerCollaborator(),
            'partner_company_id' => $user->getPartnerCompany()?->id,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Log authorization decision with audit service.
     */
    protected function logAuthorizationDecision(User $user, string $action, ?Model $model, bool $granted, ?string $reason = null): void
    {
        app(\App\Services\AuthorizationAuditService::class)->logPolicyCheck(
            $user,
            static::class,
            $action,
            $model,
            $granted,
            ['reason' => $reason]
        );
    }

    // Abstract methods that must be implemented by concrete policies

    /**
     * Determine whether the user can view any models.
     */
    abstract protected function canViewAny(User $user): bool;

    /**
     * Determine whether the user can view the model.
     */
    abstract protected function canView(User $user, Model $model): bool;

    /**
     * Determine whether the user can create models.
     */
    abstract protected function canCreate(User $user): bool;

    /**
     * Determine whether the user can update the model.
     */
    abstract protected function canUpdate(User $user, Model $model): bool;

    /**
     * Determine whether the user can delete the model.
     */
    abstract protected function canDelete(User $user, Model $model): bool;

    /**
     * Determine whether the user can restore the model.
     */
    abstract protected function canRestore(User $user, Model $model): bool;

    /**
     * Determine whether the user can permanently delete the model.
     */
    abstract protected function canForceDelete(User $user, Model $model): bool;
}
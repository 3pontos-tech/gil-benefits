<?php

namespace App\Providers;

use App\Models\Users\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Enums\CompanyRoleEnum;
use TresPontosTech\Company\Models\Company;

class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Core application models
        \App\Models\Users\User::class => \App\Policies\Users\UserPolicy::class,
        \App\Models\Users\Detail::class => \App\Policies\Users\DetailPolicy::class,
        
        // Module models
        \TresPontosTech\Appointments\Models\Appointment::class => \TresPontosTech\Appointments\Policies\AppointmentPolicy::class,
        \TresPontosTech\Company\Models\Company::class => \TresPontosTech\Company\Policies\CompanyPolicy::class,
        \TresPontosTech\Consultants\Models\Consultant::class => \TresPontosTech\Consultants\Policies\ConsultantPolicy::class,
        \TresPontosTech\Tenant\Models\TenantMember::class => \TresPontosTech\Tenant\Policies\TenantMemberPolicy::class,
        
        // Billing module models
        \TresPontosTech\Billing\Core\Models\Plan::class => \TresPontosTech\Billing\Policies\PlanPolicy::class,
        \TresPontosTech\Billing\Core\Models\Price::class => \TresPontosTech\Billing\Policies\PricePolicy::class,
        \TresPontosTech\Billing\Core\Models\Subscriptions\Subscription::class => \TresPontosTech\Billing\Policies\SubscriptionPolicy::class,
        \TresPontosTech\Billing\Core\Models\Subscriptions\SubscriptionItem::class => \TresPontosTech\Billing\Policies\SubscriptionItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    /**
     * Register authorization gates for complex business rules.
     */
    protected function registerGates(): void
    {
        // Register gate event listeners for audit logging
        Gate::after(function (User $user, string $ability, bool $result, array $arguments = []) {
            app(\App\Services\AuthorizationAuditService::class)->logGateCheck(
                $user,
                $ability,
                $arguments,
                $result
            );
        });
        // Panel access gates
        Gate::define('access-admin-panel', function (User $user) {
            // Partner collaborators cannot access admin panel
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can access admin panel
            return $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner->value,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager->value,
                ])
                ->exists();
        });

        Gate::define('access-company-panel', function (User $user) {
            // Partner collaborators cannot access company panel
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can access company panel
            return $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner->value,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager->value,
                ])
                ->exists();
        });

        Gate::define('access-consultant-panel', function (User $user) {
            // Partner collaborators cannot access consultant panel
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Users with consultant profiles can access consultant panel
            return \TresPontosTech\Consultants\Models\Consultant::where('user_id', $user->id)->exists();
        });

        Gate::define('access-user-panel', function (User $user) {
            // All authenticated users can access user panel
            return true;
        });

        Gate::define('access-guest-panel', function (?User $user) {
            // Guest panel is accessible to everyone
            return true;
        });

        // Business rule gates
        Gate::define('create-appointment', function (User $user) {
            // Check if user can create appointments based on subscription limits
            return $user->canCreateAppointment();
        });

        Gate::define('manage-company-members', function (User $user, Company $company) {
            // Only owners and managers can manage company members
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            ]);
        });

        Gate::define('manage-company-settings', function (User $user, Company $company) {
            // Only owners can manage company settings
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
        });

        Gate::define('view-company-analytics', function (User $user, Company $company) {
            // Owners and managers can view company analytics
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            ]);
        });

        Gate::define('manage-appointment-status', function (User $user, Appointment $appointment) {
            // Users can manage their own appointments
            if ($appointment->user_id === $user->id) {
                return true;
            }

            // Consultants can manage appointments assigned to them
            $consultant = \TresPontosTech\Consultants\Models\Consultant::where('user_id', $user->id)->first();
            if ($consultant && $appointment->consultant_id === $consultant->id) {
                return true;
            }

            // Owners and managers can manage appointments in their companies
            $appointmentUser = $appointment->user;
            if ($appointmentUser) {
                $userCompanyIds = $user->companies()
                    ->wherePivotIn('role', ['owner', 'manager'])
                    ->pluck('companies.id');
                    
                $appointmentUserCompanyIds = $appointmentUser->companies->pluck('id');
                
                return $userCompanyIds->intersect($appointmentUserCompanyIds)->isNotEmpty();
            }

            return false;
        });

        Gate::define('access-billing-information', function (User $user, Company $company) {
            // Only owners can access billing information
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
        });

        Gate::define('partner-collaborator-restrictions', function (User $user) {
            // Gate to check if user has partner collaborator restrictions
            return $user->isPartnerCollaborator();
        });

        Gate::define('tenant-isolation', function (User $user, $model) {
            // Ensure users can only access data from their own companies
            if ($user->isPartnerCollaborator()) {
                $partnerCompany = $user->getPartnerCompany();
                
                if (!$partnerCompany) {
                    return false;
                }

                // For Company models, check direct access
                if ($model instanceof Company) {
                    return $model->is($partnerCompany);
                }

                // Check if model belongs to the partner company
                if (method_exists($model, 'company')) {
                    return $model->company && $model->company->is($partnerCompany);
                }
                
                if (isset($model->company_id)) {
                    return $model->company_id === $partnerCompany->id;
                }
                
                // If no company relationship, deny access for partner collaborators
                return false;
            }

            // For non-partner collaborators, check if they have access to the company
            if ($model instanceof Company) {
                return $user->canAccessTenant($model);
            }
            
            if (method_exists($model, 'company') && $model->company) {
                return $user->canAccessTenant($model->company);
            }
            
            if (isset($model->company_id)) {
                $company = Company::find($model->company_id);
                return $company && $user->canAccessTenant($company);
            }

            return true;
        });

        // Additional business rule gates
        Gate::define('manage-subscription-limits', function (User $user, Company $company) {
            // Only owners can manage subscription limits and quotas
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
        });

        Gate::define('export-company-data', function (User $user, Company $company) {
            // Only owners and managers can export company data
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            ]);
        });

        Gate::define('delete-company-data', function (User $user, Company $company) {
            // Only owners can delete company data
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
        });

        Gate::define('manage-integrations', function (User $user, Company $company) {
            // Only owners and managers can manage third-party integrations
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            ]);
        });

        Gate::define('view-audit-logs', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot view audit logs
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // If no specific company, check if user is owner/manager of any company
            if (!$company) {
                return $user->companies()
                    ->wherePivotIn('role', [
                        \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner->value,
                        \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager->value,
                    ])
                    ->exists();
            }

            // For specific company, check role
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            ]);
        });

        Gate::define('manage-user-roles', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$company, $targetUser] = $arguments;
            
            // Partner collaborators cannot manage roles
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage user roles
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if ($userRole !== \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner) {
                return false;
            }

            // Cannot modify own role
            if ($user->is($targetUser)) {
                return false;
            }

            // Target user must be member of the company
            return $targetUser->companies->contains($company);
        });

        Gate::define('access-sensitive-data', function (User $user, ?string $dataType = null) {
            // Partner collaborators have restricted access to sensitive data
            if ($user->isPartnerCollaborator()) {
                // Only allow access to basic appointment and user data
                return in_array($dataType, ['appointments', 'profile']);
            }

            // Owners and managers can access most sensitive data
            return $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner->value,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager->value,
                ])
                ->exists();
        });

        Gate::define('bulk-operations', function (User $user, string $operation, ?Company $company = null) {
            // Partner collaborators cannot perform bulk operations
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Destructive operations require owner role
            $destructiveOperations = ['delete', 'force-delete', 'bulk-delete'];
            if (in_array($operation, $destructiveOperations)) {
                if ($company) {
                    $userRole = $user->companies()
                        ->where('companies.id', $company->id)
                        ->first()?->pivot?->role;

                    return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
                }

                return $user->companies()
                    ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                    ->exists();
            }

            // Non-destructive bulk operations require manager or owner role
            $hasOwnerOrManagerRole = $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
                ])
                ->exists();
            
            if (!$hasOwnerOrManagerRole) {
                return false;
            }

            // If company is specified, check if user has access to it
            if ($company) {
                return $user->companies->contains($company);
            }

            return true;
        });

        // Advanced authorization gates for enhanced security
        Gate::define('access-system-administration', function (User $user) {
            // Partner collaborators cannot access system administration
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only system owners can access system administration
            return $user->companies()
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('manage-security-settings', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot manage security settings
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage security settings
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return $userRole === CompanyRoleEnum::Owner;
            }

            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-financial-data', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot access financial data
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can access financial data
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return $userRole === CompanyRoleEnum::Owner;
            }

            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('manage-api-access', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot manage API access
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can manage API access
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
            }

            return $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
                ])
                ->exists();
        });

        Gate::define('access-reporting-analytics', function (User $user, ?Company $company = null) {
            // Partner collaborators have limited access to reporting
            if ($user->isPartnerCollaborator()) {
                // They can only view basic reports for their partner company
                $partnerCompany = $user->getPartnerCompany();
                return $company && $partnerCompany && $partnerCompany->is($company);
            }

            // Owners and managers can access reporting and analytics
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
            }

            return $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
                ])
                ->exists();
        });

        Gate::define('manage-webhooks-integrations', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot manage webhooks and integrations
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage webhooks and integrations
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return $userRole === CompanyRoleEnum::Owner;
            }

            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-audit-trail', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot access audit trails
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can access audit trails
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
            }

            return $user->companies()
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
                ])
                ->exists();
        });

        Gate::define('manage-data-retention', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot manage data retention
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage data retention policies
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return $userRole === CompanyRoleEnum::Owner;
            }

            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('emergency-access', function (User $user, string $reason = '') {
            // Partner collaborators cannot use emergency access
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Log emergency access attempt
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
                $user,
                'emergency_access',
                null,
                true,
                "Emergency access requested: {$reason}",
                [
                    'type' => 'emergency_access',
                    'reason' => $reason,
                    'requires_review' => true,
                ]
            );

            // Create security incident for emergency access
            app(\App\Services\SecurityLoggingService::class)->createIncidentReport(
                'emergency_access_used',
                [
                    'severity' => 'high',
                    'description' => 'Emergency access was used by a user',
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'reason' => $reason,
                    'affected_resources' => ['emergency_access'],
                    'mitigation_steps' => [
                        'Review emergency access usage',
                        'Verify legitimacy of access',
                        'Monitor subsequent user actions',
                    ],
                ]
            );

            // Only system owners can use emergency access
            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('impersonate-user', function (User $user, User $targetUser) {
            // Partner collaborators cannot impersonate users
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Users cannot impersonate themselves
            if ($user->is($targetUser)) {
                return false;
            }

            // Log impersonation attempt
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
                $user,
                'impersonate_user',
                $targetUser,
                true,
                'User impersonation requested',
                [
                    'type' => 'user_impersonation',
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'requires_review' => true,
                ]
            );

            // Only system owners can impersonate users
            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        // Advanced business rule gates for complex authorization scenarios
        Gate::define('cross-tenant-data-access', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$sourceCompany, $targetCompany] = $arguments;
            // Partner collaborators cannot access cross-tenant data
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Users must be owners in both companies to access cross-tenant data
            $isOwnerInSource = $user->companies()
                ->where('companies.id', $sourceCompany->id)
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();

            $isOwnerInTarget = $user->companies()
                ->where('companies.id', $targetCompany->id)
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();

            return $isOwnerInSource && $isOwnerInTarget;
        });

        Gate::define('manage-subscription-quotas', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$company, $quotaType] = $arguments;
            
            // Partner collaborators cannot manage quotas
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage subscription quotas for the specific company
            return $user->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-historical-data', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            $monthsBack = $arguments[1] ?? 12;
            // Partner collaborators have limited historical data access
            if ($user->isPartnerCollaborator()) {
                // Partner collaborators can only access last 3 months
                return $monthsBack <= 3;
            }

            // Employees can access up to 6 months
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if ($userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Employee) {
                return $monthsBack <= 6;
            }

            // Managers can access up to 24 months
            if ($userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager) {
                return $monthsBack <= 24;
            }

            // Owners have unlimited access
            return $userRole === \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner;
        });

        Gate::define('perform-data-export', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            $company = $arguments[0];
            $exportType = $arguments[1];
            $dataTypes = $arguments[2] ?? [];
            
            // Partner collaborators cannot export data
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if (!$userRole) {
                return false;
            }

            // Define sensitive data types that require owner access
            $sensitiveDataTypes = ['billing', 'financial', 'user_personal_data', 'audit_logs'];
            $hasSensitiveData = !empty(array_intersect($dataTypes, $sensitiveDataTypes));

            // Bulk exports require manager or owner role
            if ($exportType === 'bulk' && !in_array($userRole, [
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            ])) {
                return false;
            }

            // Sensitive data exports require owner role
            if ($hasSensitiveData && $userRole !== \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner) {
                return false;
            }

            return true;
        });

        Gate::define('modify-system-configuration', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            // Partner collaborators cannot modify system configuration
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only system owners can modify configuration
            return $user->companies()
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-multi-tenant-resources', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $companyIds = $arguments[0];
            // Partner collaborators cannot access multi-tenant resources
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // User must have access to all specified companies
            $userCompanyIds = $user->companies->pluck('id')->toArray();
            $companyIds = is_array($companyIds) ? $companyIds : [$companyIds];
            $hasAccessToAll = empty(array_diff($companyIds, $userCompanyIds));

            if (!$hasAccessToAll) {
                // Log unauthorized multi-tenant access attempt
                app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                    'unauthorized_multi_tenant_access',
                    'User attempted to access companies they do not belong to',
                    [
                        'user_id' => $user->id,
                        'requested_companies' => $companyIds,
                        'user_companies' => $userCompanyIds,
                        'unauthorized_companies' => array_diff($companyIds, $userCompanyIds),
                        'severity' => 'medium',
                    ]
                );
            }

            return $hasAccessToAll;
        });

        Gate::define('escalate-support-ticket', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$company, $escalationLevel] = $arguments;
            // Partner collaborators cannot escalate support tickets
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if (!$userRole) {
                return false;
            }

            // Define escalation level requirements
            $escalationRequirements = [
                'level_1' => [\TresPontosTech\Company\Enums\CompanyRoleEnum::Employee, \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager, \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner],
                'level_2' => [\TresPontosTech\Company\Enums\CompanyRoleEnum::Manager, \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner],
                'level_3' => [\TresPontosTech\Company\Enums\CompanyRoleEnum::Owner],
                'emergency' => [\TresPontosTech\Company\Enums\CompanyRoleEnum::Owner],
            ];

            $allowedRoles = $escalationRequirements[$escalationLevel] ?? [];
            
            return in_array($userRole, $allowedRoles);
        });

        // Advanced authorization gates for enhanced security and business rules
        Gate::define('access-multi-tenant-data', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$sourceCompany, $targetCompany] = $arguments;
            
            // Partner collaborators cannot access multi-tenant data
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // User must be owner in both companies
            $isOwnerInSource = $user->companies()
                ->where('companies.id', $sourceCompany->id)
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();

            $isOwnerInTarget = $user->companies()
                ->where('companies.id', $targetCompany->id)
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();

            return $isOwnerInSource && $isOwnerInTarget;
        });

        Gate::define('manage-subscription-billing', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            
            // Partner collaborators cannot manage billing
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage subscription billing
            return $user->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-compliance-data', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot access compliance data
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can access compliance data
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
            }

            return $user->companies()
                ->wherePivotIn('role', [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager])
                ->exists();
        });

        Gate::define('manage-data-privacy-settings', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot manage data privacy settings
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage data privacy settings
            if ($company) {
                return $user->companies()
                    ->where('companies.id', $company->id)
                    ->wherePivot('role', CompanyRoleEnum::Owner)
                    ->exists();
            }

            return $user->companies()
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-advanced-analytics', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            $analyticsType = $arguments[1] ?? 'basic';
            
            // Partner collaborators have limited analytics access
            if ($user->isPartnerCollaborator()) {
                // Only basic analytics for partner collaborators
                return $analyticsType === 'basic';
            }

            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if (!$userRole) {
                return false;
            }

            // Define analytics access levels
            $analyticsAccess = [
                'basic' => [CompanyRoleEnum::Employee, CompanyRoleEnum::Manager, CompanyRoleEnum::Owner],
                'advanced' => [CompanyRoleEnum::Manager, CompanyRoleEnum::Owner],
                'financial' => [CompanyRoleEnum::Owner],
                'predictive' => [CompanyRoleEnum::Owner],
            ];

            $allowedRoles = $analyticsAccess[$analyticsType] ?? [];
            
            return in_array($userRole, $allowedRoles);
        });

        Gate::define('manage-team-permissions', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$company, $targetUser] = $arguments;
            
            // Partner collaborators cannot manage team permissions
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Users cannot modify their own permissions
            if ($user->is($targetUser)) {
                return false;
            }

            // Only owners and managers can manage team permissions
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if (!in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager])) {
                return false;
            }

            // Managers cannot modify owner permissions
            if ($userRole === CompanyRoleEnum::Manager) {
                $targetRole = $targetUser->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return $targetRole !== CompanyRoleEnum::Owner;
            }

            return true;
        });

        Gate::define('access-integration-logs', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot access integration logs
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can access integration logs
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
            }

            return $user->companies()
                ->wherePivotIn('role', [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager])
                ->exists();
        });

        Gate::define('manage-backup-restore', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$company, $operation] = $arguments;
            
            // Partner collaborators cannot manage backups
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage backup and restore operations
            $isOwner = $user->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();

            if (!$isOwner) {
                return false;
            }

            // Log critical backup/restore operations
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
                $user,
                "backup_restore:{$operation}",
                null,
                true,
                "User authorized for {$operation} operation",
                [
                    'company_id' => $company->id,
                    'operation' => $operation,
                    'type' => 'backup_restore',
                    'requires_review' => true,
                ]
            );

            return true;
        });

        Gate::define('access-performance-metrics', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            $metricsLevel = $arguments[1] ?? 'basic';
            
            // Partner collaborators have limited metrics access
            if ($user->isPartnerCollaborator()) {
                return $metricsLevel === 'basic';
            }

            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if (!$userRole) {
                return false;
            }

            // Define metrics access levels
            $metricsAccess = [
                'basic' => [CompanyRoleEnum::Employee, CompanyRoleEnum::Manager, CompanyRoleEnum::Owner],
                'detailed' => [CompanyRoleEnum::Manager, CompanyRoleEnum::Owner],
                'system' => [CompanyRoleEnum::Owner],
            ];

            $allowedRoles = $metricsAccess[$metricsLevel] ?? [];
            
            return in_array($userRole, $allowedRoles);
        });

        Gate::define('manage-notification-settings', function (User $user, ...$arguments) {
            if (count($arguments) < 2) {
                return false;
            }
            
            [$company, $notificationType] = $arguments;
            
            // Partner collaborators can only manage their own notifications
            if ($user->isPartnerCollaborator()) {
                return $notificationType === 'personal';
            }

            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            if (!$userRole) {
                return false;
            }

            // Define notification management permissions
            $notificationAccess = [
                'personal' => [CompanyRoleEnum::Employee, CompanyRoleEnum::Manager, CompanyRoleEnum::Owner],
                'team' => [CompanyRoleEnum::Manager, CompanyRoleEnum::Owner],
                'company' => [CompanyRoleEnum::Owner],
                'system' => [CompanyRoleEnum::Owner],
            ];

            $allowedRoles = $notificationAccess[$notificationType] ?? [];
            
            return in_array($userRole, $allowedRoles);
        });

        Gate::define('access-developer-tools', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot access developer tools
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can access developer tools
            if ($company) {
                return $user->companies()
                    ->where('companies.id', $company->id)
                    ->wherePivot('role', CompanyRoleEnum::Owner)
                    ->exists();
            }

            return $user->companies()
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('manage-feature-flags', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            
            // Partner collaborators cannot manage feature flags
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage feature flags
            return $user->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();
        });

        Gate::define('access-error-tracking', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot access error tracking
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can access error tracking
            if ($company) {
                $userRole = $user->companies()
                    ->where('companies.id', $company->id)
                    ->first()?->pivot?->role;
                
                return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
            }

            return $user->companies()
                ->wherePivotIn('role', [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager])
                ->exists();
        });

        Gate::define('manage-custom-fields', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            
            // Partner collaborators cannot manage custom fields
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners and managers can manage custom fields
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
        });

        Gate::define('access-usage-analytics', function (User $user, ...$arguments) {
            if (count($arguments) < 1) {
                return false;
            }
            
            $company = $arguments[0];
            
            // Partner collaborators have limited usage analytics access
            if ($user->isPartnerCollaborator()) {
                $partnerCompany = $user->getPartnerCompany();
                return $partnerCompany && $partnerCompany->is($company);
            }

            // Owners and managers can access usage analytics
            $userRole = $user->companies()
                ->where('companies.id', $company->id)
                ->first()?->pivot?->role;

            return in_array($userRole, [CompanyRoleEnum::Owner, CompanyRoleEnum::Manager]);
        });

        Gate::define('manage-data-export-policies', function (User $user, ?Company $company = null) {
            // Partner collaborators cannot manage data export policies
            if ($user->isPartnerCollaborator()) {
                return false;
            }

            // Only owners can manage data export policies
            if ($company) {
                return $user->companies()
                    ->where('companies.id', $company->id)
                    ->wherePivot('role', CompanyRoleEnum::Owner)
                    ->exists();
            }

            return $user->companies()
                ->wherePivot('role', CompanyRoleEnum::Owner)
                ->exists();
        });


    }
}
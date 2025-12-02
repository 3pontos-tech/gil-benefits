<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class EnhancedAuthorizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): BaseResponse
    {
        $user = $request->user();

        // If user is not authenticated, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Process each permission requirement
        foreach ($permissions as $permission) {
            $this->validatePermission($user, $permission, $request);
        }

        // Apply context-aware security measures
        $this->applyContextAwareSecurity($user, $request);

        return $next($request);
    }

    /**
     * Validate a specific permission requirement.
     */
    protected function validatePermission(\App\Models\Users\User $user, string $permission, Request $request): void
    {
        $parts = explode(':', $permission);
        $type = $parts[0];
        $value = $parts[1] ?? null;

        switch ($type) {
            case 'gate':
                $this->validateGatePermission($user, $value, $request);
                break;
            case 'role':
                $this->validateRolePermission($user, $value, $request);
                break;
            case 'company':
                $this->validateCompanyPermission($user, $value, $request);
                break;
            case 'panel':
                $this->validatePanelPermission($user, $value, $request);
                break;
            case 'feature':
                $this->validateFeaturePermission($user, $value, $request);
                break;
            case 'subscription':
                $this->validateSubscriptionPermission($user, $value, $request);
                break;
            case 'data_access':
                $this->validateDataAccessPermission($user, $value, $request);
                break;
            default:
                // Treat as a simple gate check
                $this->validateGatePermission($user, $permission, $request);
        }
    }

    /**
     * Validate gate-based permission.
     */
    protected function validateGatePermission(\App\Models\Users\User $user, string $gate, Request $request): void
    {
        $routeParameters = $request->route()?->parameters() ?? [];
        $arguments = array_values($routeParameters);

        if (!Gate::allows($gate, $arguments)) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "gate:{$gate}",
                null,
                'Gate permission denied',
                [
                    'middleware' => 'enhanced_authorization',
                    'gate' => $gate,
                    'arguments_count' => count($arguments),
                    'route_name' => $request->route()?->getName(),
                ]
            );

            abort(403, "Access denied: {$gate} permission required.");
        }
    }

    /**
     * Validate role-based permission.
     */
    protected function validateRolePermission(\App\Models\Users\User $user, string $role, Request $request): void
    {
        $requiredRole = match (strtolower($role)) {
            'owner' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
            'manager' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
            'employee' => \TresPontosTech\Company\Enums\CompanyRoleEnum::Employee,
            default => $role,
        };

        $hasRole = $user->companies()
            ->wherePivot('role', $requiredRole)
            ->exists();

        if (!$hasRole) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "role:{$role}",
                null,
                'Required role not found',
                [
                    'middleware' => 'enhanced_authorization',
                    'required_role' => $role,
                    'user_roles' => $user->companies->pluck('pivot.role')->filter()->unique()->values()->toArray(),
                ]
            );

            abort(403, "Access denied: {$role} role required.");
        }
    }

    /**
     * Validate company-specific permission.
     */
    protected function validateCompanyPermission(\App\Models\Users\User $user, string $permission, Request $request): void
    {
        $company = $this->extractCompanyFromRequest($request);

        if (!$company) {
            abort(403, 'Company context required for this operation.');
        }

        $hasPermission = match ($permission) {
            'member' => $user->companies->contains($company),
            'owner' => $user->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('role', \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner)
                ->exists(),
            'manager' => $user->companies()
                ->where('companies.id', $company->id)
                ->wherePivotIn('role', [
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Owner,
                    \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager,
                ])
                ->exists(),
            default => false,
        };

        if (!$hasPermission) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "company:{$permission}",
                $company,
                'Company permission denied',
                [
                    'middleware' => 'enhanced_authorization',
                    'company_id' => $company->id,
                    'permission' => $permission,
                ]
            );

            abort(403, "Access denied: company {$permission} permission required.");
        }
    }

    /**
     * Validate panel access permission.
     */
    protected function validatePanelPermission(\App\Models\Users\User $user, string $panel, Request $request): void
    {
        $gateMap = [
            'admin' => 'access-admin-panel',
            'company' => 'access-company-panel',
            'consultant' => 'access-consultant-panel',
            'user' => 'access-user-panel',
            'guest' => 'access-guest-panel',
        ];

        $gate = $gateMap[$panel] ?? null;

        if (!$gate || !Gate::allows($gate)) {
            app(\App\Services\AuthorizationAuditService::class)->logPanelAccess(
                $user,
                $panel,
                false,
                null,
                ['middleware' => 'enhanced_authorization']
            );

            abort(403, "Access denied: {$panel} panel access required.");
        }

        app(\App\Services\AuthorizationAuditService::class)->logPanelAccess(
            $user,
            $panel,
            true,
            null,
            ['middleware' => 'enhanced_authorization']
        );
    }

    /**
     * Validate feature-specific permission.
     */
    protected function validateFeaturePermission(\App\Models\Users\User $user, string $feature, Request $request): void
    {
        $featureGates = [
            'appointments' => 'create-appointment',
            'billing' => 'access-billing-information',
            'analytics' => 'view-company-analytics',
            'integrations' => 'manage-integrations',
            'audit_logs' => 'view-audit-logs',
            'user_management' => 'manage-company-members',
            'settings' => 'manage-company-settings',
            'export' => 'export-company-data',
            'bulk_operations' => 'bulk-operations',
            'sensitive_data' => 'access-sensitive-data',
            'financial_data' => 'access-financial-data',
            'system_admin' => 'access-system-administration',
            'security_settings' => 'manage-security-settings',
            'api_access' => 'manage-api-access',
            'webhooks' => 'manage-webhooks-integrations',
            'data_retention' => 'manage-data-retention',
        ];

        $gate = $featureGates[$feature] ?? null;

        if (!$gate) {
            abort(403, "Unknown feature: {$feature}");
        }

        $company = $this->extractCompanyFromRequest($request);
        $arguments = $company ? [$company] : [];

        if (!Gate::allows($gate, $arguments)) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "feature:{$feature}",
                $company,
                'Feature access denied',
                [
                    'middleware' => 'enhanced_authorization',
                    'feature' => $feature,
                    'gate' => $gate,
                ]
            );

            abort(403, "Access denied: {$feature} feature access required.");
        }
    }

    /**
     * Validate subscription-based permission.
     */
    protected function validateSubscriptionPermission(\App\Models\Users\User $user, string $permission, Request $request): void
    {
        $company = $this->extractCompanyFromRequest($request);

        if (!$company) {
            abort(403, 'Company context required for subscription validation.');
        }

        $hasValidSubscription = match ($permission) {
            'active' => $this->hasActiveSubscription($company),
            'premium' => $this->hasPremiumSubscription($company),
            'unlimited' => $this->hasUnlimitedSubscription($company),
            default => false,
        };

        if (!$hasValidSubscription) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "subscription:{$permission}",
                $company,
                'Subscription requirement not met',
                [
                    'middleware' => 'enhanced_authorization',
                    'subscription_requirement' => $permission,
                    'company_id' => $company->id,
                ]
            );

            abort(403, "Access denied: {$permission} subscription required.");
        }
    }

    /**
     * Validate data access permission based on sensitivity level.
     */
    protected function validateDataAccessPermission(\App\Models\Users\User $user, string $level, Request $request): void
    {
        $dataAccessLevels = [
            'public' => true, // Everyone can access public data
            'internal' => !$user->isPartnerCollaborator(),
            'sensitive' => Gate::allows('access-sensitive-data'),
            'financial' => Gate::allows('access-financial-data'),
            'audit' => Gate::allows('access-audit-trail'),
            'system' => Gate::allows('access-system-administration'),
        ];

        $hasAccess = $dataAccessLevels[$level] ?? false;

        if (!$hasAccess) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "data_access:{$level}",
                null,
                'Data access level denied',
                [
                    'middleware' => 'enhanced_authorization',
                    'data_access_level' => $level,
                    'is_partner_collaborator' => $user->isPartnerCollaborator(),
                ]
            );

            abort(403, "Access denied: {$level} data access required.");
        }
    }

    /**
     * Apply context-aware security measures based on request context.
     */
    protected function applyContextAwareSecurity(\App\Models\Users\User $user, Request $request): void
    {
        // Enhanced security for partner collaborators
        if ($user->isPartnerCollaborator()) {
            $this->applyPartnerCollaboratorSecurity($user, $request);
        }

        // Enhanced security for sensitive operations
        if ($this->isSensitiveOperation($request)) {
            $this->applySensitiveOperationSecurity($user, $request);
        }

        // Enhanced security for high-risk users
        if ($this->isHighRiskUser($user)) {
            $this->applyHighRiskUserSecurity($user, $request);
        }

        // Enhanced security for cross-tenant operations
        if ($this->isCrossTenantOperation($request)) {
            $this->applyCrossTenantSecurity($user, $request);
        }
    }

    /**
     * Apply enhanced security measures for partner collaborators.
     */
    protected function applyPartnerCollaboratorSecurity(\App\Models\Users\User $user, Request $request): void
    {
        // Restrict access to certain routes
        $restrictedRoutes = [
            'admin.*',
            'company.*',
            'consultant.*',
            '*.billing.*',
            '*.settings.*',
            '*.analytics.*',
            '*.export.*',
            '*.import.*',
            '*.bulk.*',
        ];

        $routeName = $request->route()?->getName();
        
        if ($routeName) {
            foreach ($restrictedRoutes as $pattern) {
                if (\Illuminate\Support\Str::is($pattern, $routeName)) {
                    app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                        'partner_collaborator_restricted_access',
                        'Partner collaborator attempted to access restricted route',
                        [
                            'user_id' => $user->id,
                            'route_name' => $routeName,
                            'partner_company_id' => $user->getPartnerCompany()?->id,
                        ]
                    );

                    abort(403, 'Partner collaborators cannot access this resource.');
                }
            }
        }

        // Apply additional rate limiting
        $this->applyPartnerCollaboratorRateLimit($user, $request);
    }

    /**
     * Apply enhanced security for sensitive operations.
     */
    protected function applySensitiveOperationSecurity(\App\Models\Users\User $user, Request $request): void
    {
        // Log all sensitive operations
        app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
            $user,
            'sensitive_operation_access',
            null,
            true,
            'User accessed sensitive operation',
            [
                'middleware' => 'enhanced_authorization',
                'route_name' => $request->route()?->getName(),
                'request_method' => $request->method(),
                'type' => 'sensitive_operation',
            ]
        );

        // Additional validation for destructive operations
        if ($this->isDestructiveOperation($request)) {
            $this->validateDestructiveOperation($user, $request);
        }
    }

    /**
     * Apply enhanced security for high-risk users.
     */
    protected function applyHighRiskUserSecurity(\App\Models\Users\User $user, Request $request): void
    {
        // Additional logging and monitoring for high-risk users
        app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
            'high_risk_user_activity',
            'High-risk user performing operation',
            [
                'user_id' => $user->id,
                'route_name' => $request->route()?->getName(),
                'request_method' => $request->method(),
                'severity' => 'medium',
            ]
        );

        // Apply stricter rate limiting
        $this->applyHighRiskUserRateLimit($user, $request);
    }

    /**
     * Apply enhanced security for cross-tenant operations.
     */
    protected function applyCrossTenantSecurity(\App\Models\Users\User $user, Request $request): void
    {
        // Enhanced validation for cross-tenant data access
        $routeParameters = $request->route()?->parameters() ?? [];
        
        foreach ($routeParameters as $parameter) {
            if ($parameter instanceof \Illuminate\Database\Eloquent\Model) {
                if (!Gate::allows('tenant-isolation', $parameter)) {
                    app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                        'cross_tenant_access_violation',
                        'User attempted cross-tenant data access',
                        [
                            'user_id' => $user->id,
                            'model_type' => get_class($parameter),
                            'model_id' => $parameter->getKey(),
                            'severity' => 'high',
                        ]
                    );

                    abort(403, 'Cross-tenant data access denied.');
                }
            }
        }
    }

    /**
     * Extract company from request context.
     */
    protected function extractCompanyFromRequest(Request $request): ?\TresPontosTech\Company\Models\Company
    {
        $routeParameters = $request->route()?->parameters() ?? [];

        // Look for company in route parameters
        foreach ($routeParameters as $parameter) {
            if ($parameter instanceof \TresPontosTech\Company\Models\Company) {
                return $parameter;
            }
        }

        // Look for models with company relationship
        foreach ($routeParameters as $parameter) {
            if ($parameter instanceof \Illuminate\Database\Eloquent\Model) {
                if (method_exists($parameter, 'company') && $parameter->company) {
                    return $parameter->company;
                }
            }
        }

        return null;
    }

    /**
     * Check if company has active subscription.
     */
    protected function hasActiveSubscription(\TresPontosTech\Company\Models\Company $company): bool
    {
        // Implementation would check for active subscriptions
        // This is a placeholder - actual implementation would depend on billing system
        return true;
    }

    /**
     * Check if company has premium subscription.
     */
    protected function hasPremiumSubscription(\TresPontosTech\Company\Models\Company $company): bool
    {
        // Implementation would check for premium subscription features
        return true;
    }

    /**
     * Check if company has unlimited subscription.
     */
    protected function hasUnlimitedSubscription(\TresPontosTech\Company\Models\Company $company): bool
    {
        // Implementation would check for unlimited subscription features
        return true;
    }

    /**
     * Check if the current operation is sensitive.
     */
    protected function isSensitiveOperation(Request $request): bool
    {
        $sensitivePatterns = [
            '*.delete',
            '*.destroy',
            '*.force-delete',
            '*.export',
            '*.import',
            '*.bulk-*',
            'admin.*',
            '*.settings.*',
            '*.billing.*',
        ];

        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return false;
        }

        return collect($sensitivePatterns)->some(function ($pattern) use ($routeName) {
            return \Illuminate\Support\Str::is($pattern, $routeName);
        });
    }

    /**
     * Check if the current operation is destructive.
     */
    protected function isDestructiveOperation(Request $request): bool
    {
        $destructivePatterns = [
            '*.delete',
            '*.destroy',
            '*.force-delete',
            '*.bulk-delete',
        ];

        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return false;
        }

        return collect($destructivePatterns)->some(function ($pattern) use ($routeName) {
            return \Illuminate\Support\Str::is($pattern, $routeName);
        });
    }

    /**
     * Check if user is considered high-risk.
     */
    protected function isHighRiskUser(\App\Models\Users\User $user): bool
    {
        $suspiciousActivity = app(\App\Services\AuthorizationAuditService::class)
            ->detectSuspiciousActivity($user, 24);

        return $suspiciousActivity['risk_level'] === 'high';
    }

    /**
     * Check if the operation involves cross-tenant data access.
     */
    protected function isCrossTenantOperation(Request $request): bool
    {
        $routeParameters = $request->route()?->parameters() ?? [];
        $companyIds = [];

        foreach ($routeParameters as $parameter) {
            if ($parameter instanceof \TresPontosTech\Company\Models\Company) {
                $companyIds[] = $parameter->id;
            } elseif ($parameter instanceof \Illuminate\Database\Eloquent\Model) {
                if (method_exists($parameter, 'company') && $parameter->company) {
                    $companyIds[] = $parameter->company->id;
                } elseif (isset($parameter->company_id)) {
                    $companyIds[] = $parameter->company_id;
                }
            }
        }

        // If we have multiple different company IDs, it's a cross-tenant operation
        return count(array_unique($companyIds)) > 1;
    }

    /**
     * Validate destructive operations with additional checks.
     */
    protected function validateDestructiveOperation(\App\Models\Users\User $user, Request $request): void
    {
        // Check for recent destructive operations
        $recentDestructive = \Illuminate\Support\Facades\Cache::get("destructive_ops_{$user->id}", []);
        
        $recentCount = count(array_filter($recentDestructive, function ($op) {
            return $op['timestamp'] > (now()->timestamp - 3600); // Last hour
        }));

        if ($recentCount > 10) {
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'excessive_destructive_operations',
                'User performing excessive destructive operations',
                [
                    'user_id' => $user->id,
                    'operation_count' => $recentCount,
                    'time_window' => '1 hour',
                    'severity' => 'high',
                ]
            );

            abort(429, 'Too many destructive operations. Please wait before trying again.');
        }

        // Log this operation
        $recentDestructive[] = [
            'route' => $request->route()?->getName(),
            'timestamp' => now()->timestamp,
        ];

        \Illuminate\Support\Facades\Cache::put("destructive_ops_{$user->id}", $recentDestructive, now()->addHours(2));
    }

    /**
     * Apply rate limiting for partner collaborators.
     */
    protected function applyPartnerCollaboratorRateLimit(\App\Models\Users\User $user, Request $request): void
    {
        $cacheKey = "partner_rate_limit_{$user->id}";
        $currentCount = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);

        if ($currentCount >= 100) { // 100 requests per hour
            abort(429, 'Rate limit exceeded for partner collaborators.');
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $currentCount + 1, now()->addHour());
    }

    /**
     * Apply stricter rate limiting for high-risk users.
     */
    protected function applyHighRiskUserRateLimit(\App\Models\Users\User $user, Request $request): void
    {
        $cacheKey = "high_risk_rate_limit_{$user->id}";
        $currentCount = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);

        if ($currentCount >= 50) { // 50 requests per hour for high-risk users
            abort(429, 'Rate limit exceeded for high-risk users.');
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, $currentCount + 1, now()->addHour());
    }
}
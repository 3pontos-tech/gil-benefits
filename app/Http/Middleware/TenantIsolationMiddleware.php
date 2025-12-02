<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use TresPontosTech\Company\Models\Company;

class TenantIsolationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();

        // If user is not authenticated, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Apply global scopes for tenant isolation
        $this->applyTenantScopes($user);

        // Validate route model binding for tenant access
        $this->validateRouteModelAccess($request, $user);

        // Apply additional security measures for sensitive operations
        $this->applySensitiveOperationSecurity($request, $user);

        return $next($request);
    }

    /**
     * Apply global scopes to ensure tenant isolation.
     */
    protected function applyTenantScopes(\App\Models\Users\User $user): void
    {
        // Apply tenant isolation for appointments
        if (class_exists(\TresPontosTech\Appointments\Models\Appointment::class)) {
            \TresPontosTech\Appointments\Models\Appointment::addGlobalScope('tenant_isolation', function (Builder $builder) use ($user) {
                if ($user->isPartnerCollaborator()) {
                    $partnerCompany = $user->getPartnerCompany();
                    if ($partnerCompany) {
                        $builder->whereHas('user.companies', function (Builder $query) use ($partnerCompany) {
                            $query->where('companies.id', $partnerCompany->id);
                        });
                    } else {
                        // If no partner company, restrict to no results
                        $builder->whereRaw('1 = 0');
                    }
                } else {
                    // For non-partner collaborators, show appointments from accessible companies
                    $accessibleCompanyIds = $user->companies->pluck('id');
                    if ($accessibleCompanyIds->isNotEmpty()) {
                        $builder->whereHas('user.companies', function (Builder $query) use ($accessibleCompanyIds) {
                            $query->whereIn('companies.id', $accessibleCompanyIds);
                        });
                    }
                }
            });
        }

        // Apply tenant isolation for companies
        Company::addGlobalScope('tenant_isolation', function (Builder $builder) use ($user) {
            if ($user->isPartnerCollaborator()) {
                $partnerCompany = $user->getPartnerCompany();
                if ($partnerCompany) {
                    $builder->where('id', $partnerCompany->id);
                } else {
                    // If no partner company, restrict to no results
                    $builder->whereRaw('1 = 0');
                }
            } else {
                // For non-partner collaborators, show accessible companies
                $accessibleCompanyIds = $user->companies->pluck('id');
                if ($accessibleCompanyIds->isNotEmpty()) {
                    $builder->whereIn('id', $accessibleCompanyIds);
                }
            }
        });

        // Apply tenant isolation for consultants
        if (class_exists(\TresPontosTech\Consultants\Models\Consultant::class)) {
            \TresPontosTech\Consultants\Models\Consultant::addGlobalScope('tenant_isolation', function (Builder $builder) use ($user) {
                if ($user->isPartnerCollaborator()) {
                    $partnerCompany = $user->getPartnerCompany();
                    if ($partnerCompany) {
                        $builder->whereHas('user.companies', function (Builder $query) use ($partnerCompany) {
                            $query->where('companies.id', $partnerCompany->id);
                        });
                    } else {
                        // If no partner company, restrict to no results
                        $builder->whereRaw('1 = 0');
                    }
                } else {
                    // For non-partner collaborators, show consultants from accessible companies
                    $accessibleCompanyIds = $user->companies->pluck('id');
                    if ($accessibleCompanyIds->isNotEmpty()) {
                        $builder->whereHas('user.companies', function (Builder $query) use ($accessibleCompanyIds) {
                            $query->whereIn('companies.id', $accessibleCompanyIds);
                        });
                    }
                }
            });
        }

        // Apply tenant isolation for users (for admin/management views)
        \App\Models\Users\User::addGlobalScope('tenant_isolation', function (Builder $builder) use ($user) {
            // Don't apply scope to the current user's own queries
            if ($builder->getModel()->is($user)) {
                return;
            }

            if ($user->isPartnerCollaborator()) {
                $partnerCompany = $user->getPartnerCompany();
                if ($partnerCompany) {
                    $builder->whereHas('companies', function (Builder $query) use ($partnerCompany) {
                        $query->where('companies.id', $partnerCompany->id);
                    });
                } else {
                    // If no partner company, restrict to no results
                    $builder->whereRaw('1 = 0');
                }
            } else {
                // For non-partner collaborators, show users from accessible companies
                $accessibleCompanyIds = $user->companies->pluck('id');
                if ($accessibleCompanyIds->isNotEmpty()) {
                    $builder->whereHas('companies', function (Builder $query) use ($accessibleCompanyIds) {
                        $query->whereIn('companies.id', $accessibleCompanyIds);
                    });
                }
            }
        });
    }

    /**
     * Validate that route model bindings respect tenant isolation.
     */
    protected function validateRouteModelAccess(Request $request, \App\Models\Users\User $user): void
    {
        $route = $request->route();
        
        if (!$route) {
            return;
        }

        $parameters = $route->parameters();

        foreach ($parameters as $parameterName => $parameter) {
            if ($parameter instanceof Model) {
                // Check if user can access this model based on tenant isolation
                $canAccess = Gate::allows('tenant-isolation', $parameter);
                
                // Log all tenant isolation checks for audit purposes
                app(\App\Services\AuthorizationAuditService::class)->logTenantIsolationCheck(
                    $user,
                    $parameter,
                    $canAccess,
                    [
                        'route_name' => $route->getName(),
                        'route_uri' => $route->uri(),
                        'parameter_name' => $parameterName,
                        'request_method' => $request->method(),
                    ]
                );

                if (!$canAccess) {
                    // Additional security logging for violations
                    app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                        'tenant_isolation_violation',
                        'User attempted to access resource outside their tenant scope',
                        [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'model_type' => get_class($parameter),
                            'model_id' => $parameter->getKey(),
                            'is_partner_collaborator' => $user->isPartnerCollaborator(),
                            'partner_company_id' => $user->getPartnerCompany()?->id,
                            'accessible_company_ids' => $user->companies->pluck('id')->toArray(),
                            'route_name' => $route->getName(),
                            'route_uri' => $route->uri(),
                            'parameter_name' => $parameterName,
                            'request_method' => $request->method(),
                            'request_ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]
                    );

                    // Check if this is a repeated violation (potential attack)
                    $this->checkForRepeatedViolations($user, $parameter);

                    abort(403, 'You do not have permission to access this resource.');
                }
            }
        }
    }

    /**
     * Check for repeated tenant isolation violations that might indicate an attack.
     */
    protected function checkForRepeatedViolations(\App\Models\Users\User $user, Model $model): void
    {
        $recentViolations = \Illuminate\Support\Facades\Cache::get(
            "tenant_violations_{$user->id}",
            []
        );

        $recentViolations[] = [
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'timestamp' => now()->timestamp,
        ];

        // Keep only violations from the last 5 minutes
        $recentViolations = array_filter($recentViolations, function ($violation) {
            return $violation['timestamp'] > (now()->timestamp - 300);
        });

        // If more than 5 violations in 5 minutes, log as potential attack
        if (count($recentViolations) > 5) {
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'potential_tenant_isolation_attack',
                'Multiple tenant isolation violations detected - possible attack',
                [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'violation_count' => count($recentViolations),
                    'time_window' => '5 minutes',
                    'violations' => $recentViolations,
                    'severity' => 'high',
                ]
            );
        }

        // Store updated violations list
        \Illuminate\Support\Facades\Cache::put(
            "tenant_violations_{$user->id}",
            $recentViolations,
            now()->addMinutes(10)
        );
    }

    /**
     * Apply additional security measures for sensitive operations.
     */
    protected function applySensitiveOperationSecurity(Request $request, \App\Models\Users\User $user): void
    {
        $sensitiveRoutes = [
            'admin.*',
            '*.delete',
            '*.destroy',
            '*.force-delete',
            '*.export',
            '*.bulk-*',
            '*.import',
            '*.sync',
            '*.migrate',
            '*.backup',
            '*.restore',
        ];

        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return;
        }

        $isSensitive = collect($sensitiveRoutes)->some(function ($pattern) use ($routeName) {
            return \Illuminate\Support\Str::is($pattern, $routeName);
        });

        if ($isSensitive) {
            // Enhanced security validation for sensitive operations
            $this->validateSensitiveOperationAccess($request, $user, $routeName);

            // Log sensitive operation access with enhanced context
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
                $user,
                'sensitive_operation_access',
                null,
                true,
                'User accessed sensitive operation',
                [
                    'route_name' => $routeName,
                    'request_method' => $request->method(),
                    'is_partner_collaborator' => $user->isPartnerCollaborator(),
                    'type' => 'sensitive_operation',
                    'operation_category' => $this->categorizeSensitiveOperation($routeName),
                    'risk_level' => $this->assessOperationRisk($routeName, $user),
                    'requires_additional_validation' => $this->requiresAdditionalValidation($routeName),
                ]
            );

            // Partner collaborators should not access sensitive operations
            if ($user->isPartnerCollaborator()) {
                app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                    'partner_collaborator_sensitive_access',
                    'Partner collaborator attempted to access sensitive operation',
                    [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'route_name' => $routeName,
                        'partner_company_id' => $user->getPartnerCompany()?->id,
                        'operation_category' => $this->categorizeSensitiveOperation($routeName),
                        'severity' => 'medium',
                    ]
                );

                abort(403, 'Partner collaborators cannot access this operation.');
            }

            // Additional validation for high-risk operations
            if ($this->assessOperationRisk($routeName, $user) === 'high') {
                $this->performHighRiskOperationValidation($request, $user, $routeName);
            }
        }
    }

    /**
     * Validate access to sensitive operations with enhanced security checks.
     */
    protected function validateSensitiveOperationAccess(Request $request, \App\Models\Users\User $user, string $routeName): void
    {
        // Check for suspicious activity patterns
        $suspiciousActivity = app(\App\Services\AuthorizationAuditService::class)->detectSuspiciousActivity($user, 6);
        
        if ($suspiciousActivity['risk_level'] === 'high') {
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'high_risk_user_sensitive_operation',
                'High-risk user attempting sensitive operation',
                [
                    'user_id' => $user->id,
                    'route_name' => $routeName,
                    'risk_assessment' => $suspiciousActivity,
                    'severity' => 'high',
                ]
            );

            // Block high-risk users from critical operations
            $criticalOperations = ['*.force-delete', '*.backup', '*.restore', '*.migrate'];
            $isCritical = collect($criticalOperations)->some(function ($pattern) use ($routeName) {
                return \Illuminate\Support\Str::is($pattern, $routeName);
            });

            if ($isCritical) {
                abort(403, 'Access temporarily restricted due to security concerns.');
            }
        }

        // Rate limiting for sensitive operations
        $this->applySensitiveOperationRateLimit($user, $routeName);
    }

    /**
     * Categorize sensitive operations for better security analysis.
     */
    protected function categorizeSensitiveOperation(string $routeName): string
    {
        $categories = [
            'data_modification' => ['*.delete', '*.destroy', '*.force-delete', '*.update'],
            'data_export' => ['*.export', '*.backup'],
            'data_import' => ['*.import', '*.restore', '*.sync'],
            'bulk_operations' => ['*.bulk-*'],
            'system_administration' => ['admin.*', '*.migrate'],
        ];

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (\Illuminate\Support\Str::is($pattern, $routeName)) {
                    return $category;
                }
            }
        }

        return 'general_sensitive';
    }

    /**
     * Assess the risk level of a sensitive operation.
     */
    protected function assessOperationRisk(string $routeName, \App\Models\Users\User $user): string
    {
        $highRiskOperations = ['*.force-delete', '*.backup', '*.restore', '*.migrate', 'admin.system.*'];
        $mediumRiskOperations = ['*.delete', '*.destroy', '*.export', '*.bulk-*'];

        $isHighRisk = collect($highRiskOperations)->some(function ($pattern) use ($routeName) {
            return \Illuminate\Support\Str::is($pattern, $routeName);
        });

        if ($isHighRisk) {
            return 'high';
        }

        $isMediumRisk = collect($mediumRiskOperations)->some(function ($pattern) use ($routeName) {
            return \Illuminate\Support\Str::is($pattern, $routeName);
        });

        if ($isMediumRisk) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Check if operation requires additional validation.
     */
    protected function requiresAdditionalValidation(string $routeName): bool
    {
        $requiresValidation = ['*.force-delete', '*.backup', '*.restore', '*.migrate', '*.bulk-delete'];

        return collect($requiresValidation)->some(function ($pattern) use ($routeName) {
            return \Illuminate\Support\Str::is($pattern, $routeName);
        });
    }

    /**
     * Perform additional validation for high-risk operations.
     */
    protected function performHighRiskOperationValidation(Request $request, \App\Models\Users\User $user, string $routeName): void
    {
        // Check if user has performed similar operations recently (potential automation/script)
        $recentOperations = \Illuminate\Support\Facades\Cache::get("recent_operations_{$user->id}", []);
        
        $recentSimilarOperations = array_filter($recentOperations, function ($operation) use ($routeName) {
            return $operation['route'] === $routeName && 
                   $operation['timestamp'] > (now()->timestamp - 300); // Last 5 minutes
        });

        if (count($recentSimilarOperations) > 3) {
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'potential_automated_high_risk_operations',
                'User performing multiple high-risk operations rapidly',
                [
                    'user_id' => $user->id,
                    'route_name' => $routeName,
                    'operation_count' => count($recentSimilarOperations),
                    'time_window' => '5 minutes',
                    'severity' => 'high',
                ]
            );

            abort(429, 'Too many high-risk operations. Please wait before trying again.');
        }

        // Log this operation
        $recentOperations[] = [
            'route' => $routeName,
            'timestamp' => now()->timestamp,
            'ip_address' => $request->ip(),
        ];

        // Keep only operations from the last hour
        $recentOperations = array_filter($recentOperations, function ($operation) {
            return $operation['timestamp'] > (now()->timestamp - 3600);
        });

        \Illuminate\Support\Facades\Cache::put("recent_operations_{$user->id}", $recentOperations, now()->addHours(2));
    }

    /**
     * Apply rate limiting for sensitive operations.
     */
    protected function applySensitiveOperationRateLimit(\App\Models\Users\User $user, string $routeName): void
    {
        $rateLimits = [
            'high_risk' => ['limit' => 5, 'window' => 3600], // 5 per hour
            'medium_risk' => ['limit' => 20, 'window' => 3600], // 20 per hour
            'low_risk' => ['limit' => 100, 'window' => 3600], // 100 per hour
        ];

        $riskLevel = $this->assessOperationRisk($routeName, $user);
        $limit = $rateLimits[$riskLevel] ?? $rateLimits['low_risk'];

        $cacheKey = "sensitive_ops_{$riskLevel}_{$user->id}";
        $currentCount = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);

        if ($currentCount >= $limit['limit']) {
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'sensitive_operation_rate_limit_exceeded',
                "User exceeded {$riskLevel} risk operation rate limit",
                [
                    'user_id' => $user->id,
                    'route_name' => $routeName,
                    'risk_level' => $riskLevel,
                    'current_count' => $currentCount,
                    'limit' => $limit['limit'],
                    'window_seconds' => $limit['window'],
                    'severity' => 'medium',
                ]
            );

            abort(429, "Rate limit exceeded for {$riskLevel} risk operations. Please try again later.");
        }

        // Increment counter
        \Illuminate\Support\Facades\Cache::put(
            $cacheKey,
            $currentCount + 1,
            now()->addSeconds($limit['window'])
        );
    }
}
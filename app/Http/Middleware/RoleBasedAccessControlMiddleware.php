<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use TresPontosTech\Company\Enums\CompanyRoleEnum;

class RoleBasedAccessControlMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): BaseResponse
    {
        $user = $request->user();

        // If user is not authenticated, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Convert string roles to enum values
        $requiredRoles = collect($roles)->map(function ($role) {
            return match (strtolower($role)) {
                'owner' => CompanyRoleEnum::Owner,
                'manager' => CompanyRoleEnum::Manager,
                'employee' => CompanyRoleEnum::Employee,
                default => $role,
            };
        })->toArray();

        // Check if user has any of the required roles
        $hasRequiredRole = $this->userHasAnyRole($user, $requiredRoles);

        // Log role-based access control check
        app(\App\Services\AuthorizationAuditService::class)->logRoleCheck(
            $user,
            $requiredRoles,
            $hasRequiredRole,
            [
                'middleware' => 'role_based_access_control',
                'route_name' => $request->route()?->getName(),
                'route_uri' => $request->route()?->uri(),
                'request_method' => $request->method(),
            ]
        );

        if (!$hasRequiredRole) {
            // Log unauthorized access attempt
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'unauthorized_role_access',
                'User attempted to access resource without required role',
                [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'required_roles' => $requiredRoles,
                    'user_roles' => $this->getUserRoles($user),
                    'is_partner_collaborator' => $user->isPartnerCollaborator(),
                    'route_name' => $request->route()?->getName(),
                    'route_uri' => $request->route()?->uri(),
                    'request_method' => $request->method(),
                ]
            );

            // Check for repeated unauthorized access attempts
            $this->checkForRepeatedUnauthorizedAccess($user);

            abort(403, 'You do not have the required role to access this resource.');
        }

        return $next($request);
    }

    /**
     * Check if user has any of the required roles.
     */
    protected function userHasAnyRole(\App\Models\Users\User $user, array $requiredRoles): bool
    {
        // Partner collaborators have special restrictions
        if ($user->isPartnerCollaborator()) {
            // Partner collaborators are treated as employees with limited access
            return in_array(CompanyRoleEnum::Employee, $requiredRoles);
        }

        // Get user roles across all companies
        $userRoles = $this->getUserRoles($user);

        // Check if user has any of the required roles
        return collect($requiredRoles)->intersect($userRoles)->isNotEmpty();
    }

    /**
     * Get all roles for the user across all companies.
     */
    protected function getUserRoles(\App\Models\Users\User $user): array
    {
        return $user->companies->pluck('pivot.role')->filter()->unique()->values()->toArray();
    }

    /**
     * Check for repeated unauthorized access attempts.
     */
    protected function checkForRepeatedUnauthorizedAccess(\App\Models\Users\User $user): void
    {
        $cacheKey = "unauthorized_access_{$user->id}";
        $attempts = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        $attempts[] = [
            'timestamp' => now()->timestamp,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        // Keep only attempts from the last 10 minutes
        $attempts = array_filter($attempts, function ($attempt) {
            return $attempt['timestamp'] > (now()->timestamp - 600);
        });

        // If more than 10 attempts in 10 minutes, log as potential attack
        if (count($attempts) > 10) {
            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'potential_privilege_escalation_attack',
                'Multiple unauthorized role access attempts detected - possible privilege escalation attack',
                [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempt_count' => count($attempts),
                    'time_window' => '10 minutes',
                    'attempts' => $attempts,
                    'severity' => 'high',
                ]
            );

            // Create security incident report
            app(\App\Services\SecurityLoggingService::class)->createIncidentReport(
                'privilege_escalation_attempt',
                [
                    'severity' => 'high',
                    'description' => 'User made multiple unauthorized role access attempts',
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'attempt_count' => count($attempts),
                    'affected_resources' => ['role_based_access_control'],
                    'mitigation_steps' => [
                        'Monitor user activity closely',
                        'Consider temporary access restriction',
                        'Review user permissions and roles',
                    ],
                ]
            );
        }

        // Store updated attempts list
        \Illuminate\Support\Facades\Cache::put($cacheKey, $attempts, now()->addMinutes(15));
    }
}
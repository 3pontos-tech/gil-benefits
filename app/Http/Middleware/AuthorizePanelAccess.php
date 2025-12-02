<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class AuthorizePanelAccess
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

        $currentPanel = Filament::getCurrentPanel();
        
        if (!$currentPanel) {
            return $next($request);
        }

        $panelId = $currentPanel->getId();
        
        // Check panel access using gates
        $canAccess = match ($panelId) {
            'admin' => Gate::allows('access-admin-panel'),
            'company' => Gate::allows('access-company-panel'),
            'consultant' => Gate::allows('access-consultant-panel'),
            'app' => Gate::allows('access-user-panel'),
            'guest' => Gate::allows('access-guest-panel'),
            default => true,
        };

        // Log panel access attempt
        app(\App\Services\AuthorizationAuditService::class)->logPanelAccess(
            $user,
            $panelId,
            $canAccess,
            null,
            ['gate_used' => "access-{$panelId}-panel"]
        );

        if (!$canAccess) {
            // Perform additional role-based validation
            $roleBasedAccess = $this->validateRoleBasedAccess($user, $panelId);
            
            // Log unauthorized access attempt
            app(\App\Services\AuthorizationAuditService::class)->logPanelAccess(
                $user,
                $panelId,
                false,
                null,
                [
                    'reason' => 'Insufficient permissions for panel access',
                    'gate_check' => $canAccess,
                    'role_check' => $roleBasedAccess,
                ]
            );

            app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
                'unauthorized_panel_access',
                'User attempted to access unauthorized panel',
                [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'panel_id' => $panelId,
                    'is_partner_collaborator' => $user->isPartnerCollaborator(),
                    'partner_company_id' => $user->getPartnerCompany()?->id,
                    'user_roles' => $user->companies->map(fn($c) => $c->pivot->role)->unique()->values()->toArray(),
                ]
            );

            // Redirect to appropriate panel based on user permissions
            $redirectPanel = $this->getAppropriatePanel($user);
            
            if ($redirectPanel && $redirectPanel !== $panelId) {
                app(\App\Services\AuthorizationAuditService::class)->logPanelAccess(
                    $user,
                    $panelId,
                    false,
                    $redirectPanel,
                    ['action' => 'redirected_to_appropriate_panel']
                );
                
                return redirect($redirectPanel);
            }

            // If no appropriate panel found, deny access
            abort(403, 'You do not have permission to access this panel.');
        }

        return $next($request);
    }

    /**
     * Get the appropriate panel for the user based on their permissions.
     */
    protected function getAppropriatePanel(\App\Models\Users\User $user): ?string
    {
        // Partner collaborators can only access user panel
        if ($user->isPartnerCollaborator()) {
            return '/app';
        }

        // Check access in order of preference
        if (Gate::allows('access-admin-panel')) {
            return '/admin';
        }

        if (Gate::allows('access-company-panel')) {
            return '/company';
        }

        if (Gate::allows('access-consultant-panel')) {
            return '/consultant';
        }

        if (Gate::allows('access-user-panel')) {
            return '/app';
        }

        return null;
    }

    /**
     * Validate role-based access for the current panel.
     */
    protected function validateRoleBasedAccess(\App\Models\Users\User $user, string $panelId): bool
    {
        // Log role check attempt
        app(\App\Services\AuthorizationAuditService::class)->logRoleCheck(
            $user,
            "panel_access_{$panelId}",
            true, // Will be updated based on actual result
            ['panel_id' => $panelId]
        );

        // Partner collaborators have restricted access
        if ($user->isPartnerCollaborator()) {
            $allowed = $panelId === 'app';
            
            if (!$allowed) {
                app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                    $user,
                    "access_panel_{$panelId}",
                    null,
                    'Partner collaborators restricted to user panel only',
                    ['panel_id' => $panelId, 'user_type' => 'partner_collaborator']
                );
            }

            return $allowed;
        }

        // Role-based panel access validation
        $userRoles = $user->companies->map(fn($company) => $company->pivot->role)->unique()->values();
        
        $panelRoleRequirements = [
            'admin' => [\TresPontosTech\Company\Enums\CompanyRoleEnum::Owner, \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager],
            'company' => [\TresPontosTech\Company\Enums\CompanyRoleEnum::Owner, \TresPontosTech\Company\Enums\CompanyRoleEnum::Manager],
            'consultant' => [], // Special case: requires consultant profile
            'app' => [], // All authenticated users
            'guest' => [], // Public access
        ];

        // Special case for consultant panel
        if ($panelId === 'consultant') {
            $hasConsultantProfile = \TresPontosTech\Consultants\Models\Consultant::where('user_id', $user->id)->exists();
            
            if (!$hasConsultantProfile) {
                app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                    $user,
                    "access_panel_{$panelId}",
                    null,
                    'User does not have consultant profile',
                    ['panel_id' => $panelId, 'required' => 'consultant_profile']
                );
            }

            return $hasConsultantProfile;
        }

        // Check if user has required roles for the panel
        $requiredRoles = $panelRoleRequirements[$panelId] ?? [];
        
        if (empty($requiredRoles)) {
            return true; // No specific role requirements
        }

        $hasRequiredRole = $userRoles->intersect($requiredRoles)->isNotEmpty();
        
        if (!$hasRequiredRole) {
            app(\App\Services\AuthorizationAuditService::class)->logAuthorizationFailure(
                $user,
                "access_panel_{$panelId}",
                null,
                'Insufficient role permissions for panel access',
                [
                    'panel_id' => $panelId,
                    'required_roles' => $requiredRoles,
                    'user_roles' => $userRoles->toArray(),
                ]
            );
        }

        return $hasRequiredRole;
    }
}
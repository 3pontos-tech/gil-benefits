<?php

namespace App\Services;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuthorizationAuditService
{
    /**
     * Log an authorization decision.
     */
    public function logAuthorizationDecision(
        User $user,
        string $action,
        ?Model $model,
        bool $granted,
        ?string $reason = null,
        array $context = []
    ): void {
        $logData = [
            'event_type' => 'authorization_decision',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'action' => $action,
            'granted' => $granted,
            'reason' => $reason,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'user_roles' => $this->getUserRoles($user),
            'is_partner_collaborator' => $user->isPartnerCollaborator(),
            'partner_company_id' => $user->getPartnerCompany()?->id,
            'accessible_companies' => $user->companies->pluck('id')->toArray(),
            'timestamp' => now()->toISOString(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'session_id' => session()->getId(),
            'context' => $context,
        ];

        // Log to security channel
        Log::channel('security')->info('Authorization decision', $logData);

        // Also log to database for audit trail
        $this->logToDatabase($logData);
    }

    /**
     * Log a policy check.
     */
    public function logPolicyCheck(
        User $user,
        string $policy,
        string $method,
        ?Model $model,
        bool $granted,
        array $context = []
    ): void {
        $this->logAuthorizationDecision(
            $user,
            "{$policy}::{$method}",
            $model,
            $granted,
            "Policy check: {$policy}::{$method}",
            array_merge($context, ['type' => 'policy_check'])
        );
    }

    /**
     * Log a gate check.
     */
    public function logGateCheck(
        User $user,
        string $gate,
        array $arguments,
        bool $granted,
        array $context = []
    ): void {
        $model = null;
        if (!empty($arguments) && $arguments[0] instanceof Model) {
            $model = $arguments[0];
        }

        $this->logAuthorizationDecision(
            $user,
            "gate:{$gate}",
            $model,
            $granted,
            "Gate check: {$gate}",
            array_merge($context, [
                'type' => 'gate_check',
                'arguments_count' => count($arguments),
                'argument_types' => array_map('get_class', array_filter($arguments, fn($arg) => is_object($arg))),
            ])
        );
    }

    /**
     * Log panel access attempt.
     */
    public function logPanelAccess(
        User $user,
        string $panelId,
        bool $granted,
        ?string $redirectedTo = null,
        array $context = []
    ): void {
        $this->logAuthorizationDecision(
            $user,
            "panel_access:{$panelId}",
            null,
            $granted,
            $granted ? "Panel access granted" : "Panel access denied",
            array_merge($context, [
                'type' => 'panel_access',
                'panel_id' => $panelId,
                'redirected_to' => $redirectedTo,
            ])
        );
    }

    /**
     * Log tenant isolation check.
     */
    public function logTenantIsolationCheck(
        User $user,
        Model $model,
        bool $granted,
        array $context = []
    ): void {
        $this->logAuthorizationDecision(
            $user,
            'tenant_isolation',
            $model,
            $granted,
            $granted ? "Tenant isolation check passed" : "Tenant isolation violation",
            array_merge($context, [
                'type' => 'tenant_isolation',
                'model_company_id' => $this->getModelCompanyId($model),
            ])
        );
    }

    /**
     * Log role-based access control check.
     */
    public function logRoleCheck(
        User $user,
        string|array $requiredRoles,
        bool $granted,
        array $context = []
    ): void {
        $this->logAuthorizationDecision(
            $user,
            'role_check',
            null,
            $granted,
            $granted ? "Role check passed" : "Insufficient role permissions",
            array_merge($context, [
                'type' => 'role_check',
                'required_roles' => is_array($requiredRoles) ? $requiredRoles : [$requiredRoles],
                'user_roles' => $this->getUserRoles($user),
            ])
        );
    }

    /**
     * Log authorization failure with detailed context.
     */
    public function logAuthorizationFailure(
        User $user,
        string $action,
        ?Model $model,
        string $reason,
        array $context = []
    ): void {
        $this->logAuthorizationDecision(
            $user,
            $action,
            $model,
            false,
            $reason,
            array_merge($context, [
                'type' => 'authorization_failure',
                'severity' => 'warning',
            ])
        );

        // Also log as a security event for monitoring
        app(SecurityLoggingService::class)->logSecurityEvent(
            'authorization_failure',
            "Authorization failed: {$reason}",
            array_merge($context, [
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),
            ])
        );
    }

    /**
     * Get user roles across all companies.
     */
    protected function getUserRoles(User $user): array
    {
        return $user->companies->map(function ($company) {
            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'role' => $company->pivot->role ?? null,
            ];
        })->toArray();
    }

    /**
     * Get the company ID associated with a model.
     */
    protected function getModelCompanyId(Model $model): ?int
    {
        if (method_exists($model, 'company') && $model->company) {
            return $model->company->id;
        }

        if (isset($model->company_id)) {
            return $model->company_id;
        }

        if ($model instanceof \TresPontosTech\Company\Models\Company) {
            return $model->id;
        }

        return null;
    }

    /**
     * Log authorization data to database for audit trail.
     */
    protected function logToDatabase(array $logData): void
    {
        try {
            // Create audit log entry in database
            // This could be expanded to use a dedicated audit log table
            \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
                'event_type' => $logData['event_type'],
                'user_id' => $logData['user_id'],
                'action' => $logData['action'],
                'model_type' => $logData['model_type'],
                'model_id' => $logData['model_id'],
                'granted' => $logData['granted'],
                'reason' => $logData['reason'],
                'context' => json_encode($logData),
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If database logging fails, at least log the error
            Log::error('Failed to log authorization audit to database', [
                'error' => $e->getMessage(),
                'original_log_data' => $logData,
            ]);
        }
    }

    /**
     * Generate a summary report of authorization activities.
     */
    public function generateAuthorizationReport(
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null,
        ?int $userId = null
    ): array {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();

        $query = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event_type', 'authorization_decision')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $logs = $query->get();

        return [
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'total_decisions' => $logs->count(),
            'granted_decisions' => $logs->where('granted', true)->count(),
            'denied_decisions' => $logs->where('granted', false)->count(),
            'actions_summary' => $logs->groupBy('action')->map->count()->toArray(),
            'users_summary' => $logs->groupBy('user_id')->map->count()->toArray(),
            'models_summary' => $logs->whereNotNull('model_type')->groupBy('model_type')->map->count()->toArray(),
            'failure_reasons' => $logs->where('granted', false)->pluck('reason')->filter()->unique()->values()->toArray(),
            'partner_collaborator_activity' => $this->getPartnerCollaboratorActivity($logs),
            'security_incidents' => $this->getSecurityIncidents($logs),
            'top_denied_actions' => $this->getTopDeniedActions($logs),
            'hourly_distribution' => $this->getHourlyDistribution($logs),
        ];
    }

    /**
     * Get partner collaborator specific activity summary.
     */
    protected function getPartnerCollaboratorActivity($logs): array
    {
        $partnerLogs = $logs->filter(function ($log) {
            $context = json_decode($log->context, true);
            return $context['is_partner_collaborator'] ?? false;
        });

        return [
            'total_decisions' => $partnerLogs->count(),
            'granted_decisions' => $partnerLogs->where('granted', true)->count(),
            'denied_decisions' => $partnerLogs->where('granted', false)->count(),
            'unique_users' => $partnerLogs->pluck('user_id')->unique()->count(),
            'most_common_actions' => $partnerLogs->groupBy('action')->map->count()->sortDesc()->take(5)->toArray(),
        ];
    }

    /**
     * Get security incidents from authorization logs.
     */
    protected function getSecurityIncidents($logs): array
    {
        $incidents = $logs->filter(function ($log) {
            $context = json_decode($log->context, true);
            return ($context['type'] ?? '') === 'tenant_isolation' && !$log->granted;
        });

        return [
            'tenant_isolation_violations' => $incidents->count(),
            'affected_users' => $incidents->pluck('user_id')->unique()->count(),
            'affected_models' => $incidents->whereNotNull('model_type')->groupBy('model_type')->map->count()->toArray(),
        ];
    }

    /**
     * Get top denied actions for security analysis.
     */
    protected function getTopDeniedActions($logs): array
    {
        return $logs->where('granted', false)
            ->groupBy('action')
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->toArray();
    }

    /**
     * Get hourly distribution of authorization decisions.
     */
    protected function getHourlyDistribution($logs): array
    {
        $distribution = [];
        
        for ($hour = 0; $hour < 24; $hour++) {
            $distribution[$hour] = 0;
        }

        foreach ($logs as $log) {
            $hour = \Carbon\Carbon::parse($log->created_at)->hour;
            $distribution[$hour]++;
        }

        return $distribution;
    }

    /**
     * Log a critical authorization failure that requires immediate attention.
     */
    public function logCriticalAuthorizationFailure(
        User $user,
        string $action,
        ?Model $model,
        string $reason,
        array $context = []
    ): void {
        $this->logAuthorizationFailure($user, $action, $model, $reason, array_merge($context, [
            'severity' => 'critical',
            'requires_investigation' => true,
        ]));

        // Also send to monitoring/alerting system
        app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
            'critical_authorization_failure',
            "Critical authorization failure: {$reason}",
            array_merge($context, [
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),
                'severity' => 'critical',
            ])
        );
    }

    /**
     * Get authorization statistics for a specific user.
     */
    public function getUserAuthorizationStats(User $user, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $logs = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event_type', 'authorization_decision')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'period_days' => $days,
            'total_decisions' => $logs->count(),
            'granted_decisions' => $logs->where('granted', true)->count(),
            'denied_decisions' => $logs->where('granted', false)->count(),
            'success_rate' => $logs->count() > 0 ? round(($logs->where('granted', true)->count() / $logs->count()) * 100, 2) : 0,
            'most_accessed_models' => $logs->whereNotNull('model_type')->groupBy('model_type')->map->count()->sortDesc()->take(5)->toArray(),
            'most_common_actions' => $logs->groupBy('action')->map->count()->sortDesc()->take(5)->toArray(),
            'recent_failures' => $logs->where('granted', false)->sortByDesc('created_at')->take(5)->map(function ($log) {
                return [
                    'action' => $log->action,
                    'reason' => $log->reason,
                    'model_type' => $log->model_type,
                    'timestamp' => $log->created_at,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Check if user has suspicious authorization patterns.
     */
    public function detectSuspiciousActivity(User $user, int $hours = 24): array
    {
        $startDate = now()->subHours($hours);
        
        $logs = \Illuminate\Support\Facades\DB::table('audit_logs')
            ->where('event_type', 'authorization_decision')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        $deniedLogs = $logs->where('granted', false);
        $suspiciousPatterns = [];

        // High number of denied requests
        if ($deniedLogs->count() > 20) {
            $suspiciousPatterns[] = [
                'type' => 'high_denial_rate',
                'description' => 'Unusually high number of denied authorization requests',
                'count' => $deniedLogs->count(),
                'threshold' => 20,
            ];
        }

        // Repeated access to same denied resource
        $deniedModels = $deniedLogs->whereNotNull('model_type')->groupBy(function ($log) {
            return $log->model_type . ':' . $log->model_id;
        });

        foreach ($deniedModels as $modelKey => $modelLogs) {
            if ($modelLogs->count() > 5) {
                $suspiciousPatterns[] = [
                    'type' => 'repeated_resource_access',
                    'description' => 'Repeated attempts to access denied resource',
                    'resource' => $modelKey,
                    'attempts' => $modelLogs->count(),
                    'threshold' => 5,
                ];
            }
        }

        // Rapid-fire requests (more than 50 requests in an hour)
        if ($logs->count() > 50) {
            $suspiciousPatterns[] = [
                'type' => 'rapid_requests',
                'description' => 'Unusually high number of authorization requests',
                'count' => $logs->count(),
                'threshold' => 50,
                'time_window' => "{$hours} hours",
            ];
        }

        return [
            'user_id' => $user->id,
            'analysis_period' => "{$hours} hours",
            'total_requests' => $logs->count(),
            'denied_requests' => $deniedLogs->count(),
            'suspicious_patterns' => $suspiciousPatterns,
            'risk_level' => $this->calculateRiskLevel($suspiciousPatterns),
        ];
    }

    /**
     * Calculate risk level based on suspicious patterns.
     */
    protected function calculateRiskLevel(array $patterns): string
    {
        if (empty($patterns)) {
            return 'low';
        }

        $highRiskPatterns = ['repeated_resource_access', 'rapid_requests'];
        $hasHighRiskPattern = collect($patterns)->pluck('type')->intersect($highRiskPatterns)->isNotEmpty();

        if ($hasHighRiskPattern || count($patterns) > 2) {
            return 'high';
        }

        if (count($patterns) > 1) {
            return 'medium';
        }

        return 'low';
    }
}
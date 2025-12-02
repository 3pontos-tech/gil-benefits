<?php

namespace App\Console\Commands\Authorization;

use App\Models\Users\User;
use App\Services\AuthorizationAuditService;
use Illuminate\Console\Command;

class GenerateAuthorizationReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authorization:report 
                            {--days=7 : Number of days to include in the report}
                            {--user= : Generate report for specific user ID}
                            {--format=table : Output format (table, json)}
                            {--suspicious : Only show suspicious activity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate authorization activity reports and detect suspicious patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $auditService = app(AuthorizationAuditService::class);
        $days = (int) $this->option('days');
        $userId = $this->option('user');
        $format = $this->option('format');
        $suspiciousOnly = $this->option('suspicious');

        if ($suspiciousOnly) {
            return $this->handleSuspiciousActivity($auditService);
        }

        if ($userId) {
            return $this->handleUserReport($auditService, $userId, $days, $format);
        }

        return $this->handleSystemReport($auditService, $days, $format);
    }

    /**
     * Handle system-wide authorization report.
     */
    protected function handleSystemReport(AuthorizationAuditService $auditService, int $days, string $format): int
    {
        $this->info("Generating system authorization report for the last {$days} days...");

        $startDate = now()->subDays($days);
        $endDate = now();

        $report = $auditService->generateAuthorizationReport($startDate, $endDate);

        if ($format === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displaySystemReport($report);
        return 0;
    }

    /**
     * Handle user-specific authorization report.
     */
    protected function handleUserReport(AuthorizationAuditService $auditService, string $userId, int $days, string $format): int
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("Generating authorization report for user: {$user->email} (last {$days} days)");

        $stats = $auditService->getUserAuthorizationStats($user, $days);
        $suspicious = $auditService->detectSuspiciousActivity($user, $days * 24);

        if ($format === 'json') {
            $this->line(json_encode(['stats' => $stats, 'suspicious' => $suspicious], JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayUserReport($stats, $suspicious);
        return 0;
    }

    /**
     * Handle suspicious activity detection.
     */
    protected function handleSuspiciousActivity(AuthorizationAuditService $auditService): int
    {
        $this->info('Scanning for suspicious authorization activity...');

        $users = User::with('companies')->get();
        $suspiciousUsers = [];

        foreach ($users as $user) {
            $activity = $auditService->detectSuspiciousActivity($user, 24);
            
            if ($activity['risk_level'] !== 'low') {
                $suspiciousUsers[] = $activity;
            }
        }

        if (empty($suspiciousUsers)) {
            $this->info('No suspicious authorization activity detected.');
            return 0;
        }

        $this->warn('Suspicious authorization activity detected:');
        
        foreach ($suspiciousUsers as $activity) {
            $user = User::find($activity['user_id']);
            $this->line('');
            $this->line("User: {$user->email} (ID: {$user->id})");
            $this->line("Risk Level: " . strtoupper($activity['risk_level']));
            $this->line("Total Requests: {$activity['total_requests']}");
            $this->line("Denied Requests: {$activity['denied_requests']}");
            
            foreach ($activity['suspicious_patterns'] as $pattern) {
                $this->line("  - {$pattern['description']} (Count: {$pattern['count']})");
            }
        }

        return 0;
    }

    /**
     * Display system-wide report in table format.
     */
    protected function displaySystemReport(array $report): void
    {
        $this->line('');
        $this->line('<fg=green>Authorization System Report</>');
        $this->line("Period: {$report['period']['start']} to {$report['period']['end']}");
        $this->line('');

        // Summary statistics
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Decisions', number_format($report['total_decisions'])],
                ['Granted Decisions', number_format($report['granted_decisions'])],
                ['Denied Decisions', number_format($report['denied_decisions'])],
                ['Success Rate', round(($report['granted_decisions'] / max($report['total_decisions'], 1)) * 100, 2) . '%'],
            ]
        );

        // Partner collaborator activity
        if (!empty($report['partner_collaborator_activity'])) {
            $this->line('');
            $this->line('<fg=yellow>Partner Collaborator Activity</>');
            $partnerActivity = $report['partner_collaborator_activity'];
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Decisions', number_format($partnerActivity['total_decisions'])],
                    ['Granted Decisions', number_format($partnerActivity['granted_decisions'])],
                    ['Denied Decisions', number_format($partnerActivity['denied_decisions'])],
                    ['Unique Users', number_format($partnerActivity['unique_users'])],
                ]
            );
        }

        // Security incidents
        if (!empty($report['security_incidents'])) {
            $this->line('');
            $this->line('<fg=red>Security Incidents</>');
            $incidents = $report['security_incidents'];
            
            $this->table(
                ['Type', 'Count'],
                [
                    ['Tenant Isolation Violations', $incidents['tenant_isolation_violations']],
                    ['Affected Users', $incidents['affected_users']],
                ]
            );
        }

        // Top denied actions
        if (!empty($report['top_denied_actions'])) {
            $this->line('');
            $this->line('<fg=yellow>Top Denied Actions</>');
            
            $deniedActions = collect($report['top_denied_actions'])
                ->take(10)
                ->map(fn($count, $action) => [$action, $count])
                ->values()
                ->toArray();
            
            $this->table(['Action', 'Count'], $deniedActions);
        }
    }

    /**
     * Display user-specific report in table format.
     */
    protected function displayUserReport(array $stats, array $suspicious): void
    {
        $this->line('');
        $this->line("<fg=green>User Authorization Report: {$stats['user_email']}</>");
        $this->line("Period: {$stats['period_days']} days");
        $this->line('');

        // User statistics
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Decisions', number_format($stats['total_decisions'])],
                ['Granted Decisions', number_format($stats['granted_decisions'])],
                ['Denied Decisions', number_format($stats['denied_decisions'])],
                ['Success Rate', $stats['success_rate'] . '%'],
            ]
        );

        // Most accessed models
        if (!empty($stats['most_accessed_models'])) {
            $this->line('');
            $this->line('<fg=cyan>Most Accessed Models</>');
            
            $models = collect($stats['most_accessed_models'])
                ->map(fn($count, $model) => [class_basename($model), $count])
                ->values()
                ->toArray();
            
            $this->table(['Model', 'Count'], $models);
        }

        // Suspicious activity
        if ($suspicious['risk_level'] !== 'low') {
            $this->line('');
            $this->line("<fg=red>Suspicious Activity Detected (Risk: {$suspicious['risk_level']})</>");
            
            foreach ($suspicious['suspicious_patterns'] as $pattern) {
                $this->line("  - {$pattern['description']}");
            }
        }

        // Recent failures
        if (!empty($stats['recent_failures'])) {
            $this->line('');
            $this->line('<fg=yellow>Recent Authorization Failures</>');
            
            $failures = collect($stats['recent_failures'])
                ->map(fn($failure) => [
                    $failure['action'],
                    $failure['reason'] ?? 'N/A',
                    class_basename($failure['model_type'] ?? 'N/A'),
                    $failure['timestamp'],
                ])
                ->toArray();
            
            $this->table(['Action', 'Reason', 'Model', 'Timestamp'], $failures);
        }
    }
}

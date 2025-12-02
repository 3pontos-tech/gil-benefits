<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurityLoggingService
{
    /**
     * Log authentication events
     */
    public static function logAuthenticationEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'authentication',
            'event' => $event,
        ], $context);

        match ($event) {
            'login_success' => Log::channel('security')->info('User login successful', $logData),
            'login_failed' => Log::channel('security')->warning('User login failed', $logData),
            'logout' => Log::channel('security')->info('User logout', $logData),
            'registration_success' => Log::channel('security')->info('User registration successful', $logData),
            'registration_failed' => Log::channel('security')->warning('User registration failed', $logData),
            'password_reset_requested' => Log::channel('security')->info('Password reset requested', $logData),
            'password_reset_completed' => Log::channel('security')->info('Password reset completed', $logData),
            'email_verification_sent' => Log::channel('security')->info('Email verification sent', $logData),
            'email_verification_completed' => Log::channel('security')->info('Email verification completed', $logData),
            default => Log::channel('security')->info("Authentication event: {$event}", $logData),
        };
    }

    /**
     * Log authorization events
     */
    public static function logAuthorizationEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'authorization',
            'event' => $event,
        ], $context);

        match ($event) {
            'access_granted' => Log::channel('security')->info('Access granted', $logData),
            'access_denied' => Log::channel('security')->warning('Access denied', $logData),
            'permission_granted' => Log::channel('security')->info('Permission granted', $logData),
            'permission_denied' => Log::channel('security')->warning('Permission denied', $logData),
            'role_assigned' => Log::channel('security')->info('Role assigned', $logData),
            'role_removed' => Log::channel('security')->info('Role removed', $logData),
            default => Log::channel('security')->info("Authorization event: {$event}", $logData),
        };
    }

    /**
     * Log security threats and suspicious activity
     */
    public static function logSecurityThreat(string $threat, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'security_threat',
            'threat_type' => $threat,
            'severity' => $context['severity'] ?? 'medium',
        ], $context);

        $severity = $context['severity'] ?? 'medium';

        match ($severity) {
            'critical' => Log::channel('security')->critical("Critical security threat: {$threat}", $logData),
            'high' => Log::channel('security')->error("High security threat: {$threat}", $logData),
            'medium' => Log::channel('security')->warning("Medium security threat: {$threat}", $logData),
            'low' => Log::channel('security')->notice("Low security threat: {$threat}", $logData),
            default => Log::channel('security')->warning("Security threat: {$threat}", $logData),
        };
    }

    /**
     * Log general security events
     */
    public static function logSecurityEvent(string $event, string $message, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'security_event',
            'event' => $event,
            'message' => $message,
            'severity' => $context['severity'] ?? 'medium',
        ], $context);

        $severity = $context['severity'] ?? 'medium';

        match ($severity) {
            'critical' => Log::channel('security')->critical($message, $logData),
            'high' => Log::channel('security')->error($message, $logData),
            'medium' => Log::channel('security')->warning($message, $logData),
            'low' => Log::channel('security')->notice($message, $logData),
            default => Log::channel('security')->info($message, $logData),
        };

        // For high severity events, also create an incident report
        if (in_array($severity, ['critical', 'high'])) {
            static::createIncidentReport($event, array_merge($context, [
                'message' => $message,
                'severity' => $severity,
            ]));
        }
    }

    /**
     * Log data access events
     */
    public static function logDataAccess(string $action, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'data_access',
            'action' => $action,
        ], $context);

        match ($action) {
            'sensitive_data_accessed' => Log::channel('security')->info('Sensitive data accessed', $logData),
            'personal_data_exported' => Log::channel('security')->warning('Personal data exported', $logData),
            'bulk_data_access' => Log::channel('security')->warning('Bulk data access', $logData),
            'unauthorized_data_access' => Log::channel('security')->error('Unauthorized data access attempt', $logData),
            default => Log::channel('security')->info("Data access: {$action}", $logData),
        };
    }

    /**
     * Log system events
     */
    public static function logSystemEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'system',
            'event' => $event,
        ], $context);

        match ($event) {
            'configuration_changed' => Log::channel('security')->warning('System configuration changed', $logData),
            'maintenance_mode_enabled' => Log::channel('security')->info('Maintenance mode enabled', $logData),
            'maintenance_mode_disabled' => Log::channel('security')->info('Maintenance mode disabled', $logData),
            'cache_cleared' => Log::channel('security')->info('Cache cleared', $logData),
            'database_migration' => Log::channel('security')->warning('Database migration executed', $logData),
            'backup_created' => Log::channel('security')->info('Backup created', $logData),
            'backup_restored' => Log::channel('security')->warning('Backup restored', $logData),
            default => Log::channel('security')->info("System event: {$event}", $logData),
        };
    }

    /**
     * Log validation events
     */
    public static function logValidationEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'validation',
            'event' => $event,
        ], $context);

        match ($event) {
            'validation_failed' => Log::channel('security')->warning('Input validation failed', $logData),
            'suspicious_input_detected' => Log::channel('security')->error('Suspicious input detected', $logData),
            'rate_limit_exceeded' => Log::channel('security')->warning('Rate limit exceeded', $logData),
            'csrf_token_mismatch' => Log::channel('security')->error('CSRF token mismatch', $logData),
            default => Log::channel('security')->info("Validation event: {$event}", $logData),
        };
    }

    /**
     * Log file operations
     */
    public static function logFileOperation(string $operation, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'file_operation',
            'operation' => $operation,
        ], $context);

        match ($operation) {
            'file_uploaded' => Log::channel('security')->info('File uploaded', $logData),
            'file_downloaded' => Log::channel('security')->info('File downloaded', $logData),
            'file_deleted' => Log::channel('security')->warning('File deleted', $logData),
            'suspicious_file_upload' => Log::channel('security')->error('Suspicious file upload attempt', $logData),
            'file_scan_failed' => Log::channel('security')->error('File security scan failed', $logData),
            default => Log::channel('security')->info("File operation: {$operation}", $logData),
        };
    }

    /**
     * Log payment and billing events
     */
    public static function logBillingEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'billing',
            'event' => $event,
        ], $context);

        match ($event) {
            'payment_successful' => Log::channel('security')->info('Payment successful', $logData),
            'payment_failed' => Log::channel('security')->warning('Payment failed', $logData),
            'subscription_created' => Log::channel('security')->info('Subscription created', $logData),
            'subscription_cancelled' => Log::channel('security')->warning('Subscription cancelled', $logData),
            'refund_processed' => Log::channel('security')->warning('Refund processed', $logData),
            'billing_fraud_detected' => Log::channel('security')->critical('Billing fraud detected', $logData),
            default => Log::channel('security')->info("Billing event: {$event}", $logData),
        };
    }

    /**
     * Log admin panel events
     */
    public static function logAdminEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'admin',
            'event' => $event,
        ], $context);

        match ($event) {
            'admin_login' => Log::channel('security')->info('Admin login', $logData),
            'admin_action_performed' => Log::channel('security')->warning('Admin action performed', $logData),
            'user_impersonated' => Log::channel('security')->warning('User impersonated', $logData),
            'bulk_operation_performed' => Log::channel('security')->warning('Bulk operation performed', $logData),
            'sensitive_setting_changed' => Log::channel('security')->error('Sensitive setting changed', $logData),
            default => Log::channel('security')->info("Admin event: {$event}", $logData),
        };
    }

    /**
     * Get base context for all security logs
     */
    protected static function getBaseContext(): array
    {
        $request = request();

        return [
            'timestamp' => now()->toISOString(),
            'request_id' => Str::uuid()->toString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'referer' => $request->header('referer'),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'real_ip' => $request->header('X-Real-IP'),
        ];
    }

    /**
     * Create a security incident report
     */
    public static function createIncidentReport(string $incident, array $details = []): string
    {
        $incidentId = Str::uuid()->toString();

        $report = [
            'incident_id' => $incidentId,
            'incident_type' => $incident,
            'severity' => $details['severity'] ?? 'medium',
            'description' => $details['description'] ?? "Security incident: {$incident}",
            'affected_resources' => $details['affected_resources'] ?? [],
            'mitigation_steps' => $details['mitigation_steps'] ?? [],
            'created_at' => now()->toISOString(),
            'context' => static::getBaseContext(),
            'additional_details' => $details,
        ];

        Log::channel('security')->critical('Security incident report created', $report);

        // In a production environment, you might want to:
        // - Send alerts to security team
        // - Create tickets in incident management system
        // - Trigger automated response procedures

        return $incidentId;
    }

    /**
     * Log security metrics for monitoring
     */
    public static function logSecurityMetrics(array $metrics): void
    {
        $logData = array_merge([
            'event_type' => 'security_metrics',
            'timestamp' => now()->toISOString(),
        ], $metrics);

        Log::channel('security')->info('Security metrics', $logData);
    }

    /**
     * Log compliance events (GDPR, etc.)
     */
    public static function logComplianceEvent(string $event, array $context = []): void
    {
        $baseContext = static::getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'compliance',
            'event' => $event,
        ], $context);

        match ($event) {
            'data_export_requested' => Log::channel('security')->info('Data export requested', $logData),
            'data_deletion_requested' => Log::channel('security')->warning('Data deletion requested', $logData),
            'consent_given' => Log::channel('security')->info('User consent given', $logData),
            'consent_withdrawn' => Log::channel('security')->warning('User consent withdrawn', $logData),
            'privacy_policy_accepted' => Log::channel('security')->info('Privacy policy accepted', $logData),
            default => Log::channel('security')->info("Compliance event: {$event}", $logData),
        };
    }

    /**
     * Batch log multiple events efficiently
     */
    public static function batchLog(array $events): void
    {
        foreach ($events as $event) {
            $type = $event['type'] ?? 'system';
            $eventName = $event['event'] ?? 'unknown';
            $context = $event['context'] ?? [];

            match ($type) {
                'authentication' => static::logAuthenticationEvent($eventName, $context),
                'authorization' => static::logAuthorizationEvent($eventName, $context),
                'security_threat' => static::logSecurityThreat($eventName, $context),
                'data_access' => static::logDataAccess($eventName, $context),
                'validation' => static::logValidationEvent($eventName, $context),
                'file_operation' => static::logFileOperation($eventName, $context),
                'billing' => static::logBillingEvent($eventName, $context),
                'admin' => static::logAdminEvent($eventName, $context),
                'compliance' => static::logComplianceEvent($eventName, $context),
                default => static::logSystemEvent($eventName, $context),
            };
        }
    }
}

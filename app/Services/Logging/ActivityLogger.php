<?php

declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function __construct(
        private readonly StructuredLogger $logger
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function logModelCreated(Model $model, array $context = []): void
    {
        $this->logger->logUserAction('model_created', array_merge([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'model_attributes' => $this->sanitizeModelAttributes($model->getAttributes()),
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $originalAttributes
     * @param  array<string, mixed>  $context
     */
    public function logModelUpdated(Model $model, array $originalAttributes, array $context = []): void
    {
        $changes = $model->getChanges();

        $this->logger->logUserAction('model_updated', array_merge([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'changes' => $this->sanitizeModelAttributes($changes),
            'original_attributes' => $this->sanitizeModelAttributes($originalAttributes),
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logModelDeleted(Model $model, array $context = []): void
    {
        $this->logger->logUserAction('model_deleted', array_merge([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'model_attributes' => $this->sanitizeModelAttributes($model->getAttributes()),
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logLogin(int $userId, string $email, array $context = []): void
    {
        $this->logger->logSecurityEvent('user_login', array_merge([
            'user_id' => $userId,
            'email' => $email,
            'login_method' => 'standard',
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logLogout(int $userId, array $context = []): void
    {
        $this->logger->logSecurityEvent('user_logout', array_merge([
            'user_id' => $userId,
        ], $context), 'info');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logFailedLogin(string $email, string $reason, array $context = []): void
    {
        $this->logger->logSecurityEvent('failed_login', array_merge([
            'email' => $email,
            'reason' => $reason,
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logRegistration(int $userId, string $email, array $context = []): void
    {
        $this->logger->logSecurityEvent('user_registration', array_merge([
            'user_id' => $userId,
            'email' => $email,
        ], $context), 'info');
    }

    public function logPasswordReset(string $email, array $context = []): void
    {
        $this->logger->logSecurityEvent('password_reset_requested', array_merge([
            'email' => $email,
        ], $context), 'info');
    }

    public function logPasswordChanged(int $userId, array $context = []): void
    {
        $this->logger->logSecurityEvent('password_changed', array_merge([
            'user_id' => $userId,
        ], $context), 'info');
    }

    public function logPermissionGranted(int $userId, string $permission, array $context = []): void
    {
        $this->logger->logSecurityEvent('permission_granted', array_merge([
            'user_id' => $userId,
            'permission' => $permission,
            'granted_by' => Auth::id(),
        ], $context), 'info');
    }

    public function logPermissionRevoked(int $userId, string $permission, array $context = []): void
    {
        $this->logger->logSecurityEvent('permission_revoked', array_merge([
            'user_id' => $userId,
            'permission' => $permission,
            'revoked_by' => Auth::id(),
        ], $context), 'warning');
    }

    public function logRoleAssigned(int $userId, string $role, array $context = []): void
    {
        $this->logger->logSecurityEvent('role_assigned', array_merge([
            'user_id' => $userId,
            'role' => $role,
            'assigned_by' => Auth::id(),
        ], $context), 'info');
    }

    public function logRoleRemoved(int $userId, string $role, array $context = []): void
    {
        $this->logger->logSecurityEvent('role_removed', array_merge([
            'user_id' => $userId,
            'role' => $role,
            'removed_by' => Auth::id(),
        ], $context), 'warning');
    }

    public function logFileUpload(string $filename, int $size, string $mimeType, array $context = []): void
    {
        $this->logger->logUserAction('file_uploaded', array_merge([
            'filename' => $filename,
            'size_bytes' => $size,
            'mime_type' => $mimeType,
        ], $context));
    }

    public function logFileDownload(string $filename, array $context = []): void
    {
        $this->logger->logUserAction('file_downloaded', array_merge([
            'filename' => $filename,
        ], $context));
    }

    public function logDataExport(string $exportType, int $recordCount, array $context = []): void
    {
        $this->logger->logSecurityEvent('data_exported', array_merge([
            'export_type' => $exportType,
            'record_count' => $recordCount,
        ], $context), 'warning');
    }

    public function logBulkOperation(string $operation, string $modelType, int $affectedCount, array $context = []): void
    {
        $this->logger->logUserAction('bulk_operation', array_merge([
            'operation' => $operation,
            'model_type' => $modelType,
            'affected_count' => $affectedCount,
        ], $context));
    }

    public function logApiAccess(string $endpoint, string $method, array $context = []): void
    {
        $this->logger->logUserAction('api_access', array_merge([
            'endpoint' => $endpoint,
            'method' => $method,
        ], $context));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logConfigurationChange(string $setting, mixed $oldValue, mixed $newValue, array $context = []): void
    {
        $this->logger->logSystemEvent('configuration_changed', array_merge([
            'setting' => $setting,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue),
            'changed_by' => Auth::id(),
        ], $context), 'warning');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function sanitizeModelAttributes(array $attributes): array
    {
        $sensitiveFields = [
            'password',
            'remember_token',
            'api_token',
            'cpf',
            'ssn',
            'credit_card_number',
            'bank_account',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = '[REDACTED]';
            }
        }

        return $attributes;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value) && (
            str_contains(strtolower($value), 'password') ||
            str_contains(strtolower($value), 'secret') ||
            str_contains(strtolower($value), 'token')
        )) {
            return '[REDACTED]';
        }

        return $value;
    }
}

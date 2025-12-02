<?php

declare(strict_types=1);

namespace App\Exceptions;

class SecurityException extends BaseException
{
    public function __construct(
        string $message = 'Security violation detected',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->logLevel = 'error';
        $this->logChannel = 'security';
    }

    protected function getDefaultErrorCode(): string
    {
        return 'SECURITY_VIOLATION';
    }

    public static function unauthorizedAccess(string $resource, array $context = []): static
    {
        return new static(
            "Unauthorized access attempt to resource: {$resource}",
            403,
            null,
            array_merge($context, ['resource' => $resource])
        )->setErrorCode('UNAUTHORIZED_ACCESS')->setLogLevel('warning');
    }

    public static function invalidToken(string $tokenType = 'authentication', array $context = []): static
    {
        return new static(
            "Invalid {$tokenType} token provided",
            401,
            null,
            array_merge($context, ['token_type' => $tokenType])
        )->setErrorCode('INVALID_TOKEN');
    }

    public static function rateLimitExceeded(string $identifier, array $context = []): static
    {
        return new static(
            "Rate limit exceeded for identifier: {$identifier}",
            429,
            null,
            array_merge($context, ['identifier' => $identifier])
        )->setErrorCode('RATE_LIMIT_EXCEEDED')->setLogLevel('warning');
    }

    public static function suspiciousActivity(string $activity, array $context = []): static
    {
        return new static(
            "Suspicious activity detected: {$activity}",
            403,
            null,
            array_merge($context, ['activity' => $activity])
        )->setErrorCode('SUSPICIOUS_ACTIVITY')->setLogLevel('critical');
    }

    public static function csrfTokenMismatch(array $context = []): static
    {
        return new static(
            'CSRF token mismatch detected',
            419,
            null,
            $context
        )->setErrorCode('CSRF_TOKEN_MISMATCH')->setLogLevel('warning');
    }

    public static function inputValidationFailed(string $field, array $context = []): static
    {
        return new static(
            "Input validation failed for field: {$field}",
            400,
            null,
            array_merge($context, ['field' => $field])
        )->setErrorCode('INPUT_VALIDATION_FAILED');
    }

    protected function getHttpStatusCode(): int
    {
        return match ($this->errorCode) {
            'INVALID_TOKEN' => 401,
            'CSRF_TOKEN_MISMATCH' => 419,
            'RATE_LIMIT_EXCEEDED' => 429,
            'INPUT_VALIDATION_FAILED' => 400,
            default => 403,
        };
    }
}

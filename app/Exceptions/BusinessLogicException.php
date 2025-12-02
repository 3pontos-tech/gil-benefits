<?php

declare(strict_types=1);

namespace App\Exceptions;

class BusinessLogicException extends BaseException
{
    protected function getDefaultErrorCode(): string
    {
        return 'BUSINESS_LOGIC_ERROR';
    }

    public static function invalidOperation(string $operation, array $context = []): static
    {
        return new static(
            "Invalid operation: {$operation}",
            400,
            null,
            $context
        )->setErrorCode('INVALID_OPERATION');
    }

    public static function resourceNotFound(string $resource, mixed $identifier, array $context = []): static
    {
        return new static(
            "Resource not found: {$resource} with identifier {$identifier}",
            404,
            null,
            array_merge($context, ['resource' => $resource, 'identifier' => $identifier])
        )->setErrorCode('RESOURCE_NOT_FOUND');
    }

    public static function resourceAlreadyExists(string $resource, mixed $identifier, array $context = []): static
    {
        return new static(
            "Resource already exists: {$resource} with identifier {$identifier}",
            409,
            null,
            array_merge($context, ['resource' => $resource, 'identifier' => $identifier])
        )->setErrorCode('RESOURCE_ALREADY_EXISTS');
    }

    public static function insufficientPermissions(string $action, array $context = []): static
    {
        return new static(
            "Insufficient permissions to perform action: {$action}",
            403,
            null,
            array_merge($context, ['action' => $action])
        )->setErrorCode('INSUFFICIENT_PERMISSIONS')->setLogChannel('security');
    }

    public static function operationNotAllowed(string $reason, array $context = []): static
    {
        return new static(
            "Operation not allowed: {$reason}",
            403,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('OPERATION_NOT_ALLOWED');
    }

    protected function getHttpStatusCode(): int
    {
        return match ($this->errorCode) {
            'RESOURCE_NOT_FOUND' => 404,
            'RESOURCE_ALREADY_EXISTS' => 409,
            'INSUFFICIENT_PERMISSIONS', 'OPERATION_NOT_ALLOWED' => 403,
            default => 400,
        };
    }
}

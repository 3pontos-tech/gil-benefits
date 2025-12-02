<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Throwable;

class DatabaseException extends BaseException
{
    public function __construct(
        string $message = 'Database operation failed',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->logLevel = 'error';
    }

    protected function getDefaultErrorCode(): string
    {
        return 'DATABASE_ERROR';
    }

    protected function getHttpStatusCode(): int
    {
        return 500;
    }

    public static function fromQueryException(QueryException $exception, array $context = []): static
    {
        $message = 'Database query failed';
        $errorCode = 'QUERY_FAILED';

        // Analyze the exception to provide more specific error information
        if (str_contains($exception->getMessage(), 'UNIQUE constraint failed')) {
            $message = 'Duplicate entry detected';
            $errorCode = 'DUPLICATE_ENTRY';
        } elseif (str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
            $message = 'Foreign key constraint violation';
            $errorCode = 'FOREIGN_KEY_VIOLATION';
        } elseif (str_contains($exception->getMessage(), 'NOT NULL constraint failed')) {
            $message = 'Required field is missing';
            $errorCode = 'REQUIRED_FIELD_MISSING';
        }

        return new static(
            $message,
            $exception->getCode(),
            $exception,
            array_merge($context, [
                'sql' => $exception->getSql(),
                'bindings' => $exception->getBindings(),
            ])
        )->setErrorCode($errorCode);
    }

    public static function transactionFailed(string $operation, ?Throwable $previous = null, array $context = []): static
    {
        return new static(
            "Transaction failed during operation: {$operation}",
            0,
            $previous,
            array_merge($context, ['operation' => $operation])
        )->setErrorCode('TRANSACTION_FAILED');
    }

    public static function connectionFailed(string $connection = 'default', array $context = []): static
    {
        return new static(
            "Database connection failed: {$connection}",
            0,
            null,
            array_merge($context, ['connection' => $connection])
        )->setErrorCode('CONNECTION_FAILED')->setLogLevel('critical');
    }

    public static function migrationFailed(string $migration, ?Throwable $previous = null, array $context = []): static
    {
        return new static(
            "Migration failed: {$migration}",
            0,
            $previous,
            array_merge($context, ['migration' => $migration])
        )->setErrorCode('MIGRATION_FAILED')->setLogLevel('critical');
    }
}

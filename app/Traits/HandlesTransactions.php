<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Database\TransactionManager;
use Closure;

trait HandlesTransactions
{
    protected function executeInTransaction(
        Closure $callback,
        ?string $operation = null,
        ?string $connection = null,
        int $attempts = 1
    ): mixed {
        $transactionManager = app(TransactionManager::class);

        $operation = $operation ?? $this->getDefaultOperationName();

        return $transactionManager->executeTransaction(
            $callback,
            $operation,
            $connection,
            $attempts
        );
    }

    protected function executeBatch(
        array $operations,
        ?string $batchName = null,
        ?string $connection = null
    ): array {
        $transactionManager = app(TransactionManager::class);

        $batchName = $batchName ?? $this->getDefaultBatchName();

        return $transactionManager->executeBatch(
            $operations,
            $batchName,
            $connection
        );
    }

    protected function executeWithSavepoint(
        Closure $callback,
        ?string $savepointName = null,
        ?string $operation = null,
        ?string $connection = null
    ): mixed {
        $transactionManager = app(TransactionManager::class);

        $savepointName = $savepointName ?? $this->generateSavepointName();
        $operation = $operation ?? $this->getDefaultOperationName();

        return $transactionManager->executeWithSavepoint(
            $callback,
            $savepointName,
            $operation,
            $connection
        );
    }

    protected function inTransaction(?string $connection = null): bool
    {
        $transactionManager = app(TransactionManager::class);

        return $transactionManager->inTransaction($connection);
    }

    private function getDefaultOperationName(): string
    {
        $className = class_basename(static::class);

        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($className)));
    }

    private function getDefaultBatchName(): string
    {
        return $this->getDefaultOperationName() . '_batch';
    }

    private function generateSavepointName(): string
    {
        return 'sp_' . uniqid();
    }
}

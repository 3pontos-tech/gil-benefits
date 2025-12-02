<?php

declare(strict_types=1);

namespace App\Services\Database;

use App\Exceptions\DatabaseException;
use App\Services\Logging\StructuredLogger;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionManager
{
    public function __construct(
        private readonly StructuredLogger $logger
    ) {}

    /**
     * Execute a database transaction with proper error handling and logging
     */
    public function executeTransaction(
        Closure $callback,
        string $operation = 'database_operation',
        ?string $connection = null,
        int $attempts = 1
    ): mixed {
        $startTime = microtime(true);
        $connection = $connection ?? config('database.default');

        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            try {
                $this->logger->logSystemEvent('transaction_started', [
                    'operation' => $operation,
                    'connection' => $connection,
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                ]);

                $result = DB::connection($connection)->transaction(function () use ($callback) {
                    return $callback();
                });

                $duration = microtime(true) - $startTime;

                $this->logger->logPerformanceEvent('transaction_completed', $duration, [
                    'operation' => $operation,
                    'connection' => $connection,
                    'attempt' => $attempt,
                ]);

                return $result;

            } catch (QueryException $e) {
                $duration = microtime(true) - $startTime;

                $this->logger->logSystemEvent('transaction_failed', [
                    'operation' => $operation,
                    'connection' => $connection,
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'duration_ms' => round($duration * 1000, 2),
                ], 'error');

                // If this is the last attempt or a non-retryable error, throw exception
                if ($attempt === $attempts || ! $this->isRetryableError($e)) {
                    throw DatabaseException::fromQueryException($e, [
                        'operation' => $operation,
                        'connection' => $connection,
                        'attempts_made' => $attempt,
                    ]);
                }

                // Wait before retrying (exponential backoff)
                if ($attempt < $attempts) {
                    $waitTime = pow(2, $attempt - 1) * 100000; // microseconds
                    usleep($waitTime);
                }

            } catch (Throwable $e) {
                $duration = microtime(true) - $startTime;

                $this->logger->logSystemEvent('transaction_error', [
                    'operation' => $operation,
                    'connection' => $connection,
                    'attempt' => $attempt,
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'duration_ms' => round($duration * 1000, 2),
                ], 'error');

                throw DatabaseException::transactionFailed($operation, $e, [
                    'connection' => $connection,
                    'attempts_made' => $attempt,
                ]);
            }
        }

        // This should never be reached, but just in case
        throw DatabaseException::transactionFailed($operation, null, [
            'connection' => $connection,
            'attempts_made' => $attempts,
            'reason' => 'Maximum attempts exceeded',
        ]);
    }

    /**
     * Execute multiple operations in a single transaction
     */
    public function executeBatch(
        array $operations,
        string $batchName = 'batch_operation',
        ?string $connection = null
    ): array {
        return $this->executeTransaction(
            function () use ($operations, $batchName) {
                $results = [];

                foreach ($operations as $index => $operation) {
                    $operationName = is_string($index) ? $index : "operation_{$index}";

                    $this->logger->logSystemEvent('batch_operation_started', [
                        'batch_name' => $batchName,
                        'operation_name' => $operationName,
                        'operation_index' => $index,
                    ]);

                    try {
                        $result = $operation();
                        $results[$operationName] = $result;

                        $this->logger->logSystemEvent('batch_operation_completed', [
                            'batch_name' => $batchName,
                            'operation_name' => $operationName,
                            'operation_index' => $index,
                        ]);

                    } catch (Throwable $e) {
                        $this->logger->logSystemEvent('batch_operation_failed', [
                            'batch_name' => $batchName,
                            'operation_name' => $operationName,
                            'operation_index' => $index,
                            'error_type' => get_class($e),
                            'error_message' => $e->getMessage(),
                        ], 'error');

                        throw $e;
                    }
                }

                return $results;
            },
            $batchName,
            $connection
        );
    }

    /**
     * Execute a transaction with savepoints for nested operations
     */
    public function executeWithSavepoint(
        Closure $callback,
        string $savepointName,
        string $operation = 'savepoint_operation',
        ?string $connection = null
    ): mixed {
        $connection = $connection ?? config('database.default');
        $db = DB::connection($connection);

        $this->logger->logSystemEvent('savepoint_created', [
            'savepoint_name' => $savepointName,
            'operation' => $operation,
            'connection' => $connection,
        ]);

        try {
            // Create savepoint
            $db->statement("SAVEPOINT {$savepointName}");

            $result = $callback();

            // Release savepoint on success
            $db->statement("RELEASE SAVEPOINT {$savepointName}");

            $this->logger->logSystemEvent('savepoint_released', [
                'savepoint_name' => $savepointName,
                'operation' => $operation,
                'connection' => $connection,
            ]);

            return $result;

        } catch (Throwable $e) {
            // Rollback to savepoint on error
            try {
                $db->statement("ROLLBACK TO SAVEPOINT {$savepointName}");

                $this->logger->logSystemEvent('savepoint_rollback', [
                    'savepoint_name' => $savepointName,
                    'operation' => $operation,
                    'connection' => $connection,
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                ], 'warning');

            } catch (Throwable $rollbackError) {
                $this->logger->logSystemEvent('savepoint_rollback_failed', [
                    'savepoint_name' => $savepointName,
                    'operation' => $operation,
                    'connection' => $connection,
                    'original_error' => $e->getMessage(),
                    'rollback_error' => $rollbackError->getMessage(),
                ], 'error');
            }

            throw $e;
        }
    }

    /**
     * Check if a database error is retryable
     */
    private function isRetryableError(QueryException $exception): bool
    {
        $retryableCodes = [
            1205, // Lock wait timeout
            1213, // Deadlock found
            2006, // MySQL server has gone away
            2013, // Lost connection to MySQL server
        ];

        $retryableMessages = [
            'database is locked',
            'connection reset',
            'broken pipe',
        ];

        // Check error codes
        if (in_array($exception->getCode(), $retryableCodes)) {
            return true;
        }

        // Check error messages
        $message = strtolower($exception->getMessage());
        foreach ($retryableMessages as $retryableMessage) {
            if (str_contains($message, $retryableMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current transaction level
     */
    public function getTransactionLevel(?string $connection = null): int
    {
        $connection = $connection ?? config('database.default');

        return DB::connection($connection)->transactionLevel();
    }

    /**
     * Check if currently in a transaction
     */
    public function inTransaction(?string $connection = null): bool
    {
        return $this->getTransactionLevel($connection) > 0;
    }
}

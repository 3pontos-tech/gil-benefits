<?php

declare(strict_types=1);

use App\Exceptions\BusinessLogicException;
use App\Exceptions\DatabaseException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidationException;
use App\Http\Responses\ApiErrorResponse;
use App\Http\Responses\ApiSuccessResponse;
use App\Services\Database\TransactionManager;
use App\Services\Logging\ActivityLogger;
use App\Services\Logging\StructuredLogger;
use App\Services\Monitoring\SystemMonitor;
use Illuminate\Support\Facades\Log;

it('handles business logic exceptions correctly', function () {
    $exception = BusinessLogicException::resourceNotFound('User', 123);

    expect($exception->getErrorCode())->toBe('RESOURCE_NOT_FOUND');
    expect($exception->getMessage())->toBe('Resource not found: User with identifier 123');
    expect($exception->getContext())->toHaveKey('resource');
    expect($exception->getContext())->toHaveKey('identifier');
});

it('handles validation exceptions correctly', function () {
    $errors = ['email' => ['The email field is required.']];
    $exception = new ValidationException('Validation failed', $errors);

    expect($exception->getErrorCode())->toBe('VALIDATION_FAILED');
    expect($exception->getErrors())->toBe($errors);
    expect($exception->getCode())->toBe(422);
});

it('handles security exceptions correctly', function () {
    $exception = SecurityException::unauthorizedAccess('admin-panel');

    expect($exception->getErrorCode())->toBe('UNAUTHORIZED_ACCESS');
    expect($exception->getMessage())->toContain('admin-panel');
});

it('handles database exceptions correctly', function () {
    $exception = DatabaseException::transactionFailed('user_creation');

    expect($exception->getErrorCode())->toBe('TRANSACTION_FAILED');
    expect($exception->getMessage())->toContain('user_creation');
});

it('formats API error responses correctly', function () {
    $response = ApiErrorResponse::badRequest('Invalid input', 'INVALID_INPUT');
    $jsonResponse = $response->toResponse(request());

    $data = json_decode($jsonResponse->getContent(), true);

    expect($data['success'])->toBeFalse();
    expect($data['error']['message'])->toBe('Invalid input');
    expect($data['error']['code'])->toBe('INVALID_INPUT');
    expect($data['error']['status'])->toBe(400);
    expect($data)->toHaveKey('meta');
    expect($data['meta'])->toHaveKey('request_id');
    expect($data['meta'])->toHaveKey('timestamp');
});

it('formats API success responses correctly', function () {
    $data = ['id' => 1, 'name' => 'Test User'];
    $response = ApiSuccessResponse::ok($data, 'User retrieved successfully');
    $jsonResponse = $response->toResponse(request());

    $responseData = json_decode($jsonResponse->getContent(), true);

    expect($responseData['success'])->toBeTrue();
    expect($responseData['message'])->toBe('User retrieved successfully');
    expect($responseData['data'])->toBe($data);
    expect($responseData)->toHaveKey('meta');
});

it('logs user actions with structured logger', function () {
    Log::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->once()
        ->with('User action: test_action', Mockery::type('array'));

    $logger = app(StructuredLogger::class);
    $logger->logUserAction('test_action', ['test' => 'data']);
});

it('logs security events with structured logger', function () {
    Log::shouldReceive('channel')
        ->with('security')
        ->andReturnSelf();

    Log::shouldReceive('warning')
        ->once()
        ->with('Security event: test_security_event', Mockery::type('array'));

    $logger = app(StructuredLogger::class);
    $logger->logSecurityEvent('test_security_event', ['ip' => '127.0.0.1']);
});

it('executes transactions successfully', function () {
    $transactionManager = app(TransactionManager::class);

    $result = $transactionManager->executeTransaction(function () {
        return 'success';
    }, 'test_operation');

    expect($result)->toBe('success');
});

it('rolls back transactions on exceptions', function () {
    $transactionManager = app(TransactionManager::class);

    expect(function () use ($transactionManager) {
        $transactionManager->executeTransaction(function () {
            throw new Exception('Test exception');
        }, 'test_operation');
    })->toThrow(DatabaseException::class);
});

it('performs system health checks', function () {
    $monitor = app(SystemMonitor::class);
    $health = $monitor->checkSystemHealth();

    expect($health)->toHaveKey('status');
    expect($health)->toHaveKey('checks');
    expect($health)->toHaveKey('timestamp');

    expect($health['status'])->toBeIn(['healthy', 'degraded', 'critical']);

    // Check that all expected health checks are present
    $expectedChecks = ['database', 'cache', 'storage', 'memory', 'queue'];
    foreach ($expectedChecks as $check) {
        expect($health['checks'])->toHaveKey($check);
        expect($health['checks'][$check])->toHaveKey('status');
    }
});

it('logs model creation activities', function () {
    Log::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf();

    Log::shouldReceive('info')
        ->once()
        ->with('User action: model_created', Mockery::type('array'));

    $activityLogger = app(ActivityLogger::class);

    // Create a mock model
    $model = new class extends Illuminate\Database\Eloquent\Model
    {
        protected $fillable = ['name'];

        public function getKey()
        {
            return 1;
        }

        public function getAttributes()
        {
            return ['name' => 'Test'];
        }
    };

    $activityLogger->logModelCreated($model);
});

it('records error monitoring data', function () {
    $monitor = app(SystemMonitor::class);

    // This should not throw an exception
    $monitor->recordError('test_error', 'Test error message', ['context' => 'test']);

    expect(true)->toBeTrue(); // If we get here, the method worked
});

it('records performance monitoring metrics', function () {
    $monitor = app(SystemMonitor::class);

    // This should not throw an exception
    $monitor->recordPerformanceMetric('test_metric', 100.5, ['context' => 'test']);

    expect(true)->toBeTrue(); // If we get here, the method worked
});

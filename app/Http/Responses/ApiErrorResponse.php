<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApiErrorResponse implements Responsable
{
    public function __construct(
        private readonly string $message,
        private readonly int $statusCode = 500,
        private readonly ?string $errorCode = null,
        private readonly array $errors = [],
        private readonly array $context = [],
        private readonly ?Throwable $exception = null,
        private readonly bool $logError = true
    ) {}

    public function toResponse($request): JsonResponse
    {
        if ($this->logError) {
            $this->logError($request);
        }

        $response = [
            'success' => false,
            'error' => [
                'message' => $this->message,
                'code' => $this->errorCode ?? $this->getDefaultErrorCode(),
                'status' => $this->statusCode,
            ],
        ];

        // Add validation errors if present
        if (! empty($this->errors)) {
            $response['error']['errors'] = $this->errors;
        }

        // Add context in development/testing environments
        if (app()->hasDebugModeEnabled() && ! empty($this->context)) {
            $response['error']['context'] = $this->context;
        }

        // Add debug information in development
        if (app()->hasDebugModeEnabled() && $this->exception) {
            $response['debug'] = [
                'exception' => get_class($this->exception),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
                'trace' => $this->exception->getTraceAsString(),
            ];
        }

        // Add request ID for tracking
        $response['meta'] = [
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response, $this->statusCode);
    }

    private function logError(Request $request): void
    {
        $logData = [
            'error_code' => $this->errorCode ?? $this->getDefaultErrorCode(),
            'status_code' => $this->statusCode,
            'message' => $this->message,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'context' => $this->context,
        ];

        if ($this->exception) {
            $logData['exception'] = [
                'class' => get_class($this->exception),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
                'message' => $this->exception->getMessage(),
            ];
        }

        $logLevel = $this->getLogLevel();
        $logChannel = $this->getLogChannel();

        Log::channel($logChannel)->{$logLevel}(
            "API Error: {$this->message}",
            $logData
        );
    }

    private function getDefaultErrorCode(): string
    {
        return match ($this->statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_SERVER_ERROR',
            502 => 'BAD_GATEWAY',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'UNKNOWN_ERROR',
        };
    }

    private function getLogLevel(): string
    {
        return match ($this->statusCode) {
            400, 401, 403, 404, 405, 409, 422 => 'warning',
            429 => 'notice',
            500, 502, 503 => 'error',
            default => 'error',
        };
    }

    private function getLogChannel(): string
    {
        return match ($this->statusCode) {
            401, 403, 429 => 'security',
            default => 'api',
        };
    }

    // Static factory methods for common error types
    public static function badRequest(
        string $message = 'Bad request',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 400, $errorCode, [], $context);
    }

    public static function unauthorized(
        string $message = 'Unauthorized',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 401, $errorCode, [], $context);
    }

    public static function forbidden(
        string $message = 'Forbidden',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 403, $errorCode, [], $context);
    }

    public static function notFound(
        string $message = 'Resource not found',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 404, $errorCode, [], $context);
    }

    public static function methodNotAllowed(
        string $message = 'Method not allowed',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 405, $errorCode, [], $context);
    }

    public static function conflict(
        string $message = 'Conflict',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 409, $errorCode, [], $context);
    }

    public static function validationError(
        string $message = 'Validation failed',
        array $errors = [],
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 422, $errorCode, $errors, $context);
    }

    public static function rateLimitExceeded(
        string $message = 'Rate limit exceeded',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 429, $errorCode, [], $context);
    }

    public static function internalServerError(
        string $message = 'Internal server error',
        ?string $errorCode = null,
        array $context = [],
        ?Throwable $exception = null
    ): self {
        return new self($message, 500, $errorCode, [], $context, $exception);
    }

    public static function serviceUnavailable(
        string $message = 'Service unavailable',
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self($message, 503, $errorCode, [], $context);
    }

    public static function fromException(
        Throwable $exception,
        ?string $message = null,
        ?int $statusCode = null,
        ?string $errorCode = null,
        array $context = []
    ): self {
        return new self(
            $message ?? $exception->getMessage(),
            $statusCode ?? 500,
            $errorCode,
            [],
            $context,
            $exception
        );
    }
}

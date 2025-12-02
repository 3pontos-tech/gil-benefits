<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseException extends Exception
{
    protected string $errorCode;

    protected array $context = [];

    protected string $logLevel = 'error';

    protected string $logChannel = 'default';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->errorCode = $this->getDefaultErrorCode();
    }

    abstract protected function getDefaultErrorCode(): string;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function setErrorCode(string $errorCode): static
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function addContext(string $key, mixed $value): static
    {
        $this->context[$key] = $value;

        return $this;
    }

    public function setLogLevel(string $level): static
    {
        $this->logLevel = $level;

        return $this;
    }

    public function setLogChannel(string $channel): static
    {
        $this->logChannel = $channel;

        return $this;
    }

    public function report(): void
    {
        $logData = [
            'exception' => static::class,
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ];

        Log::channel($this->logChannel)->{$this->logLevel}(
            "Exception occurred: {$this->getMessage()}",
            $logData
        );
    }

    public function render(Request $request): JsonResponse
    {
        $response = [
            'error' => true,
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if (app()->hasDebugModeEnabled()) {
            $response['debug'] = [
                'exception' => static::class,
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTrace(),
                'context' => $this->context,
            ];
        }

        return response()->json($response, $this->getHttpStatusCode());
    }

    protected function getHttpStatusCode(): int
    {
        return 500;
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidationException extends BaseException
{
    protected array $errors = [];

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        array $context = []
    ) {
        parent::__construct($message, 422, null, $context);
        $this->errors = $errors;
        $this->logLevel = 'warning';
    }

    protected function getDefaultErrorCode(): string
    {
        return 'VALIDATION_FAILED';
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    public function addError(string $field, string $message): static
    {
        if (! isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;

        return $this;
    }

    public function render(Request $request): JsonResponse
    {
        $response = [
            'error' => true,
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ];

        if (app()->hasDebugModeEnabled()) {
            $response['debug'] = [
                'exception' => static::class,
                'context' => $this->context,
            ];
        }

        return response()->json($response, 422);
    }

    protected function getHttpStatusCode(): int
    {
        return 422;
    }
}

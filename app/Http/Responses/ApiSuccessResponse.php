<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ApiSuccessResponse implements Responsable
{
    public function __construct(
        private readonly mixed $data = null,
        private readonly string $message = 'Success',
        private readonly int $statusCode = 200,
        private readonly array $meta = []
    ) {}

    public function toResponse($request): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $this->message,
        ];

        // Add data if present
        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        // Add meta information
        $response['meta'] = array_merge([
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            'timestamp' => now()->toISOString(),
        ], $this->meta);

        return response()->json($response, $this->statusCode);
    }

    // Static factory methods for common success responses
    public static function ok(mixed $data = null, string $message = 'Success'): self
    {
        return new self($data, $message, 200);
    }

    public static function created(mixed $data = null, string $message = 'Resource created successfully'): self
    {
        return new self($data, $message, 201);
    }

    public static function accepted(mixed $data = null, string $message = 'Request accepted'): self
    {
        return new self($data, $message, 202);
    }

    public static function noContent(string $message = 'No content'): self
    {
        return new self(null, $message, 204);
    }

    public static function paginated(
        mixed $data,
        int $total,
        int $perPage,
        int $currentPage,
        string $message = 'Success'
    ): self {
        $meta = [
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total),
            ],
        ];

        return new self($data, $message, 200, $meta);
    }

    public static function collection(
        mixed $data,
        ?int $count = null,
        string $message = 'Success'
    ): self {
        $meta = [];

        if ($count !== null) {
            $meta['count'] = $count;
        } elseif (is_countable($data)) {
            $meta['count'] = count($data);
        }

        return new self($data, $message, 200, $meta);
    }
}

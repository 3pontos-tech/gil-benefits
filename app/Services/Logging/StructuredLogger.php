<?php

declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StructuredLogger
{
    public function __construct(
        private readonly string $defaultChannel = 'default'
    ) {}

    public function logUserAction(
        string $action,
        array $context = [],
        string $level = 'info',
        ?string $channel = null
    ): void {
        $logData = $this->buildUserActionContext($action, $context);

        Log::channel($channel ?? $this->defaultChannel)
            ->{$level}("User action: {$action}", $logData);
    }

    public function logSystemEvent(
        string $event,
        array $context = [],
        string $level = 'info',
        ?string $channel = null
    ): void {
        $logData = $this->buildSystemEventContext($event, $context);

        Log::channel($channel ?? $this->defaultChannel)
            ->{$level}("System event: {$event}", $logData);
    }

    public function logSecurityEvent(
        string $event,
        array $context = [],
        string $level = 'warning'
    ): void {
        $logData = $this->buildSecurityEventContext($event, $context);

        Log::channel('security')
            ->{$level}("Security event: {$event}", $logData);
    }

    public function logBusinessEvent(
        string $event,
        string $module,
        array $context = [],
        string $level = 'info'
    ): void {
        $logData = $this->buildBusinessEventContext($event, $module, $context);

        Log::channel($this->defaultChannel)
            ->{$level}("Business event: {$module}.{$event}", $logData);
    }

    public function logPerformanceEvent(
        string $operation,
        float $duration,
        array $context = [],
        string $level = 'info'
    ): void {
        $logData = $this->buildPerformanceEventContext($operation, $duration, $context);

        Log::channel('performance')
            ->{$level}("Performance: {$operation}", $logData);
    }

    public function logApiRequest(
        Request $request,
        ?int $responseStatus = null,
        ?float $duration = null,
        array $context = []
    ): void {
        $logData = $this->buildApiRequestContext($request, $responseStatus, $duration, $context);

        $level = $this->determineApiLogLevel($responseStatus);
        $channel = $this->isSecuritySensitiveEndpoint($request) ? 'security' : 'api';

        Log::channel($channel)
            ->{$level}("API request: {$request->method()} {$request->path()}", $logData);
    }

    private function buildUserActionContext(string $action, array $context): array
    {
        $baseContext = $this->getBaseContext();

        return array_merge($baseContext, [
            'event_type' => 'user_action',
            'action' => $action,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'session_id' => session()->getId(),
        ], $context);
    }

    private function buildSystemEventContext(string $event, array $context): array
    {
        $baseContext = $this->getBaseContext();

        return array_merge($baseContext, [
            'event_type' => 'system_event',
            'event' => $event,
            'system_user' => 'system',
        ], $context);
    }

    private function buildSecurityEventContext(string $event, array $context): array
    {
        $baseContext = $this->getBaseContext();
        $request = request();

        return array_merge($baseContext, [
            'event_type' => 'security_event',
            'event' => $event,
            'user_id' => Auth::id(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'referer' => $request?->header('referer'),
            'session_id' => session()->getId(),
        ], $context);
    }

    private function buildBusinessEventContext(string $event, string $module, array $context): array
    {
        $baseContext = $this->getBaseContext();

        return array_merge($baseContext, [
            'event_type' => 'business_event',
            'module' => $module,
            'event' => $event,
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
        ], $context);
    }

    private function buildPerformanceEventContext(string $operation, float $duration, array $context): array
    {
        $baseContext = $this->getBaseContext();

        return array_merge($baseContext, [
            'event_type' => 'performance_event',
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ], $context);
    }

    private function buildApiRequestContext(
        Request $request,
        ?int $responseStatus,
        ?float $duration,
        array $context
    ): array {
        $baseContext = $this->getBaseContext();

        $logData = array_merge($baseContext, [
            'event_type' => 'api_request',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => Auth::id(),
        ], $context);

        if ($responseStatus !== null) {
            $logData['response_status'] = $responseStatus;
        }

        if ($duration !== null) {
            $logData['duration_ms'] = round($duration * 1000, 2);
        }

        // Add request payload for non-GET requests (but sanitize sensitive data)
        if (! $request->isMethod('GET') && $request->getContentTypeFormat() === 'json') {
            $payload = $request->json()->all();
            $logData['request_payload'] = $this->sanitizePayload($payload);
        }

        return $logData;
    }

    private function getBaseContext(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'application' => config('app.name'),
            'trace_id' => $this->generateTraceId(),
        ];
    }

    private function generateTraceId(): string
    {
        return Str::uuid()->toString();
    }

    private function determineApiLogLevel(?int $responseStatus): string
    {
        if ($responseStatus === null) {
            return 'info';
        }

        return match (true) {
            $responseStatus >= 500 => 'error',
            $responseStatus >= 400 => 'warning',
            default => 'info',
        };
    }

    private function isSecuritySensitiveEndpoint(Request $request): bool
    {
        $sensitivePatterns = [
            '/login',
            '/register',
            '/password',
            '/auth',
            '/admin',
            '/api/admin',
        ];

        $path = $request->path();

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'key',
            'cpf',
            'ssn',
            'credit_card',
            'card_number',
        ];

        return $this->recursiveSanitize($payload, $sensitiveFields);
    }

    private function recursiveSanitize(array $data, array $sensitiveFields): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value, $sensitiveFields);
            } elseif (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }
}

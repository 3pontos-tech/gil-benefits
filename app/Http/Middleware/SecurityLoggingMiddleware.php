<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityLoggingMiddleware
{
    /**
     * Suspicious patterns to detect in requests
     */
    protected array $suspiciousPatterns = [
        // SQL Injection patterns
        '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bfrom\b)|(\binsert\b.*\binto\b)|(\bdelete\b.*\bfrom\b)|(\bdrop\b.*\btable\b)/i',

        // XSS patterns
        '/<script[^>]*>.*?<\/script>/i',
        '/javascript:/i',
        '/on\w+\s*=/i',

        // Path traversal patterns
        '/\.\.[\/\\\\]/i',
        '/\/(etc|proc|sys|var)\//i',

        // Command injection patterns
        '/[;&|`$(){}]/i',

        // File inclusion patterns
        '/(php|asp|jsp|cfm):\/\//i',
        '/\b(include|require|eval)\s*\(/i',
    ];

    /**
     * Suspicious user agent patterns
     */
    protected array $suspiciousUserAgents = [
        '/bot|crawler|spider|scraper/i',
        '/curl|wget|python|java/i',
        '/nikto|sqlmap|nmap|masscan/i',
        '/burp|zap|w3af|acunetix/i',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log security events before processing
        $this->logSecurityEvent($request);

        // Check for suspicious activity
        $this->detectSuspiciousActivity($request);

        $response = $next($request);

        // Log response information if needed
        $this->logResponseSecurity($request, $response);

        return $response;
    }

    /**
     * Log security-related request information
     */
    protected function logSecurityEvent(Request $request): void
    {
        $logData = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'referer' => $request->header('referer'),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => Str::uuid()->toString(),
        ];

        // Add user information if authenticated
        if ($request->user()) {
            $logData['user_id'] = $request->user()->id;
            $logData['user_email'] = $request->user()->email;
        }

        // Add forwarded IP information
        if ($request->hasHeader('X-Forwarded-For')) {
            $logData['forwarded_for'] = $request->header('X-Forwarded-For');
        }

        // Log different types of requests with appropriate levels
        if ($this->isSensitiveEndpoint($request)) {
            Log::channel('security')->info('Sensitive endpoint access', $logData);
        } elseif ($this->isAuthenticationEndpoint($request)) {
            Log::channel('security')->info('Authentication attempt', $logData);
        } else {
            Log::info('Request logged', $logData);
        }
    }

    /**
     * Detect and log suspicious activity
     */
    protected function detectSuspiciousActivity(Request $request): void
    {
        $flags = [];

        // Check for suspicious patterns in request data
        $allInput = json_encode($request->all());
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $allInput)) {
                $flags[] = 'suspicious_input_pattern';
                break;
            }
        }

        // Check for suspicious patterns in URL
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $request->fullUrl())) {
                $flags[] = 'suspicious_url_pattern';
                break;
            }
        }

        // Check for suspicious user agents
        $userAgent = $request->userAgent() ?? '';
        foreach ($this->suspiciousUserAgents as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                $flags[] = 'suspicious_user_agent';
                break;
            }
        }

        // Check for missing user agent
        if (empty($userAgent)) {
            $flags[] = 'missing_user_agent';
        }

        // Check for suspicious headers
        if ($this->hasSuspiciousHeaders($request)) {
            $flags[] = 'suspicious_headers';
        }

        // Check for rapid requests (basic implementation)
        if ($this->isRapidRequest($request)) {
            $flags[] = 'rapid_requests';
        }

        // Log suspicious activity
        if (! empty($flags)) {
            Log::channel('security')->warning('Suspicious activity detected', [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'flags' => $flags,
                'input_keys' => array_keys($request->all()),
                'timestamp' => now()->toISOString(),
                'user_id' => $request->user()?->id,
            ]);
        }
    }

    /**
     * Log response security information
     */
    protected function logResponseSecurity(Request $request, Response $response): void
    {
        // Log failed authentication attempts
        if ($response->getStatusCode() === 401) {
            Log::channel('security')->warning('Authentication failed', [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Log authorization failures
        if ($response->getStatusCode() === 403) {
            Log::channel('security')->warning('Authorization failed', [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Log server errors that might indicate attacks
        if ($response->getStatusCode() >= 500) {
            Log::channel('security')->error('Server error response', [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'user_id' => $request->user()?->id,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Check if the request is to a sensitive endpoint
     */
    protected function isSensitiveEndpoint(Request $request): bool
    {
        $sensitivePatterns = [
            '/admin/',
            '/api/',
            '/billing/',
            '/payments/',
            '/users/',
            '/profile/',
        ];

        $path = $request->path();
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($path, trim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the request is to an authentication endpoint
     */
    protected function isAuthenticationEndpoint(Request $request): bool
    {
        $authPatterns = [
            '/login',
            '/register',
            '/password',
            '/auth',
            '/partners',
        ];

        $path = $request->path();
        foreach ($authPatterns as $pattern) {
            if (str_contains($path, trim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious headers
     */
    protected function hasSuspiciousHeaders(Request $request): bool
    {
        // Check for too many proxy headers
        $proxyHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'X-Originating-IP',
            'CF-Connecting-IP',
            'X-Cluster-Client-IP',
        ];

        $proxyCount = 0;
        foreach ($proxyHeaders as $header) {
            if ($request->hasHeader($header)) {
                ++$proxyCount;
            }
        }

        if ($proxyCount > 2) {
            return true;
        }

        // Check for suspicious header values
        $suspiciousHeaders = [
            'X-Requested-With' => ['XMLHttpRequest'],
            'Accept' => ['*/*'],
        ];

        foreach ($suspiciousHeaders as $header => $suspiciousValues) {
            $value = $request->header($header);
            if ($value && ! in_array($value, $suspiciousValues, true)) {
                // This is just an example - adjust logic as needed
            }
        }

        return false;
    }

    /**
     * Basic rapid request detection
     */
    protected function isRapidRequest(Request $request): bool
    {
        // This is a basic implementation
        // In production, you'd want to use Redis or another cache store
        // to track request timestamps per IP

        $cacheKey = 'rapid_requests:' . $request->ip();
        $requests = cache()->get($cacheKey, []);

        // Clean old requests (older than 1 minute)
        $cutoff = now()->subMinute();
        $requests = array_filter($requests, fn ($timestamp) => $timestamp > $cutoff);

        // Add current request
        $requests[] = now();

        // Store updated requests
        cache()->put($cacheKey, $requests, 300); // 5 minutes

        // Check if too many requests in the last minute
        return count($requests) > 60; // More than 60 requests per minute
    }
}

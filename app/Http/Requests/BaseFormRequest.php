<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Common security headers to check
     */
    protected array $securityHeaders = [
        'X-Forwarded-For',
        'X-Real-IP',
        'X-Originating-IP',
        'CF-Connecting-IP',
    ];

    /**
     * Suspicious user agent patterns
     */
    protected array $suspiciousUserAgents = [
        'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java',
        'postman', 'insomnia', 'httpie', 'test', 'automation', 'headless',
    ];

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Log validation attempt for security monitoring
        $this->logSecurityEvent('validation_attempt');

        // Perform common input sanitization
        $this->sanitizeCommonInputs();
    }

    /**
     * Sanitize common input fields
     */
    protected function sanitizeCommonInputs(): void
    {
        $sanitized = [];

        // Sanitize email fields
        if ($this->has('email')) {
            $sanitized['email'] = $this->sanitizeEmail($this->input('email'));
        }

        // Sanitize name fields
        foreach (['name', 'first_name', 'last_name', 'full_name'] as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = $this->sanitizeName($this->input($field));
            }
        }

        // Sanitize phone fields
        foreach (['phone', 'mobile', 'telephone'] as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = $this->sanitizePhone($this->input($field));
            }
        }

        // Merge sanitized data
        if (! empty($sanitized)) {
            $this->merge($sanitized);
        }
    }

    /**
     * Sanitize email input
     */
    protected function sanitizeEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        // Remove HTML tags and convert to lowercase
        $email = strtolower(trim(strip_tags($email)));

        // Remove any characters that are not valid in email addresses
        $email = preg_replace('/[^a-z0-9._%+-@]/', '', $email);

        return $email;
    }

    /**
     * Sanitize name input
     */
    protected function sanitizeName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        // Remove HTML tags and decode entities
        $name = html_entity_decode(strip_tags($name), ENT_QUOTES, 'UTF-8');

        // Trim whitespace and normalize spaces
        $name = trim(preg_replace('/\s+/', ' ', $name));

        // Remove any non-letter characters except spaces, hyphens, apostrophes, and dots
        $name = preg_replace('/[^\p{L}\s\-\'\.]/u', '', $name);

        return $name;
    }

    /**
     * Sanitize phone input
     */
    protected function sanitizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Remove HTML tags and trim
        $phone = trim(strip_tags($phone));

        // Keep only numbers, spaces, parentheses, hyphens, and plus signs
        $phone = preg_replace('/[^0-9\s\(\)\-\+]/', '', $phone);

        return $phone;
    }

    /**
     * Log security events for monitoring
     */
    protected function logSecurityEvent(string $event, array $additionalData = []): void
    {
        $request = request();

        $logData = [
            'event' => $event,
            'request_class' => static::class,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => Str::uuid()->toString(),
        ];

        // Add user information if authenticated
        if ($request->user()) {
            $logData['user_id'] = $request->user()->id;
            $logData['user_email'] = $request->user()->email;
        }

        // Add additional data
        $logData = array_merge($logData, $additionalData);

        // Detect potential security threats
        $securityFlags = $this->detectSecurityThreats($request);
        if (! empty($securityFlags)) {
            $logData['security_flags'] = $securityFlags;
            Log::channel('security')->warning("Security event: {$event}", $logData);
        } else {
            Log::info("Security event: {$event}", $logData);
        }
    }

    /**
     * Detect potential security threats
     */
    protected function detectSecurityThreats($request): array
    {
        $flags = [];

        // Check for suspicious user agents
        $userAgent = strtolower($request->userAgent() ?? '');
        foreach ($this->suspiciousUserAgents as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                $flags[] = 'suspicious_user_agent';
                break;
            }
        }

        // Check for missing user agent
        if (empty($userAgent)) {
            $flags[] = 'missing_user_agent';
        }

        // Check for missing referer (direct access)
        if (empty($request->header('referer'))) {
            $flags[] = 'missing_referer';
        }

        // Check for multiple proxy headers (potential proxy chaining)
        $proxyHeaders = 0;
        foreach ($this->securityHeaders as $header) {
            if ($request->hasHeader($header)) {
                ++$proxyHeaders;
            }
        }
        if ($proxyHeaders > 2) {
            $flags[] = 'multiple_proxies';
        }

        // Check for suspicious request patterns
        if ($request->hasHeader('X-Forwarded-For')) {
            $forwardedIps = explode(',', $request->header('X-Forwarded-For'));
            if (count($forwardedIps) > 3) {
                $flags[] = 'excessive_proxy_chain';
            }
        }

        // Check for rapid requests (would need cache/Redis for proper implementation)
        // This is a placeholder for future implementation with rate limiting

        return $flags;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log failed validation for security monitoring
        $this->logSecurityEvent('validation_failed', [
            'errors' => $validator->errors()->toArray(),
            'input_fields' => array_keys($this->all()),
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get common validation rules for email
     */
    protected function getEmailRules(): array
    {
        return [
            'required',
            'email:rfc,dns',
            'max:255',
            'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        ];
    }

    /**
     * Get common validation rules for names
     */
    protected function getNameRules(): array
    {
        return [
            'required',
            'string',
            'min:2',
            'max:255',
            'regex:/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/u',
        ];
    }

    /**
     * Get common validation rules for phone numbers
     */
    protected function getPhoneRules(): array
    {
        return [
            'required',
            'string',
            'min:10',
            'max:20',
            'regex:/^[\+]?[0-9\s\(\)\-]+$/',
        ];
    }

    /**
     * Get common validation messages
     */
    protected function getCommonMessages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto válido.',
            'email' => 'O campo :attribute deve ser um e-mail válido.',
            'min' => 'O campo :attribute deve ter pelo menos :min caracteres.',
            'max' => 'O campo :attribute não pode ter mais de :max caracteres.',
            'regex' => 'O formato do campo :attribute não é válido.',
            'unique' => 'Este :attribute já está em uso.',
            'confirmed' => 'A confirmação do campo :attribute não confere.',
        ];
    }
}

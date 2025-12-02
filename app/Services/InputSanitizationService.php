<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class InputSanitizationService
{
    /**
     * Dangerous HTML tags to remove
     *
     * @var array<int, string>
     */
    protected static array $dangerousTags = [
        'script', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'option', 'link', 'meta', 'style', 'base',
        'applet', 'body', 'html', 'head', 'title', 'frameset', 'frame',
    ];

    /**
     * Dangerous attributes to remove
     *
     * @var array<int, string>
     */
    protected static array $dangerousAttributes = [
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onkeydown', 'onkeyup', 'onkeypress', 'onfocus', 'onblur',
        'onchange', 'onsubmit', 'onreset', 'onselect', 'onunload',
        'javascript', 'vbscript', 'data', 'src', 'href',
    ];

    /**
     * SQL injection patterns
     *
     * @var array<int, string>
     */
    protected static array $sqlPatterns = [
        '/(\bunion\b.*\bselect\b)/i',
        '/(\bselect\b.*\bfrom\b)/i',
        '/(\binsert\b.*\binto\b)/i',
        '/(\bdelete\b.*\bfrom\b)/i',
        '/(\bdrop\b.*\btable\b)/i',
        '/(\bupdate\b.*\bset\b)/i',
        '/(\balter\b.*\btable\b)/i',
        '/(\bcreate\b.*\btable\b)/i',
        '/(\bexec\b.*\()/i',
        '/(\bexecute\b.*\()/i',
    ];

    /**
     * XSS patterns
     *
     * @var array<int, string>
     */
    protected static array $xssPatterns = [
        '/<script[^>]*>.*?<\/script>/is',
        '/javascript:/i',
        '/vbscript:/i',
        '/on\w+\s*=/i',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>/i',
        '/<applet[^>]*>.*?<\/applet>/is',
    ];

    /**
     * Sanitize a string input
     *
     * @param  array<string, mixed>  $options
     */
    public static function sanitizeString(?string $input, array $options = []): ?string
    {
        if ($input === null || $input === '') {
            return $input;
        }

        $options = array_merge([
            'strip_tags' => true,
            'decode_entities' => true,
            'trim' => true,
            'normalize_spaces' => true,
            'remove_control_chars' => true,
            'max_length' => null,
            'allowed_tags' => [],
            'log_suspicious' => true,
        ], $options);

        $original = $input;

        // Detect and log suspicious patterns
        if ($options['log_suspicious']) {
            static::detectSuspiciousPatterns($input);
        }

        // Decode HTML entities first
        if ($options['decode_entities']) {
            $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Remove dangerous script content first
        foreach (static::$xssPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        // Remove or escape dangerous content
        if ($options['strip_tags']) {
            if (! empty($options['allowed_tags'])) {
                $input = strip_tags($input, $options['allowed_tags']);
            } else {
                $input = strip_tags($input);
            }

            // Additional XSS protection - remove remaining script content
            $input = preg_replace('/javascript:/i', '', $input);
            $input = preg_replace('/on\w+\s*=/i', '', $input);
        }

        // Remove control characters
        if ($options['remove_control_chars']) {
            $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        }

        // Normalize whitespace
        if ($options['normalize_spaces']) {
            $input = preg_replace('/\s+/', ' ', $input);
        }

        // Trim whitespace
        if ($options['trim']) {
            $input = trim($input);
        }

        // Enforce maximum length
        if ($options['max_length'] && strlen($input) > $options['max_length']) {
            $input = substr($input, 0, $options['max_length']);
        }

        // Log if input was significantly modified
        if ($options['log_suspicious'] && static::wasSignificantlyModified($original, $input)) {
            Log::channel('security')->warning('Input significantly modified during sanitization', [
                'original_length' => strlen($original),
                'sanitized_length' => strlen($input),
                'original_preview' => substr($original, 0, 100),
                'sanitized_preview' => substr($input, 0, 100),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        return $input;
    }

    /**
     * Sanitize an email address
     */
    public static function sanitizeEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        // Convert to lowercase and trim
        $email = strtolower(trim($email));

        // Remove any characters that are not valid in email addresses
        $email = preg_replace('/[^a-z0-9._%+-@]/', '', $email);

        // Basic email format validation
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    /**
     * Sanitize a name (person's name)
     */
    public static function sanitizeName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        return static::sanitizeString($name, [
            'strip_tags' => true,
            'decode_entities' => true,
            'trim' => true,
            'normalize_spaces' => true,
            'max_length' => 255,
        ]);
    }

    /**
     * Sanitize a phone number
     */
    public static function sanitizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Remove common extension patterns first
        $phone = preg_replace('/\s*(ext|extension|x)\s*\d+$/i', '', trim($phone));

        // Remove all non-numeric characters except +, -, (, ), and spaces
        $phone = preg_replace('/[^0-9\+\-\(\)\s]/', '', $phone);

        // Normalize spaces
        $phone = preg_replace('/\s+/', ' ', $phone);

        return trim($phone);
    }

    /**
     * Sanitize a URL
     */
    public static function sanitizeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);

        // Add protocol if missing
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        // Validate URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Check for dangerous protocols
        $parsed = parse_url($url);
        if (! $parsed || ! in_array($parsed['scheme'], ['http', 'https'])) {
            return null;
        }

        return $url;
    }

    /**
     * Sanitize HTML content (for rich text)
     *
     * @param  array<int, string>  $allowedTags
     */
    public static function sanitizeHtml(?string $html, array $allowedTags = []): ?string
    {
        if (! $html) {
            return null;
        }

        // Default allowed tags for rich content
        if (empty($allowedTags)) {
            $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        }

        return static::sanitizeString($html, [
            'strip_tags' => true,
            'allowed_tags' => '<' . implode('><', $allowedTags) . '>',
            'decode_entities' => true,
            'trim' => true,
            'log_suspicious' => true,
        ]);
    }

    /**
     * Sanitize an array of inputs
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function sanitizeArray(array $input, array $rules = []): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            $rule = $rules[$key] ?? 'string';

            if (is_array($value)) {
                $sanitized[$key] = static::sanitizeArray($value, $rules[$key] ?? []);
            } else {
                $sanitized[$key] = static::sanitizeByType($value, $rule);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize input based on type
     */
    public static function sanitizeByType(mixed $value, string $type): mixed
    {
        return match ($type) {
            'email' => static::sanitizeEmail($value),
            'name' => static::sanitizeName($value),
            'phone' => static::sanitizePhone($value),
            'url' => static::sanitizeUrl($value),
            'html' => static::sanitizeHtml($value),
            'integer' => filter_var($value, FILTER_SANITIZE_NUMBER_INT),
            'float' => filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => static::sanitizeString($value),
            default => static::sanitizeString($value),
        };
    }

    /**
     * Detect suspicious patterns in input
     */
    protected static function detectSuspiciousPatterns(string $input): void
    {
        $flags = [];

        // Check for SQL injection patterns
        foreach (static::$sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $flags[] = 'sql_injection_pattern';
                break;
            }
        }

        // Check for XSS patterns
        foreach (static::$xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $flags[] = 'xss_pattern';
                break;
            }
        }

        // Check for path traversal
        if (preg_match('/\.\.[\/\\\\]/', $input)) {
            $flags[] = 'path_traversal';
        }

        // Check for command injection
        if (preg_match('/[;&|`$()]/', $input)) {
            $flags[] = 'command_injection';
        }

        // Check for file inclusion
        if (preg_match('/(php|asp|jsp):\/\/|include\s*\(|require\s*\(/i', $input)) {
            $flags[] = 'file_inclusion';
        }

        // Log suspicious patterns
        if (! empty($flags)) {
            Log::channel('security')->warning('Suspicious input patterns detected', [
                'flags' => $flags,
                'input_preview' => substr($input, 0, 200),
                'input_length' => strlen($input),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Check if input was significantly modified
     */
    protected static function wasSignificantlyModified(string $original, string $sanitized): bool
    {
        $originalLength = strlen($original);
        $sanitizedLength = strlen($sanitized);

        // Consider it significant if more than 20% was removed or if dangerous patterns were found
        $percentageRemoved = $originalLength > 0 ? (($originalLength - $sanitizedLength) / $originalLength) * 100 : 0;

        return $percentageRemoved > 20 || $originalLength > $sanitizedLength + 50;
    }

    /**
     * Validate CSRF token manually (for custom forms)
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (! $token) {
            return false;
        }

        $sessionToken = session()->token();

        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate a secure random token
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Sanitize file upload data
     *
     * @param  array<string, mixed>  $fileData
     * @return array<string, mixed>
     */
    public static function sanitizeFileUpload(array $fileData): array
    {
        $sanitized = [];

        // Sanitize filename
        if (isset($fileData['name'])) {
            // First strip HTML tags and decode entities
            $filename = strip_tags(html_entity_decode($fileData['name'], ENT_QUOTES, 'UTF-8'));
            // Remove path traversal attempts
            $filename = basename($filename);
            // Remove dangerous characters but keep dots for extensions
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            // Remove multiple dots (potential path traversal)
            $filename = preg_replace('/\.{2,}/', '.', $filename);
            $sanitized['name'] = substr($filename, 0, 255);
        }

        // Validate file type
        if (isset($fileData['type'])) {
            $allowedTypes = [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf', 'text/plain', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];

            if (in_array($fileData['type'], $allowedTypes)) {
                $sanitized['type'] = $fileData['type'];
            }
        }

        // Keep other safe properties
        foreach (['size', 'tmp_name', 'error'] as $key) {
            if (isset($fileData[$key])) {
                $sanitized[$key] = $fileData[$key];
            }
        }

        return $sanitized;
    }
}

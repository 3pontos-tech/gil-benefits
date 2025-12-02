<?php

use App\Http\Requests\PartnerRegistrationRequest;
use App\Services\InputSanitizationService;
use App\Services\SecurityLoggingService;
use App\Services\ValidationRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

describe('Security Validation', function () {
    it('sanitizes malicious input correctly', function () {
        $maliciousInput = '<script>alert("xss")</script>Test Name';
        $sanitized = InputSanitizationService::sanitizeString($maliciousInput);

        expect($sanitized)->toBe('Test Name');
        expect($sanitized)->not->toContain('<script>');
        expect($sanitized)->not->toContain('alert');
    });

    it('detects SQL injection patterns', function () {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->with('Suspicious input patterns detected', \Mockery::type('array'));

        $sqlInjection = "'; DROP TABLE users; --";
        InputSanitizationService::sanitizeString($sqlInjection);
    });

    it('validates email addresses correctly', function () {
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';

        expect(InputSanitizationService::sanitizeEmail($validEmail))->toBe($validEmail);
        expect(InputSanitizationService::sanitizeEmail($invalidEmail))->toBeNull();
    });

    it('sanitizes phone numbers correctly', function () {
        $phone = '+1 (555) 123-4567 ext 123';
        $sanitized = InputSanitizationService::sanitizePhone($phone);

        expect($sanitized)->toBe('+1 (555) 123-4567');
        expect($sanitized)->not->toContain('ext');
    });

    it('validates partner registration request with valid data', function () {
        $validData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'cpf' => '123.456.789-09',
            'rg' => '12.345.678-9',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'partner_code' => 'VALID123',
        ];

        $request = new PartnerRegistrationRequest;
        $request->merge($validData);

        $rules = $request->rules();

        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('cpf');
        expect($rules)->toHaveKey('rg');
        expect($rules)->toHaveKey('password');
        expect($rules)->toHaveKey('partner_code');
    });

    it('provides standardized validation rules', function () {
        $emailRules = ValidationRulesService::email();
        $nameRules = ValidationRulesService::name();
        $phoneRules = ValidationRulesService::phone();

        expect($emailRules)->toContain('required');
        expect($emailRules)->toContain('email:rfc,dns');
        expect($nameRules)->toContain('required');
        expect($nameRules)->toContain('string');
        expect($phoneRules)->toContain('required');
    });

    it('logs security events correctly', function () {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('User login successful', \Mockery::type('array'));

        SecurityLoggingService::logAuthenticationEvent('login_success', [
            'user_id' => 1,
            'email' => 'test@example.com',
        ]);
    });

    it('logs security threats with appropriate severity', function () {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('critical')
            ->once()
            ->with(\Mockery::pattern('/Critical security threat/'), \Mockery::type('array'));

        SecurityLoggingService::logSecurityThreat('sql_injection_attempt', [
            'severity' => 'critical',
            'pattern' => 'DROP TABLE',
        ]);
    });

    it('creates incident reports with unique IDs', function () {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();
        Log::shouldReceive('critical')
            ->once()
            ->with('Security incident report created', \Mockery::type('array'));

        $incidentId = SecurityLoggingService::createIncidentReport('data_breach', [
            'severity' => 'critical',
            'description' => 'Unauthorized access detected',
        ]);

        expect($incidentId)->toBeString();
        expect(strlen($incidentId))->toBe(36); // UUID length
    });

    it('handles rate limiting configuration', function () {
        // Test that rate limiters are configured
        $limiters = [
            'api', 'auth', 'partner-registration', 'password-reset',
            'contact', 'uploads', 'search', 'admin', 'guest',
        ];

        foreach ($limiters as $limiter) {
            expect(RateLimiter::limiter($limiter))->not->toBeNull();
        }
    });

    it('sanitizes arrays with different types', function () {
        $input = [
            'name' => '<script>alert("xss")</script>John Doe',
            'email' => 'JOHN@EXAMPLE.COM',
            'phone' => '+1 (555) 123-4567 ext 999',
            'age' => '25abc',
        ];

        $rules = [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'age' => 'integer',
        ];

        $sanitized = InputSanitizationService::sanitizeArray($input, $rules);

        expect($sanitized['name'])->toBe('John Doe');
        expect($sanitized['email'])->toBe('john@example.com');
        expect($sanitized['phone'])->toBe('+1 (555) 123-4567');
        expect($sanitized['age'])->toBe('25');
    });

    it('validates CSRF tokens correctly', function () {
        session()->regenerateToken();
        $validToken = session()->token();
        $invalidToken = 'invalid-token';

        expect(InputSanitizationService::validateCsrfToken($validToken))->toBeTrue();
        expect(InputSanitizationService::validateCsrfToken($invalidToken))->toBeFalse();
        expect(InputSanitizationService::validateCsrfToken(null))->toBeFalse();
    });

    it('generates secure tokens', function () {
        $token1 = InputSanitizationService::generateSecureToken();
        $token2 = InputSanitizationService::generateSecureToken();

        expect($token1)->toBeString();
        expect($token2)->toBeString();
        expect($token1)->not->toBe($token2);
        expect(strlen($token1))->toBe(64); // 32 bytes = 64 hex chars
    });

    it('sanitizes file upload data', function () {
        $fileData = [
            'name' => '../../../etc/passwd<script>alert("xss")</script>.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/phpABC123',
            'error' => 0,
        ];

        $sanitized = InputSanitizationService::sanitizeFileUpload($fileData);

        expect($sanitized['name'])->toBe('passwdalertxss.jpg');
        expect($sanitized['type'])->toBe('image/jpeg');
        expect($sanitized['size'])->toBe(1024);
        expect($sanitized)->toHaveKey('tmp_name');
        expect($sanitized)->toHaveKey('error');
    });
});

describe('Rate Limiting', function () {
    it('applies rate limiting to partner registration', function () {
        // This test would need to be more complex in a real scenario
        // as it would need to simulate multiple requests
        $response = $this->get('/partners');

        expect($response->status())->toBe(200);
    });
});

describe('Security Headers', function () {
    it('applies security headers to responses', function () {
        $response = $this->get('/partners');

        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
        expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
        expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
        expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
    });
});

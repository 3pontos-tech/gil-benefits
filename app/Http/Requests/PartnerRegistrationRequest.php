<?php

namespace App\Http\Requests;

use App\Services\SecurityLoggingService;
use App\Services\ValidationRulesService;
use App\Utils\CpfValidator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerRegistrationRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize and normalize input data
        $this->merge([
            'name' => $this->sanitizeName($this->input('name')),
            'email' => $this->sanitizeEmail($this->input('email')),
            'cpf' => $this->sanitizeCpf($this->input('cpf')),
            'rg' => $this->sanitizeRg($this->input('rg')),
            'partner_code' => $this->sanitizePartnerCode($this->input('partner_code')),
        ]);

        // Log validation attempt for security monitoring
        SecurityLoggingService::logValidationEvent('partner_registration_validation', [
            'email' => $this->input('email'),
            'partner_code' => $this->input('partner_code'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ValidationRulesService::name(),
            'email' => ValidationRulesService::email(),
            'cpf' => ValidationRulesService::cpf(),
            'rg' => ValidationRulesService::rg(),
            'password' => ValidationRulesService::password(),
            'password_confirmation' => [
                'required',
                'string',
            ],
            'partner_code' => ValidationRulesService::partnerCode(),
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return array_merge(ValidationRulesService::messages(), [
            'email.unique' => 'Este e-mail já está cadastrado no sistema. Tente fazer login ou use outro e-mail.',
            'name.regex' => 'O nome deve conter apenas letras, espaços, hífens e apostrofes.',
            'password.confirmed' => 'As senhas não coincidem. Digite a mesma senha nos dois campos.',
            'password_confirmation.required' => 'A confirmação de senha é obrigatória.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return ValidationRulesService::attributes();
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
     * Sanitize CPF input
     */
    protected function sanitizeCpf(?string $cpf): ?string
    {
        if (! $cpf) {
            return null;
        }

        // Use the existing CPF validator utility to clean the input
        return CpfValidator::clean($cpf);
    }

    /**
     * Sanitize RG input
     */
    protected function sanitizeRg(?string $rg): ?string
    {
        if (! $rg) {
            return null;
        }

        // Remove HTML tags and trim
        $rg = trim(strip_tags($rg));

        // Keep only numbers, dots, and hyphens
        $rg = preg_replace('/[^0-9\.\-]/', '', $rg);

        return $rg;
    }

    /**
     * Sanitize partner code input
     */
    protected function sanitizePartnerCode(?string $partnerCode): ?string
    {
        if (! $partnerCode) {
            return null;
        }

        // Remove HTML tags and trim
        $partnerCode = trim(strip_tags($partnerCode));

        // Keep only alphanumeric characters
        $partnerCode = preg_replace('/[^a-zA-Z0-9]/', '', $partnerCode);

        return strtoupper($partnerCode);
    }

    /**
     * Log validation attempt for security monitoring
     */
    protected function logValidationAttempt(): void
    {
        $request = request();

        $logData = [
            'action' => 'partner_registration_validation',
            'email' => $this->input('email'),
            'partner_code' => $this->input('partner_code'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => Str::uuid()->toString(),
        ];

        // Detect potential security threats
        $securityFlags = $this->detectSecurityThreats($request);
        if (! empty($securityFlags)) {
            $logData['security_flags'] = $securityFlags;
            Log::channel('security')->warning('Suspicious validation attempt detected', $logData);
        } else {
            Log::info('Partner registration validation attempt', $logData);
        }
    }

    /**
     * Detect potential security threats in validation attempts
     */
    protected function detectSecurityThreats($request): array
    {
        $flags = [];

        // Check for suspicious user agents
        $userAgent = $request->userAgent();
        if (empty($userAgent) || preg_match('/bot|crawler|spider|scraper|curl|wget/i', $userAgent)) {
            $flags[] = 'suspicious_user_agent';
        }

        // Check for suspicious email patterns
        $email = $this->input('email', '');
        if (preg_match('/[+].*[+]|\.{2,}|[0-9]{10,}|temp|disposable|fake/i', $email)) {
            $flags[] = 'suspicious_email_pattern';
        }

        // Check for suspicious name patterns
        $name = $this->input('name', '');
        if (strlen($name) < 3 || ctype_upper($name) || ctype_digit(str_replace(' ', '', $name))) {
            $flags[] = 'suspicious_name_pattern';
        }

        // Check for missing referer (direct access)
        if (empty($request->header('referer'))) {
            $flags[] = 'missing_referer';
        }

        // Check for suspicious request patterns
        if ($request->hasHeader('X-Forwarded-For') && count(explode(',', $request->header('X-Forwarded-For'))) > 3) {
            $flags[] = 'multiple_proxies';
        }

        return $flags;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log failed validation for security monitoring
        SecurityLoggingService::logValidationEvent('validation_failed', [
            'form_type' => 'partner_registration',
            'errors' => $validator->errors()->toArray(),
            'input_fields' => array_keys($this->except(['password', 'password_confirmation'])),
        ]);

        parent::failedValidation($validator);
    }
}

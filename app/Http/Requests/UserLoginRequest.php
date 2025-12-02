<?php

namespace App\Http\Requests;

use App\Services\ValidationRulesService;

class UserLoginRequest extends BaseFormRequest
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
        parent::prepareForValidation();

        // Additional sanitization for login
        $this->merge([
            'email' => $this->sanitizeEmail($this->input('email')),
        ]);

        // Log login attempt for security monitoring
        $this->logSecurityEvent('login_attempt', [
            'email' => $this->input('email'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ValidationRulesService::email(),
            'password' => [
                'required',
                'string',
                'min:1', // Don't reveal password requirements on login
            ],
            'remember' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return array_merge(ValidationRulesService::messages(), [
            'password.min' => 'A senha é obrigatória.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return ValidationRulesService::attributes();
    }
}

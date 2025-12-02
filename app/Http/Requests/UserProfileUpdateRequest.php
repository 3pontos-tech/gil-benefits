<?php

namespace App\Http\Requests;

use App\Services\ValidationRulesService;

class UserProfileUpdateRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Additional sanitization for profile update
        $this->merge([
            'phone' => $this->sanitizePhone($this->input('phone')),
        ]);

        // Log profile update attempt
        $this->logSecurityEvent('profile_update_attempt', [
            'user_id' => auth()->id(),
            'fields_updated' => array_keys($this->all()),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => ValidationRulesService::name(),
            'email' => ValidationRulesService::email(true, $userId),
            'phone' => ValidationRulesService::phone(false),
            'cpf' => ValidationRulesService::cpf(false, $userId),
            'rg' => ValidationRulesService::rg(false),
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return array_merge(ValidationRulesService::messages(), [
            'name.regex' => 'O nome deve conter apenas letras, espaços, hífens e apostrofes.',
            'email.unique' => 'Este e-mail já está em uso por outro usuário.',
            'phone.regex' => 'O formato do telefone não é válido.',
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

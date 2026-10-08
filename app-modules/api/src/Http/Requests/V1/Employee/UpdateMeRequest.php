<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use TresPontosTech\User\Support\EmailAddress;

/**
 * Edição de um campo por vez, como a tela 29 do app. Trocar o e-mail pede a senha atual,
 * como o perfil do painel. O telefone segue o E.164 que o PhoneInput do painel grava.
 */
class UpdateMeRequest extends FormRequest
{
    /** @var list<string> */
    public const array EDITABLE_FIELDS = ['name', 'email', 'phone_number'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * O e-mail é gravado em minúsculas (#291): validação e gravação recebem a forma normalizada.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => EmailAddress::normalize($this->string('email')->toString())]);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'current_password' => ['required_with:email', 'string', 'current_password:sanctum'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'regex:/^\+[1-9]\d{9,14}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => __('api::validation.phone_number'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('api::attributes.name'),
            'email' => __('api::attributes.email'),
            'current_password' => __('api::attributes.current_password'),
            'phone_number' => __('api::attributes.phone_number'),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(self::EDITABLE_FIELDS)) {
                    $validator->errors()->add('name', __('api::validation.nothing_to_update'));
                }
            },
        ];
    }

    /**
     * @return array{name?: string, email?: string, phone_number?: string|null}
     */
    public function profileData(): array
    {
        /** @var array{name?: string, email?: string, phone_number?: string|null} */
        return $this->safe()->only(self::EDITABLE_FIELDS);
    }
}

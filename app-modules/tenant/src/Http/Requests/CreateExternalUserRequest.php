<?php

declare(strict_types=1);

namespace TresPontosTech\Tenant\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use TresPontosTech\User\Support\EmailAddress;

class CreateExternalUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O e-mail é gravado em minúsculas (#291): validação e gravação recebem a forma normalizada.
     * Valor que não é texto segue cru para a regra `string` recusar com 422.
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => EmailAddress::normalize($email)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'external_id' => ['string', 'required'],
            'name' => ['string', 'required', 'max:255'],
            'email' => ['string', 'required', 'unique:users,email', 'email', 'max:255'],
            'metadata' => ['array', 'nullable'],
        ];
    }
}

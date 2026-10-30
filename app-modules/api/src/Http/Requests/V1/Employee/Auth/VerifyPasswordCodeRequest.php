<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPasswordCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => __('api::attributes.email'),
            'code' => __('api::attributes.code'),
        ];
    }

    /**
     * O código chega como a pessoa colou do e-mail ("123 456", "123-456"): fica só com os dígitos.
     * Um código de outro tamanho cai no mesmo erro de código inválido, sem mensagem própria.
     */
    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge(['code' => preg_replace('/\D/', '', $code)]);
        }
    }
}

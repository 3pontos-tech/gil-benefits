<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee\Me;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TresPontosTech\Api\Enums\PushPlatform;

class UpdatePushTokenRequest extends FormRequest
{
    /**
     * O token que o app recebe da Expo. Há dois prefixos em uso, `ExponentPushToken[...]` e
     * `ExpoPushToken[...]`.
     *
     * @return array<string, list<string|ValidationRule|object>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[[^\]\s]+\]$/'],
            'platform' => ['required', Rule::enum(PushPlatform::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'token' => __('api::attributes.push_token'),
            'platform' => __('api::attributes.platform'),
        ];
    }
}

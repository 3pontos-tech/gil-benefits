<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mesmos tipos e limite do upload de foto no perfil do painel.
 */
class StoreAvatarRequest extends FormRequest
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
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'avatar' => __('api::attributes.avatar'),
        ];
    }
}

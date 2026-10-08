<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteMaterialRequest extends FormRequest
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
            'favorite' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'favorite' => __('api::attributes.favorite'),
        ];
    }
}

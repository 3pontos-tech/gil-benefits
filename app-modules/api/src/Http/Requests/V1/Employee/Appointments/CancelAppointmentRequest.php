<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee\Appointments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TresPontosTech\Appointments\Enums\CancelImpact;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `expected_impact` é o `cancel_impact` que o alerta do app mostrou e a pessoa confirmou.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'expected_impact' => ['sometimes', 'string', Rule::enum(CancelImpact::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expected_impact' => __('api::attributes.expected_impact'),
        ];
    }
}

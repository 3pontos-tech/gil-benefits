<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * A antecedência é por dia inteiro (`toDateString()`), igual ao `startOfDay()` do painel; a action
 * de domínio re-checa e também valida a disponibilidade real.
 */
class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'category_type' => ['required', Rule::enum(AppointmentCategoryEnum::class)],
            'appointment_at' => ['required', 'date', 'after_or_equal:' . now()->addDays(Appointment::BOOKING_LEAD_DAYS)->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appointment_at.after_or_equal' => __('api::validation.appointment_lead', ['days' => Appointment::BOOKING_LEAD_DAYS]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_type' => __('api::attributes.category_type'),
            'appointment_at' => __('api::attributes.appointment_at'),
            'notes' => __('api::attributes.notes'),
        ];
    }
}

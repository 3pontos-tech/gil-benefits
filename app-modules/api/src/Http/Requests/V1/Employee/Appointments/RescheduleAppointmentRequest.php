<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee\Appointments;

use Illuminate\Foundation\Http\FormRequest;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\BookableSlots;

/**
 * Mesma regra de antecedência por dia inteiro do agendamento (StoreAppointmentRequest).
 */
class RescheduleAppointmentRequest extends FormRequest
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
            'appointment_at' => [
                'required',
                'date',
                'after_or_equal:' . now()->addDays(Appointment::BOOKING_LEAD_DAYS)->toDateString(),
                'before_or_equal:' . resolve(BookableSlots::class)->lastBookableDay()->endOfDay()->toDateTimeString(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appointment_at.after_or_equal' => __('api::validation.appointment_lead', ['days' => Appointment::BOOKING_LEAD_DAYS]),
            'appointment_at.before_or_equal' => __('api::validation.appointment_horizon', ['days' => Appointment::BOOKING_HORIZON_DAYS]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'appointment_at' => __('api::attributes.appointment_at'),
        ];
    }
}

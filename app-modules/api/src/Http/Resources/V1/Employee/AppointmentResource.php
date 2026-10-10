<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * @property Appointment $resource
 */
class AppointmentResource extends JsonResource
{
    public const array EAGER_LOADS = ['consultant.media', 'feedback', 'record'];

    public function __construct(Appointment $resource)
    {
        parent::__construct($resource);
    }

    /**
     * `meeting_url` é derivado do estado, nunca do campo cru: some ao cancelar (o job do Google
     * Calendar é assíncrono) e vale enquanto o encontro confirmado ainda está em andamento.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $appointment = $this->resource;
        $duration = (int) config('google-calendar.default_event_duration', 60);
        $consultant = $appointment->consultant;
        $feedback = $appointment->feedback;
        $record = $appointment->record;

        return [
            'id' => $appointment->id,
            'category_type' => $appointment->category_type->value,
            'category_label' => $appointment->category_type->getLabel(),
            'appointment_at' => ApiDates::dateTime($appointment->appointment_at),
            'duration_minutes' => $duration,
            'status' => $appointment->status->value,
            'meeting_url' => $appointment->isActive() && $appointment->appointment_at->copy()->addMinutes($duration)->isFuture()
                ? $appointment->meeting_url
                : null,
            'consultant' => $consultant === null ? null : [
                'id' => $consultant->id,
                'name' => $consultant->name,
                'avatar_url' => $consultant->getFirstMedia('avatars')?->getTemporaryUrl(now()->addMinutes((int) config('api.media_url_ttl_minutes'))),
            ],
            'notes' => $appointment->notes,
            'feedback' => $feedback === null ? null : [
                'rating' => (int) $feedback->rating,
                'comment' => $feedback->comment,
            ],
            'record' => $record?->isPublished() ? [
                'published_at' => ApiDates::dateTime($record->published_at),
                'content' => $record->content,
            ] : null,
            'can_reschedule' => $appointment->canBeRescheduled(),
            'can_cancel' => $appointment->canBeCancelled(),
            'cancel_impact' => $appointment->cancelImpact()?->value,
            'materials' => [],
            'created_at' => ApiDates::dateTime($appointment->created_at),
        ];
    }
}

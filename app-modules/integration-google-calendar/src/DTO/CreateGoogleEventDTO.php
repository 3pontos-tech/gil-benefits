<?php

namespace TresPontosTech\IntegrationGoogleCalendar\DTO;

use TresPontosTech\Appointments\Models\Appointment;

readonly class CreateGoogleEventDTO
{
    /**
     * @param  array<int, string>  $attendees
     */
    public function __construct(
        public string $summary,
        public string $description,
        public string $startDateTime,
        public string $endDateTime,
        public string $timezone,
        public array $attendees,
        public string $appointmentId,
    ) {}

    public static function fromAppointment(Appointment $appointment): self
    {
        $appointment->loadMissing(['consultant', 'user']);

        $timezone = config('app.timezone');

        $descriptionParts = [];

        if ($appointment->category_type) {
            $descriptionParts[] = sprintf('Categoria: %s', $appointment->category_type->getLabel());
        }

        if (filled($appointment->notes)) {
            $descriptionParts[] = sprintf('Notas: %s', $appointment->notes);
        }

        $durationMinutes = (int) config('google-calendar.default_event_duration', 60);

        return new self(
            summary: sprintf('Consulta - %s', $appointment->user->name),
            description: implode("\n", $descriptionParts),
            startDateTime: $appointment->appointment_at->toIso8601String(),
            endDateTime: $appointment->appointment_at->copy()->addMinutes($durationMinutes)->toIso8601String(),
            timezone: $timezone,
            attendees: array_filter([
                $appointment->user->email,
            ]),
            appointmentId: $appointment->id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toGooglePayload(): array
    {
        return [
            'summary' => $this->summary,
            'description' => $this->description,
            'start' => [
                'dateTime' => $this->startDateTime,
                'timeZone' => $this->timezone,
            ],
            'end' => [
                'dateTime' => $this->endDateTime,
                'timeZone' => $this->timezone,
            ],
            'attendees' => array_map(
                fn (string $email): array => ['email' => $email],
                $this->attendees
            ),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => $this->appointmentId,
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];
    }
}

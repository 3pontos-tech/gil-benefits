<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

readonly class UpdateCalendarEventTimeAction
{
    public function __construct(
        private GoogleCalendarClient $client,
    ) {}

    /**
     * Reschedule the appointment's existing Google Calendar event, keeping the same
     * event and Meet link — only the start/end times change. Attendees are notified.
     */
    public function handle(Appointment $appointment): void
    {
        if (blank($appointment->google_event_id)) {
            return;
        }

        $appointment->loadMissing('consultant');

        $consultant = $appointment->consultant;

        if (blank($consultant) || blank($consultant->email)) {
            return;
        }

        $accessToken = $this->client->getAccessToken($consultant->email);

        $timezone = config('app.timezone');
        $durationMinutes = (int) config('google-calendar.default_event_duration', 60);

        $this->client->patchEvent(
            $accessToken,
            $consultant->email,
            $appointment->google_event_id,
            [
                'start' => [
                    'dateTime' => $appointment->appointment_at->toIso8601String(),
                    'timeZone' => $timezone,
                ],
                'end' => [
                    'dateTime' => $appointment->appointment_at->copy()->addMinutes($durationMinutes)->toIso8601String(),
                    'timeZone' => $timezone,
                ],
            ],
        );
    }
}

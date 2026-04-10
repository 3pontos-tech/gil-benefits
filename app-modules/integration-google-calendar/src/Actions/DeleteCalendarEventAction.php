<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

readonly class DeleteCalendarEventAction
{
    public function __construct(
        private GoogleCalendarClient $client,
    ) {}

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

        $this->client->deleteEvent($accessToken, $consultant->email, $appointment->google_event_id);

        $appointment->update([
            'google_event_id' => null,
            'meeting_url' => null,
        ]);
    }
}

<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

readonly class DeleteCalendarEventAction
{
    public function __construct(
        private GoogleCalendarClient $client,
    ) {}

    /**
     * @param  string|null  $calendarEmail  Calendar to delete the event from. Defaults to the appointment's
     *                                      current consultant. Pass an explicit email when the consultant has
     *                                      already been reassigned and the event still lives on the previous one.
     */
    public function handle(Appointment $appointment, ?string $calendarEmail = null): void
    {
        if (blank($appointment->google_event_id)) {
            return;
        }

        if (blank($calendarEmail)) {
            $appointment->loadMissing('consultant');
            $calendarEmail = $appointment->consultant?->email;
        }

        if (blank($calendarEmail)) {
            return;
        }

        $accessToken = $this->client->getAccessToken($calendarEmail);

        $this->client->deleteEvent($accessToken, $calendarEmail, $appointment->google_event_id);

        $appointment->update([
            'google_event_id' => null,
            'meeting_url' => null,
        ]);
    }
}

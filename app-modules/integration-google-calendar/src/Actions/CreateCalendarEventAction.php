<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\DTO\CreateGoogleEventDTO;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

readonly class CreateCalendarEventAction
{
    public function __construct(
        private GoogleCalendarClient $client,
    ) {}

    public function handle(Appointment $appointment): void
    {
        $appointment->loadMissing(['consultant', 'user']);

        $consultant = $appointment->consultant;

        $accessToken = $this->client->getAccessToken($consultant->email);

        $dto = CreateGoogleEventDTO::fromAppointment($appointment);
        $response = $this->client->createEvent($accessToken, $consultant->email, $dto->toGooglePayload());

        $updateData = [
            'google_event_id' => $response->eventId,
        ];

        if (filled($response->meetLink)) {
            $updateData['meeting_url'] = $response->meetLink;
        }

        $appointment->update($updateData);
    }
}

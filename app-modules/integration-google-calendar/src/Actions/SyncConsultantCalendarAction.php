<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

readonly class SyncConsultantCalendarAction
{
    public function __construct(
        private GoogleCalendarClient $client,
        private UpsertBlockedScheduleAction $upsertAction,
        private RemoveCancelledGoogleEventAction $removeCancelledAction,
        private RemoveStaleBlockedSchedulesAction $removeStaleAction,
    ) {}

    public function handle(Consultant $consultant): void
    {
        $accessToken = $this->client->getAccessToken($consultant->email);

        $now = Date::now();
        $timeMin = $now->copy()->startOfDay()->toRfc3339String();
        $timeMax = $now->copy()->addDays(config('google-calendar.sync_days_ahead'))->endOfDay()->toRfc3339String();

        $syncedEventIds = [];
        $pageToken = null;

        do {
            $response = $this->client->listEvents(
                $accessToken,
                'primary',
                $timeMin,
                $timeMax,
                $pageToken,
            );

            foreach ($response->events as $event) {
                if ($event->isCancelled) {
                    $this->removeCancelledAction->handle($consultant, $event->eventId);
                } else {
                    $this->upsertAction->handle($consultant, $event);
                    $syncedEventIds[] = $event->eventId;
                }
            }

            $pageToken = $response->nextPageToken;
        } while ($pageToken);

        $this->removeStaleAction->handle($consultant, $syncedEventIds);

        $consultant->update(['google_calendar_synced_at' => $now]);
    }
}

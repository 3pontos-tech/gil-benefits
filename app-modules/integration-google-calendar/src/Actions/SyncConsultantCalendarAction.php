<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;

readonly class SyncConsultantCalendarAction
{
    private const FULL_SYNC_INTERVAL_HOURS = 24;

    public function __construct(
        private GoogleCalendarClient $client,
        private UpsertBlockedScheduleAction $upsertAction,
        private RemoveCancelledGoogleEventAction $removeCancelledAction,
        private RemoveStaleBlockedSchedulesAction $removeStaleAction,
    ) {}

    public function handle(Consultant $consultant): void
    {
        $accessToken = $this->client->getAccessToken($consultant->email);

        if ($this->shouldFullSync($consultant)) {
            $this->fullSync($consultant, $accessToken);

            return;
        }

        try {
            $this->incrementalSync($consultant, $accessToken);
        } catch (GoogleCalendarApiException $googleCalendarApiException) {
            throw_if($googleCalendarApiException->getCode() !== 410, $googleCalendarApiException);

            Log::warning('Google Calendar sync token expired (410), falling back to full sync', [
                'consultant_id' => $consultant->id,
            ]);

            $consultant->update(['google_calendar_sync_token' => null]);
            $this->fullSync($consultant->refresh(), $accessToken);
        }
    }

    private function shouldFullSync(Consultant $consultant): bool
    {
        if (blank($consultant->google_calendar_sync_token)) {
            return true;
        }

        if (blank($consultant->google_calendar_synced_at)) {
            return true;
        }

        return $consultant->google_calendar_synced_at
            ->lt(Date::now()->subHours(self::FULL_SYNC_INTERVAL_HOURS));
    }

    private function fullSync(Consultant $consultant, string $accessToken): void
    {
        $now = Date::now();
        $timeMin = $now->copy()->startOfDay()->toRfc3339String();
        $timeMax = $now->copy()->addDays((int) config('google-calendar.sync_days_ahead'))->endOfDay()->toRfc3339String();

        $syncedEventIds = [];
        $pageToken = null;
        $nextSyncToken = null;

        do {
            $response = $this->client->listEvents(
                accessToken: $accessToken,
                calendarId: 'primary',
                timeMin: $timeMin,
                timeMax: $timeMax,
                pageToken: $pageToken,
            );

            foreach ($response->events as $event) {
                if ($event->isCancelled) {
                    $this->removeCancelledAction->handle($consultant, $event->eventId);

                    continue;
                }

                $this->upsertAction->handle($consultant, $event);
                $syncedEventIds[] = $event->eventId;
            }

            $pageToken = $response->nextPageToken;

            if ($response->nextSyncToken !== null) {
                $nextSyncToken = $response->nextSyncToken;
            }
        } while ($pageToken);

        $this->removeStaleAction->handle($consultant, $syncedEventIds);

        $consultant->update([
            'google_calendar_synced_at' => $now,
            'google_calendar_sync_token' => $nextSyncToken,
        ]);

        Log::info('Google Calendar full sync completed', [
            'consultant_id' => $consultant->id,
            'sync_type' => 'full',
            'events_processed' => count($syncedEventIds),
            'has_next_sync_token' => $nextSyncToken !== null,
        ]);
    }

    private function incrementalSync(Consultant $consultant, string $accessToken): void
    {
        $now = Date::now();
        $syncToken = $consultant->google_calendar_sync_token;
        $pageToken = null;
        $nextSyncToken = null;
        $eventsProcessed = 0;

        do {
            $response = $this->client->listEvents(
                accessToken: $accessToken,
                calendarId: 'primary',
                pageToken: $pageToken,
                syncToken: $pageToken === null ? $syncToken : null,
            );

            foreach ($response->events as $event) {
                if ($event->isCancelled) {
                    $this->removeCancelledAction->handle($consultant, $event->eventId);
                } else {
                    $this->upsertAction->handle($consultant, $event);
                }

                ++$eventsProcessed;
            }

            $pageToken = $response->nextPageToken;

            if ($response->nextSyncToken !== null) {
                $nextSyncToken = $response->nextSyncToken;
            }
        } while ($pageToken);

        $consultant->update([
            'google_calendar_synced_at' => $now,
            'google_calendar_sync_token' => $nextSyncToken,
        ]);

        Log::info('Google Calendar incremental sync completed', [
            'consultant_id' => $consultant->id,
            'sync_type' => 'incremental',
            'events_processed' => $eventsProcessed,
            'has_next_sync_token' => $nextSyncToken !== null,
        ]);
    }
}

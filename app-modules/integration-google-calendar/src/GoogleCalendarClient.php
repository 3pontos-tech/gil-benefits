<?php

namespace TresPontosTech\IntegrationGoogleCalendar;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;

class GoogleCalendarClient
{
    private array $credentials;

    public function __construct()
    {
        $this->validateCredentials();
    }

    public function getAccessToken(string $email): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->buildJwt($email),
        ]);

        $error = $response->json('error');

        if (in_array($error, ['invalid_grant', 'unauthorized_client'])) {
            throw new GoogleCalendarApiException(
                sprintf('Consultant %s is not in the Google Workspace domain (%s)', $email, $error),
                retryable: false,
            );
        }

        if ($response->failed()) {
            throw new GoogleCalendarApiException(
                sprintf('Failed to get access token for %s: %s', $email, $response->body()),
            );
        }

        return $response->json('access_token');
    }

    public function listEvents(
        string $accessToken,
        string $calendarId,
        string $timeMin,
        string $timeMax,
        ?string $pageToken = null,
    ): CalendarEventsResponse {
        $params = [
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'maxResults' => 250,
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
        ];

        if (filled($pageToken)) {
            $params['pageToken'] = $pageToken;
        }

        $url = sprintf(
            'https://www.googleapis.com/calendar/v3/calendars/%s/events',
            urlencode($calendarId)
        );

        $response = Http::withToken($accessToken)->get($url, $params);

        if ($response->failed()) {
            throw new GoogleCalendarApiException(
                sprintf('Failed to list events for %s: %s', $calendarId, $response->body()),
                $response->status(),
            );
        }

        return CalendarEventsResponse::make($response->json());
    }

    private function buildJwt(string $email): string
    {
        $now = Date::now()->getTimestamp();

        $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claimSet = $this->base64url(json_encode([
            'iss' => $this->credentials['client_email'],
            'sub' => $email,
            'scope' => 'https://www.googleapis.com/auth/calendar.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = sprintf('%s.%s', $header, $claimSet);
        openssl_sign($signatureInput, $signature, $this->credentials['private_key'], 'SHA256');

        return sprintf('%s.%s', $signatureInput, $this->base64url($signature));
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function validateCredentials(): void
    {
        $credentialsPath = storage_path(config('google-calendar.service_account_credentials'));

        if (! file_exists($credentialsPath)) {
            throw new GoogleCalendarApiException(
                sprintf('Google service account credentials file not found at %s', $credentialsPath),
                retryable: false,
            );
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);

        throw_unless(is_array($credentials), GoogleCalendarApiException::class, 'Google service account credentials file contains invalid JSON', retryable: false);

        throw_if(blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null), GoogleCalendarApiException::class, 'Google service account credentials file is missing required fields (client_email, private_key)', retryable: false);

        $this->credentials = $credentials;
    }
}

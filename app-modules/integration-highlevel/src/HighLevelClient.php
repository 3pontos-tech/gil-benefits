<?php

namespace TresPontosTech\IntegrationHighlevel;

use Illuminate\Support\Facades\Http;
use TresPontosTech\IntegrationHighlevel\Requests\CreateAppointmentDTO;
use TresPontosTech\IntegrationHighlevel\Requests\FetchCalendarSlotsDTO;
use TresPontosTech\IntegrationHighlevel\Requests\UpsertContactDTO;
use TresPontosTech\IntegrationHighlevel\Requests\UpsertOpportunityDTO;
use TresPontosTech\IntegrationHighlevel\Responses\ContactResponse;
use TresPontosTech\IntegrationHighlevel\Responses\ScheduledAppointmentResponse;
use TresPontosTech\IntegrationHighlevel\Responses\UpsertOpportunityResponse;

class HighLevelClient
{
    public function searchContacts(string $query = '')
    {
        return Http::withToken(config('highlevel.secret'))
            ->withLocation()
            ->withDefaultVersion()
            ->withQueryParameters([
                'query' => $query,
            ])
            ->get('https://services.leadconnectorhq.com/contacts/search');
    }

    public function createContact(UpsertContactDTO $dto): ContactResponse
    {
        $response = Http::withToken(config('highlevel.secret'))
            ->withDefaultVersion()
            ->post('https://services.leadconnectorhq.com/contacts/upsert', $dto->jsonSerialize());

        return ContactResponse::make($response->json());
    }

    public function getLocationPipelines(): array
    {
        return Http::withToken(config('highlevel.secret'))
            ->withLocation()
            ->withDefaultVersion()
            ->get('https://services.leadconnectorhq.com/opportunities/pipelines')
            ->json();
    }

    public function getCompanyEmployees()
    {
        return Http::withToken(config('highlevel.secret'))
            ->withLocation()
            ->withDefaultVersion()
            ->withDefaultCompany()
            ->withQueryParameters(['limit' => 50])
            ->get('https://services.leadconnectorhq.com/users/search')
            ->json();
    }

    public function upsertOpportunity(UpsertOpportunityDTO $dto): UpsertOpportunityResponse
    {
        $response = Http::withToken(config('highlevel.secret'))
            ->withDefaultVersion()
            ->asJson()
            ->post('https://services.leadconnectorhq.com/opportunities/upsert', $dto->jsonSerialize());

        if (! $response->created()) {
            throw new \Exception('Error: ' . $response->status() . ' - ' . $response->body());
        }

        return UpsertOpportunityResponse::make($response->json());
    }

    public function getCalendarFreeSlots(FetchCalendarSlotsDTO $dto)
    {
        $url = sprintf('https://services.leadconnectorhq.com/calendars/%s/free-slots', $dto->calendarId);

        return Http::withToken(config('highlevel.secret'))
            ->withDefaultVersion()
            ->asJson()
            ->withQueryParameters($dto->jsonSerialize())
            ->get($url)
            ->json();
    }

    public function scheduleAppointment(CreateAppointmentDTO $dto): ScheduledAppointmentResponse
    {
        $url = 'https://services.leadconnectorhq.com/calendars/events/appointments';

        $response = Http::withToken(config('highlevel.secret'))
            ->withDefaultVersion()
            ->asJson()
            ->withQueryParameters($dto->jsonSerialize())
            ->post($url, $dto->jsonSerialize())
            ->json();

        return ScheduledAppointmentResponse::make($response);
    }
}

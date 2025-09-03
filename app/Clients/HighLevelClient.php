<?php

namespace App\Clients;

use App\Clients\Requests\UpsertContactDTO;
use App\Clients\Requests\UpsertOpportunityDTO;
use App\Clients\Responses\ContactResponse;
use Illuminate\Support\Facades\Http;

class HighLevelClient
{
    public function searchContacts(string $query = '')
    {
        return Http::withToken(config('services.highlevel.secret'))
            ->withLocation()
            ->withDefaultVersion()
            ->withQueryParameters([
                'query' => $query,
            ])
            ->get('https://services.leadconnectorhq.com/contacts/search');
    }

    public function createContact(UpsertContactDTO $dto): ContactResponse
    {
        $response = Http::withToken(config('services.highlevel.secret'))
            ->withDefaultVersion()
            ->post('https://services.leadconnectorhq.com/contacts/upsert', $dto->jsonSerialize());

        return ContactResponse::make($response->json());
    }

    public function getLocationPipelines(): array
    {
        return Http::withToken(config('services.highlevel.secret'))
            ->withLocation()
            ->withDefaultVersion()
            ->get('https://services.leadconnectorhq.com/opportunities/pipelines')
            ->json();
    }

    public function getCompanyEmployees()
    {
        return Http::withToken(config('services.highlevel.secret'))
            ->withLocation()
            ->withDefaultVersion()
            ->withDefaultCompany()
            ->withQueryParameters(['limit' => 50])
            ->get('https://services.leadconnectorhq.com/users/search')
            ->json();
    }

    public function upsertOpportunity(UpsertOpportunityDTO $dto)
    {
        return Http::withToken(config('services.highlevel.secret'))
            ->withDefaultVersion()
            ->asJson()
            ->post('https://services.leadconnectorhq.com/opportunities/upsert', $dto->jsonSerialize())
            ->json();
    }
}
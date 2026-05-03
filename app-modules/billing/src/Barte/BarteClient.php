<?php

namespace TresPontosTech\Billing\Barte;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use TresPontosTech\Billing\Barte\DTOs\CreateBuyerDto;
use TresPontosTech\Billing\Barte\DTOs\CreatePaymentLinkDto;

final class BarteClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(config('services.barte.base_url'))
            ->withHeader('X-Token-Api', config('services.barte.api_key'))
            ->acceptJson()
            ->timeout(30);
    }

    public function getPlans(): array
    {
        $response = $this->http->get('/v2/plans');

        if ($response->failed()) {
            throw new BarteApiException(
                message: $response->json('message') ?? 'Erro na API da Barte',
                code: $response->status(),
            );
        }

        return $response->json();
    }

    public function createBuyer(CreateBuyerDto $dto): array
    {
        $response = $this->http->post('/v2/buyers', $dto->toArray());

        if ($response->failed()) {
            throw new BarteApiException(
                message: $response->json('message') ?? 'Erro na API da Barte',
                code: $response->status(),
            );
        }

        return $response->json();
    }

    public function createPaymentLink(CreatePaymentLinkDto $dto): array
    {
        $response = $this->http->post('/v2/payment-links', $dto->toArray());

        if ($response->failed()) {
            throw new BarteApiException(
                message: $response->json('message') ?? 'Erro na API da Barte',
                code: $response->status(),
            );
        }

        return $response->json();
    }

    public function deleteSubscription(string $subscriptionId): void
    {
        $response = $this->http->delete('/v2/subscriptions/' . $subscriptionId);

        if ($response->failed()) {
            throw new BarteApiException(
                message: $response->json('message') ?? 'Erro na API da Barte',
                code: $response->status(),
            );
        }
    }
}

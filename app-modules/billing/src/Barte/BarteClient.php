<?php

namespace TresPontosTech\Billing\Barte;

use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class BarteClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(config('services.barte.base_url'))
            ->withHeader('X-Token-Api', config('services.barte.api_key'))
            ->acceptJson()
            ->timeout(30)
            ->retry(3, 100);
    }

    public function post(string $path, array $data = []): array
    {
        return $this->send(
            fn () => $this->http->post($path, $data)
        );
    }

    public function get(string $path, array $query = []): array
    {
        return $this->send(
            fn () => $this->http->get($path, $query)
        );
    }

    public function patch(string $path, array $data = []): array
    {
        return $this->send(
            fn () => $this->http->patch($path, $data)
        );
    }

    public function delete(string $path): array
    {
        return $this->send(
            fn () => $this->http->delete($path)
        );
    }

    private function send(Closure $request): array
    {
        $response = $request();

        if ($response->failed()) {
            throw new BarteApiException(
                message: $response->json('message') ?? 'Erro na API da Barte',
                code: $response->status(),
            );
        }

        return $response->json();
    }
}

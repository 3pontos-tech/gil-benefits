<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TresPontosTech\IntegrationVirtu\DTO\CreatePaymentLinkDTO;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\Responses\PaymentLinkResponse;

/**
 * HTTP client for the Pagaa/Virtu public API.
 *
 * The API is read-only except for payment links, which is all we need: the
 * remaining BillingContract questions (is this billable subscribed? what is the
 * portal URL?) are answered from our own tables, exactly as the Barte adapter
 * already does.
 */
class VirtuClient
{
    private ?PendingRequest $request = null;

    /**
     * Built on first use rather than in the constructor.
     *
     * The adapter is instantiated whenever BillingManager resolves the driver,
     * including from access middleware that only ever reads local subscription
     * state. Validating credentials at construction would turn a missing env var
     * into a 500 on every authenticated request; deferring it means an
     * unconfigured install only fails when it genuinely tries to reach Virtu.
     */
    private function request(): PendingRequest
    {
        if ($this->request instanceof PendingRequest) {
            return $this->request;
        }

        throw_if(
            blank(config('virtu.api_key')),
            VirtuApiException::class,
            'Virtu API key is not configured.',
            0,
            false,
        );

        throw_if(
            blank(config('virtu.company_id')),
            VirtuApiException::class,
            'Virtu company id is not configured.',
            0,
            false,
        );

        return $this->request = Http::baseUrl(rtrim((string) config('virtu.base_url'), '/'))
            ->withHeaders([
                'x-api-key' => (string) config('virtu.api_key'),
                'x-company-id' => (string) config('virtu.company_id'),
            ])
            ->acceptJson()
            ->timeout(30);
    }

    public function createPaymentLink(CreatePaymentLinkDTO $data): PaymentLinkResponse
    {
        return PaymentLinkResponse::make(
            $this->execute(fn (PendingRequest $request): Response => $request->post('/payment-links', $data->toArray()))
        );
    }

    public function getPaymentLink(string $publicId): PaymentLinkResponse
    {
        return PaymentLinkResponse::make(
            $this->execute(fn (PendingRequest $request): Response => $request->get('/payment-links/' . $publicId))
        );
    }

    /**
     * Routes the current API key may reach, with the tab permission each one
     * requires. Useful as a deploy-time check that the key was issued with the
     * payment-link write permission.
     *
     * @return list<array<string, mixed>>
     */
    public function getRoutes(): array
    {
        $data = $this->execute(fn (PendingRequest $request): Response => $request->get('/routes'));

        /** @var list<array<string, mixed>> $routes */
        $routes = $data['routes'] ?? [];

        return $routes;
    }

    /**
     * Sends the request and unwraps the `{success, data, timestamp}` envelope
     * every endpoint returns.
     *
     * Retryability is decided by status class, not by `failed()`: a 5xx is worth
     * another attempt, a 4xx is a rejected payload and never will be. Returning
     * `success: false` on a 200 is likewise terminal.
     *
     * @param  callable(PendingRequest): Response  $send
     * @return array<string, mixed>
     */
    private function execute(callable $send): array
    {
        $response = $send((clone $this->request())->asJson());

        // TEMPORÁRIO — toda ida e volta com a Virtu, crua. Remover junto com os
        // comandos virtu:probe.
        Log::channel('virtu')->debug('Chamada à API da Virtu.', [
            'url' => (string) $response->effectiveUri(),
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->serverError()) {
            throw new VirtuApiException(
                sprintf('Virtu API request failed: %s', $response->body()),
                $response->status(),
            );
        }

        if ($response->clientError()) {
            throw new VirtuApiException(
                sprintf('Virtu API rejected the request: %s', $response->body()),
                $response->status(),
                retryable: false,
            );
        }

        if ($response->json('success') !== true) {
            throw new VirtuApiException(
                sprintf('Virtu API returned an unsuccessful envelope: %s', $response->body()),
                $response->status(),
                retryable: false,
            );
        }

        /** @var array<string, mixed> $data */
        $data = $response->json('data') ?? [];

        return $data;
    }
}

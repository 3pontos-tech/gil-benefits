<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Testing;

use TresPontosTech\IntegrationVirtu\DTO\CreatePaymentLinkDTO;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\Responses\PaymentLinkResponse;
use TresPontosTech\IntegrationVirtu\VirtuClient;

/**
 * In-memory VirtuClient for tests: records every call and returns canned
 * responses without touching the network. Swap it in to guarantee a test can
 * never reach the real API:
 *
 *   $this->app->instance(VirtuClient::class, $fake = new FakeVirtuClient);
 */
final class FakeVirtuClient extends VirtuClient
{
    /** @var list<array<string, mixed>> */
    public array $createdLinks = [];

    public bool $shouldFail = false;

    private int $sequence = 0;

    public function createPaymentLink(CreatePaymentLinkDTO $data): PaymentLinkResponse
    {
        throw_if($this->shouldFail, VirtuApiException::class, 'Fake Virtu failure.', 0, false);

        $suffix = (string) (++$this->sequence);

        $this->createdLinks[] = $data->toArray();

        return PaymentLinkResponse::make([
            'id' => 'pl_fake' . $suffix,
            'url' => 'https://checkout.pagaa.com.br/checkout/checkout_fake' . $suffix,
            'status' => 'PENDING',
            'amountCents' => $data->amountCents,
            'kind' => $data->kind,
        ]);
    }

    public function getPaymentLink(string $publicId): PaymentLinkResponse
    {
        throw_if($this->shouldFail, VirtuApiException::class, 'Fake Virtu failure.', 0, false);

        return PaymentLinkResponse::make([
            'id' => $publicId,
            'url' => 'https://checkout.pagaa.com.br/checkout/checkout_' . $publicId,
            'status' => 'PAID',
            'amountCents' => 4990,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return [];
    }
}

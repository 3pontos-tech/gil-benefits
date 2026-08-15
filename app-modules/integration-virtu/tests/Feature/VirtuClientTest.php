<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TresPontosTech\IntegrationVirtu\DTO\CreatePaymentLinkDTO;
use TresPontosTech\IntegrationVirtu\Enums\VirtuIntervalEnum;
use TresPontosTech\IntegrationVirtu\Exceptions\VirtuApiException;
use TresPontosTech\IntegrationVirtu\VirtuClient;

beforeEach(function (): void {
    config([
        'virtu.base_url' => 'https://sandbox-virtu-api.pagaa.com.br/api/v1/public',
        'virtu.api_key' => 'sk_test_key',
        'virtu.company_id' => '5',
        'virtu.subscription_methods' => ['CREDIT_CARD'],
        'virtu.order_methods' => ['PIX', 'CREDIT_CARD'],
        'virtu.max_installments' => 12,
        'virtu.interest_mode' => 'AUTO_TRANSFER',
    ]);

    Http::preventStrayRequests();
});

function virtuLinkResponse(array $overrides = []): array
{
    return ['success' => true, 'data' => array_merge([
        'id' => 'pl_15b42865ecc98ef451aee63a5a6cd79a',
        'companyId' => 5,
        'title' => 'Assinatura Mensal',
        'amountCents' => 4990,
        'status' => 'PENDING',
        'kind' => 'SUBSCRIPTION',
        'interval' => 'MONTHLY',
        'url' => 'https://checkout.pagaa.com.br/checkout/checkout_38a07904fb3cf077076a6d52fc0a7386',
    ], $overrides), 'timestamp' => '2026-07-15T12:07:29.400Z'];
}

it('creates a subscription link and unwraps the envelope', function (): void {
    Http::fake(['sandbox-virtu-api.pagaa.com.br/*' => Http::response(virtuLinkResponse())]);

    $link = (new VirtuClient)->createPaymentLink(
        CreatePaymentLinkDTO::subscription('Gold', 25000, VirtuIntervalEnum::Monthly)
    );

    expect($link->id)->toBe('pl_15b42865ecc98ef451aee63a5a6cd79a')
        ->and($link->amountCents)->toBe(4990)
        ->and($link->status)->toBe('PENDING');

    Http::assertSent(function (array $request): bool {
        expect($request->header('x-api-key'))->toBe(['sk_test_key'])
            ->and($request->header('x-company-id'))->toBe(['5']);

        return $request['kind'] === 'SUBSCRIPTION'
            && $request['amountCents'] === 25000
            && $request['interval'] === 'MONTHLY'
            && $request['acceptedMethods'] === ['CREDIT_CARD'];
    });
});

it('extracts the checkout id from the hosted url', function (): void {
    Http::fake(['sandbox-virtu-api.pagaa.com.br/*' => Http::response(virtuLinkResponse())]);

    $link = (new VirtuClient)->createPaymentLink(CreatePaymentLinkDTO::subscription('Gold', 25000));

    // The checkout id has no field of its own — it only exists as the last URL segment.
    expect($link->checkoutId)->toBe('checkout_38a07904fb3cf077076a6d52fc0a7386');
});

it('omits kind on one-off links, which is what makes them one-off', function (): void {
    Http::fake(['sandbox-virtu-api.pagaa.com.br/*' => Http::response(virtuLinkResponse(['kind' => null]))]);

    (new VirtuClient)->createPaymentLink(CreatePaymentLinkDTO::order('Compra de 2 crédito(s)', 30000));

    Http::assertSent(fn ($request): bool => ! array_key_exists('kind', $request->data())
        && $request['acceptedMethods'] === ['PIX', 'CREDIT_CARD']);
});

it('caps subscription installments at 12 even when config asks for more', function (): void {
    config(['virtu.max_installments' => 24]);
    Http::fake(['sandbox-virtu-api.pagaa.com.br/*' => Http::response(virtuLinkResponse())]);

    (new VirtuClient)->createPaymentLink(CreatePaymentLinkDTO::subscription('Gold', 25000));

    Http::assertSent(fn ($request): bool => $request['maxInstallments'] === 12);
});

it('reads a payment link by public id', function (): void {
    Http::fake(['sandbox-virtu-api.pagaa.com.br/*' => Http::response(virtuLinkResponse(['status' => 'PAID']))]);

    $link = (new VirtuClient)->getPaymentLink('pl_15b42865ecc98ef451aee63a5a6cd79a');

    expect($link->isPaid())->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && str_ends_with($request->url(), '/payment-links/pl_15b42865ecc98ef451aee63a5a6cd79a'));
});

it('treats a 5xx as retryable', function (): void {
    Http::fake(['sandbox-virtu-api.pagaa.com.br/*' => Http::response('boom', 500)]);

    try {
        (new VirtuClient)->createPaymentLink(CreatePaymentLinkDTO::subscription('Gold', 25000));
    } catch (VirtuApiException $virtuApiException) {
        expect($virtuApiException->retryable)->toBeTrue();

        return;
    }

    $this->fail('Expected a VirtuApiException.');
});

it('treats a 4xx as terminal, since retrying a rejected payload never helps', function (): void {
    Http::fake([
        'sandbox-virtu-api.pagaa.com.br/*' => Http::response(['code' => 'THREE_DS_REQUIRES_CARD'], 400),
    ]);

    try {
        (new VirtuClient)->createPaymentLink(CreatePaymentLinkDTO::subscription('Gold', 25000));
    } catch (VirtuApiException $virtuApiException) {
        expect($virtuApiException->retryable)->toBeFalse();

        return;
    }

    $this->fail('Expected a VirtuApiException.');
});

it('rejects a 200 that carries an unsuccessful envelope', function (): void {
    Http::fake([
        'sandbox-virtu-api.pagaa.com.br/*' => Http::response(['success' => false, 'data' => null]),
    ]);

    try {
        (new VirtuClient)->createPaymentLink(CreatePaymentLinkDTO::subscription('Gold', 25000));
    } catch (VirtuApiException $virtuApiException) {
        expect($virtuApiException->retryable)->toBeFalse();

        return;
    }

    $this->fail('Expected a VirtuApiException.');
});

it('does not require credentials until it actually calls the API', function (): void {
    config(['virtu.api_key' => null]);

    // Constructing must stay safe: the adapter is built whenever BillingManager
    // resolves the driver, including from access middleware that never calls out.
    $client = new VirtuClient;

    expect(fn (): array => $client->getRoutes())->toThrow(VirtuApiException::class);
});

<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\IntegrationStripe\DTO\CheckoutLineItemDTO;
use TresPontosTech\IntegrationStripe\DTO\CheckoutSessionDTO;
use TresPontosTech\IntegrationStripe\DTO\CouponDTO;

it('sends a subscription checkout in the shape Stripe expects', function (): void {
    $session = CheckoutSessionDTO::subscription(
        customerId: 'cus_1',
        reference: 'checkout_ref',
        lineItem: CheckoutLineItemDTO::inline('Empresa', 4_490, 5, 'brl', monthly: true),
        successUrl: 'https://app.test/ok',
        cancelUrl: 'https://app.test/cancel',
        pixMandateCents: 22_450,
        couponId: 'coupon_1',
    );

    expect($session->toArray())->toBe([
        'mode' => 'subscription',
        'customer' => 'cus_1',
        'success_url' => 'https://app.test/ok',
        'cancel_url' => 'https://app.test/cancel',
        'line_items' => [[
            'price_data' => [
                'currency' => 'brl',
                'unit_amount' => 4_490,
                'product_data' => ['name' => 'Empresa'],
                'recurring' => ['interval' => 'month'],
            ],
            'quantity' => 5,
        ]],
        'allowed_payment_method_types' => ['card', 'pix'],
        'client_reference_id' => 'checkout_ref',
        'metadata' => ['checkout_reference' => 'checkout_ref'],
        'subscription_data' => ['metadata' => ['checkout_reference' => 'checkout_ref']],
        'payment_method_options' => [
            'pix' => [
                'mandate_options' => [
                    'amount' => 22_450,
                    'amount_type' => 'maximum',
                    'payment_schedule' => 'monthly',
                ],
            ],
        ],
        'discounts' => [['coupon' => 'coupon_1']],
    ]);
});

it('sends a card-only subscription without a Pix mandate', function (): void {
    $session = CheckoutSessionDTO::subscription(
        customerId: 'cus_1',
        reference: 'checkout_ref',
        lineItem: CheckoutLineItemDTO::catalog('price_gold', 1),
        successUrl: 'https://app.test/ok',
        cancelUrl: 'https://app.test/cancel',
        pixMandateCents: null,
        couponId: null,
    );

    $request = $session->toArray();

    expect($request['line_items'])->toBe([['price' => 'price_gold', 'quantity' => 1]])
        ->and($request['allowed_payment_method_types'])->toBe(['card'])
        ->and($request)->not->toHaveKeys(['payment_method_options', 'discounts']);
});

it('sends a one-off payment without subscription metadata or mandate', function (): void {
    $session = CheckoutSessionDTO::payment(
        customerId: 'cus_1',
        lineItem: CheckoutLineItemDTO::inline('Crédito de consultoria', 15_000, 2, 'brl', monthly: false),
        successUrl: 'https://app.test/ok',
        cancelUrl: 'https://app.test/cancel',
        acceptsPix: true,
    );

    expect($session->toArray())->toBe([
        'mode' => 'payment',
        'customer' => 'cus_1',
        'success_url' => 'https://app.test/ok',
        'cancel_url' => 'https://app.test/cancel',
        'line_items' => [[
            'price_data' => [
                'currency' => 'brl',
                'unit_amount' => 15_000,
                'product_data' => ['name' => 'Crédito de consultoria'],
            ],
            'quantity' => 2,
        ]],
        'allowed_payment_method_types' => ['card', 'pix'],
    ]);
});

it('mirrors a discount in a single-use coupon', function (CheckoutDiscount $discount, array $coupon): void {
    expect(CouponDTO::fromDiscount($discount, 'brl')->toArray())->toBe($coupon);
})->with([
    'percent, every charge' => [new CheckoutDiscount(percentOff: 30), ['percent_off' => 30, 'duration' => 'forever', 'max_redemptions' => 1]],
    'percent, three charges' => [new CheckoutDiscount(percentOff: 30, durationCycles: 3), ['percent_off' => 30, 'duration' => 'repeating', 'duration_in_months' => 3, 'max_redemptions' => 1]],
    'fixed amount' => [new CheckoutDiscount(amountOffCents: 5_000), ['amount_off' => 5_000, 'currency' => 'brl', 'duration' => 'forever', 'max_redemptions' => 1]],
]);

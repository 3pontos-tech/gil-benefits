<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Models\Discount;

it('cria o desconto da Clau inativo com 30% por 180 dias', function (): void {
    $discount = Discount::query()->where('slug', 'clau-diagnostico')->sole();

    expect($discount->percent_off)->toBe(30)
        ->and($discount->audience)->toBe(PriceAudienceEnum::Standalone)
        ->and($discount->claim_window_days)->toBe(180)
        ->and($discount->active)->toBeFalse();
});

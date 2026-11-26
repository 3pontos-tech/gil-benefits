<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;

it('cria o desconto da Clau inativo com 30% por 180 dias, travado no plano e sem limite por pessoa', function (): void {
    $discount = Discount::query()->where('slug', 'clau-diagnostico')->sole();

    expect($discount->value_type)->toBe(DiscountValueTypeEnum::Percent)
        ->and($discount->percent_off)->toBe(30)
        ->and($discount->duration)->toBe(DiscountDurationEnum::Forever)
        ->and($discount->audience)->toBe(PriceAudienceEnum::Standalone)
        ->and($discount->claim_window_days)->toBe(180)
        ->and($discount->locks_plan)->toBeTrue()
        ->and($discount->max_redemptions_per_customer)->toBeNull()
        ->and($discount->active)->toBeFalse();
});

<?php

namespace TresPontosTech\Billing\Core\Entities;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

final readonly class PlanEntity
{
    /**
     * @param  Collection<int, PriceEntity>  $prices
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $productId,
        public Collection $prices,
        public int|false|null $trialDays,
        public bool $hasGenericTrial,
        public bool $allowPromotionCodes,
        public bool $collectTaxIds,
        public bool $isMeteredPrice,
    ) {
        throw_if($this->slug === '', InvalidArgumentException::class, message: 'Type cannot be empty.');

        throw_if($this->productId === '', InvalidArgumentException::class, message: 'Product ID cannot be empty.');

        throw_if($this->prices->isEmpty(), InvalidArgumentException::class, message: 'PriceEntity ID cannot be empty.');

        throw_if($this->trialDays !== false && $this->hasGenericTrial, InvalidArgumentException::class, message: 'Only "trial days" or "has generic trial" can be used.');

        throw_if($this->trialDays !== false && $this->trialDays < 0, InvalidArgumentException::class, message: 'Trial days must be greater than 0.');
    }

    public static function fromEloquent(Plan $plan): self
    {
        return new self(
            name: $plan->name,
            slug: $plan->slug,
            productId: $plan->provider_product_id,
            prices: $plan->prices->map(fn (Price $price): PriceEntity => PriceEntity::fromEloquent($price->toArray())),
            trialDays: $plan->trial_days,
            hasGenericTrial: $plan->has_generic_trial,
            allowPromotionCodes: $plan->allow_promotion_codes,
            collectTaxIds: $plan->collect_tax_ids,
            isMeteredPrice: $plan->type->isMetered()
        );
    }

    public static function default(): self
    {
        return new self(
            name: 'Default Item',
            slug: 'default-plan',
            productId: 'prod_default',
            prices: collect([
                new PriceEntity(
                    type: 'one_time',
                    priceId: 'price_default',
                    metadata: []
                ),
            ]),
            trialDays: false,
            hasGenericTrial: false,
            allowPromotionCodes: false,
            collectTaxIds: false,
            isMeteredPrice: false
        );
    }
}

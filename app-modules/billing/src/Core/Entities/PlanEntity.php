<?php

namespace TresPontosTech\Billing\Core\Entities;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use TresPontosTech\Billing\Core\Entities\PriceEntity;

final readonly class PlanEntity
{
    /**
     * @param  Collection<int, PriceEntity>  $prices
     */
    public function __construct(
        public string $type,
        public string $productId,
        public Collection $prices,
        public int|false $trialDays,
        public bool $hasGenericTrial,
        public bool $allowPromotionCodes,
        public bool $collectTaxIds,
        public bool $isMeteredPrice,
    ) {
        throw_if($this->type === '', InvalidArgumentException::class, message: 'Type cannot be empty.');

        throw_if($this->productId === '', InvalidArgumentException::class, message: 'Product ID cannot be empty.');

        throw_if($this->prices->isEmpty(), InvalidArgumentException::class, message: 'PriceEntity ID cannot be empty.');

        throw_if($this->trialDays !== false && $this->hasGenericTrial, InvalidArgumentException::class, message: 'Only "trial days" or "has generic trial" can be used.');

        throw_if($this->trialDays !== false && $this->trialDays < 0, InvalidArgumentException::class, message: 'Trial days must be greater than 0.');
    }

}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class CategoryMix
{
    /**
     * @param  array<int, CategorySlice>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {}
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class ConsultantRow
{
    /**
     * @param  float  $barWidthPercent  Session bar width relative to the busiest consultant
     * @param  string  $color  Semantic colour token mapped to literal classes in the view
     */
    public function __construct(
        public string $name,
        public string $initials,
        public int $sessions,
        public ?float $rating,
        public float $barWidthPercent,
        public string $color,
    ) {}
}

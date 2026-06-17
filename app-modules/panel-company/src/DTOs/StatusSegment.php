<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class StatusSegment
{
    /**
     * @param  string  $color  Semantic colour token mapped to literal classes in the view
     */
    public function __construct(
        public string $label,
        public int $value,
        public float $percent,
        public string $color,
    ) {}
}

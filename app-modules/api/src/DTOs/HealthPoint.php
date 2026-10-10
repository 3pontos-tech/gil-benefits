<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

final readonly class HealthPoint
{
    /**
     * @param  string  $month  'Y-m'
     */
    public function __construct(
        public string $month,
        public int $score,
    ) {}
}

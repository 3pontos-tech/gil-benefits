<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use Carbon\CarbonInterface;

final readonly class QuarterProgress
{
    public function __construct(
        public int $completed,
        public int $goal,
        public CarbonInterface $startsAt,
        public CarbonInterface $endsAt,
    ) {}
}

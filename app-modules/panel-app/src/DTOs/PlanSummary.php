<?php

declare(strict_types=1);

namespace TresPontosTech\App\DTOs;

final readonly class PlanSummary
{
    /**
     * @param  'active'|'inactive'|'expired'  $status
     * @param  list<string>  $features
     */
    public function __construct(
        public string $name,
        public string $status,
        public ?string $description,
        public int $monthlyLimit,
        public array $features,
    ) {}
}

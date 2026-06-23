<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\DTOs;

use TresPontosTech\PanelApp\Enums\PlanStatus;

final readonly class PlanSummary
{
    /**
     * @param  list<string>  $features
     */
    public function __construct(
        public string $name,
        public PlanStatus $status,
        public ?string $description,
        public int $monthlyLimit,
        public array $features,
    ) {}
}

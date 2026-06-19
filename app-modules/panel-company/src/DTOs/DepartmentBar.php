<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class DepartmentBar
{
    public function __construct(
        public string $label,
        public int $adopted,
        public int $total,
        public float $percent,
    ) {}
}

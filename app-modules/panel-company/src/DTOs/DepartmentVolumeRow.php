<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class DepartmentVolumeRow
{
    public function __construct(
        public string $id,
        public string $name,
        public int $total,
    ) {}
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class TopUser
{
    public function __construct(
        public string $name,
        public int $count,
    ) {}
}

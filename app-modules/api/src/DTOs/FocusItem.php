<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use TresPontosTech\Api\Enums\FocusTone;

final readonly class FocusItem
{
    public function __construct(
        public string $label,
        public string $statusLabel,
        public FocusTone $tone,
    ) {}
}

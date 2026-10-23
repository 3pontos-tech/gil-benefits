<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs\Employee\Me;

use TresPontosTech\Api\Enums\Employee\Me\FocusTone;

final readonly class FocusItem
{
    public function __construct(
        public string $label,
        public string $statusLabel,
        public FocusTone $tone,
    ) {}
}

<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\DTO;

final readonly class ChangeStatusDTO
{
    public function __construct(
        public string $itemId,
        public string $boardId,
        public string $columnId,
        public int $index,
    ) {}
}

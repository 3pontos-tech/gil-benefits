<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\DTO;

final readonly class CreateItemDTO
{
    /**
     * @param  array<string, mixed>  $columnValues
     */
    public function __construct(
        public string $boardId,
        public string $groupId,
        public string $itemName,
        public array $columnValues,
    ) {}
}

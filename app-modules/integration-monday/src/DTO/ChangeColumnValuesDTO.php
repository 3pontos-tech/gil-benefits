<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\DTO;

final readonly class ChangeColumnValuesDTO
{
    /**
     * @param  array<string, mixed>  $columnValues
     */
    public function __construct(
        public string $itemId,
        public string $boardId,
        public array $columnValues,
    ) {}
}

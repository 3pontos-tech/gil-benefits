<?php

declare(strict_types=1);

namespace TresPontosTech\User\DTOs;

final readonly class ImportErrorDTO
{
    public function __construct(
        public int $row,
        public string $email,
        public string $message,
    ) {}
}

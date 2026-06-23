<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\DTO;

final readonly class UploadFileDTO
{
    public function __construct(
        public string $itemId,
        public string $columnId,
        public string $contents,
        public string $filename,
    ) {}
}

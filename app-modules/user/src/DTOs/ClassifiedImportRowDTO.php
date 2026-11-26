<?php

declare(strict_types=1);

namespace TresPontosTech\User\DTOs;

use TresPontosTech\User\Enums\ImportRowOutcome;
use TresPontosTech\User\Support\EmailAddress;

final readonly class ClassifiedImportRowDTO
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function __construct(
        public array $row,
        public ImportRowOutcome $outcome,
        public ?string $userId = null,
        public ?string $membershipId = null,
    ) {}

    public function rowNumber(): int
    {
        return (int) ($this->row['__row_number'] ?? 0);
    }

    public function email(): string
    {
        return EmailAddress::normalize((string) ($this->row['email'] ?? ''));
    }

    public function toReport(): ImportErrorDTO
    {
        return new ImportErrorDTO(
            row: $this->rowNumber(),
            email: $this->email() ?: 'N/A',
            message: $this->outcome->message(),
        );
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\User\DTOs;

use Illuminate\Support\Collection;

final readonly class PreparedUserImportDTO
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  list<ImportErrorDTO>  $errors
     */
    public function __construct(
        public Collection $rows,
        public array $errors = [],
        public ?ImportUsersResultDTO $preview = null,
    ) {}

    /**
     * @param  list<ImportErrorDTO>  $errors
     */
    public static function rejected(array $errors): self
    {
        return new self(collect(), $errors);
    }

    public function isRejected(): bool
    {
        return $this->errors !== [];
    }

    public function isEmpty(): bool
    {
        return $this->rows->isEmpty();
    }

    public function joined(): int
    {
        return $this->preview?->joined() ?? 0;
    }
}

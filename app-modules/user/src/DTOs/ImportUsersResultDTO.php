<?php

namespace TresPontosTech\User\DTOs;

final readonly class ImportUsersResultDTO
{
    /**
     * @param  list<ImportErrorDTO>  $errors
     */
    public function __construct(
        public int $imported,
        public array $errors,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function isEmpty(): bool
    {
        return $this->imported === 0 && $this->errors === [];
    }
}

<?php

namespace TresPontosTech\Company\DTOs;

use JsonSerializable;

final readonly class CompanyDTO implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $taxId,
        public string $integrationAccessKey,
        public string $userId,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            taxId: $data['tax_id'],
            integrationAccessKey: $data['integration_access_key'],
            userId: $data['user_id'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'tax_id' => $this->taxId,
            'integration_access_key' => $this->integrationAccessKey,
            'user_id' => $this->userId,
        ];
    }
}

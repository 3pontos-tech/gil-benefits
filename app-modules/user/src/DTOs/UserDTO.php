<?php

namespace TresPontosTech\User\DTOs;

final readonly class UserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $crm_id = null,
        public ?string $external_id = null,
        public ?string $tenant_id = null,
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            name: $payload['name'],
            email: $payload['email'],
            password: $payload['password'],
            crm_id: $payload['crm_id'] ?? null,
            external_id: $payload['external_id'] ?? null,
            tenant_id: $payload['tenant_id'] ?? null,
        );
    }
}

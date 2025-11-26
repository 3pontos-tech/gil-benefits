<?php

namespace App\DTO;

class PartnerRegistrationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $rg,
        public readonly string $cpf,
        public readonly string $email,
        public readonly string $password,
        public readonly string $partnerCode,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            rg: $data['rg'],
            cpf: $data['cpf'],
            email: $data['email'],
            password: $data['password'],
            partnerCode: $data['partner_code'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'rg' => $this->rg,
            'cpf' => $this->cpf,
            'email' => $this->email,
            'password' => $this->password,
            'partner_code' => $this->partnerCode,
        ];
    }
}
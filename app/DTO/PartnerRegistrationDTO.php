<?php

namespace App\DTO;

/**
 * Data Transfer Object for partner collaborator registration.
 * 
 * This DTO encapsulates all the data required for registering a new partner
 * collaborator in the system. It provides a type-safe way to pass registration
 * data between different layers of the application.
 * 
 * All properties are readonly to ensure immutability and data integrity
 * throughout the registration process.
 * 
 * @package App\DTO
 * @author TresPontosTech Development Team
 * @since 1.0.0
 * 
 * @property-read string $name Full name of the user being registered
 * @property-read string $rg Brazilian RG (Registro Geral) document number
 * @property-read string $cpf Brazilian CPF (Cadastro de Pessoas Físicas) tax ID
 * @property-read string $email Email address for the user account
 * @property-read string $password Plain text password (will be hashed during registration)
 * @property-read string $partnerCode Company partner code for association
 */
class PartnerRegistrationDTO
{
    /**
     * Create a new PartnerRegistrationDTO instance.
     * 
     * @param string $name Full name of the user (2-255 characters)
     * @param string $rg Brazilian RG document number (up to 20 characters)
     * @param string $cpf Brazilian CPF tax ID (formatted or unformatted)
     * @param string $email Valid email address for the user account
     * @param string $password Plain text password (minimum 8 characters with complexity requirements)
     * @param string $partnerCode Company partner code for association (alphanumeric, up to 50 characters)
     */
    public function __construct(
        public readonly string $name,
        public readonly string $rg,
        public readonly string $cpf,
        public readonly string $email,
        public readonly string $password,
        public readonly string $partnerCode,
    ) {}

    /**
     * Create a PartnerRegistrationDTO instance from an array of data.
     * 
     * This factory method provides a convenient way to create DTO instances
     * from form data, API requests, or other array-based data sources.
     * 
     * @param array<string, mixed> $data Associative array containing registration data
     *                                  Expected keys: name, rg, cpf, email, password, partner_code
     * 
     * @return self New PartnerRegistrationDTO instance
     * 
     * @throws \InvalidArgumentException If required keys are missing from the array
     * 
     * @example
     * ```php
     * $dto = PartnerRegistrationDTO::fromArray([
     *     'name' => 'João Silva',
     *     'rg' => '12.345.678-9',
     *     'cpf' => '123.456.789-00',
     *     'email' => 'joao@example.com',
     *     'password' => 'SecurePass123!',
     *     'partner_code' => 'PARTNER123'
     * ]);
     * ```
     */
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

    /**
     * Convert the DTO to an associative array.
     * 
     * This method provides a way to convert the DTO back to array format,
     * useful for logging, debugging, or when interfacing with systems
     * that expect array data.
     * 
     * Note: The password is included in the array output. Be careful when
     * logging or exposing this data to ensure password security.
     * 
     * @return array<string, string> Associative array with all DTO properties
     * 
     * @example
     * ```php
     * $dto = new PartnerRegistrationDTO(...);
     * $array = $dto->toArray();
     * // Result: ['name' => '...', 'rg' => '...', 'cpf' => '...', ...]
     * ```
     */
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

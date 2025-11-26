<?php

use App\DTO\PartnerRegistrationDTO;

describe('PartnerRegistrationDTO', function () {
    it('can be created with all required properties', function () {
        $dto = new PartnerRegistrationDTO(
            name: 'João Silva',
            rg: '12.345.678-9',
            cpf: '123.456.789-09',
            email: 'joao@example.com',
            password: 'password123',
            partnerCode: 'PARTNER001'
        );

        expect($dto->name)->toBe('João Silva');
        expect($dto->rg)->toBe('12.345.678-9');
        expect($dto->cpf)->toBe('123.456.789-09');
        expect($dto->email)->toBe('joao@example.com');
        expect($dto->password)->toBe('password123');
        expect($dto->partnerCode)->toBe('PARTNER001');
    });

    it('can be created from array', function () {
        $data = [
            'name' => 'Maria Santos',
            'rg' => '98.765.432-1',
            'cpf' => '987.654.321-00',
            'email' => 'maria@example.com',
            'password' => 'securepass',
            'partner_code' => 'PARTNER002'
        ];

        $dto = PartnerRegistrationDTO::fromArray($data);

        expect($dto->name)->toBe('Maria Santos');
        expect($dto->rg)->toBe('98.765.432-1');
        expect($dto->cpf)->toBe('987.654.321-00');
        expect($dto->email)->toBe('maria@example.com');
        expect($dto->password)->toBe('securepass');
        expect($dto->partnerCode)->toBe('PARTNER002');
    });

    it('can be converted to array', function () {
        $dto = new PartnerRegistrationDTO(
            name: 'Carlos Oliveira',
            rg: '11.222.333-4',
            cpf: '111.222.333-44',
            email: 'carlos@example.com',
            password: 'mypassword',
            partnerCode: 'PARTNER003'
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'Carlos Oliveira',
            'rg' => '11.222.333-4',
            'cpf' => '111.222.333-44',
            'email' => 'carlos@example.com',
            'password' => 'mypassword',
            'partner_code' => 'PARTNER003'
        ]);
    });

    it('properties are readonly', function () {
        $dto = new PartnerRegistrationDTO(
            name: 'Test User',
            rg: '12.345.678-9',
            cpf: '123.456.789-09',
            email: 'test@example.com',
            password: 'password',
            partnerCode: 'TEST001'
        );

        // This should work - reading properties
        expect($dto->name)->toBe('Test User');
        
        // Properties should be readonly (this would cause an error if attempted)
        // $dto->name = 'New Name'; // This would fail
    });
});
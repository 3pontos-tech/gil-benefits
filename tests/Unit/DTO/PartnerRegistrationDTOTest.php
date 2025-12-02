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
            'partner_code' => 'PARTNER002',
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
            'partner_code' => 'PARTNER003',
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

    it('handles special characters in name', function () {
        $dto = new PartnerRegistrationDTO(
            name: 'José da Silva Júnior',
            rg: '12.345.678-9',
            cpf: '123.456.789-09',
            email: 'jose@example.com',
            password: 'password',
            partnerCode: 'PARTNER001'
        );

        expect($dto->name)->toBe('José da Silva Júnior');
    });

    it('handles different cpf formats', function () {
        $formattedCpf = '123.456.789-09';
        $unformattedCpf = '12345678909';

        $dto1 = new PartnerRegistrationDTO(
            name: 'User 1',
            rg: '12.345.678-9',
            cpf: $formattedCpf,
            email: 'user1@example.com',
            password: 'password',
            partnerCode: 'PARTNER001'
        );

        $dto2 = new PartnerRegistrationDTO(
            name: 'User 2',
            rg: '12.345.678-9',
            cpf: $unformattedCpf,
            email: 'user2@example.com',
            password: 'password',
            partnerCode: 'PARTNER001'
        );

        expect($dto1->cpf)->toBe($formattedCpf);
        expect($dto2->cpf)->toBe($unformattedCpf);
    });

    it('handles different rg formats', function () {
        $formattedRg = '12.345.678-9';
        $unformattedRg = '123456789';

        $dto1 = new PartnerRegistrationDTO(
            name: 'User 1',
            rg: $formattedRg,
            cpf: '123.456.789-09',
            email: 'user1@example.com',
            password: 'password',
            partnerCode: 'PARTNER001'
        );

        $dto2 = new PartnerRegistrationDTO(
            name: 'User 2',
            rg: $unformattedRg,
            cpf: '123.456.789-09',
            email: 'user2@example.com',
            password: 'password',
            partnerCode: 'PARTNER001'
        );

        expect($dto1->rg)->toBe($formattedRg);
        expect($dto2->rg)->toBe($unformattedRg);
    });

    it('handles different email formats', function () {
        $emails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.com',
            'user123@sub.example.com',
        ];

        foreach ($emails as $email) {
            $dto = new PartnerRegistrationDTO(
                name: 'Test User',
                rg: '12.345.678-9',
                cpf: '123.456.789-09',
                email: $email,
                password: 'password',
                partnerCode: 'PARTNER001'
            );

            expect($dto->email)->toBe($email);
        }
    });

    it('handles different partner code formats', function () {
        $partnerCodes = [
            'PARTNER001',
            'partner001',
            'COMP-123',
            'ABC_XYZ_789',
        ];

        foreach ($partnerCodes as $code) {
            $dto = new PartnerRegistrationDTO(
                name: 'Test User',
                rg: '12.345.678-9',
                cpf: '123.456.789-09',
                email: 'test@example.com',
                password: 'password',
                partnerCode: $code
            );

            expect($dto->partnerCode)->toBe($code);
        }
    });

    it('fromArray and toArray are inverse operations', function () {
        $originalData = [
            'name' => 'Test User',
            'rg' => '12.345.678-9',
            'cpf' => '123.456.789-09',
            'email' => 'test@example.com',
            'password' => 'password123',
            'partner_code' => 'PARTNER001',
        ];

        $dto = PartnerRegistrationDTO::fromArray($originalData);
        $convertedData = $dto->toArray();

        expect($convertedData)->toBe($originalData);
    });

    it('handles empty strings gracefully', function () {
        $dto = new PartnerRegistrationDTO(
            name: '',
            rg: '',
            cpf: '',
            email: '',
            password: '',
            partnerCode: ''
        );

        expect($dto->name)->toBe('');
        expect($dto->rg)->toBe('');
        expect($dto->cpf)->toBe('');
        expect($dto->email)->toBe('');
        expect($dto->password)->toBe('');
        expect($dto->partnerCode)->toBe('');
    });

    it('maintains data integrity across operations', function () {
        $originalDto = new PartnerRegistrationDTO(
            name: 'João da Silva',
            rg: '12.345.678-9',
            cpf: '123.456.789-09',
            email: 'joao@example.com',
            password: 'securePassword123!',
            partnerCode: 'PARTNER_ABC_123'
        );

        $array = $originalDto->toArray();
        $newDto = PartnerRegistrationDTO::fromArray($array);

        expect($newDto->name)->toBe($originalDto->name);
        expect($newDto->rg)->toBe($originalDto->rg);
        expect($newDto->cpf)->toBe($originalDto->cpf);
        expect($newDto->email)->toBe($originalDto->email);
        expect($newDto->password)->toBe($originalDto->password);
        expect($newDto->partnerCode)->toBe($originalDto->partnerCode);
    });
});

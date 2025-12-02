<?php

use App\Rules\CpfRule;

describe('CpfRule', function () {
    beforeEach(function () {
        $this->rule = new CpfRule;
        $this->failMessages = [];
        $this->fail = function (string $message) {
            $this->failMessages[] = $message;
        };
    });

    it('passes validation for valid CPF numbers', function () {
        $validCpfs = [
            '11144477735',
            '111.444.777-35',
            '52998224725',
            '529.982.247-25',
        ];

        foreach ($validCpfs as $cpf) {
            $this->failMessages = [];
            $this->rule->validate('cpf', $cpf, $this->fail);
            expect($this->failMessages)->toBeEmpty();
        }
    });

    it('fails validation for invalid CPF numbers', function () {
        $invalidCpfs = [
            '11144477736', // Wrong check digit
            '123.456.789-10', // Invalid
            '000.000.000-00', // All zeros
            '111.111.111-11', // All same digits
            '123456789', // Too short
            '123456789012', // Too long
            '', // Empty
        ];

        foreach ($invalidCpfs as $cpf) {
            $this->failMessages = [];
            $this->rule->validate('cpf', $cpf, $this->fail);
            expect($this->failMessages)->not->toBeEmpty();
        }
    });

    it('fails validation for non-string values', function () {
        $nonStringValues = [
            123,
            null,
            [],
            (object) [],
            true,
            false,
        ];

        foreach ($nonStringValues as $value) {
            $this->failMessages = [];
            $this->rule->validate('cpf', $value, $this->fail);
            expect($this->failMessages)->toContain('O CPF deve ser uma string válida.');
        }
    });

    it('provides user-friendly error message for invalid CPF', function () {
        $this->rule->validate('cpf', '12345678901', $this->fail);
        expect($this->failMessages)->toContain('O CPF informado é inválido. Verifique os dígitos e tente novamente.');
    });
});

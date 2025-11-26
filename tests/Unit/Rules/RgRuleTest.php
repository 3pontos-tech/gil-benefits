<?php

use App\Rules\RgRule;

describe('RgRule', function () {
    beforeEach(function () {
        $this->rule = new RgRule();
        $this->failMessages = [];
        $this->fail = function (string $message) {
            $this->failMessages[] = $message;
        };
    });

    it('passes validation for valid RG numbers', function () {
        $validRgs = [
            '12.345.678-9',
            '123456789',
            '12345678X',
            'MG1234567',
            'SP123456789',
            '12.345.678-X',
        ];

        foreach ($validRgs as $rg) {
            $this->failMessages = [];
            $this->rule->validate('rg', $rg, $this->fail);
            expect($this->failMessages)->toBeEmpty();
        }
    });

    it('fails validation for RG that is too short', function () {
        $shortRgs = [
            '1234',
            '123',
            'AB12',
        ];

        foreach ($shortRgs as $rg) {
            $this->failMessages = [];
            $this->rule->validate('rg', $rg, $this->fail);
            expect($this->failMessages)->toContain('O RG deve ter entre 5 e 15 caracteres.');
        }
    });

    it('fails validation for RG that is too long', function () {
        $longRgs = [
            '1234567890123456', // 16 characters
            'ABCDEFGHIJKLMNOP', // 16 characters
        ];

        foreach ($longRgs as $rg) {
            $this->failMessages = [];
            $this->rule->validate('rg', $rg, $this->fail);
            expect($this->failMessages)->toContain('O RG deve ter entre 5 e 15 caracteres.');
        }
    });

    it('fails validation for RG without numbers', function () {
        $noNumberRgs = [
            'ABCDEFGH',
            'ABCDE',
            'XXXXXX',
        ];

        foreach ($noNumberRgs as $rg) {
            $this->failMessages = [];
            $this->rule->validate('rg', $rg, $this->fail);
            expect($this->failMessages)->toContain('O RG deve conter pelo menos um número.');
        }
    });

    it('fails validation for empty RG', function () {
        $this->rule->validate('rg', '', $this->fail);
        expect($this->failMessages)->toContain('O RG é obrigatório.');
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
            $this->rule->validate('rg', $value, $this->fail);
            expect($this->failMessages)->toContain('O RG deve ser uma string válida.');
        }
    });

    it('handles RG with special characters correctly', function () {
        $rgsWithSpecialChars = [
            '12.345.678-9', // Valid with dots and dash
            '12-345-678-9', // Valid with dashes
            '12 345 678 9', // Valid with spaces
            '12/345/678/9', // Valid with slashes
        ];

        foreach ($rgsWithSpecialChars as $rg) {
            $this->failMessages = [];
            $this->rule->validate('rg', $rg, $this->fail);
            expect($this->failMessages)->toBeEmpty();
        }
    });
});
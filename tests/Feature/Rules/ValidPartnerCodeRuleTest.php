<?php

use App\Rules\ValidPartnerCodeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TresPontosTech\Company\Models\Company;

uses(RefreshDatabase::class);

describe('ValidPartnerCodeRule', function () {
    beforeEach(function () {
        $this->rule = new ValidPartnerCodeRule();
        $this->failMessages = [];
        $this->fail = function (string $message) {
            $this->failMessages[] = $message;
        };

        // Create a company with partner code for testing
        $this->company = Company::factory()->create([
            'partner_code' => 'TEST123',
        ]);
    });

    it('passes validation for valid partner code', function () {
        $this->rule->validate('partner_code', 'TEST123', $this->fail);
        expect($this->failMessages)->toBeEmpty();
    });

    it('passes validation for valid partner code with different case', function () {
        $this->rule->validate('partner_code', 'test123', $this->fail);
        expect($this->failMessages)->toBeEmpty();
        
        $this->failMessages = [];
        $this->rule->validate('partner_code', 'Test123', $this->fail);
        expect($this->failMessages)->toBeEmpty();
    });

    it('passes validation for partner code with whitespace', function () {
        $this->rule->validate('partner_code', ' TEST123 ', $this->fail);
        expect($this->failMessages)->toBeEmpty();
    });

    it('fails validation for invalid partner code', function () {
        $this->rule->validate('partner_code', 'INVALID', $this->fail);
        expect($this->failMessages)->toContain('Código de parceiro inválido ou não encontrado. Verifique o código e tente novamente.');
    });

    it('fails validation for empty partner code', function () {
        $this->rule->validate('partner_code', '', $this->fail);
        expect($this->failMessages)->toContain('O código do parceiro é obrigatório.');
        
        $this->failMessages = [];
        $this->rule->validate('partner_code', '   ', $this->fail);
        expect($this->failMessages)->toContain('O código do parceiro é obrigatório.');
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
            $this->rule->validate('partner_code', $value, $this->fail);
            expect($this->failMessages)->toContain('O código do parceiro deve ser uma string válida.');
        }
    });
});
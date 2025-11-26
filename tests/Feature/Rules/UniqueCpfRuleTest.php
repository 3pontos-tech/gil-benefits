<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use App\Rules\UniqueCpfRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UniqueCpfRule', function () {
    beforeEach(function () {
        $this->failMessages = [];
        $this->fail = function (string $message) {
            $this->failMessages[] = $message;
        };
    });

    it('passes validation for unique CPF', function () {
        $rule = new UniqueCpfRule();
        $rule->validate('cpf', '11144477735', $this->fail);
        expect($this->failMessages)->toBeEmpty();
    });

    it('fails validation for duplicate CPF', function () {
        // Create a user with CPF
        $user = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $user->id,
            'tax_id' => '11144477735',
        ]);

        $rule = new UniqueCpfRule();
        $rule->validate('cpf', '111.444.777-35', $this->fail);
        expect($this->failMessages)->toContain('Este CPF já está cadastrado no sistema.');
    });

    it('passes validation when ignoring specific user ID', function () {
        // Create a user with CPF
        $user = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $user->id,
            'tax_id' => '11144477735',
        ]);

        // Should pass when ignoring the same user
        $rule = new UniqueCpfRule($user->id);
        $rule->validate('cpf', '111.444.777-35', $this->fail);
        expect($this->failMessages)->toBeEmpty();
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
            $rule = new UniqueCpfRule();
            $rule->validate('cpf', $value, $this->fail);
            expect($this->failMessages)->toContain('O CPF deve ser uma string válida.');
        }
    });

    it('fails validation for empty CPF', function () {
        $rule = new UniqueCpfRule();
        $rule->validate('cpf', '', $this->fail);
        expect($this->failMessages)->toContain('O CPF não pode estar vazio.');
    });

    it('handles CPF with different formatting', function () {
        // Create a user with clean CPF
        $user = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $user->id,
            'tax_id' => '11144477735',
        ]);

        // Test with formatted CPF
        $rule = new UniqueCpfRule();
        $rule->validate('cpf', '111.444.777-35', $this->fail);
        expect($this->failMessages)->toContain('Este CPF já está cadastrado no sistema.');
    });
});
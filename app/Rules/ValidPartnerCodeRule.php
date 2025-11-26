<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use TresPontosTech\Company\Models\Company;

class ValidPartnerCodeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O código do parceiro deve ser uma string válida.');
            return;
        }

        if (empty(trim($value))) {
            $fail('O código do parceiro é obrigatório.');
            return;
        }

        $exists = Company::whereRaw('LOWER(partner_code) = LOWER(?)', [trim($value)])->exists();
        
        if (!$exists) {
            $fail('Código de parceiro inválido ou não encontrado. Verifique o código e tente novamente.');
        }
    }
}
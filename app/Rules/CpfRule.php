<?php

namespace App\Rules;

use App\Utils\CpfValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O CPF deve ser uma string válida.');
            return;
        }

        if (!CpfValidator::validate($value)) {
            $fail('O CPF informado é inválido. Verifique os dígitos e tente novamente.');
        }
    }
}
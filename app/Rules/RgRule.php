<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RgRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O RG deve ser uma string válida.');
            return;
        }

        $cleanRg = preg_replace('/[^0-9A-Za-z]/', '', $value);
        
        if (empty($cleanRg)) {
            $fail('O RG é obrigatório.');
            return;
        }

        // RG should have at least 5 characters and at most 15
        if (strlen($cleanRg) < 5 || strlen($cleanRg) > 15) {
            $fail('O RG deve ter entre 5 e 15 caracteres.');
            return;
        }

        // RG should contain at least one number
        if (!preg_match('/\d/', $cleanRg)) {
            $fail('O RG deve conter pelo menos um número.');
        }
    }
}
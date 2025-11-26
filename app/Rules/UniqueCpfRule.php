<?php

namespace App\Rules;

use App\Models\Users\Detail;
use App\Utils\CpfValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueCpfRule implements ValidationRule
{
    public function __construct(
        private ?int $ignoreUserId = null
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O CPF deve ser uma string válida.');
            return;
        }

        $cleanCpf = CpfValidator::clean($value);
        
        if (empty($cleanCpf)) {
            $fail('O CPF não pode estar vazio.');
            return;
        }

        $query = Detail::where('tax_id', $cleanCpf);
        
        if ($this->ignoreUserId) {
            $query->where('user_id', '!=', $this->ignoreUserId);
        }

        if ($query->exists()) {
            $fail('Este CPF já está cadastrado no sistema.');
        }
    }
}
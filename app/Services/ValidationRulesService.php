<?php

namespace App\Services;

use App\Rules\CpfRule;
use App\Rules\RgRule;
use App\Rules\UniqueCpfRule;
use App\Rules\ValidPartnerCodeRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Centralized validation rules service for consistent data validation.
 * 
 * This service provides standardized validation rules for common data types
 * used throughout the application. It ensures consistency in validation
 * logic and makes it easy to update validation requirements globally.
 * 
 * All methods are static for easy access and return arrays of validation
 * rules that can be used directly with Laravel's validation system.
 * 
 * The service includes specialized validation for Brazilian documents
 * (CPF, RG) and business-specific requirements like partner codes.
 * 
 * @package App\Services
 * @author TresPontosTech Development Team
 * @since 1.0.0
 */
class ValidationRulesService
{
    /**
     * Get standardized email validation rules
     *
     * @return array<int, mixed>
     */
    public static function email(bool $required = true, ?int $ignoreUserId = null): array
    {
        $rules = [
            'email:rfc,dns',
            'max:255',
            'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        // Add unique rule if needed
        if ($ignoreUserId !== null) {
            $rules[] = Rule::unique('users', 'email')->ignore($ignoreUserId);
        } else {
            $rules[] = 'unique:users,email';
        }

        return $rules;
    }

    /**
     * Get standardized name validation rules
     *
     * @return array<int, string>
     */
    public static function name(bool $required = true, int $minLength = 2, int $maxLength = 255): array
    {
        $rules = [
            'string',
            "min:{$minLength}",
            "max:{$maxLength}",
            'regex:/^[a-zA-ZÀ-ÿ\s\-\'\.]+$/u', // Only letters, spaces, hyphens, apostrophes, dots
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized phone validation rules
     *
     * @return array<int, string>
     */
    public static function phone(bool $required = true): array
    {
        $rules = [
            'string',
            'min:10',
            'max:20',
            'regex:/^[\+]?[0-9\s\(\)\-]+$/',
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized password validation rules
     *
     * @return array<int, mixed>
     */
    public static function password(bool $requireConfirmation = true, bool $checkCompromised = true): array
    {
        $passwordRule = Password::min(8)
            ->letters()
            ->numbers()
            ->mixedCase()
            ->symbols();

        if ($checkCompromised) {
            $passwordRule->uncompromised();
        }

        $rules = [
            'required',
            'string',
            $passwordRule,
        ];

        if ($requireConfirmation) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Get standardized CPF validation rules
     *
     * @return array<int, mixed>
     */
    public static function cpf(bool $required = true, ?int $ignoreUserId = null): array
    {
        $rules = [
            'string',
            new CpfRule,
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        // Add unique rule
        $rules[] = new UniqueCpfRule($ignoreUserId);

        return $rules;
    }

    /**
     * Get standardized RG validation rules
     *
     * @return array<int, mixed>
     */
    public static function rg(bool $required = true): array
    {
        $rules = [
            'string',
            'max:20',
            new RgRule,
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized partner code validation rules
     *
     * @return array<int, mixed>
     */
    public static function partnerCode(bool $required = true): array
    {
        $rules = [
            'string',
            'max:50',
            'alpha_num',
            new ValidPartnerCodeRule,
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized text validation rules
     *
     * @return array<int, string>
     */
    public static function text(bool $required = true, int $minLength = 1, int $maxLength = 1000): array
    {
        $rules = [
            'string',
            "min:{$minLength}",
            "max:{$maxLength}",
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized URL validation rules
     *
     * @return array<int, string>
     */
    public static function url(bool $required = true): array
    {
        $rules = [
            'url',
            'max:2048',
            'regex:/^https?:\/\/[^\s\/$.?#].[^\s]*$/i',
        ];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized date validation rules
     *
     * @return array<int, string>
     */
    public static function date(bool $required = true, ?string $format = null): array
    {
        $rules = [];

        if ($format) {
            $rules[] = "date_format:{$format}";
        } else {
            $rules[] = 'date';
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized numeric validation rules
     *
     * @return array<int, string>
     */
    public static function numeric(bool $required = true, ?float $min = null, ?float $max = null): array
    {
        $rules = ['numeric'];

        if ($min !== null) {
            $rules[] = "min:{$min}";
        }

        if ($max !== null) {
            $rules[] = "max:{$max}";
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized integer validation rules
     *
     * @return array<int, string>
     */
    public static function integer(bool $required = true, ?int $min = null, ?int $max = null): array
    {
        $rules = ['integer'];

        if ($min !== null) {
            $rules[] = "min:{$min}";
        }

        if ($max !== null) {
            $rules[] = "max:{$max}";
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized boolean validation rules
     *
     * @return array<int, string>
     */
    public static function boolean(bool $required = true): array
    {
        $rules = ['boolean'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized array validation rules
     *
     * @return array<int, string>
     */
    public static function array(bool $required = true, ?int $min = null, ?int $max = null): array
    {
        $rules = ['array'];

        if ($min !== null) {
            $rules[] = "min:{$min}";
        }

        if ($max !== null) {
            $rules[] = "max:{$max}";
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized file validation rules
     *
     * @param  array<int, string>  $mimes
     * @return array<int, string>
     */
    public static function file(bool $required = true, array $mimes = [], ?int $maxSizeKb = null): array
    {
        $rules = ['file'];

        if (! empty($mimes)) {
            $rules[] = 'mimes:' . implode(',', $mimes);
        }

        if ($maxSizeKb !== null) {
            $rules[] = "max:{$maxSizeKb}";
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get standardized image validation rules
     *
     * @return array<int, string>
     */
    public static function image(bool $required = true, ?int $maxSizeKb = 2048): array
    {
        $rules = [
            'image',
            'mimes:jpeg,jpg,png,gif,webp',
        ];

        if ($maxSizeKb !== null) {
            $rules[] = "max:{$maxSizeKb}";
        }

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    /**
     * Get all standardized validation messages
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto válido.',
            'email' => 'O campo :attribute deve ser um e-mail válido.',
            'min' => 'O campo :attribute deve ter pelo menos :min caracteres.',
            'max' => 'O campo :attribute não pode ter mais de :max caracteres.',
            'regex' => 'O formato do campo :attribute não é válido.',
            'unique' => 'Este :attribute já está em uso.',
            'confirmed' => 'A confirmação do campo :attribute não confere.',
            'numeric' => 'O campo :attribute deve ser um número.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
            'array' => 'O campo :attribute deve ser uma lista.',
            'date' => 'O campo :attribute deve ser uma data válida.',
            'date_format' => 'O campo :attribute deve estar no formato :format.',
            'url' => 'O campo :attribute deve ser uma URL válida.',
            'file' => 'O campo :attribute deve ser um arquivo.',
            'image' => 'O campo :attribute deve ser uma imagem.',
            'mimes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
            'alpha_num' => 'O campo :attribute deve conter apenas letras e números.',
        ];
    }

    /**
     * Get standardized attribute names in Portuguese
     *
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'name' => 'nome',
            'first_name' => 'primeiro nome',
            'last_name' => 'sobrenome',
            'full_name' => 'nome completo',
            'email' => 'e-mail',
            'password' => 'senha',
            'password_confirmation' => 'confirmação de senha',
            'phone' => 'telefone',
            'mobile' => 'celular',
            'cpf' => 'CPF',
            'rg' => 'RG',
            'partner_code' => 'código do parceiro',
            'company_name' => 'nome da empresa',
            'address' => 'endereço',
            'city' => 'cidade',
            'state' => 'estado',
            'zip_code' => 'CEP',
            'country' => 'país',
            'birth_date' => 'data de nascimento',
            'description' => 'descrição',
            'title' => 'título',
            'content' => 'conteúdo',
            'message' => 'mensagem',
            'subject' => 'assunto',
            'website' => 'site',
            'linkedin' => 'LinkedIn',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter',
        ];
    }
}

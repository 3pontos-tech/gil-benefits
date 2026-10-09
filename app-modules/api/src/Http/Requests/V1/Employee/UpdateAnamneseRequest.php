<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use TresPontosTech\User\Enums\LifeMoment;

/**
 * Qualquer subconjunto das respostas: o app salva ao sair de cada campo. `null` apaga a
 * resposta; campo ausente fica como está.
 */
class UpdateAnamneseRequest extends FormRequest
{
    /** @var list<string> */
    public const array FIELDS = [
        'life_moment',
        'main_motivation',
        'money_relationship',
        'plans_monthly_expenses',
        'tried_financial_strategies',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $text = ['sometimes', 'nullable', 'string', 'max:5000'];

        return [
            'life_moment' => ['sometimes', 'nullable', Rule::enum(LifeMoment::class)],
            'main_motivation' => $text,
            'money_relationship' => $text,
            'plans_monthly_expenses' => $text,
            'tried_financial_strategies' => $text,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(self::FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => __('api::attributes.' . $field)])
            ->all();
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->hasAny(self::FIELDS)) {
                    $validator->errors()->add('life_moment', __('api::validation.nothing_to_update'));
                }
            },
        ];
    }

    /**
     * @return array{life_moment?: string|null, main_motivation?: string|null, money_relationship?: string|null, plans_monthly_expenses?: string|null, tried_financial_strategies?: string|null}
     */
    public function answers(): array
    {
        /** @var array{life_moment?: string|null, main_motivation?: string|null, money_relationship?: string|null, plans_monthly_expenses?: string|null, tried_financial_strategies?: string|null} */
        return $this->safe()->only(self::FIELDS);
    }
}

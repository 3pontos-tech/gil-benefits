<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

/**
 * Mesmos campos do "Novo chamado" do painel, sem anexos. A descrição ganha limite porque a
 * API aceita envio direto.
 */
class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(SupportTicketCategoryEnum::class)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category' => __('api::attributes.category'),
            'subject' => __('api::attributes.subject'),
            'description' => __('api::attributes.description'),
        ];
    }
}

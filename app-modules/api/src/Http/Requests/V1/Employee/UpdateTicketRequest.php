<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Requests\V1\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;

/**
 * O colaborador só finaliza o chamado; os demais status são da equipe de suporte.
 */
class UpdateTicketRequest extends FormRequest
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
            'status' => ['required', Rule::in([SupportTicketStatusEnum::Closed->value])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => __('api::attributes.status'),
        ];
    }
}

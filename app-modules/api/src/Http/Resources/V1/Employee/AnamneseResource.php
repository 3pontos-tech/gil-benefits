<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\User\Models\UserAnamnese;

/**
 * Sem anamnese ainda, recebe um modelo vazio e responde tudo `null`.
 *
 * @mixin UserAnamnese
 */
class AnamneseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'life_moment' => $this->life_moment?->value,
            'main_motivation' => $this->main_motivation,
            'money_relationship' => $this->money_relationship,
            'plans_monthly_expenses' => $this->plans_monthly_expenses,
            'tried_financial_strategies' => $this->tried_financial_strategies,
            'updated_at' => ApiDates::dateTime($this->updated_at),
        ];
    }
}

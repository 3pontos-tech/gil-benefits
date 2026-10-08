<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\DTOs\EmployeeCredits;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Credits\Models\UserCredit;

/**
 * @property EmployeeCredits $resource
 */
class CreditsResource extends JsonResource
{
    public function __construct(EmployeeCredits $resource)
    {
        parent::__construct($resource);
    }

    /**
     * `status` é o efetivo (validade vencida já conta como `expired`) e `owner_type` diz se
     * o crédito veio da empresa ou é da própria pessoa (ver UserCredit).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'monthly_quota' => [
                'limit' => $this->resource->monthlyLimit,
                'left' => $this->resource->monthlyLeft,
                'renews_at' => ApiDates::date($this->resource->renewsAt),
            ],
            'credits' => $this->resource->credits->map(fn (UserCredit $credit): array => [
                'id' => $credit->id,
                'status' => $credit->effectiveStatus()->value,
                'owner_type' => $credit->isFromCompany() ? 'company' : 'user',
                'expires_at' => ApiDates::dateTime($credit->expires_at),
                'used_at' => ApiDates::dateTime($credit->used_at),
                'appointment_id' => $credit->appointment_id,
                'grant_id' => $credit->grant_id,
                'created_at' => ApiDates::dateTime($credit->created_at),
                'updated_at' => ApiDates::dateTime($credit->updated_at),
            ])->values()->all(),
        ];
    }
}

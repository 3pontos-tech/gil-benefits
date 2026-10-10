<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use TresPontosTech\Api\Support\ApiDates;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * @mixin SupportTicket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'protocol' => $this->protocol,
            'category' => $this->category->value,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'created_at' => ApiDates::dateTime($this->created_at),
            'updated_at' => ApiDates::dateTime($this->updated_at),
        ];
    }
}

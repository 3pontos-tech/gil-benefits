<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use TresPontosTech\Api\Support\ApiDates;

/**
 * `type` é o tipo do aviso (NotificationKind); `data` traz os textos, o tom e o id do
 * encontro ou do material que o toque abre.
 *
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->data;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => array_filter([
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'] ?? null,
                'icon' => $data['icon'] ?? null,
                'format' => $data['format'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'document_id' => $data['document_id'] ?? null,
            ], fn (mixed $value): bool => $value !== null),
            'read_at' => ApiDates::dateTime($this->read_at),
            'created_at' => ApiDates::dateTime($this->created_at),
        ];
    }
}

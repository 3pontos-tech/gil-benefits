<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketStatusChangeSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketStatusChange;

/**
 * @extends Factory<SupportTicketStatusChange>
 */
class SupportTicketStatusChangeFactory extends Factory
{
    protected $model = SupportTicketStatusChange::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'from_status' => SupportTicketStatusEnum::Pending,
            'to_status' => SupportTicketStatusEnum::InProgress,
            'source' => TicketStatusChangeSourceEnum::Admin,
            'actor_user_id' => null,
            'reason' => null,
        ];
    }

    public function opening(): static
    {
        return $this->state(fn (): array => [
            'from_status' => null,
            'to_status' => SupportTicketStatusEnum::Pending,
            'source' => TicketStatusChangeSourceEnum::Requester,
        ]);
    }

    public function reopening(string $reason): static
    {
        return $this->state(fn (): array => [
            'from_status' => SupportTicketStatusEnum::Resolved,
            'to_status' => SupportTicketStatusEnum::InProgress,
            'source' => TicketStatusChangeSourceEnum::Admin,
            'reason' => $reason,
        ]);
    }
}

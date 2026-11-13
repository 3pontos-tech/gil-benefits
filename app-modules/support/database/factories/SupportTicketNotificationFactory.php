<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;
use TresPontosTech\Support\Enums\TicketNotificationEventEnum;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\SupportTicketNotification;

/**
 * @extends Factory<SupportTicketNotification>
 */
class SupportTicketNotificationFactory extends Factory
{
    protected $model = SupportTicketNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'channel' => TicketNotificationChannelEnum::Whatsapp,
            'event' => TicketNotificationEventEnum::Created,
            'from_status' => null,
            'to_status' => SupportTicketStatusEnum::Pending,
            'message' => $this->faker->sentence(),
            'status' => TicketNotificationStatusEnum::Pending,
            'attempts' => 0,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => TicketNotificationStatusEnum::Failed,
            'attempts' => 3,
            'response_code' => 401,
            'error' => '{"message":"Unauthenticated."}',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => TicketNotificationStatusEnum::Sent,
            'attempts' => 1,
            'response_code' => 200,
            'sent_at' => now(),
        ]);
    }
}

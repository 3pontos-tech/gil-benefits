<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * @extends Factory<TicketDestination>
 */
class TicketDestinationFactory extends Factory
{
    protected $model = TicketDestination::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'type' => $this->faker->randomElement(TicketDestinationTypeEnum::cases()),
            'channel' => $this->faker->randomElement(TicketDestinationChannelEnum::cases()),
            'reference_id' => null,
            'status' => TicketDestinationStatusEnum::Pending,
        ];
    }
}

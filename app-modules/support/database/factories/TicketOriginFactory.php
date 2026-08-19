<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketOrigin;

/**
 * @extends Factory<TicketOrigin>
 */
class TicketOriginFactory extends Factory
{
    protected $model = TicketOrigin::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'source' => TicketOriginSourceEnum::Chatx,
            'external_reference' => 'CHATX-' . $this->faker->unique()->numberBetween(100000, 999999),
        ];
    }
}

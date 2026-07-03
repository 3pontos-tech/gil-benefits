<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'protocol' => sprintf('SUP-%s-%s', now()->year, $this->faker->unique()->numerify('####')),
            'user_id' => null,
            'company_id' => Company::factory(),
            'visitor_name' => null,
            'visitor_email' => null,
            'visitor_company_name' => null,
            'category' => $this->faker->randomElement(SupportTicketCategoryEnum::cases()),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => SupportTicketStatusEnum::Pending,
            'url' => null,
            'browser' => null,
            'device' => null,
            'environment' => 'testing',
        ];
    }
}

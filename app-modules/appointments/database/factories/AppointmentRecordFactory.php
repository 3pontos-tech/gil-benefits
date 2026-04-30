<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentRecord;

class AppointmentRecordFactory extends Factory
{
    protected $model = AppointmentRecord::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'content' => $this->faker->paragraphs(5, true),
            'internal_summary' => $this->faker->paragraphs(2, true),
            'model_used' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'published_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(fn (): array => [
            'published_at' => now(),
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn (): array => [
            'published_at' => null,
            'content' => null,
            'internal_summary' => null,
        ]);
    }

    public function withTokens(
        string $model = 'gemini-2.5-pro',
        int $input = 12450,
        int $output = 2110,
    ): self {
        return $this->state(fn (): array => [
            'model_used' => $model,
            'input_tokens' => $input,
            'output_tokens' => $output,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Consultant;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'status' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'consultant_id' => Consultant::factory(),
            'voucher_id' => Voucher::factory(),
        ];
    }
}

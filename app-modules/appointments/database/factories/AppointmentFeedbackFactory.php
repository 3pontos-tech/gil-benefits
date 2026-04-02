<?php

namespace TresPontosTech\Appointments\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

class AppointmentFeedbackFactory extends Factory
{
    protected $model = AppointmentFeedback::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
}

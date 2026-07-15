<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;

class AppointmentHistoryFactory extends Factory
{
    protected $model = AppointmentHistory::class;

    public function definition(): array
    {
        return [
            'action_type' => $this->faker->randomElements(AppointmentHistoryActionType::cases()),
            'old_values' => $this->faker->words(),
            'new_values' => $this->faker->words(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'appointment_id' => Appointment::factory(),
            'admin_Id' => User::factory(),
        ];
    }
}

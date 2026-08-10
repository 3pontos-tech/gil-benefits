<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;

/**
 * @extends Factory<AppointmentHistory>
 */
class AppointmentHistoryFactory extends Factory
{
    protected $model = AppointmentHistory::class;

    public function definition(): array
    {
        return [
            'action_type' => $this->faker->randomElement(AppointmentHistoryActionType::cases()),
            'old_values' => ['consultant_id' => $this->faker->uuid()],
            'new_values' => ['consultant_id' => $this->faker->uuid()],
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'appointment_id' => Appointment::factory(),
            'actor_id' => User::factory(),
            'actor_type' => AppointmentHistoryActor::Admin,
        ];
    }

    public function actionType(AppointmentHistoryActionType $actionType): static
    {
        return $this->state(fn (): array => ['action_type' => $actionType]);
    }
}

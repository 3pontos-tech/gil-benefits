<?php

namespace TresPontosTech\Appointments\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'consultant_id' => Consultant::factory(),
            'user_id' => User::factory(),
            'company_id' => Company::factory(),

            'status' => $this->faker->randomElement(AppointmentStatus::cases()),
            'category_type' => $this->faker->randomElement(AppointmentCategoryEnum::cases()),

            'appointment_at' => Date::now(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function withoutConsultant(): self
    {
        return $this->state(function (): array {
            return [
                'consultant_id' => null,
            ];
        });
    }

    public function withStatus(AppointmentStatus $status = AppointmentStatus::Pending): self
    {
        return $this->state(function () use ($status): array {
            return [
                'status' => $status,
            ];
        });
    }
}

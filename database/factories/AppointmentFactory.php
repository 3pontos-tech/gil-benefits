<?php

namespace Database\Factories;

use App\Enums\AppointmentCategoryEnum;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Companies\Company;
use App\Models\Consultant;
use App\Models\Users\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'consultant_id' => Consultant::factory(),
            'voucher_id' => Voucher::factory(),
            'user_id' => User::factory(),
            'company_id' => Company::factory(),

            'external_opportunity_id' => $this->faker->word(),
            'external_appointment_id' => $this->faker->word(),

            'status' => $this->faker->randomElement(AppointmentStatus::cases()),
            'category_type' => $this->faker->randomElement(AppointmentCategoryEnum::cases()),

            'appointment_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

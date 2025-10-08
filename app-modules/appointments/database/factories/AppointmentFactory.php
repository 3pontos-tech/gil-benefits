<?php

namespace TresPontosTech\Appointments\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Vouchers\Models\Voucher;

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

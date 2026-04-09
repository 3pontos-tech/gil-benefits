<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\assertDatabaseHas;

it('creates appointment with pending status', function (): void {
    $user = User::factory()->create();
    $date = Date::now()->addDays(3);

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: $date->copy()->setTime(10, 0),
    );

    resolve(BookAppointmentAction::class)->handle($dto);

    assertDatabaseHas(Appointment::class, [
        'user_id' => $user->getKey(),
        'status' => AppointmentStatus::Pending->value,
        'category_type' => AppointmentCategoryEnum::PersonalFinance->value,
    ]);
});

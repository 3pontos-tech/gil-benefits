<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Facades\Zap;

use function Pest\Laravel\assertDatabaseHas;

it('creates appointment when slot is available', function (): void {
    $user = User::factory()->create();
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: $date->copy()->setTime(10, 0),
    );

    resolve(BookAppointmentAction::class)->handle($dto);

    assertDatabaseHas(Appointment::class, [
        'user_id' => $user->getKey(),
        'status' => AppointmentStatus::Pending->value,
    ]);
});

it('throws SlotUnavailableException when no consultant is available', function (): void {
    $user = User::factory()->create();
    $date = Date::now()->addDays(3);

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: $date->copy()->setTime(10, 0),
    );

    resolve(BookAppointmentAction::class)->handle($dto);
})->throws(SlotUnavailableException::class);

it('does not create appointment when slot is unavailable', function (): void {
    $user = User::factory()->create();
    $date = Date::now()->addDays(3);

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: $date->copy()->setTime(10, 0),
    );

    try {
        resolve(BookAppointmentAction::class)->handle($dto);
    } catch (SlotUnavailableException) {
        // expected
    }

    expect(Appointment::count())->toBe(0);
});

<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Mail\AppointmentRequestedAdminMail;
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

it('queues AppointmentRequestedAdminMail to the configured recipients on booking', function (): void {
    Mail::fake();
    config(['appointments.admin_notification_recipients' => ['atendimento@flammabeneficios.com.br', 'renan@firece.com.br']]);

    $user = User::factory()->create();

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: Date::now()->addDays(3)->setTime(10, 0),
    );

    resolve(BookAppointmentAction::class)->handle($dto);

    Mail::assertQueued(
        AppointmentRequestedAdminMail::class,
        fn (AppointmentRequestedAdminMail $mail): bool => $mail->hasTo('atendimento@flammabeneficios.com.br')
            && $mail->hasTo('renan@firece.com.br'),
    );
});

it('does not queue AppointmentRequestedAdminMail when no recipients are configured', function (): void {
    Mail::fake();
    config(['appointments.admin_notification_recipients' => []]);

    $user = User::factory()->create();

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: Date::now()->addDays(3)->setTime(10, 0),
    );

    resolve(BookAppointmentAction::class)->handle($dto);

    Mail::assertNotQueued(AppointmentRequestedAdminMail::class);
});

it('still creates the appointment when the admin notification fails to queue', function (): void {
    Exceptions::fake();
    config(['appointments.admin_notification_recipients' => ['atendimento@flammabeneficios.com.br']]);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('mail broker unavailable'));

    $user = User::factory()->create();

    $dto = new BookAppointmentDTO(
        userId: $user->getKey(),
        categoryType: AppointmentCategoryEnum::PersonalFinance,
        appointmentAt: Date::now()->addDays(3)->setTime(10, 0),
    );

    resolve(BookAppointmentAction::class)->handle($dto);

    assertDatabaseHas(Appointment::class, [
        'user_id' => $user->getKey(),
        'status' => AppointmentStatus::Pending->value,
    ]);
    Exceptions::assertReported(RuntimeException::class);
});

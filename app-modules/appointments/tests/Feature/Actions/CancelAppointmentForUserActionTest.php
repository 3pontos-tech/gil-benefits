<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Appointments\Actions\CancelAppointmentForUserAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCancelledNotification;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

beforeEach(function (): void {
    Mail::fake();
    $this->travelTo('2026-10-07 10:00:00');
    $this->user = actingAsEmployee();
    filament()->setTenant(null);
});

function creditInUseFor(User $user, Appointment $appointment): UserCredit
{
    return UserCredit::factory()->inUse()->create([
        'owner_id' => $user->getKey(),
        'holder_id' => $user->getKey(),
        'company_id' => $user->employerCompanyId(),
        'appointment_id' => $appointment->getKey(),
    ]);
}

it('cancels on time and returns the credit', function (): void {
    $appointment = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHours(5));
    $credit = creditInUseFor($this->user, $appointment);

    resolve(CancelAppointmentForUserAction::class)->handle($appointment, $this->user);

    $credit->refresh();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($credit->status)->toBe(UserCreditStatusEnum::Available)
        ->and($credit->appointment_id)->toBeNull();

    Mail::assertQueued(AppointmentCancelledMail::class);
});

it('cancels late as cancelled_late and spends the credit', function (): void {
    $appointment = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHour());
    $credit = creditInUseFor($this->user, $appointment);

    resolve(CancelAppointmentForUserAction::class)->handle($appointment, $this->user);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate)
        ->and($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);

    Mail::assertQueued(AppointmentUserCancelledLateMail::class);
});

it('refuses an appointment of another user', function (): void {
    $appointment = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHours(5));
    $other = User::factory()->employee()->create();

    expect(fn () => resolve(CancelAppointmentForUserAction::class)->handle($appointment, $other))
        ->toThrow(AppointmentStateException::class);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
});

it('refuses a past or terminal appointment instead of leaking InvalidTransitionException', function (AppointmentStatus $status, int $hoursFromNow): void {
    $appointment = appointmentFor($this->user, $status, now()->addHours($hoursFromNow));

    expect(fn () => resolve(CancelAppointmentForUserAction::class)->handle($appointment, $this->user))
        ->toThrow(AppointmentStateException::class);

    Mail::assertNothingQueued();
})->with([
    'pending in the past' => [AppointmentStatus::Pending, -1],
    'already cancelled' => [AppointmentStatus::Cancelled, 5],
]);

/**
 * Dois cancelamentos simultâneos: os dois carregaram o encontro ainda pendente. Com a trava, o
 * segundo relê o estado sob a linha travada e é recusado.
 */
it('cancels only once when two requests loaded the appointment before either cancelled', function (): void {
    Notification::fake();
    $appointment = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHours(5));
    $first = Appointment::query()->findOrFail($appointment->id);
    $second = Appointment::query()->findOrFail($appointment->id);

    resolve(CancelAppointmentForUserAction::class)->handle($first, $this->user);

    expect($second->status)->toBe(AppointmentStatus::Pending)
        ->and(fn () => resolve(CancelAppointmentForUserAction::class)->handle($second, $this->user))
        ->toThrow(AppointmentStateException::class);

    Mail::assertQueuedCount(1);
    Notification::assertSentToTimes($this->user, AppointmentCancelledNotification::class, 1);
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('updates the instance it received, which the panel reads for the confirmation', function (): void {
    $appointment = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHour());

    $returned = resolve(CancelAppointmentForUserAction::class)->handle($appointment, $this->user);

    expect($returned)->toBe($appointment)
        ->and($appointment->status)->toBe(AppointmentStatus::CancelledLate);
});

it('locks the appointment row before checking it', function (): void {
    $appointment = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHours(5));
    $statements = [];
    DB::listen(function (QueryExecuted $query) use (&$statements): void {
        $statements[] = $query->sql;
    });

    resolve(CancelAppointmentForUserAction::class)->handle($appointment, $this->user);

    $lock = collect($statements)->search(fn (string $sql): bool => str_starts_with($sql, 'select') && str_contains($sql, '"appointments"') && str_contains($sql, 'limit 1'));
    $update = collect($statements)->search(fn (string $sql): bool => str_starts_with($sql, 'update "appointments"'));

    expect($lock)->toBeInt()->and($update)->toBeInt()->and($lock)->toBeLessThan($update);
});

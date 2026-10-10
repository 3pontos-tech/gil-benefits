<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Actions\ScheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\BookingBlockedException;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $this->user = actingAsEmployee();
    filament()->setTenant(null);
});

function exhaustQuotaOf(User $user): void
{
    appointmentFor($user, AppointmentStatus::Completed, now()->subDays(3));
}

it('books a pending appointment on a bookable slot and returns it', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    $appointment = resolve(ScheduleAppointmentForUserAction::class)
        ->handle($this->user, 'personal_finance', '2026-10-09 09:00:00');

    expect($appointment->status)->toBe(AppointmentStatus::Pending)
        ->and($appointment->appointment_at->toDateTimeString())->toBe('2026-10-09 09:00:00')
        ->and($appointment->company_id)->toBe($this->user->employerCompanyId())
        ->and($appointment->consultant_id)->toBeNull();
});

it('normalises an ISO time in UTC to the application timezone', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    $appointment = resolve(ScheduleAppointmentForUserAction::class)
        ->handle($this->user, 'personal_finance', '2026-10-09T12:00:00.000Z');

    expect($appointment->appointment_at->toDateTimeString())->toBe('2026-10-09 09:00:00');
});

it('refuses when the user cannot book and names the reasons', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));
    exhaustQuotaOf($this->user);
    $user = $this->user->fresh();

    try {
        resolve(ScheduleAppointmentForUserAction::class)->handle($user, 'personal_finance', '2026-10-09 09:00:00');
        $this->fail('BookingBlockedException was not thrown');
    } catch (BookingBlockedException $bookingBlockedException) {
        expect($bookingBlockedException->reasons)->toBe([__('appointments::resources.appointments.booking_block.no_appointments_available')]);
    }

    expect(Appointment::query()->where('status', AppointmentStatus::Pending)->count())->toBe(0);
});

it('checks the allowance before the slot', function (): void {
    exhaustQuotaOf($this->user);

    expect(fn () => resolve(ScheduleAppointmentForUserAction::class)->handle($this->user->fresh(), 'personal_finance', 'garbage'))
        ->toThrow(BookingBlockedException::class);
});

it('refuses a slot before the booking lead', function (): void {
    consultantAvailableOn(Date::parse('2026-10-08'));

    expect(fn () => resolve(ScheduleAppointmentForUserAction::class)->handle($this->user, 'personal_finance', '2026-10-08 09:00:00'))
        ->toThrow(SlotUnavailableException::class);
});

it('refuses a slot outside the availability', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    expect(fn () => resolve(ScheduleAppointmentForUserAction::class)->handle($this->user, 'personal_finance', '2026-10-09 03:00:00'))
        ->toThrow(SlotUnavailableException::class);
});

it('refuses a malformed or missing appointment time', function (?string $value): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    expect(fn () => resolve(ScheduleAppointmentForUserAction::class)->handle($this->user, 'personal_finance', $value))
        ->toThrow(SlotUnavailableException::class);
})->with([
    'null' => [null],
    'garbage' => ['not-a-datetime'],
]);

it('consumes a standalone credit once the quota is spent', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));
    exhaustQuotaOf($this->user);
    $credit = standaloneCreditFor($this->user);

    $appointment = resolve(ScheduleAppointmentForUserAction::class)
        ->handle($this->user->fresh(), 'personal_finance', '2026-10-09 09:00:00');

    $credit->refresh();

    expect($credit->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($credit->appointment_id)->toBe($appointment->getKey());
});

it('checks the allowance against the database, not the quota cached on the given user', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    expect($this->user->monthly_appointments_left)->toBe(1);

    exhaustQuotaOf($this->user);

    expect(fn () => resolve(ScheduleAppointmentForUserAction::class)
        ->handle($this->user, 'personal_finance', '2026-10-09 09:00:00'))
        ->toThrow(BookingBlockedException::class);

    expect(Appointment::query()->where('status', AppointmentStatus::Pending)->count())->toBe(0);
});

it('sees a booking committed by a concurrent request while it waited for the lock', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));
    $user = $this->user;
    $competitorBooked = false;

    DB::listen(function (QueryExecuted $query) use ($user, &$competitorBooked): void {
        if ($competitorBooked || DB::transactionLevel() === 0 || ! str_contains($query->sql, 'from "users"')) {
            return;
        }

        $competitorBooked = true;
        appointmentFor($user, AppointmentStatus::Pending, Date::parse('2026-10-09 10:00:00'));
    });

    expect(fn () => resolve(ScheduleAppointmentForUserAction::class)
        ->handle($user, 'personal_finance', '2026-10-09 09:00:00'))
        ->toThrow(BookingBlockedException::class);

    expect($competitorBooked)->toBeTrue()
        ->and(Appointment::query()->where('appointment_at', '2026-10-09 09:00:00')->exists())->toBeFalse();
});

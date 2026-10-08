<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Exceptions;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Actions\RescheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use Zap\Facades\Zap;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $this->user = actingAsEmployee();
    filament()->setTenant(null);
    $this->appointment = appointmentFor($this->user, AppointmentStatus::Pending, Date::parse('2026-10-10 14:00:00'));
});

it('moves the appointment and records the user as the history actor', function (): void {
    consultantAvailableOn(Date::parse('2026-10-12'));

    $outcome = resolve(RescheduleAppointmentForUserAction::class)
        ->handle($this->appointment, $this->user, '2026-10-12 10:00:00');

    $history = AppointmentHistory::query()
        ->where('appointment_id', $this->appointment->getKey())
        ->where('action_type', AppointmentHistoryActionType::ReScheduled)
        ->firstOrFail();

    expect($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-12 10:00:00')
        ->and($history->actor_type)->toBe(AppointmentHistoryActor::User)
        ->and($history->actor_id)->toBe($this->user->getKey())
        ->and($outcome->calendarSynced)->toBeTrue();
});

it('refuses an appointment of another user', function (): void {
    consultantAvailableOn(Date::parse('2026-10-12'));
    $other = User::factory()->employee()->create();

    expect(fn () => resolve(RescheduleAppointmentForUserAction::class)->handle($this->appointment, $other, '2026-10-12 10:00:00'))
        ->toThrow(AppointmentStateException::class);

    expect($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-10 14:00:00');
});

it('refuses inside the notice window', function (): void {
    consultantAvailableOn(Date::parse('2026-10-12'));
    $soon = appointmentFor($this->user, AppointmentStatus::Pending, now()->addHours(3));

    expect(fn () => resolve(RescheduleAppointmentForUserAction::class)->handle($soon, $this->user, '2026-10-12 10:00:00'))
        ->toThrow(AppointmentStateException::class);
});

it('refuses a slot outside the availability and keeps the time', function (): void {
    consultantAvailableOn(Date::parse('2026-10-12'));

    expect(fn () => resolve(RescheduleAppointmentForUserAction::class)->handle($this->appointment, $this->user, '2026-10-12 03:00:00'))
        ->toThrow(SlotUnavailableException::class);

    expect($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-10 14:00:00');
});

it('rethrows when the current consultant is busy and the sync already reverted', function (): void {
    $originalAt = Date::parse('2026-10-10 14:00:00');
    $targetAt = Date::parse('2026-10-12 10:00:00');
    $consultant = consultantAvailableOn($originalAt, $targetAt);
    consultantAvailableOn($targetAt);

    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->recycle($consultant)
        ->create([
            'user_id' => $this->user->getKey(),
            'company_id' => $this->user->employerCompanyId(),
            'appointment_at' => $originalAt,
        ]);
    resolve(AssignConsultantAction::class)->handle($appointment);

    Zap::for($consultant)
        ->named('Busy')
        ->appointment()
        ->from($targetAt->toDateString())
        ->to($targetAt->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    expect(fn () => resolve(RescheduleAppointmentForUserAction::class)->handle($appointment, $this->user, $targetAt->toDateTimeString()))
        ->toThrow(SlotUnavailableException::class);

    $fresh = $appointment->refresh();

    expect($fresh->appointment_at->toDateTimeString())->toBe($originalAt->toDateTimeString())
        ->and($fresh->consultant_id)->toBe($consultant->getKey())
        ->and($fresh->status)->toBe(AppointmentStatus::Active)
        ->and(AppointmentHistory::query()
            ->where('appointment_id', $appointment->getKey())
            ->where('action_type', AppointmentHistoryActionType::ReScheduled)
            ->exists())->toBeFalse();
});

it('restores the appointment and rethrows without reporting when the sync fails unexpectedly', function (): void {
    Exceptions::fake();
    consultantAvailableOn(Date::parse('2026-10-12'));

    app()->instance(SyncAppointmentScheduleAction::class, new class
    {
        public function handle(): bool
        {
            throw new RuntimeException('boom');
        }
    });

    expect(fn () => resolve(RescheduleAppointmentForUserAction::class)->handle($this->appointment, $this->user, '2026-10-12 10:00:00'))
        ->toThrow(RuntimeException::class);

    Exceptions::assertNotReported(RuntimeException::class);

    expect($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-10 14:00:00');
});

it('reports a failed calendar sync in the outcome', function (): void {
    consultantAvailableOn(Date::parse('2026-10-12'));

    app()->instance(SyncAppointmentScheduleAction::class, new class
    {
        public function handle(): bool
        {
            return false;
        }
    });

    $outcome = resolve(RescheduleAppointmentForUserAction::class)->handle($this->appointment, $this->user, '2026-10-12 10:00:00');

    expect($outcome->calendarSynced)->toBeFalse()
        ->and($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-12 10:00:00');
});

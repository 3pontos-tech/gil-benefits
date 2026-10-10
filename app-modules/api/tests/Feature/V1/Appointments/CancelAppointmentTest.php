<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;

beforeEach(function (): void {
    Mail::fake();
    $this->travelTo('2026-10-07 10:00:00');
    $this->employee = actingAsApiEmployee();
});

it('cancels on time, returns the credit and answers cancelled', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(5));
    $credit = standaloneCreditFor($this->employee, ['status' => UserCreditStatusEnum::InUse, 'appointment_id' => $appointment->id]);

    deleteJson(route('api.v1.appointments.destroy', $appointment))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.can_cancel', false)
        ->assertJsonPath('data.cancel_impact', null);

    $credit->refresh();

    expect($credit->status)->toBe(UserCreditStatusEnum::Available)
        ->and($credit->appointment_id)->toBeNull();
    Mail::assertQueued(AppointmentCancelledMail::class);
});

it('cancels late, spends the credit and answers cancelled_late', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHour());
    $credit = standaloneCreditFor($this->employee, ['status' => UserCreditStatusEnum::InUse, 'appointment_id' => $appointment->id]);

    getJson(route('api.v1.appointments.show', $appointment))->assertJsonPath('data.cancel_impact', 'loses_credit');

    deleteJson(route('api.v1.appointments.destroy', $appointment))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled_late');

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
    Mail::assertQueued(AppointmentUserCancelledLateMail::class);
});

it('frees the monthly quota on an on-time cancellation', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(5));
    Sanctum::actingAs($this->employee->fresh(), ['employee']);

    expect($this->employee->fresh()->monthly_appointments_left)->toBe(0);

    deleteJson(route('api.v1.appointments.destroy', $appointment))->assertOk();

    expect($this->employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('refuses a past appointment with appointment', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->subHour());

    deleteJson(route('api.v1.appointments.destroy', $appointment))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment' => 'Este agendamento não pode mais ser cancelado.']);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
});

it('refuses an already cancelled appointment', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Cancelled, now()->addHours(5));

    deleteJson(route('api.v1.appointments.destroy', $appointment))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment']);
});

it('hides the meeting url once cancelled even before the calendar job runs', function (): void {
    Bus::fake();
    $appointment = appointmentFor($this->employee, AppointmentStatus::Active, now()->addHours(5), [
        'google_event_id' => 'evt',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    deleteJson(route('api.v1.appointments.destroy', $appointment))
        ->assertOk()
        ->assertJsonPath('data.meeting_url', null);

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('answers 404 for another employee appointment', function (): void {
    $other = appointmentFor(User::factory()->employee()->create(), AppointmentStatus::Pending, Date::parse('2026-10-10 14:00:00'));

    deleteJson(route('api.v1.appointments.destroy', $other))->assertNotFound();
});

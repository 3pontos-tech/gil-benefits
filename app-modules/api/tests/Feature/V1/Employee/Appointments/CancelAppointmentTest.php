<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Api\Enums\TokenAbility;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Mail::fake();
    $this->travelTo('2026-10-07 10:00:00');
    $this->employee = actingAsApiEmployee();
});

it('cancels on time, returns the credit and answers cancelled', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(5));
    $credit = standaloneCreditFor($this->employee, ['status' => UserCreditStatusEnum::InUse, 'appointment_id' => $appointment->id]);

    postJson(route('api.v1.appointments.cancel', $appointment))
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

    postJson(route('api.v1.appointments.cancel', $appointment))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled_late');

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used);
    Mail::assertQueued(AppointmentUserCancelledLateMail::class);
});

it('frees the monthly quota on an on-time cancellation', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(5));
    Sanctum::actingAs($this->employee->fresh(), [TokenAbility::Employee->value]);

    expect($this->employee->fresh()->monthly_appointments_left)->toBe(0);

    postJson(route('api.v1.appointments.cancel', $appointment))->assertOk();

    expect($this->employee->fresh()->monthly_appointments_left)->toBe(1);
});

it('refuses a past appointment with appointment', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->subHour());

    postJson(route('api.v1.appointments.cancel', $appointment))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment' => 'Este agendamento não pode mais ser cancelado.']);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
});

it('refuses an already cancelled appointment', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Cancelled, now()->addHours(5));

    postJson(route('api.v1.appointments.cancel', $appointment))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment']);
});

it('hides the meeting url once cancelled even before the calendar job runs', function (): void {
    Bus::fake();
    $appointment = appointmentFor($this->employee, AppointmentStatus::Active, now()->addHours(5), [
        'google_event_id' => 'evt',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    postJson(route('api.v1.appointments.cancel', $appointment))
        ->assertOk()
        ->assertJsonPath('data.meeting_url', null);

    Bus::assertDispatched(DeleteAppointmentCalendarEventJob::class);
});

it('answers 404 for another employee appointment', function (): void {
    $other = appointmentFor(User::factory()->employee()->create(), AppointmentStatus::Pending, Date::parse('2026-10-10 14:00:00'));

    postJson(route('api.v1.appointments.cancel', $other))->assertNotFound();
});

it('cancels on time when the app confirmed the credit coming back', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(5));
    $credit = standaloneCreditFor($this->employee, ['status' => UserCreditStatusEnum::InUse, 'appointment_id' => $appointment->id]);

    postJson(route('api.v1.appointments.cancel', $appointment), ['expected_impact' => 'returns_credit'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('refuses with expected_impact when the 4 hours ran out between the alert and the request', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(4)->addSeconds(3));
    $credit = standaloneCreditFor($this->employee, ['status' => UserCreditStatusEnum::InUse, 'appointment_id' => $appointment->id]);

    getJson(route('api.v1.appointments.show', $appointment))->assertJsonPath('data.cancel_impact', 'returns_credit');

    // The app holds the request for its 6 s undo window.
    $this->travel(6)->seconds();

    postJson(route('api.v1.appointments.cancel', $appointment), ['expected_impact' => 'returns_credit'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expected_impact' => 'Faltam menos de 4 horas para o encontro: cancelar agora consome o crédito.']);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending)
        ->and($credit->refresh()->status)->toBe(UserCreditStatusEnum::InUse);
    Mail::assertNothingQueued();
    getJson(route('api.v1.appointments.show', $appointment))->assertJsonPath('data.cancel_impact', 'loses_credit');
});

it('cancels whatever the app expected when the outcome is no worse', function (string $expected, int $hoursAhead, string $status, UserCreditStatusEnum $creditStatus): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours($hoursAhead));
    $credit = standaloneCreditFor($this->employee, ['status' => UserCreditStatusEnum::InUse, 'appointment_id' => $appointment->id]);

    postJson(route('api.v1.appointments.cancel', $appointment), ['expected_impact' => $expected])
        ->assertOk()
        ->assertJsonPath('data.status', $status);

    expect($credit->refresh()->status)->toBe($creditStatus);
})->with([
    'accepted the loss, still on time' => ['loses_credit', 5, 'cancelled', UserCreditStatusEnum::Available],
    'accepted the loss, inside the 4 hours' => ['loses_credit', 1, 'cancelled_late', UserCreditStatusEnum::Used],
]);

it('rejects an unknown expected_impact', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(5));

    postJson(route('api.v1.appointments.cancel', $appointment), ['expected_impact' => 'maybe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expected_impact']);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
});

it('still cancels through the deprecated DELETE route of the published v1', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(4)->addSeconds(3));

    $this->travel(6)->seconds();

    deleteJson(route('api.v1.appointments.destroy', ['appointment' => $appointment, 'expected_impact' => 'returns_credit']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['expected_impact']);

    deleteJson(route('api.v1.appointments.destroy', $appointment))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled_late');
});

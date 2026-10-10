<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use Zap\Facades\Zap;

use function Pest\Laravel\patchJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $this->employee = actingAsApiEmployee();
    $this->appointment = appointmentFor($this->employee, AppointmentStatus::Pending, Date::parse('2026-10-10 14:00:00'));
    consultantAvailableOn(Date::parse('2026-10-12'));
    $this->newAt = '2026-10-12T10:00:00-03:00';
});

it('moves a pending appointment and records the employee as the history actor', function (): void {
    patchJson(route('api.v1.appointments.update', $this->appointment), ['appointment_at' => $this->newAt])
        ->assertOk()
        ->assertJsonPath('data.appointment_at', '2026-10-12T10:00:00-03:00');

    $history = AppointmentHistory::query()
        ->where('appointment_id', $this->appointment->getKey())
        ->where('action_type', AppointmentHistoryActionType::ReScheduled->value)
        ->sole();

    expect($history->actor_id)->toBe($this->employee->getKey())
        ->and($history->actor_type)->toBe(AppointmentHistoryActor::User);
});

it('keeps the credit reserved', function (): void {
    $credit = standaloneCreditFor($this->employee, [
        'status' => UserCreditStatusEnum::InUse,
        'appointment_id' => $this->appointment->getKey(),
    ]);

    patchJson(route('api.v1.appointments.update', $this->appointment), ['appointment_at' => $this->newAt])->assertOk();

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::InUse);
});

it('refuses inside the notice window with appointment', function (): void {
    $close = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addHours(3));
    $originalAt = $close->appointment_at->toDateTimeString();

    patchJson(route('api.v1.appointments.update', $close), ['appointment_at' => $this->newAt])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment' => 'Este agendamento não pode mais ser reagendado.']);

    expect($close->refresh()->appointment_at->toDateTimeString())->toBe($originalAt);
});

it('refuses a slot outside the availability with appointment_at', function (): void {
    patchJson(route('api.v1.appointments.update', $this->appointment), ['appointment_at' => '2026-10-12T03:00:00-03:00'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment_at']);

    expect($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-10 14:00:00');
});

it('drops a busy consultant and returns the appointment as pending', function (): void {
    $originalAt = Date::parse('2026-10-10 14:00:00');
    $targetAt = Date::parse('2026-10-12 10:00:00');
    $consultant = consultantAvailableOn($originalAt, $targetAt);

    $active = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->recycle($consultant)
        ->create([
            'user_id' => $this->employee->getKey(),
            'company_id' => $this->employee->employerCompanyId(),
            'appointment_at' => $originalAt,
        ]);
    $this->appointment->delete();
    resolve(AssignConsultantAction::class)->handle($active);

    Zap::for($consultant)
        ->named('Busy')
        ->appointment()
        ->from($targetAt->toDateString())
        ->to($targetAt->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    patchJson(route('api.v1.appointments.update', $active), ['appointment_at' => $this->newAt])
        ->assertOk()
        ->assertJsonPath('data.appointment_at', $this->newAt)
        ->assertJsonPath('data.status', AppointmentStatus::Pending->value)
        ->assertJsonPath('data.consultant', null);

    $fresh = $active->refresh();

    expect($fresh->appointment_at->toDateTimeString())->toBe($targetAt->toDateTimeString())
        ->and($fresh->consultant_id)->toBeNull()
        ->and($fresh->status)->toBe(AppointmentStatus::Pending);
});

it('refuses an appointment_at before the lead with the lead message', function (): void {
    patchJson(route('api.v1.appointments.update', $this->appointment), ['appointment_at' => '2026-10-08T09:00:00-03:00'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment_at' => 'Escolha um horário com pelo menos 2 dias de antecedência.']);
});

it('validates appointment_at', function (mixed $value): void {
    patchJson(route('api.v1.appointments.update', $this->appointment), ['appointment_at' => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment_at']);
})->with(['missing' => [null], 'not a date' => ['not-a-date']]);

it('normalises a UTC appointment_at to the application timezone', function (): void {
    patchJson(route('api.v1.appointments.update', $this->appointment), ['appointment_at' => '2026-10-12T13:00:00.000Z'])
        ->assertOk()
        ->assertJsonPath('data.appointment_at', '2026-10-12T10:00:00-03:00');

    expect($this->appointment->refresh()->appointment_at->toDateTimeString())->toBe('2026-10-12 10:00:00');
});

it('answers 404 for another employee appointment', function (): void {
    $other = appointmentFor(User::factory()->employee()->create(), AppointmentStatus::Pending, Date::parse('2026-10-10 14:00:00'));

    patchJson(route('api.v1.appointments.update', $other), ['appointment_at' => $this->newAt])->assertNotFound();
});

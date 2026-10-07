<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $this->employee = actingAsApiEmployee();
    consultantAvailableOn(Date::parse('2026-10-09'));
    $this->payload = ['category_type' => 'personal_finance', 'appointment_at' => '2026-10-09T09:00:00-03:00'];
});

it('books a pending appointment with the monthly quota and leaves credits untouched', function (): void {
    $credit = standaloneCreditFor($this->employee);

    postJson(route('api.v1.appointments.store'), $this->payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.consultant', null)
        ->assertJsonPath('data.appointment_at', '2026-10-09T09:00:00-03:00')
        ->assertJsonPath('data.can_cancel', true)
        ->assertJsonPath('data.cancel_impact', 'returns_credit')
        ->assertJsonPath('data.materials', []);

    assertDatabaseHas(Appointment::class, [
        'user_id' => $this->employee->getKey(),
        'company_id' => $this->employee->employerCompanyId(),
        'status' => AppointmentStatus::Pending->value,
    ]);
    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('normalises a UTC appointment_at to the application timezone', function (): void {
    postJson(route('api.v1.appointments.store'), [...$this->payload, 'appointment_at' => '2026-10-09T12:00:00.000Z'])
        ->assertCreated()
        ->assertJsonPath('data.appointment_at', '2026-10-09T09:00:00-03:00');

    expect(Appointment::query()->sole()->appointment_at->toDateTimeString())->toBe('2026-10-09 09:00:00');
});

it('consumes a standalone credit once the quota is spent', function (): void {
    appointmentFor($this->employee, AppointmentStatus::Completed, now()->subDays(3));
    $credit = standaloneCreditFor($this->employee);
    Sanctum::actingAs($this->employee->fresh(), ['employee']);

    $response = postJson(route('api.v1.appointments.store'), $this->payload)->assertCreated();

    $credit->refresh();

    expect($credit->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($credit->appointment_id)->toBe($response->json('data.id'));
});

it('refuses with credit when there is neither quota nor credit', function (): void {
    appointmentFor($this->employee, AppointmentStatus::Completed, now()->subDays(3));
    Sanctum::actingAs($this->employee->fresh(), ['employee']);

    postJson(route('api.v1.appointments.store'), $this->payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credit' => 'Você não possui agendamentos disponíveis neste mês.']);

    expect(Appointment::query()->where('status', AppointmentStatus::Pending->value)->exists())->toBeFalse();
});

it('refuses with credit while another appointment is open', function (): void {
    appointmentFor($this->employee, AppointmentStatus::Pending, now()->addWeek());
    Sanctum::actingAs($this->employee->fresh(), ['employee']);

    postJson(route('api.v1.appointments.store'), $this->payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credit' => 'Você possui uma consultoria em andamento. Finalize a anterior para agendar outra.']);
});

it('refuses an appointment_at before the lead', function (): void {
    consultantAvailableOn(Date::parse('2026-10-08'));

    postJson(route('api.v1.appointments.store'), [...$this->payload, 'appointment_at' => '2026-10-08T09:00:00-03:00'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment_at' => 'Escolha um horário com pelo menos 2 dias de antecedência.']);
});

it('refuses an appointment_at outside the availability', function (): void {
    postJson(route('api.v1.appointments.store'), [...$this->payload, 'appointment_at' => '2026-10-09T03:00:00-03:00'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment_at' => 'Este horário não está mais disponível. Por favor, selecione outro.']);
});

it('validates category_type, appointment_at and notes', function (string $field, mixed $value): void {
    postJson(route('api.v1.appointments.store'), [...$this->payload, $field => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'category_type' => ['category_type', 'cooking'],
    'appointment_at' => ['appointment_at', 'not-a-date'],
    'notes' => ['notes', str_repeat('a', 1001)],
]);

it('stores the notes', function (): void {
    postJson(route('api.v1.appointments.store'), [...$this->payload, 'notes' => 'Quero falar de dívidas.'])
        ->assertCreated()
        ->assertJsonPath('data.notes', 'Quero falar de dívidas.');
});

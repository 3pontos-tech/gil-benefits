<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\Appointments\Models\AppointmentRecord;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Laravel\getJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $this->employee = actingAsApiEmployee();
});

it('exposes every key the app reads from Appointment', function (): void {
    $consultant = Consultant::factory()->create();
    $appointment = appointmentFor($this->employee, AppointmentStatus::Completed, now()->subDays(2), [
        'consultant_id' => $consultant->getKey(),
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
    ]);
    AppointmentFeedback::factory()->create(['appointment_id' => $appointment->id, 'user_id' => $this->employee->id]);
    $record = AppointmentRecord::factory()->published()->create(['appointment_id' => $appointment->id]);

    getJson(route('api.v1.appointments.show', $appointment))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'id', 'category_type', 'category_label', 'appointment_at', 'duration_minutes', 'status', 'meeting_url',
            'consultant' => ['id', 'name', 'avatar_url'],
            'notes',
            'feedback' => ['rating', 'comment'],
            'record' => ['published_at', 'content'],
            'can_reschedule', 'can_cancel', 'cancel_impact', 'materials', 'created_at',
        ]])
        ->assertJsonPath('data.category_label', 'Finanças pessoais')
        ->assertJsonPath('data.consultant.id', $consultant->id)
        ->assertJsonPath('data.consultant.name', $consultant->name)
        ->assertJsonPath('data.record.content', $record->content)
        ->assertJsonPath('data.record.published_at', '2026-10-07T10:00:00-03:00')
        ->assertJsonPath('data.materials', [])
        ->assertJsonPath('data.consultant.avatar_url', null)
        ->assertJsonPath('data.duration_minutes', 60);
});

it('derives meeting_url, can_cancel, can_reschedule and cancel_impact from the state', function (
    AppointmentStatus $status,
    float $hoursFromNow,
    bool $exposesUrl,
    bool $canCancel,
    bool $canReschedule,
    ?string $impact,
): void {
    $url = 'https://meet.google.com/abc-defg-hij';
    $appointment = appointmentFor($this->employee, $status, now()->addMinutes((int) ($hoursFromNow * 60)), ['meeting_url' => $url]);

    getJson(route('api.v1.appointments.show', $appointment))
        ->assertOk()
        ->assertJsonPath('data.meeting_url', $exposesUrl ? $url : null)
        ->assertJsonPath('data.can_cancel', $canCancel)
        ->assertJsonPath('data.can_reschedule', $canReschedule)
        ->assertJsonPath('data.cancel_impact', $impact);
})->with([
    'active in 5h' => [AppointmentStatus::Active, 5, true, true, true, 'returns_credit'],
    'active in 1h' => [AppointmentStatus::Active, 1, true, true, false, 'loses_credit'],
    'active started 30min ago' => [AppointmentStatus::Active, -0.5, true, false, false, null],
    'active already finished' => [AppointmentStatus::Active, -2, false, false, false, null],
    'pending in 5h' => [AppointmentStatus::Pending, 5, false, true, true, 'returns_credit'],
    'completed 24h ago' => [AppointmentStatus::Completed, -24, false, false, false, null],
]);

it('hides an unpublished record', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Completed, now()->subDays(2));
    AppointmentRecord::factory()->draft()->create(['appointment_id' => $appointment->id]);

    getJson(route('api.v1.appointments.show', $appointment))
        ->assertOk()
        ->assertJsonPath('data.record', null);
});

it('formats dates as the app expects', function (): void {
    $appointment = appointmentFor($this->employee, AppointmentStatus::Pending, now()->addDays(3));

    $data = getJson(route('api.v1.appointments.show', $appointment))->assertOk()->json('data');

    expect($data['appointment_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/')
        ->and($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

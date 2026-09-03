<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\AssignConsultantAction;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Pages\ListAppointments;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();

    $this->employee = actingAsSubscribedEmployee();
    $this->originalAt = today()->addDays(3)->setTime(14, 0);
    $this->targetAt = today()->addDays(5)->setTime(10, 0);
});

/**
 * A consultant bookable 08:00-18:00 on the given days. Zap only yields slots for a
 * day-scoped availability window, so each day is declared on its own.
 */
function availableConsultant(CarbonInterface ...$days): Consultant
{
    $consultant = Consultant::factory()->create(['email' => fake()->unique()->companyEmail()]);

    foreach ($days as $day) {
        Zap::for($consultant)
            ->named('Availability')
            ->availability()
            ->from($day->toDateString())
            ->to($day->copy()->addDay()->toDateString())
            ->addPeriod('08:00', '18:00')
            ->save();
    }

    return $consultant;
}

/** Books the consultant's agenda for real, the way the assignment flow does. */
function appointmentWithBookedAgenda(Consultant $consultant, CarbonInterface $at, string $userId): Appointment
{
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->recycle($consultant)
        ->create(['user_id' => $userId, 'appointment_at' => $at]);

    resolve(AssignConsultantAction::class)->handle($appointment);

    return $appointment;
}

function isFreeAt(Consultant $consultant, CarbonInterface $at): bool
{
    return $consultant->isBookableAtTime(
        $at->format('Y-m-d'),
        $at->format('H:i'),
        $at->copy()->addHour()->format('H:i'),
    );
}

function reschedule(Appointment $appointment, CarbonInterface $to): void
{
    livewire(ListAppointments::class)
        ->callTableAction('reschedule-appointment', $appointment, [
            'date' => $to->toDateString(),
            'appointment_at' => $to->toDateTimeString(),
        ]);
}

// ---------------------------------------------------------------------------
// Visibility
// ---------------------------------------------------------------------------

it('shows the reschedule action while the window is open', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->withStatus($status)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => $this->originalAt]);

    livewire(ListAppointments::class)
        ->assertTableActionVisible('reschedule-appointment', $appointment);
})->with([
    'Pending' => AppointmentStatus::Pending,
    'Active' => AppointmentStatus::Active,
]);

it('hides the reschedule action inside the reschedule window', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addHour()]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('reschedule-appointment', $appointment);
});

it('hides the reschedule action on terminal statuses', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->withStatus($status)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => $this->originalAt]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('reschedule-appointment', $appointment);
})->with([
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
    'NoShow' => AppointmentStatus::NoShow,
]);

// ---------------------------------------------------------------------------
// Consultant still available — kept
// ---------------------------------------------------------------------------

it('keeps the consultant when they are free at the new time', function (): void {
    $consultant = availableConsultant($this->originalAt, $this->targetAt);
    $appointment = appointmentWithBookedAgenda($consultant, $this->originalAt, $this->employee->id);

    reschedule($appointment, $this->targetAt);

    $fresh = $appointment->refresh();

    expect($fresh->consultant_id)->toBe($consultant->getKey())
        ->and($fresh->status)->toBe(AppointmentStatus::Active)
        ->and($fresh->appointment_at->toDateTimeString())->toBe($this->targetAt->toDateTimeString());
});

it('frees the old slot and blocks the new one when the consultant is kept', function (): void {
    $consultant = availableConsultant($this->originalAt, $this->targetAt);
    $appointment = appointmentWithBookedAgenda($consultant, $this->originalAt, $this->employee->id);

    expect(isFreeAt($consultant, $this->originalAt))->toBeFalse();

    reschedule($appointment, $this->targetAt);

    // The vacated hour must go back on the market; the new one takes its place.
    expect(isFreeAt($consultant, $this->originalAt))->toBeTrue()
        ->and(isFreeAt($consultant, $this->targetAt))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Consultant busy — dropped, appointment back to Pending
// ---------------------------------------------------------------------------

it('drops the consultant and returns to Pending when they are busy at the new time', function (): void {
    $consultant = availableConsultant($this->originalAt, $this->targetAt);
    // A second consultant keeps the target slot on offer while the first one is taken.
    availableConsultant($this->targetAt);

    $appointment = appointmentWithBookedAgenda($consultant, $this->originalAt, $this->employee->id);

    Zap::for($consultant)
        ->named('Busy')
        ->appointment()
        ->from($this->targetAt->toDateString())
        ->to($this->targetAt->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    reschedule($appointment, $this->targetAt);

    $fresh = $appointment->refresh();

    expect($fresh->consultant_id)->toBeNull()
        ->and($fresh->status)->toBe(AppointmentStatus::Pending)
        ->and($fresh->appointment_at->toDateTimeString())->toBe($this->targetAt->toDateTimeString());
});

it('frees the old slot of the dropped consultant so someone else can take it', function (): void {
    $consultant = availableConsultant($this->originalAt, $this->targetAt);
    availableConsultant($this->targetAt);

    $appointment = appointmentWithBookedAgenda($consultant, $this->originalAt, $this->employee->id);

    Zap::for($consultant)
        ->named('Busy')
        ->appointment()
        ->from($this->targetAt->toDateString())
        ->to($this->targetAt->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    expect(isFreeAt($consultant, $this->originalAt))->toBeFalse();

    reschedule($appointment, $this->targetAt);

    // Nobody is serving this appointment any more, so the consultant must not stay blocked.
    expect($appointment->refresh()->consultant_id)->toBeNull()
        ->and(isFreeAt($consultant, $this->originalAt))->toBeTrue()
        ->and(Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $appointment->id)
            ->exists()
        )->toBeFalse();
});

it('deletes the calendar event and clears the meeting link when the consultant is dropped', function (): void {
    $consultant = availableConsultant($this->originalAt, $this->targetAt);
    availableConsultant($this->targetAt);

    $appointment = appointmentWithBookedAgenda($consultant, $this->originalAt, $this->employee->id);
    // google_event_id and meeting_url are always written together by the calendar job.
    $appointment->update([
        'google_event_id' => 'evt-drop',
        'meeting_url' => 'https://meet.google.com/abc-defg-hij',
    ]);

    Zap::for($consultant)
        ->named('Busy')
        ->appointment()
        ->from($this->targetAt->toDateString())
        ->to($this->targetAt->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    $mockClient = Mockery::mock(GoogleCalendarClient::class);
    $mockClient->shouldReceive('getAccessToken')->with($consultant->email)->andReturn('tok');
    $mockClient->shouldReceive('deleteEvent')->once()->with('tok', $consultant->email, 'evt-drop');
    app()->instance(GoogleCalendarClient::class, $mockClient);

    reschedule($appointment, $this->targetAt);

    $fresh = $appointment->refresh();

    expect($fresh->meeting_url)->toBeNull()
        ->and($fresh->google_event_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// History and validation
// ---------------------------------------------------------------------------

it('records the reschedule in the appointment history', function (): void {
    availableConsultant($this->targetAt);
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create(['user_id' => $this->employee->id, 'appointment_at' => $this->originalAt]);

    reschedule($appointment, $this->targetAt);

    expect($appointment->histories()->pluck('action_type'))
        ->toContain(AppointmentHistoryActionType::ReScheduled);
});

it('attributes the history entry to the client side, not to an admin', function (): void {
    availableConsultant($this->targetAt);
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create(['user_id' => $this->employee->id, 'appointment_at' => $this->originalAt]);

    reschedule($appointment, $this->targetAt);

    $history = $appointment->histories()->firstOrFail();

    expect($history->actor_type)->toBe(AppointmentHistoryActor::User)
        ->and($history->actor_id)->toBe($this->employee->getKey());
});

it('rejects a slot that is not on offer for the chosen date', function (): void {
    availableConsultant($this->targetAt);
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create(['user_id' => $this->employee->id, 'appointment_at' => $this->originalAt]);

    reschedule($appointment, $this->targetAt->copy()->setTime(10, 17));

    expect($appointment->refresh()->appointment_at->toDateTimeString())
        ->toBe($this->originalAt->toDateTimeString());
});

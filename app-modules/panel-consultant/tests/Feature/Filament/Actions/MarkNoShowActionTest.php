<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\Billing\Core\Events\Credit\AppointmentCreditUsed;
use TresPontosTech\PanelConsultant\Filament\Actions\MarkNoShowAction;
use TresPontosTech\PanelConsultant\Filament\Resources\Appointments\Pages\ListAppointments;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();

    Mail::fake();
    Bus::fake();
});

it('marks an active past appointment as no-show', function (): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    livewire(ListAppointments::class)
        ->callAction(TestAction::make(MarkNoShowAction::getDefaultName())->table($appointment))
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::NoShow);
});

/**
 * Filament's modal body is delivered through a Livewire partial-render effect
 * rather than the component's main HTML, so assertSee() after mounting the
 * action cannot see it. Evaluating the closure through the action instance
 * directly exercises the exact same modalDescription() the modal renders.
 */
it('shows the beneficiary name and appointment date/time in the confirmation modal', function (): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    $action = MarkNoShowAction::make()->record($appointment);
    $description = (string) $action->getModalDescription();

    expect($description)
        ->toContain($appointment->user->name)
        ->toContain($appointment->appointment_at->format('d/m/Y H:i'));

    expect($action->isConfirmationRequired())->toBeTrue();
});

it('is hidden when the appointment is active but in the future', function (): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addHour()]);

    livewire(ListAppointments::class)
        ->assertActionHidden(TestAction::make(MarkNoShowAction::getDefaultName())->table($appointment));
});

it('is hidden for non-active statuses', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus($status)
        ->create(['appointment_at' => now()->subHour()]);

    livewire(ListAppointments::class)
        ->assertActionHidden(TestAction::make(MarkNoShowAction::getDefaultName())->table($appointment));
})->with([
    'Pending' => AppointmentStatus::Pending,
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
    'NoShow' => AppointmentStatus::NoShow,
]);

/**
 * Stale page: the record still looks Active in memory here, but the row changes
 * underneath before the click reaches the server. Filament's own visibility gate
 * would refuse to call a hidden action through the Livewire table, so the
 * action() closure is invoked directly to reach its refresh()-then-recheck
 * guard against that race.
 */
it('keeps the status when the transition fails', function (): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    $staleAppointment = Appointment::query()->whereKey($appointment->getKey())->firstOrFail();

    Appointment::query()->whereKey($appointment->getKey())->update(['status' => AppointmentStatus::Completed]);

    $actionFunction = MarkNoShowAction::make()->getActionFunction();
    expect($actionFunction)->not->toBeNull();
    $actionFunction($staleAppointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
    expect(session('filament.notifications')[0]['status'] ?? null)->toBe('danger');
});

/**
 * The credit-consumption event fires synchronously from inside the transition's
 * DB::transaction(), before the no_show_marked history row is written. A
 * listener that throws here must roll back the whole transaction, so the
 * status update and the history insert are undone together.
 *
 * The action function is invoked directly (same technique as the "stale page"
 * test above) because session('filament.notifications') only reflects what
 * Notification::send() pushed when the closure runs in-process, not through a
 * simulated Livewire request.
 */
it('rolls back the no-show transition and notifies failure when a listener throws mid-transaction', function (): void {
    $appointment = Appointment::factory()
        ->recycle($this->consultant)
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    Event::listen(AppointmentCreditUsed::class, function (): void {
        throw new RuntimeException('Forced failure to exercise the transaction rollback.');
    });

    $actionFunction = MarkNoShowAction::make()->getActionFunction();
    expect($actionFunction)->not->toBeNull();
    $actionFunction($appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Active);
    expect(session('filament.notifications')[0]['status'] ?? null)->toBe('danger');
    expect(AppointmentHistory::query()
        ->where('appointment_id', $appointment->getKey())
        ->where('action_type', AppointmentHistoryActionType::NoShowMarked)
        ->exists()
    )->toBeFalse();
});

it('does not allow marking an appointment of another consultant', function (): void {
    $foreign = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    livewire(ListAppointments::class)
        ->assertCanNotSeeTableRecords([$foreign]);
});

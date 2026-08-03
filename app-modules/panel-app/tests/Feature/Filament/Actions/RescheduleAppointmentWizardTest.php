<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentHistory;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

function reschedulableAppointment(int $hoursAhead = 72): Appointment
{
    return Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create([
            'user_id' => test()->employee->getKey(),
            'appointment_at' => now()->addHours($hoursAhead),
        ]);
}

it('offers rescheduling only while the notice period still holds', function (): void {
    $open = reschedulableAppointment(Appointment::RESCHEDULE_WINDOW_HOURS + 2);
    $closed = reschedulableAppointment(Appointment::RESCHEDULE_WINDOW_HOURS - 1);

    $completed = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $this->employee->getKey(), 'appointment_at' => now()->subDay()]);

    $rows = livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->viewData('rows')
        ->keyBy('id');

    expect($rows[$open->getKey()]['canReschedule'])->toBeTrue()
        ->and($rows[$closed->getKey()]['canReschedule'])->toBeFalse()
        ->and($rows[$completed->getKey()]['canReschedule'])->toBeFalse();
});

it('advances from the intro to the slot picker keeping the appointment', function (): void {
    $appointment = reschedulableAppointment();

    $component = livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->assertActionMounted('reschedulePickSlot');

    expect($component->instance()->mountedActions[0]['arguments']['appointment'])
        ->toBe($appointment->getKey());
});

it('reschedules on the summary confirmation and records the history', function (): void {
    $appointment = reschedulableAppointment();
    $previousAt = $appointment->appointment_at->toDateTimeString();
    $newAt = now()->addDays(6)->setTime(8, 0);

    $component = livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->setActionData([
            'date' => $newAt->toDateString(),
            'appointment_at' => $newAt->toDateTimeString(),
        ])
        ->callMountedAction()
        ->assertActionMounted('rescheduleReview');

    $component
        ->callMountedAction()
        ->assertActionMounted('rescheduleConfirmed')
        ->assertDispatched('appointment-rescheduled');

    $arguments = $component->instance()->mountedActions[0]['arguments'];

    expect($arguments['previous_at'])->toBe($previousAt)
        ->and($appointment->refresh()->appointment_at->toDateTimeString())->toBe($newAt->toDateTimeString());

    $history = AppointmentHistory::query()
        ->where('appointment_id', $appointment->getKey())
        ->firstOrFail();

    expect($history->action_type)->toBe(AppointmentHistoryActionType::ReScheduled)
        ->and($history->actor_id)->toBe($this->employee->getKey())
        ->and($history->actor_type)->toBe(AppointmentHistoryActor::User);
});

it('restores the appointment when the schedule sync fails unexpectedly', function (): void {
    $appointment = reschedulableAppointment();
    $previousAt = $appointment->appointment_at->toDateTimeString();
    $newAt = now()->addDays(6)->setTime(8, 0);

    // Só o horário indisponível reverte dentro da sync; qualquer outra falha
    // precisa do catch-all do wizard para o registro não ficar pela metade.
    // Classe anônima porque a action é final e o Mockery não a substitui.
    app()->instance(SyncAppointmentScheduleAction::class, new class
    {
        public function handle(): bool
        {
            throw new RuntimeException('schedule sync exploded');
        }
    });

    livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->setActionData([
            'date' => $newAt->toDateString(),
            'appointment_at' => $newAt->toDateTimeString(),
        ])
        ->callMountedAction()
        ->assertActionMounted('rescheduleReview')
        ->callMountedAction()
        ->assertNotified(__('panel-app::resources.appointments.reschedule.failed'))
        ->assertNotDispatched('appointment-rescheduled');

    expect($appointment->refresh()->appointment_at->toDateTimeString())->toBe($previousAt);
});

it('keeps the current time until the summary is confirmed', function (): void {
    $appointment = reschedulableAppointment();
    $previousAt = $appointment->appointment_at->toDateTimeString();
    $newAt = now()->addDays(6)->setTime(8, 0);

    livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->setActionData([
            'date' => $newAt->toDateString(),
            'appointment_at' => $newAt->toDateTimeString(),
        ])
        ->callMountedAction()
        ->assertActionMounted('rescheduleReview');

    expect($appointment->refresh()->appointment_at->toDateTimeString())->toBe($previousAt);
});

it('refuses to reschedule inside the notice period', function (): void {
    $appointment = reschedulableAppointment(Appointment::RESCHEDULE_WINDOW_HOURS - 1);
    $previousAt = $appointment->appointment_at->toDateTimeString();

    livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->assertNotified(__('panel-app::resources.appointments.reschedule.cannot_reschedule'))
        ->assertActionNotMounted();

    expect($appointment->refresh()->appointment_at->toDateTimeString())->toBe($previousAt);
});

it('refuses to reschedule an appointment that belongs to someone else', function (): void {
    $stranger = User::factory()->employee()->create();

    $foreign = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create([
            'user_id' => $stranger->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    // Capturado antes: refresh() muta o objeto, e comparar depois seria
    // comparar o valor recarregado com ele mesmo.
    $previousAt = $foreign->appointment_at->toDateTimeString();

    livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $foreign->getKey()])
        ->assertNotified(__('panel-app::resources.appointments.reschedule.cannot_reschedule'))
        ->assertActionNotMounted();

    expect($foreign->refresh()->appointment_at->toDateTimeString())->toBe($previousAt);
});

it('shows the before and after times on the confirmation content', function (): void {
    $previousAt = now()->addDays(3)->setTime(12, 0);
    $newAt = now()->addDays(6)->setTime(8, 0);

    $html = view('filament.app.appointments.wizard.reschedule-confirmed', [
        'previousAt' => $previousAt,
        'newAt' => $newAt,
    ])->render();

    expect($html)
        ->toContain(__('panel-app::resources.appointments.reschedule.confirmed.before'))
        ->toContain($previousAt->format('d/m/y - H:i'))
        ->toContain(__('panel-app::resources.appointments.reschedule.confirmed.now'))
        ->toContain($newAt->format('d/m/y - H:i'))
        ->toContain(__('panel-app::resources.appointments.reschedule.confirmed.awaiting_confirmation'));
});

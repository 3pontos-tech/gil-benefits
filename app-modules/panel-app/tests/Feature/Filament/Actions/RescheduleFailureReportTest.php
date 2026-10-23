<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;

use function Pest\Livewire\livewire;

it('reports an unexpected sync failure once from the wizard', function (): void {
    Exceptions::fake();
    $employee = actingAsSubscribedEmployee();
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create(['user_id' => $employee->getKey(), 'appointment_at' => now()->addHours(72)]);
    $newAt = now()->addDays(6)->setTime(8, 0);
    consultantAvailableOn($newAt);

    app()->instance(SyncAppointmentScheduleAction::class, new class
    {
        public function handle(): bool
        {
            throw new RuntimeException('schedule sync exploded');
        }
    });

    livewire(LatestAppointmentsWidget::class)
        ->callAction('rescheduleAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->setActionData(['date' => $newAt->toDateString(), 'appointment_at' => $newAt->toDateTimeString()])
        ->callMountedAction()
        ->callMountedAction()
        ->assertNotified(__('panel-app::resources.appointments.reschedule.failed'));

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(RuntimeException::class);
});

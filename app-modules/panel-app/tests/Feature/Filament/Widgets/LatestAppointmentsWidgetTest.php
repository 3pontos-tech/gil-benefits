<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('shows an empty state when the user has no appointments', function (): void {
    livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.latest_appointments.title'))
        ->assertSee(__('panel-app::widgets.latest_appointments.new_appointment'))
        ->assertSee(__('panel-app::widgets.latest_appointments.empty_title'));
});

it('lists the appointment with its consultant and date', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.latest_appointments.with_consultant', [
            'name' => $appointment->consultant->name,
        ]))
        ->assertSeeText($appointment->appointment_at->format('d/m/Y'))
        ->assertSee(AppointmentStatus::Pending->getLabel());
});

it('falls back to the category label when no consultant is assigned', function (): void {
    $appointment = Appointment::factory()
        ->withoutConsultant()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertSee($appointment->category_type->getLabel());
});

it('offers rebooking for a cancelled appointment whose date has not passed', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.latest_appointments.reschedule'));
});

it('offers no reschedule button once the date has passed', function (): void {
    // Perdida (pendente com horário vencido) e cancelada no passado: nos dois
    // casos a linha mostra só o estado, sem ação de reagendar.
    Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->subWeek(),
        ]);

    Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->subDay(),
        ]);

    $component = livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertDontSee(__('panel-app::widgets.latest_appointments.reschedule'));

    expect($component->viewData('rows')->pluck('canRebook')->filter())->toBeEmpty();
});

it('does not offer rescheduling for a completed appointment', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->subWeek(),
        ]);

    livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertDontSee(__('panel-app::widgets.latest_appointments.reschedule'))
        ->assertSee(AppointmentStatus::Completed->getLabel());
});

it('cancels the appointment picked by the row action', function (): void {
    $target = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    $untouched = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeeks(2),
        ]);

    livewire(LatestAppointmentsWidget::class)
        ->callAction('cancelAppointment', arguments: ['appointment' => $target->getKey()])
        ->assertSuccessful();

    expect($target->refresh()->status)->not->toBe(AppointmentStatus::Pending)
        ->and($untouched->refresh()->status)->toBe(AppointmentStatus::Pending);
});

it('offers cancelling only for appointments that can still be cancelled', function (): void {
    $future = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['user_id' => $this->employee->getKey(), 'appointment_at' => now()->addWeek()]);

    $past = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['user_id' => $this->employee->getKey(), 'appointment_at' => now()->subWeek()]);

    $completed = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $this->employee->getKey(), 'appointment_at' => now()->subDay()]);

    $rows = livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->viewData('rows')
        ->keyBy('id');

    // Regressão: a consultoria passada rendia um botão desabilitado em vez de
    // nenhum botão, e o usuário via um "Cancelar" que não fazia nada.
    expect($rows[$future->getKey()]['canCancel'])->toBeTrue()
        ->and($rows[$past->getKey()]['canCancel'])->toBeFalse()
        ->and($rows[$completed->getKey()]['canCancel'])->toBeFalse();
});

it('renders no cancel button once the appointment has passed', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['user_id' => $this->employee->getKey(), 'appointment_at' => now()->subWeek()]);

    livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->assertDontSee("mountAction('cancelAppointment'", false);
});

it('caps the list at five appointments', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->count(7)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    $rows = livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->viewData('rows');

    expect($rows)->toHaveCount(5);
});

/**
 * Distâncias em dias até agora, todas distintas para não haver empate na
 * seleção: as cinco mais próximas são -2, +1, +5, -20 e +40.
 */
function appointmentsAtDayOffsets(string $userId): Collection
{
    return collect([-50, -20, -2, 1, 5, 40, 80])->mapWithKeys(fn (int $days): array => [
        $days => Appointment::factory()
            ->withStatus(AppointmentStatus::Pending)
            ->create([
                'user_id' => $userId,
                'appointment_at' => now()->addDays($days),
            ]),
    ]);
}

it('picks the appointments closest to now, in both directions', function (): void {
    $byOffset = appointmentsAtDayOffsets($this->employee->getKey());

    $ids = livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->viewData('rows')
        ->pluck('id')
        ->all();

    expect($ids)->toHaveCount(5)
        ->and($ids)->toContain(
            $byOffset[1]->getKey(),
            $byOffset[-2]->getKey(),
            $byOffset[5]->getKey(),
            $byOffset[-20]->getKey(),
            $byOffset[40]->getKey(),
        )
        // Descartadas por estarem mais longe, apesar de uma delas ser a mais futura.
        ->and($ids)->not->toContain($byOffset[80]->getKey())
        ->and($ids)->not->toContain($byOffset[-50]->getKey());
});

it('lists the selected appointments from the newest to the oldest', function (): void {
    appointmentsAtDayOffsets($this->employee->getKey());

    $dates = livewire(LatestAppointmentsWidget::class)
        ->assertSuccessful()
        ->viewData('rows')
        ->map(fn (array $row): string => $row['record']->appointment_at->toDateTimeString())
        ->all();

    expect($dates)->toBe(collect($dates)->sortDesc()->values()->all());
});

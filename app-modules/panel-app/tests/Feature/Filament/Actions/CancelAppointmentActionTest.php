<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\PanelApp\Filament\Actions\CancelAppointmentAction;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

function appointmentIn(int $hours): Appointment
{
    return Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => test()->employee->getKey(),
            'appointment_at' => now()->addHours($hours),
        ]);
}

function cancelActionFor(Appointment $appointment): CancelAppointmentAction
{
    return CancelAppointmentAction::make('cancelAppointment')->record($appointment);
}

it('renders the heading, description and button labels from the design', function (): void {
    $action = cancelActionFor(appointmentIn(72));

    expect($action->getModalHeading())
        ->toBe(__('panel-app::resources.appointments.cancel.modal_heading'))
        ->and($action->getModalDescription())
        ->toBe(__('panel-app::resources.appointments.cancel.modal_description'))
        ->and($action->getModalSubmitActionLabel())
        ->toBe(__('panel-app::resources.appointments.cancel.modal_submit_label'))
        ->and($action->getModalCancelActionLabel())
        ->toBe(__('panel-app::resources.appointments.cancel.modal_cancel_label'));
});

it('shows which appointment is about to be cancelled', function (): void {
    $appointment = appointmentIn(72);

    $html = cancelActionFor($appointment)->getModalContent()->render();

    expect($html)
        ->toContain($appointment->category_type->getLabel())
        ->toContain($appointment->appointment_at->format('d/m/y - H:i'))
        ->toContain($appointment->consultant->name);
});

it('promises the credit back while the notice period still holds', function (): void {
    $hours = AppointmentStatus::CANCELLATION_NOTICE_HOURS;

    $html = cancelActionFor(appointmentIn($hours + 1))->getModalContent()->render();

    expect($html)->toContain(e(__(
        'panel-app::resources.appointments.cancel.notice_keeps_credit',
        ['hours' => $hours],
    )));
});

it('warns the credit is lost once inside the notice period', function (): void {
    $hours = AppointmentStatus::CANCELLATION_NOTICE_HOURS;

    $html = cancelActionFor(appointmentIn($hours - 1))->getModalContent()->render();

    expect($html)->toContain(e(__(
        'panel-app::resources.appointments.cancel.notice_loses_credit',
        ['hours' => $hours],
    )));
});

it('returns the credit when cancelled before the notice period', function (): void {
    $appointment = appointmentIn(AppointmentStatus::CANCELLATION_NOTICE_HOURS + 1);

    $credit = UserCredit::factory()->create([
        'owner_id' => $this->employee->getKey(),
        'holder_id' => $this->employee->getKey(),
        'company_id' => filament()->getTenant()->getKey(),
        'appointment_id' => $appointment->getKey(),
        'status' => UserCreditStatusEnum::InUse,
    ]);

    livewire(LatestAppointmentsWidget::class)
        ->callAction('cancelAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->assertSuccessful();

    // A promessa que o modal faz precisa valer no banco.
    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available);
});

it('consumes the credit when cancelled inside the notice period', function (): void {
    $appointment = appointmentIn(AppointmentStatus::CANCELLATION_NOTICE_HOURS - 1);

    $credit = UserCredit::factory()->create([
        'owner_id' => $this->employee->getKey(),
        'holder_id' => $this->employee->getKey(),
        'company_id' => filament()->getTenant()->getKey(),
        'appointment_id' => $appointment->getKey(),
        'status' => UserCreditStatusEnum::InUse,
    ]);

    livewire(LatestAppointmentsWidget::class)
        ->callAction('cancelAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->assertSuccessful();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate)
        ->and($credit->refresh()->status)->not->toBe(UserCreditStatusEnum::Available);
});

it('opens the success confirmation carrying the kept-credit flag once cancelled in time', function (): void {
    $appointment = appointmentIn(AppointmentStatus::CANCELLATION_NOTICE_HOURS + 1);

    $component = livewire(LatestAppointmentsWidget::class)
        ->callAction('cancelAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->assertActionMounted('cancelledConfirmation');

    $arguments = $component->instance()->mountedActions[0]['arguments'];

    expect($arguments['appointment'])->toBe($appointment->getKey())
        ->and($arguments['keepsCredit'])->toBeTrue();
});

it('flags the confirmation to consume the credit once cancelled late', function (): void {
    $appointment = appointmentIn(AppointmentStatus::CANCELLATION_NOTICE_HOURS - 1);

    $component = livewire(LatestAppointmentsWidget::class)
        ->callAction('cancelAppointment', arguments: ['appointment' => $appointment->getKey()])
        ->assertActionMounted('cancelledConfirmation');

    expect($component->instance()->mountedActions[0]['arguments']['keepsCredit'])->toBeFalse();
});

it('shows the cancelled appointment and credit state on the confirmation content', function (): void {
    $appointment = appointmentIn(AppointmentStatus::CANCELLATION_NOTICE_HOURS + 1);

    $render = fn (bool $keepsCredit): string => view('filament.app.appointments.cancelled-confirmation-modal', [
        'appointment' => $appointment,
        'keepsCredit' => $keepsCredit,
    ])->render();

    expect($render(true))
        ->toContain(__('panel-app::resources.appointments.cancel.confirmed.appointment_cancelled'))
        ->toContain($appointment->category_type->getLabel())
        ->toContain($appointment->appointment_at->format('d/m/y - H:i'))
        ->toContain(__('panel-app::resources.appointments.cancel.confirmed.credit_processing'));

    // Fora do prazo o crédito não volta, então a linha de crédito some.
    expect($render(false))
        ->not->toContain(__('panel-app::resources.appointments.cancel.confirmed.credit_processing'));
});

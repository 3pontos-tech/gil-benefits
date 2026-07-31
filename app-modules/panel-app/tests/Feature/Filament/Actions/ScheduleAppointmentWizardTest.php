<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Widgets\LatestAppointmentsWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('refuses to open the wizard when the user cannot book', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeek(),
        ]);

    livewire(LatestAppointmentsWidget::class)
        ->mountAction('scheduleAppointment')
        ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
        ->assertActionNotMounted();
});

it('requires a category before advancing to the slot step', function (): void {
    livewire(LatestAppointmentsWidget::class)
        ->mountAction('scheduleAppointment')
        ->callMountedAction()
        ->assertHasActionErrors(['category_type' => 'required'])
        ->assertActionMounted('scheduleAppointment');
});

it('carries the picks through the steps and books on the final confirmation', function (): void {
    $appointmentAt = now()->addDays(5)->setTime(14, 0);

    $component = livewire(LatestAppointmentsWidget::class)
        ->mountAction('scheduleAppointment')
        ->setActionData(['category_type' => AppointmentCategoryEnum::InvestmentAdvisory->value])
        ->callMountedAction()
        ->assertActionMounted('schedulePickSlot')
        ->setActionData([
            'date' => $appointmentAt->toDateString(),
            'appointment_at' => $appointmentAt->toDateTimeString(),
        ])
        ->callMountedAction()
        ->assertActionMounted('scheduleReview');

    $arguments = $component->instance()->mountedActions[0]['arguments'];

    expect($arguments['category_type'])->toBe(AppointmentCategoryEnum::InvestmentAdvisory->value)
        ->and($arguments['appointment_at'])->toBe($appointmentAt->toDateTimeString());

    $component
        ->callMountedAction()
        ->assertActionMounted('scheduleConfirmed')
        ->assertDispatched('appointment-booked');

    $appointment = Appointment::query()
        ->where('user_id', $this->employee->getKey())
        ->firstOrFail();

    expect($appointment->category_type)->toBe(AppointmentCategoryEnum::InvestmentAdvisory)
        ->and($appointment->appointment_at->toDateTimeString())->toBe($appointmentAt->toDateTimeString())
        ->and($appointment->status)->toBe(AppointmentStatus::Pending);
});

it('re-checks the booking allowance on the final confirmation', function (): void {
    $appointmentAt = now()->addDays(5)->setTime(14, 0);

    $component = livewire(LatestAppointmentsWidget::class)
        ->mountAction('scheduleAppointment')
        ->setActionData(['category_type' => AppointmentCategoryEnum::PersonalFinance->value])
        ->callMountedAction()
        ->setActionData([
            'date' => $appointmentAt->toDateString(),
            'appointment_at' => $appointmentAt->toDateTimeString(),
        ])
        ->callMountedAction()
        ->assertActionMounted('scheduleReview');

    // O saldo muda no meio do fluxo: outra consultoria passa a estar em aberto.
    $blocker = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create([
            'user_id' => $this->employee->getKey(),
            'appointment_at' => now()->addWeeks(2),
        ]);

    $component
        ->callMountedAction()
        ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'));

    expect(Appointment::query()->where('user_id', $this->employee->getKey())->count())->toBe(1)
        ->and(Appointment::query()->whereKeyNot($blocker->getKey())->where('user_id', $this->employee->getKey())->exists())->toBeFalse();
});

it('shows the booked consultation on the confirmation content', function (): void {
    $appointmentAt = now()->addDays(5)->setTime(14, 0);

    $html = view('filament.app.appointments.wizard.schedule-confirmed', [
        'category' => AppointmentCategoryEnum::InvestmentAdvisory,
        'appointmentAt' => $appointmentAt,
    ])->render();

    expect($html)
        ->toContain(AppointmentCategoryEnum::InvestmentAdvisory->getLabel())
        ->toContain($appointmentAt->format('H:i'))
        ->toContain(__('panel-app::resources.appointments.schedule.confirmed.awaiting_confirmation'));
});

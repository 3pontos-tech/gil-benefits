<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\App\Filament\Widgets\UserCurrentPlanWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    actingAsEmployee();

    livewire(UserCurrentPlanWidget::class)
        ->assertOk();
});

describe('company plan', function (): void {
    it('should be able to make an appointment when status is Cancelled', function (): void {
        $employee = actingAsEmployee();
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Scheduling)
            ->create([
                'user_id' => $employee->getKey(),
            ]);

        // no appoints left
        livewire(UserCurrentPlanWidget::class)
            ->assertOk()
            ->assertSee(trans_choice('all.appointments_left', 0, ['count' => 0]))
            ->assertSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'));

        expect($appointment->status->value)->toBe(AppointmentStatus::Scheduling->value);
        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        travelTo(now()->addMinutes(2));
        // refreshing the employee because of cached attribute

        $employee->refresh();

        livewire(UserCurrentPlanWidget::class)
            ->assertOk()
            ->assertSee(trans_choice('all.appointments_left', 1, ['count' => 1]))
            ->assertDontSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertRedirect(AppointmentResource::getUrl('create'));
    });
});

describe('employee with plan, company plan does not exists', function (): void {
    it('should be able to make an appointment when status is Cancelled', function (): void {
        $employee = actingAsSubscribedEmployee();
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Scheduling)
            ->create([
                'user_id' => $employee->getKey(),
            ]);

        // no appoints left
        livewire(UserCurrentPlanWidget::class)
            ->assertOk()
            ->assertSee(trans_choice('all.appointments_left', 0, ['count' => 0]))
            ->assertSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'));

        expect($appointment->status->value)->toBe(AppointmentStatus::Scheduling->value);
        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        travelTo(now()->addMinutes(2));
        // refreshing the employee because of cached attribute

        $employee->refresh();

        livewire(UserCurrentPlanWidget::class)
            ->assertOk()
            ->assertSee(trans_choice('all.appointments_left', 1, ['count' => 1]))
            ->assertDontSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertRedirect(AppointmentResource::getUrl('create'));
    });
});

<?php

declare(strict_types=1);

use TresPontosTech\App\DTOs\PlanSummary;
use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\App\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\UserCredit;

use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

it('renders plan name, monthly allowance and available credits', function (): void {
    $employee = actingAsSubscribedEmployee(monthlyLimit: 4);

    UserCredit::factory()->available()->count(3)->create([
        'owner_id' => $employee->id,
        'holder_id' => $employee->id,
        'company_id' => filament()->getTenant()->getKey(),
    ]);

    livewire(PlanCreditsWidget::class)
        ->assertSuccessful()
        ->assertSee('Plano & créditos')
        ->assertSee('Agendar consultoria')
        ->assertSee('3'); // créditos disponíveis
});

it('shows the plan name and an active status badge', function (): void {
    actingAsEmployee();

    livewire(PlanCreditsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.plan_status.active'));
});

it('exposes the view-plan action and renders description and features in the modal partial', function (): void {
    actingAsEmployee(); // CompanyPlan: 1 consulta/mês

    livewire(PlanCreditsWidget::class)
        ->assertActionVisible('viewPlan');

    $plan = new PlanSummary(
        name: 'Plano X',
        status: 'active',
        description: 'Descrição do plano de teste',
        monthlyLimit: 1,
        features: [trans_choice('panel-app::widgets.plan_details.monthly_appointments', 1, ['count' => 1])],
    );

    $html = view('filament.app.widgets.partials.plan-details', ['plan' => $plan])->render();

    expect($html)
        ->toContain('Descrição do plano de teste')
        ->toContain(trans_choice('panel-app::widgets.plan_details.monthly_appointments', 1, ['count' => 1]));
});

it('lists every applicable block reason at once', function (): void {
    $employee = actingAsEmployee(); // CompanyPlan: 1 consulta/mês, sem créditos

    Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['user_id' => $employee->getKey()]);

    livewire(PlanCreditsWidget::class)
        ->assertOk()
        ->assertSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
        ->assertSeeText(__('panel-app::widgets.plans_overview.no_appointments_available'));
});

it('shows only the ongoing reason when the user still has quota', function (): void {
    $employee = actingAsSubscribedEmployee(monthlyLimit: 4);

    Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['user_id' => $employee->getKey()]);

    livewire(PlanCreditsWidget::class)
        ->assertOk()
        ->assertSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
        ->assertDontSeeText(__('panel-app::widgets.plans_overview.no_appointments_available'));
});

describe('appointment guard', function (): void {
    it('blocks booking with company plan and allows after cancellation', function (): void {
        $employee = actingAsEmployee();
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Active)
            ->create(['user_id' => $employee->getKey()]);

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'));

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        travelTo(now()->addMinutes(2));
        $employee->refresh();

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertDontSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertRedirect(AppointmentResource::getUrl('create'));
    });

    it('disables the booking button and unbinds the action when the user cannot book', function (): void {
        $employee = actingAsSubscribedEmployee();
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Active)
            ->create(['user_id' => $employee->getKey()]);

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertSee('aria-disabled', false)
            ->assertDontSee('redirectToAppointmentCreation', false);

        $appointment->update(['status' => AppointmentStatus::Cancelled]);
        travelTo(now()->addMinutes(2));
        $employee->refresh();

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertSee('redirectToAppointmentCreation', false)
            ->assertDontSee('aria-disabled', false);
    });

    it('blocks booking with subscription and allows after cancellation', function (): void {
        $employee = actingAsSubscribedEmployee();
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Active)
            ->create(['user_id' => $employee->getKey()]);

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'));

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        travelTo(now()->addMinutes(2));
        $employee->refresh();

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertDontSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->call('redirectToAppointmentCreation')
            ->assertRedirect(AppointmentResource::getUrl('create'));
    });
});

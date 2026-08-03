<?php

declare(strict_types=1);

use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\DTOs\PlanSummary;
use TresPontosTech\PanelApp\Enums\PlanStatus;
use TresPontosTech\PanelApp\Filament\Widgets\PlanCreditsWidget;

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
        ->assertSee(__('panel-app::widgets.plan_credits.title'))
        ->assertSee(__('panel-app::widgets.plan_credits.credits_card_title'))
        ->assertSee(__('panel-app::widgets.plan_credits.book_appointment'))
        ->assertSee(__('panel-app::widgets.plan_credits.monthly_appointments'))
        ->assertSee('3'); // créditos disponíveis
});

it('shows the holder name and the consultant of the latest appointment', function (): void {
    $employee = actingAsSubscribedEmployee();

    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $employee->getKey()]);

    livewire(PlanCreditsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.plan_credits.holder'))
        ->assertSee(mb_strtoupper($employee->name))
        ->assertSee($appointment->consultant->name);
});

it('falls back to a placeholder when no consultant has been assigned yet', function (): void {
    actingAsSubscribedEmployee();

    livewire(PlanCreditsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.plan_credits.no_consultant'));
});

it('counts only credits of the current tenant', function (): void {
    $employee = actingAsEmployee();
    $tenant = filament()->getTenant();

    UserCredit::factory()->available()->count(2)->create([
        'owner_id' => $employee->id,
        'holder_id' => $employee->id,
        'company_id' => $tenant->getKey(),
    ]);

    // Crédito do mesmo usuário em outra empresa: fora do total do cartão,
    // como já acontece na página "Meus Créditos".
    UserCredit::factory()->available()->create([
        'owner_id' => $employee->id,
        'holder_id' => $employee->id,
        'company_id' => Company::factory()->create()->getKey(),
    ]);

    expect(livewire(PlanCreditsWidget::class)->assertOk()->viewData('creditsTotal'))->toBe(2);
});

it('counts credits from both origins in the card total', function (): void {
    $employee = actingAsEmployee(); // CompanyPlan ativo
    $tenant = filament()->getTenant();

    // 2 comprados pelo próprio colaborador (owner = ele)
    UserCredit::factory()->available()->count(2)->create([
        'owner_id' => $employee->id,
        'holder_id' => $employee->id,
        'company_id' => $tenant->getKey(),
    ]);

    // 5 comprados pela empresa e alocados a ele (owner = dono da empresa)
    UserCredit::factory()->available()->count(5)->create([
        'owner_id' => $tenant->user_id,
        'holder_id' => $employee->id,
        'company_id' => $tenant->getKey(),
    ]);

    // O cartão mostra só o total disponível para o cliente, sem separar origem.
    livewire(PlanCreditsWidget::class)
        ->assertOk()
        ->assertSee(__('panel-app::widgets.plan_credits.credits_available'))
        ->assertSeeText('7')
        ->assertDontSeeText('2 seus')
        ->assertDontSeeText('5 da empresa');
});

it('reaches the plan name through the access-plan modal', function (): void {
    $employee = actingAsEmployee();

    $companyPlan = CompanyPlan::query()
        ->whereIn('company_id', $employee->companies()->select('companies.id'))
        ->with('plan')
        ->first();

    $component = livewire(PlanCreditsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.plan_credits.access_plan'))
        ->assertActionVisible('viewPlan');

    expect($component->instance()->viewPlanAction()->getModalHeading())
        ->toBe($companyPlan->plan->name);
});

it('exposes the view-plan action and renders description and features in the modal partial', function (): void {
    actingAsEmployee(); // CompanyPlan: 1 consulta/mês

    $component = livewire(PlanCreditsWidget::class)
        ->assertActionVisible('viewPlan');

    $action = $component->instance()->viewPlanAction();

    expect($action->getModalWidth())->toBe(Width::Medium)
        ->and($action->getModalCancelAction())->toBeNull();

    $plan = new PlanSummary(
        name: 'Plano X',
        status: PlanStatus::Active,
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

it('checks ongoing-appointment eligibility with a single query', function (): void {
    $employee = actingAsEmployee();

    Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['user_id' => $employee->getKey()]);

    $ongoingChecks = 0;
    DB::listen(function ($query) use (&$ongoingChecks): void {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'appointments') && str_contains($sql, 'not in')) {
            ++$ongoingChecks;
        }
    });

    livewire(PlanCreditsWidget::class)->assertOk();

    // canCreateAppointment e blockReasons compartilham o mesmo resultado, sem duplicar a query.
    expect($ongoingChecks)->toBe(1);
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
            ->mountAction('scheduleAppointment')
            ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
            ->assertActionNotMounted();

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        travelTo(now()->addMinutes(2));
        $employee->refresh();

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertDontSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->mountAction('scheduleAppointment')
            ->assertActionMounted('scheduleAppointment');
    });

    it('disables the booking button and unbinds the action when the user cannot book', function (): void {
        $employee = actingAsSubscribedEmployee();
        $appointment = Appointment::factory()
            ->withStatus(AppointmentStatus::Active)
            ->create(['user_id' => $employee->getKey()]);

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertSee('aria-disabled', false)
            ->assertDontSee("mountAction('scheduleAppointment')", false);

        $appointment->update(['status' => AppointmentStatus::Cancelled]);
        travelTo(now()->addMinutes(2));
        $employee->refresh();

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertSee("mountAction('scheduleAppointment')", false)
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
            ->mountAction('scheduleAppointment')
            ->assertNotified(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
            ->assertActionNotMounted();

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        travelTo(now()->addMinutes(2));
        $employee->refresh();

        livewire(PlanCreditsWidget::class)
            ->assertOk()
            ->assertDontSeeText(__('panel-app::widgets.plans_overview.ongoing_appointment'))
            ->mountAction('scheduleAppointment')
            ->assertActionMounted('scheduleAppointment');
    });
});

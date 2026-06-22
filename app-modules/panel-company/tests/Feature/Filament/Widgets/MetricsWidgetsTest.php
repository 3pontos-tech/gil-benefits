<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CategoryMixWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\DepartmentAdoptionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SatisfactionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\StatusBreakdownWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\TopConsultantsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentStatsTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditFlowTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\DepartmentVolumeChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementInsightsTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\NeverUsedTileWidget;

use function Pest\Livewire\livewire;

beforeEach(fn () => Cache::flush());

it('renders reused period-scoped widgets on the metrics context', function (string $widget): void {
    actingAsCompanyOwner();

    livewire($widget, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk();
})->with([
    CategoryMixWidget::class,
    SatisfactionWidget::class,
    TopConsultantsWidget::class,
    'DepartmentAdoptionWidget (period-only)' => DepartmentAdoptionWidget::class,
]);

it('scopes a reused home widget to the selected date range', function (): void {
    actingAsCompanyOwner();
    $company = Filament::getTenant();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::create(2026, 5, 15),
    ]);

    livewire(StatusBreakdownWidget::class, ['pageFilters' => ['startDate' => '2026-05-01', 'endDate' => '2026-05-31']])
        ->assertOk()
        ->assertSeeHtml('>3<');

    livewire(StatusBreakdownWidget::class, ['pageFilters' => ['startDate' => '2026-04-01', 'endDate' => '2026-04-30']])
        ->assertOk()
        ->assertDontSeeHtml('>3<');
});

it('scopes TopConsultantsWidget to the selected date range', function (): void {
    actingAsCompanyOwner();
    $company = Filament::getTenant();

    $consultant = Consultant::factory()->create(['name' => 'Zara Testconsult']);

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id,
        'consultant_id' => $consultant->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::create(2026, 5, 15),
    ]);

    livewire(TopConsultantsWidget::class, ['pageFilters' => ['startDate' => '2026-05-01', 'endDate' => '2026-05-31']])
        ->assertOk()
        ->assertSee('Zara Testconsult');

    livewire(TopConsultantsWidget::class, ['pageFilters' => ['startDate' => '2026-04-01', 'endDate' => '2026-04-30']])
        ->assertOk()
        ->assertDontSee('Zara Testconsult');
});

it('renders the appointment stats tiles', function (): void {
    actingAsCompanyOwner();

    livewire(AppointmentStatsTilesWidget::class, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk()
        ->assertSee(__('panel-company::widgets.appointment_stats.total_scheduled'));
});

it('renders the engagement tiles', function (): void {
    actingAsCompanyOwner();

    livewire(EngagementTilesWidget::class, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk()
        ->assertSee(__('panel-company::widgets.engagement_stats.active_users'));
});

it('renders never-used and engagement insights tiles', function (): void {
    actingAsCompanyOwner();

    livewire(NeverUsedTileWidget::class, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk();

    livewire(EngagementInsightsTilesWidget::class, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk();
});

it('renders the credit flow tiles', function (): void {
    actingAsCompanyOwner();

    livewire(CreditFlowTilesWidget::class, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk()
        ->assertSee(__('panel-company::widgets.credit_stats_metrics.distributed'))
        ->assertSee(__('panel-company::widgets.credit_stats_metrics.used_in_period'))
        ->assertDontSee('Em uso')
        ->assertDontSee('Disponíveis');
});

it('renders the department volume chart', function (): void {
    actingAsCompanyOwner();

    livewire(DepartmentVolumeChart::class, ['pageFilters' => ['startDate' => now()->subDays(30)->toDateString(), 'endDate' => now()->toDateString()]])
        ->assertOk();
});

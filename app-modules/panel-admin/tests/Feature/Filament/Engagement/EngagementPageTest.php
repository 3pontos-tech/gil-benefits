<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Actions\Engagement\ExportEngagementCsv;
use TresPontosTech\PanelAdmin\Actions\Engagement\GetEngagementFunnel;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;
use TresPontosTech\PanelAdmin\Filament\Pages\Engagement;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementFunnelTableWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementTotalsWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementWeeklyChartWidget;
use TresPontosTech\PanelAdmin\Filament\Widgets\Engagement\EngagementWeeklyTableWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Cache::flush();
    actingAsAdmin();
});

/**
 * Two companies: "Critical Co" holds none of its booked consultancies, while
 * "Healthy Co" holds every one of them.
 */
function seedEngagementCompanies(): void
{
    $critical = Company::factory()->create(['name' => 'Critical Co']);
    CompanyPlan::factory()->active()->create(['company_id' => $critical->id, 'seats' => 10]);
    $criticalEmployee = User::factory()->create();
    $critical->employees()->attach($criticalEmployee->id, ['created_at' => now()->subMonth()]);

    Appointment::factory()->create([
        'company_id' => $critical->id,
        'user_id' => $criticalEmployee->id,
        'status' => AppointmentStatus::Cancelled,
        'appointment_at' => now()->subDays(3),
    ]);

    $healthy = Company::factory()->create(['name' => 'Healthy Co']);
    CompanyPlan::factory()->active()->create(['company_id' => $healthy->id, 'seats' => 4]);
    $healthyEmployee = User::factory()->create();
    $healthy->employees()->attach($healthyEmployee->id, ['created_at' => now()->subMonth()]);

    Appointment::factory()->create([
        'company_id' => $healthy->id,
        'user_id' => $healthyEmployee->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now()->subDays(2),
    ]);
}

function engagementPageFilters(): array
{
    return [
        'startDate' => now()->subDays(30)->toDateString(),
        'endDate' => now()->toDateString(),
        'companies' => [],
    ];
}

it('renders the engagement page', function (): void {
    seedEngagementCompanies();

    livewire(Engagement::class)->assertOk();
});

it('renders the engagement widgets', function (): void {
    seedEngagementCompanies();

    $filters = ['pageFilters' => engagementPageFilters()];

    livewire(EngagementTotalsWidget::class, $filters)->assertOk();
    livewire(EngagementFunnelTableWidget::class, $filters)->assertOk();
    livewire(EngagementWeeklyChartWidget::class, $filters)->assertOk();
    livewire(EngagementWeeklyTableWidget::class, $filters)->assertOk();
});

it('flags the companies below the critical completion rate', function (): void {
    seedEngagementCompanies();

    $alertIcon = 'fi-icon fi-size-sm fi-color fi-color-danger';

    livewire(EngagementFunnelTableWidget::class, ['pageFilters' => engagementPageFilters()])
        ->assertSeeHtml($alertIcon);

    livewire(EngagementFunnelTableWidget::class, [
        'pageFilters' => [
            ...engagementPageFilters(),
            'companies' => [Company::query()->where('name', 'Healthy Co')->value('id')],
        ],
    ])->assertDontSeeHtml($alertIcon);
});

it('lists the companies sorted by the lowest completion rate', function (): void {
    seedEngagementCompanies();

    livewire(EngagementFunnelTableWidget::class, ['pageFilters' => engagementPageFilters()])
        ->assertSee('Critical Co')
        ->assertSee('Healthy Co')
        ->assertSee('0%')
        ->assertSee('100%')
        ->assertSeeHtmlInOrder(['Critical Co', 'Healthy Co']);
});

it('only flags the consolidated indicator that has an agreed critical line', function (): void {
    // Cadastro (1%) e recorrência (0%) estariam abaixo dos limiares que já foram
    // removidos; realização (100%) é o único indicador com corte acordado.
    $company = Company::factory()->create();
    CompanyPlan::factory()->active()->create(['company_id' => $company->id, 'seats' => 100]);

    $employee = User::factory()->create();
    $company->employees()->attach($employee->id);

    Appointment::factory()->create([
        'company_id' => $company->id,
        'user_id' => $employee->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => now()->subDay(),
    ]);

    livewire(EngagementTotalsWidget::class, [
        'pageFilters' => [...engagementPageFilters(), 'companies' => [$company->id]],
    ])
        ->assertSee('1%')
        ->assertSee('100%')
        ->assertDontSeeHtml('fi-color-danger');
});

it('shows the consolidated indicators of every company in scope', function (): void {
    seedEngagementCompanies();

    livewire(EngagementTotalsWidget::class, ['pageFilters' => engagementPageFilters()])
        ->assertSee(__('panel-admin::widgets.engagement.totals.completion_rate'))
        ->assertSee('14')
        ->assertSee('50%');
});

it('narrows the funnel table down to the companies selected on the page', function (): void {
    seedEngagementCompanies();

    livewire(EngagementFunnelTableWidget::class, [
        'pageFilters' => [
            ...engagementPageFilters(),
            'companies' => [Company::query()->where('name', 'Healthy Co')->value('id')],
        ],
    ])
        ->assertSee('Healthy Co')
        ->assertDontSee('Critical Co');
});

it('paginates the funnel table instead of rendering every company at once', function (): void {
    Company::factory()->count(30)->create();

    $widget = livewire(EngagementFunnelTableWidget::class, ['pageFilters' => engagementPageFilters()])
        ->assertCountTableRecords(30);

    expect($widget->instance()->getTableRecords())->toHaveCount(25);

    $widget->set('tableRecordsPerPage', 10);

    expect($widget->instance()->getTableRecords())->toHaveCount(10);
});

it('shows the active filters and clears them without leaving the page', function (): void {
    seedEngagementCompanies();

    $healthy = Company::query()->where('name', 'Healthy Co')->value('id');

    livewire(Engagement::class)
        ->assertDontSeeHtml(__('panel-admin::resources.pages.engagement.clear_filters'))
        ->set('filters.companies', [$healthy])
        ->assertSee(__('panel-admin::resources.pages.engagement.active_filters'))
        ->assertSee('Healthy Co')
        ->callAction('clearFilters')
        ->assertSet('filters.companies', [])
        ->assertSet('filters.endDate', now()->toDateString());
});

function exportedCsv(array $pageFilters): string
{
    $filters = EngagementFilters::fromPageFilters($pageFilters);

    $response = resolve(ExportEngagementCsv::class)->handle(
        $filters,
        resolve(GetEngagementFunnel::class)->handle($filters),
    );

    expect($response->headers->get('content-disposition'))
        ->toContain(sprintf('engajamento_flamma_%s.csv', now()->toDateString()));

    ob_start();
    $response->sendContent();

    return (string) ob_get_clean();
}

it('exports the funnel of the filtered companies to csv', function (): void {
    seedEngagementCompanies();

    $csv = exportedCsv(engagementPageFilters());

    expect($csv)->toContain('Critical Co')
        ->toContain('Healthy Co')
        ->toContain('Empresa;Cadeiras;Cadastrados');
});

it('stamps the analysed period on every exported row', function (): void {
    seedEngagementCompanies();

    $start = now()->subDays(7);
    $csv = exportedCsv([
        ...engagementPageFilters(),
        'startDate' => $start->toDateString(),
    ]);

    $lines = array_values(array_filter(explode("\n", trim($csv))));
    $period = sprintf('%s;%s', $start->format('d/m/Y'), now()->format('d/m/Y'));

    expect($lines[0])
        ->toContain(__('panel-admin::resources.pages.engagement.filter_start_date'))
        ->toContain(__('panel-admin::resources.pages.engagement.filter_end_date'));

    foreach (array_slice($lines, 1) as $line) {
        expect(trim($line))->toEndWith($period);
    }
});

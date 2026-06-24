---
type: plan
title: "Página de Métricas — Plano de Implementação"
module: panel-company
status: completed
date: 2026-06-20
related:
  spec: panel-company/2026-06-20-company-metrics-page-design
---

# Página de Métricas — Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transformar `/company/metrics` no superconjunto das métricas (tudo da home + tudo da Metrics atual), com o visual da home, em 5 abas, totalmente filtrável.

**Architecture:** Camada `Actions/Metrics` → DTOs puros → widgets Filament (SVG sob medida + Chart.js + tiles). Widgets da home são parametrizados (default 12m quando a página não fornece filtros) e reusados na Metrics. Métricas hoje em query crua viram novas Actions/DTOs com cache de 5 min.

**Tech Stack:** Laravel 12, Filament v5, Livewire v4, Pest v4, Tailwind v4, Flowframe Trend, Chart.js (via `ChartWidget`).

## Global Constraints

- PHP 8.4; tipos explícitos em parâmetros e retornos; `declare(strict_types=1)` em todo arquivo PHP.
- DTOs: classes `final readonly` em `app-modules/panel-company/src/DTOs/` (pasta `DTOs`, nunca `DataTransferObjects`).
- Actions: classes `final` em `app-modules/panel-company/src/Actions/Metrics/`, injeção via construtor, `use BuildsMetricsCacheKey`, `Cache::remember($key, $this->metricsCacheTtl(), ...)`, chave via `metricsCacheKey('bucket', $tenant, $period->cacheKey(), $filters->cacheKey())`.
- Escopo de usuários sempre via `ResolveScopedUserIds`.
- Curly braces sempre; promoção de propriedades no construtor.
- Testes Pest; `beforeEach(fn () => Cache::flush())` em testes de Action; `actingAsCompanyOwner()` + `Filament::getTenant()` em testes de widget/página.
- Rodar testes **no checkout principal** (worktree gera `ViewException` falsa): `php artisan test --compact --filter=<nome>`.
- **Trava de regressão:** os testes atuais dos 8 widgets da home (`tests/Feature/Filament/Widgets/CommandDashboardWidgetsTest.php`, `SessionsTrendWidgetTest.php`) devem permanecer verdes **sem alteração**.
- `vendor/bin/pint --dirty --format agent` ao final de cada fase.
- Sem `Co-Authored-By` nos commits.

## Mapa de arquivos

**Camada (criar):**
- `src/DTOs/{AppointmentStats,EngagementData,InsightsData,VolumeVariation,TopUser,CreditFlow,DepartmentVolume,DepartmentVolumeRow,MetricTile}.php`
- `src/Actions/Metrics/{GetAppointmentStats,GetEngagement,GetInsights,GetCreditFlow,GetDepartmentVolume}.php`

**Concern (modificar):**
- `src/Filament/Concerns/HasMetricsDateRange.php` — default 12m.

**Widgets da home (modificar — parametrizar/badge):**
- `src/Filament/Widgets/CommandDashboard/{StatusBreakdownWidget,CategoryMixWidget,SatisfactionWidget,TopConsultantsWidget,DepartmentAdoptionWidget}.php`
- `src/Filament/Widgets/CommandDashboard/{AdoptionFunnelWidget,CreditKpisWidget}.php` + suas views (badge).

**Widgets da Metrics (criar/refatorar):**
- Criar: `src/Filament/Widgets/Metrics/{AppointmentStatsTilesWidget,EngagementTilesWidget,NeverUsedTileWidget,EngagementInsightsTilesWidget,CreditFlowTilesWidget,DepartmentVolumeChart}.php`
- View compartilhada: `resources/views/filament/widgets/metrics/metric-tiles.blade.php`
- Refatorar: `src/Filament/Widgets/Metrics/{AppointmentVolumeChart,CreditUsageTableWidget}.php`
- Remover (Fase 4): `src/Filament/Widgets/Metrics/{AppointmentStatsWidget,AppointmentsByCategoryChart,AppointmentsByDepartmentChart,EngagementStatsWidget,InsightsWidget,CreditStatsWidget}.php`

**Página (modificar):**
- `src/Filament/Pages/Metrics.php` — 5 abas (Estrutura C).

**i18n (modificar):**
- `lang/pt_BR/resources.php`, `lang/en/resources.php` — tabs + badge.

---

## Fase 0 — Fundação (parametrização)

### Task 0.1: Default de 12 meses no `HasMetricsDateRange`

**Files:**
- Modify: `app-modules/panel-company/src/Filament/Concerns/HasMetricsDateRange.php`
- Test: `app-modules/panel-company/tests/Feature/Filament/Widgets/MetricsWidgetsTest.php` (criar)

**Interfaces:**
- Produces: `metricsPeriod(): MetricsPeriod` retorna `MetricsPeriod::lastMonths(12)` quando não há `month`/`startDate`/`endDate`; `month(...)` quando há `month` válido; `range(...)` quando há datas. `dateRange(): array{start,end}` alinhado ao mesmo default.

- [ ] **Step 1: Escrever o teste que falha**

Criar `tests/Feature/Filament/Widgets/MetricsWidgetsTest.php`:

```php
<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\StatusBreakdownWidget;

use function Pest\Livewire\livewire;

beforeEach(fn () => Cache::flush());

it('scopes a reused home widget to the selected month', function (): void {
    actingAsCompanyOwner();
    $company = Filament\Facades\Filament::getTenant();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => AppointmentStatus::Completed,
        'appointment_at' => CarbonImmutable::create(2026, 5, 15),
    ]);

    livewire(StatusBreakdownWidget::class)
        ->set('filters', ['month' => '2026-05'])
        ->assertOk()
        ->assertSee('3');

    livewire(StatusBreakdownWidget::class)
        ->set('filters', ['month' => '2026-04'])
        ->assertOk()
        ->assertDontSee('3');
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=MetricsWidgetsTest`
Expected: FAIL (sem o default, o mês não escopa como esperado / valores divergem).

- [ ] **Step 3: Implementar o default**

Substituir os métodos `dateRange()` e `metricsPeriod()` em `HasMetricsDateRange.php` por:

```php
private function dateRange(): array
{
    $period = $this->metricsPeriod();

    return ['start' => $period->start, 'end' => $period->end];
}

private function metricsPeriod(): MetricsPeriod
{
    $parsedMonth = $this->parsedMonthFilter();

    if ($parsedMonth !== null) {
        return MetricsPeriod::month($parsedMonth['year'], $parsedMonth['month']);
    }

    $startDate = data_get($this->filters, 'startDate');
    $endDate = data_get($this->filters, 'endDate');

    if (blank($startDate) && blank($endDate)) {
        return MetricsPeriod::lastMonths(12);
    }

    return MetricsPeriod::range(
        filled($startDate) ? now()->parse($startDate) : now()->subDays(30),
        filled($endDate) ? now()->parse($endDate) : now(),
    );
}
```

> Nota: `dateRange()` agora deriva de `metricsPeriod()`; os widgets antigos que usam `dateRange()['start'/'end']` recebem `CarbonImmutable` (compatível com `whereBetween`). Eles têm os DatePickers sempre preenchidos na Metrics, então continuam em 30 dias; serão removidos na Fase 4.

- [ ] **Step 4: Rodar e ver passar + regressão da home**

Run: `php artisan test --compact --filter=MetricsWidgetsTest`
Expected: PASS

Run: `php artisan test --compact --filter=CommandDashboardWidgetsTest`
Expected: PASS (regressão da home intacta — default 12m preserva a saída)

- [ ] **Step 5: Commit**

```bash
git add app-modules/panel-company/src/Filament/Concerns/HasMetricsDateRange.php app-modules/panel-company/tests/Feature/Filament/Widgets/MetricsWidgetsTest.php
git commit -m "feat(metrics): default de 12 meses no HasMetricsDateRange para reúso na home"
```

### Task 0.2: Parametrizar os widgets período-scoped da home

**Files:**
- Modify: `StatusBreakdownWidget.php`, `CategoryMixWidget.php`, `SatisfactionWidget.php`, `TopConsultantsWidget.php`, `DepartmentAdoptionWidget.php`
- Test: `MetricsWidgetsTest.php` (estender)

**Interfaces:**
- Consumes: `metricsPeriod()`, `metricsFilters()` do concern (Task 0.1 + `HasMetricsDateRange::metricsFilters()` já existente).
- Produces: mesmos widgets, agora lendo período/filtros da página.

- [ ] **Step 1: Estender o teste (reação a filtro de funcionário)**

Adicionar a `MetricsWidgetsTest.php`:

```php
it('renders reused period-scoped widgets on the metrics context', function (string $widget): void {
    actingAsCompanyOwner();

    livewire($widget)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk();
})->with([
    TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CategoryMixWidget::class,
    TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SatisfactionWidget::class,
    TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\TopConsultantsWidget::class,
    TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\DepartmentAdoptionWidget::class,
]);
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=MetricsWidgetsTest`
Expected: FAIL (widgets ainda usam `lastMonths(12)`/`none()` fixos e não têm `InteractsWithPageFilters` → `set('filters', ...)` falha).

- [ ] **Step 3: Parametrizar cada widget**

Em `StatusBreakdownWidget.php`, `CategoryMixWidget.php`, `SatisfactionWidget.php`, `TopConsultantsWidget.php` — adicionar os traits e trocar a chamada. Exemplo completo (`StatusBreakdownWidget.php`):

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetStatusBreakdown;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class StatusBreakdownWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.status-breakdown';

    protected int|string|array $columnSpan = 3;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        return ['data' => resolve(GetStatusBreakdown::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters())];
    }
}
```

Aplicar a mesma transformação (adicionar `use HasMetricsDateRange;` + `use InteractsWithPageFilters;`, remover imports `MetricsPeriod`/`MetricsFilters`, trocar `MetricsPeriod::lastMonths(12), MetricsFilters::none()` por `$this->metricsPeriod(), $this->metricsFilters()`) em:
- `CategoryMixWidget.php` (chave `'mix'`)
- `SatisfactionWidget.php` (chave `'data'`)
- `TopConsultantsWidget.php` (chave `'consultants'`)

Em `DepartmentAdoptionWidget.php` (Action sem filtros — só período):

```php
return ['departments' => resolve(GetDepartmentAdoption::class)->handle($tenant, $this->metricsPeriod())];
```

(adicionar os mesmos traits; manter import de `GetDepartmentAdoption`).

- [ ] **Step 4: Rodar e ver passar + regressão**

Run: `php artisan test --compact --filter=MetricsWidgetsTest`
Expected: PASS

Run: `php artisan test --compact --filter=CommandDashboardWidgetsTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/CommandDashboard/ app-modules/panel-company/tests/
git commit -m "feat(metrics): parametriza widgets período-scoped da home para reúso filtrável"
```

### Task 0.3: Selo "estado atual" nos snapshots (Funil e Saldo)

**Files:**
- Modify: `AdoptionFunnelWidget.php`, `CreditKpisWidget.php`
- Modify: `resources/views/filament/widgets/command-dashboard/adoption-funnel.blade.php`, `credit-kpis.blade.php`
- Modify: `lang/pt_BR/resources.php`, `lang/en/resources.php`

**Interfaces:**
- Produces: as views recebem `bool $isFiltered`; quando `true`, exibem o selo `metrics.snapshot_badge`.

- [ ] **Step 1: Teste (selo presente na Metrics, ausente na home)**

Adicionar a `MetricsWidgetsTest.php`:

```php
it('shows the snapshot badge on snapshot widgets only when filtered', function (): void {
    actingAsCompanyOwner();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\AdoptionFunnelWidget::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertSee(__('panel-company::resources.pages.metrics.snapshot_badge'));

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\AdoptionFunnelWidget::class)
        ->assertDontSee(__('panel-company::resources.pages.metrics.snapshot_badge'));
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=MetricsWidgetsTest`
Expected: FAIL (chave de lang ausente / selo não renderizado).

- [ ] **Step 3: Adicionar chave de lang**

Em `lang/pt_BR/resources.php`, dentro de `pages.metrics`, adicionar:

```php
'snapshot_badge' => 'Estado atual',
```

Em `lang/en/resources.php`, dentro de `pages.metrics`:

```php
'snapshot_badge' => 'Current state',
```

- [ ] **Step 4: Passar `isFiltered` e renderizar o selo**

Em `AdoptionFunnelWidget.php` e `CreditKpisWidget.php`: adicionar `use Filament\Widgets\Concerns\InteractsWithPageFilters;` (trait) e incluir no array de `getViewData()`:

```php
'isFiltered' => filled($this->filters),
```

No topo de `adoption-funnel.blade.php` e `credit-kpis.blade.php`, logo após o `@php ... @endphp`, dentro do card principal, adicionar:

```blade
@if (($isFiltered ?? false))
    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
        {{ __('panel-company::resources.pages.metrics.snapshot_badge') }}
    </span>
@endif
```

- [ ] **Step 5: Rodar e ver passar + regressão**

Run: `php artisan test --compact --filter=MetricsWidgetsTest`
Expected: PASS

Run: `php artisan test --compact --filter=CommandDashboardWidgetsTest`
Expected: PASS (na home `$this->filters` é nulo → sem selo)

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/CommandDashboard/ app-modules/panel-company/resources/views/ app-modules/panel-company/lang/ app-modules/panel-company/tests/
git commit -m "feat(metrics): selo estado atual nos widgets snapshot quando filtrados"
```

---

## Fase 1 — Camada (Actions + DTOs)

### Task 1.1: `AppointmentStats` + `GetAppointmentStats`

**Files:**
- Create: `src/DTOs/AppointmentStats.php`, `src/Actions/Metrics/GetAppointmentStats.php`
- Test: `tests/Feature/Actions/GetAppointmentStatsTest.php`

**Interfaces:**
- Produces: `GetAppointmentStats::handle(Company, MetricsPeriod, MetricsFilters): AppointmentStats` com `int $total, int $completed, int $cancelled, float $attendanceRate`.

- [ ] **Step 1: Teste**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetAppointmentStats;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('computes totals and attendance rate', function (): void {
    $company = Company::factory()->create();

    Appointment::factory()->count(3)->create([
        'company_id' => $company->id, 'status' => AppointmentStatus::Completed, 'appointment_at' => now(),
    ]);
    Appointment::factory()->create([
        'company_id' => $company->id, 'status' => AppointmentStatus::Cancelled, 'appointment_at' => now(),
    ]);

    $stats = resolve(GetAppointmentStats::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($stats->total)->toBe(4)
        ->and($stats->completed)->toBe(3)
        ->and($stats->cancelled)->toBe(1)
        ->and($stats->attendanceRate)->toBe(75.0);
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=GetAppointmentStatsTest`
Expected: FAIL ("class not found").

- [ ] **Step 3: Criar DTO**

`src/DTOs/AppointmentStats.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class AppointmentStats
{
    public function __construct(
        public int $total,
        public int $completed,
        public int $cancelled,
        public float $attendanceRate,
    ) {}
}
```

- [ ] **Step 4: Criar Action**

`src/Actions/Metrics/GetAppointmentStats.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\AppointmentStats;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Appointment volume + attendance rate within the window (raw data; no SVG).
 */
final class GetAppointmentStats
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): AppointmentStats
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('appointment_stats', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): AppointmentStats {
            $base = Appointment::query()
                ->where('company_id', $tenant->getKey())
                ->whereBetween('appointment_at', [$period->start, $period->end])
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds));

            $total = (clone $base)->count();
            $completed = (clone $base)->where('status', AppointmentStatus::Completed)->count();
            $cancelled = (clone $base)->whereIn('status', [AppointmentStatus::Cancelled, AppointmentStatus::CancelledLate])->count();

            $finalized = $completed + $cancelled;
            $attendanceRate = $finalized > 0 ? round($completed / $finalized * 100, 1) : 0.0;

            return new AppointmentStats($total, $completed, $cancelled, $attendanceRate);
        });
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=GetAppointmentStatsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/DTOs/AppointmentStats.php app-modules/panel-company/src/Actions/Metrics/GetAppointmentStats.php app-modules/panel-company/tests/Feature/Actions/GetAppointmentStatsTest.php
git commit -m "feat(metrics): GetAppointmentStats + AppointmentStats DTO"
```

### Task 1.2: `EngagementData` + `GetEngagement`

**Files:**
- Create: `src/DTOs/EngagementData.php`, `src/Actions/Metrics/GetEngagement.php`
- Test: `tests/Feature/Actions/GetEngagementTest.php`

**Interfaces:**
- Produces: `GetEngagement::handle(Company, MetricsPeriod, MetricsFilters): EngagementData` com `int $totalEmployees, int $activeUsers, int $inactiveUsers, float $utilizationRate, int $firstTimeUsers`.

- [ ] **Step 1: Teste**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetEngagement;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('counts active, inactive and utilization', function (): void {
    $company = Company::factory()->create();
    $active = App\Models\Users\User::factory()->create();
    $idle = App\Models\Users\User::factory()->create();
    $company->employees()->attach([$active->getKey(), $idle->getKey()]);

    Appointment::factory()->create([
        'company_id' => $company->id, 'user_id' => $active->getKey(), 'appointment_at' => now(),
    ]);

    $data = resolve(GetEngagement::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->totalEmployees)->toBe(2)
        ->and($data->activeUsers)->toBe(1)
        ->and($data->inactiveUsers)->toBe(1)
        ->and($data->utilizationRate)->toBe(50.0)
        ->and($data->firstTimeUsers)->toBe(1);
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=GetEngagementTest`
Expected: FAIL ("class not found").

- [ ] **Step 3: Criar DTO**

`src/DTOs/EngagementData.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class EngagementData
{
    public function __construct(
        public int $totalEmployees,
        public int $activeUsers,
        public int $inactiveUsers,
        public float $utilizationRate,
        public int $firstTimeUsers,
    ) {}
}
```

- [ ] **Step 4: Criar Action**

`src/Actions/Metrics/GetEngagement.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\EngagementData;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Employee engagement within the window: active/inactive counts, utilization
 * rate and first-time users (raw data; no SVG).
 */
final class GetEngagement
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): EngagementData
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('engagement', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): EngagementData {
            $employeesQuery = $tenant->employees()
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('users.id', $userIds));

            $totalEmployees = (clone $employeesQuery)->count();

            $activeUsers = (clone $employeesQuery)
                ->whereHas('appointments', fn ($q) => $q
                    ->where('company_id', $tenant->getKey())
                    ->whereBetween('appointment_at', [$period->start, $period->end])
                    ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds)))
                ->count();

            $inactiveUsers = $totalEmployees - $activeUsers;
            $utilizationRate = $totalEmployees > 0 ? round($activeUsers / $totalEmployees * 100, 1) : 0.0;

            $firstTimeUsers = (clone $employeesQuery)
                ->whereHas('appointments', fn ($q) => $q
                    ->where('company_id', $tenant->getKey())
                    ->whereBetween('appointment_at', [$period->start, $period->end])
                    ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds)))
                ->whereDoesntHave('appointments', fn ($q) => $q
                    ->where('company_id', $tenant->getKey())
                    ->where('appointment_at', '<', $period->start)
                    ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds)))
                ->count();

            return new EngagementData($totalEmployees, $activeUsers, $inactiveUsers, $utilizationRate, $firstTimeUsers);
        });
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=GetEngagementTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/DTOs/EngagementData.php app-modules/panel-company/src/Actions/Metrics/GetEngagement.php app-modules/panel-company/tests/Feature/Actions/GetEngagementTest.php
git commit -m "feat(metrics): GetEngagement + EngagementData DTO"
```

### Task 1.3: `InsightsData` (+ `VolumeVariation`, `TopUser`) + `GetInsights`

**Files:**
- Create: `src/DTOs/VolumeVariation.php`, `src/DTOs/TopUser.php`, `src/DTOs/InsightsData.php`, `src/Actions/Metrics/GetInsights.php`
- Test: `tests/Feature/Actions/GetInsightsTest.php`

**Interfaces:**
- Produces: `GetInsights::handle(Company, MetricsPeriod, MetricsFilters): InsightsData` com `int $neverUsedCount, int $totalEmployees, float $neverUsedRate, VolumeVariation $volume, ?TopUser $topUser`. `VolumeVariation(int $current, int $previous, ?float $variation)`. `TopUser(string $name, int $count)`.

- [ ] **Step 1: Teste**

```php
<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetInsights;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('computes never-used share and a top user', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $idle = User::factory()->create();
    $company->employees()->attach([$user->getKey(), $idle->getKey()]);

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id, 'user_id' => $user->getKey(), 'appointment_at' => now(),
    ]);

    $data = resolve(GetInsights::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($data->totalEmployees)->toBe(2)
        ->and($data->neverUsedCount)->toBe(1)
        ->and($data->neverUsedRate)->toBe(50.0)
        ->and($data->topUser)->not->toBeNull()
        ->and($data->topUser->count)->toBe(2)
        ->and($data->volume->current)->toBe(2);
});

it('omits the top user when scoped to a single employee', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey());
    Appointment::factory()->create([
        'company_id' => $company->id, 'user_id' => $user->getKey(), 'appointment_at' => now(),
    ]);

    $data = resolve(GetInsights::class)->handle(
        $company,
        MetricsPeriod::lastMonths(12),
        new MetricsFilters(userId: (string) $user->getKey()),
    );

    expect($data->topUser)->toBeNull();
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=GetInsightsTest`
Expected: FAIL ("class not found").

- [ ] **Step 3: Criar DTOs**

`src/DTOs/VolumeVariation.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class VolumeVariation
{
    public function __construct(
        public int $current,
        public int $previous,
        public ?float $variation,
    ) {}
}
```

`src/DTOs/TopUser.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class TopUser
{
    public function __construct(
        public string $name,
        public int $count,
    ) {}
}
```

`src/DTOs/InsightsData.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class InsightsData
{
    public function __construct(
        public int $neverUsedCount,
        public int $totalEmployees,
        public float $neverUsedRate,
        public VolumeVariation $volume,
        public ?TopUser $topUser,
    ) {}
}
```

- [ ] **Step 4: Criar Action**

`src/Actions/Metrics/GetInsights.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\InsightsData;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\DTOs\TopUser;
use TresPontosTech\PanelCompany\DTOs\VolumeVariation;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Cross-cutting insights within the window: never-used share, volume variation
 * vs the preceding equal-length window, and the top employee (raw data).
 */
final class GetInsights
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): InsightsData
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('insights', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $filters, $userIds): InsightsData {
            $employeesQuery = $tenant->employees()
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('users.id', $userIds));

            $totalEmployees = (clone $employeesQuery)->count();
            $everUsed = (clone $employeesQuery)
                ->whereHas('appointments', fn ($q) => $q
                    ->where('company_id', $tenant->getKey())
                    ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds)))
                ->count();

            $neverUsedCount = max(0, $totalEmployees - $everUsed);
            $neverUsedRate = $totalEmployees > 0 ? round($neverUsedCount / $totalEmployees * 100, 1) : 0.0;

            $durationDays = (int) $period->start->diffInDays($period->end);
            $prevEnd = $period->start->subDay();
            $prevStart = $prevEnd->subDays($durationDays);

            $current = $this->volume($tenant, $userIds, $period->start, $period->end);
            $previous = $this->volume($tenant, $userIds, $prevStart, $prevEnd);
            $variation = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;

            $topUser = null;

            if (blank($filters->userId)) {
                $employeeIds = $userIds ?? $tenant->employees()->pluck('users.id');

                $top = Appointment::query()
                    ->where('company_id', $tenant->getKey())
                    ->whereBetween('appointment_at', [$period->start, $period->end])
                    ->whereIn('user_id', $employeeIds)
                    ->selectRaw('user_id, count(*) as period_count')
                    ->groupBy('user_id')
                    ->orderByDesc('period_count')
                    ->toBase()
                    ->first();

                if ($top !== null) {
                    $name = (string) ($tenant->employees()->find($top->user_id)?->name ?? '—');
                    $topUser = new TopUser($name, (int) $top->period_count);
                }
            }

            return new InsightsData(
                neverUsedCount: $neverUsedCount,
                totalEmployees: $totalEmployees,
                neverUsedRate: $neverUsedRate,
                volume: new VolumeVariation($current, $previous, $variation),
                topUser: $topUser,
            );
        });
    }

    private function volume(Company $tenant, ?Collection $userIds, mixed $start, mixed $end): int
    {
        return Appointment::query()
            ->where('company_id', $tenant->getKey())
            ->whereBetween('appointment_at', [$start, $end])
            ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
            ->count();
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=GetInsightsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/DTOs/VolumeVariation.php app-modules/panel-company/src/DTOs/TopUser.php app-modules/panel-company/src/DTOs/InsightsData.php app-modules/panel-company/src/Actions/Metrics/GetInsights.php app-modules/panel-company/tests/Feature/Actions/GetInsightsTest.php
git commit -m "feat(metrics): GetInsights + InsightsData/VolumeVariation/TopUser DTOs"
```

### Task 1.4: `CreditFlow` + `GetCreditFlow`

**Files:**
- Create: `src/DTOs/CreditFlow.php`, `src/Actions/Metrics/GetCreditFlow.php`
- Test: `tests/Feature/Actions/GetCreditFlowTest.php`

**Interfaces:**
- Produces: `GetCreditFlow::handle(Company, MetricsPeriod, MetricsFilters): CreditFlow` com `int $distributed, int $usedInPeriod, int $inUse, int $available`. `distributed`/`usedInPeriod` são período-scoped; `inUse`/`available` são contagens atuais. Escopo por `holder_id`.

- [ ] **Step 1: Teste**

```php
<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditFlow;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('counts distributed in window plus current in-use and available', function (): void {
    $company = Company::factory()->create();
    $holder = User::factory()->create();

    UserCredit::factory()->transferred()->count(2)->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(),
    ]);
    UserCredit::factory()->inUse()->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(),
    ]);
    UserCredit::factory()->available()->create([
        'company_id' => $company->id, 'holder_id' => $holder->getKey(),
    ]);

    $flow = resolve(GetCreditFlow::class)->handle($company, MetricsPeriod::lastMonths(12), MetricsFilters::none());

    expect($flow->distributed)->toBe(2)
        ->and($flow->inUse)->toBe(1)
        ->and($flow->available)->toBe(3); // Available conta estoque + entregues-não-usados
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=GetCreditFlowTest`
Expected: FAIL ("class not found").

- [ ] **Step 3: Criar DTO**

`src/DTOs/CreditFlow.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class CreditFlow
{
    public function __construct(
        public int $distributed,
        public int $usedInPeriod,
        public int $inUse,
        public int $available,
    ) {}
}
```

- [ ] **Step 4: Criar Action**

`src/Actions/Metrics/GetCreditFlow.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\CreditFlow;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Credit flow scoped to the window: distributed and used within it, plus the
 * current in-use and available balances (raw data; scoped by holder).
 */
final class GetCreditFlow
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): CreditFlow
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('credit_flow', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): CreditFlow {
            $base = UserCredit::query()
                ->where('company_id', $tenant->getKey())
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('holder_id', $userIds));

            $distributed = (clone $base)
                ->whereNotNull('transferred_at')
                ->whereBetween('transferred_at', [$period->start, $period->end])
                ->count();

            $usedInPeriod = (clone $base)
                ->where('status', UserCreditStatusEnum::Used)
                ->whereHas('appointment', fn ($q) => $q->whereBetween('appointment_at', [$period->start, $period->end]))
                ->count();

            $inUse = (clone $base)->where('status', UserCreditStatusEnum::InUse)->count();
            $available = (clone $base)->where('status', UserCreditStatusEnum::Available)->count();

            return new CreditFlow($distributed, $usedInPeriod, $inUse, $available);
        });
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=GetCreditFlowTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/DTOs/CreditFlow.php app-modules/panel-company/src/Actions/Metrics/GetCreditFlow.php app-modules/panel-company/tests/Feature/Actions/GetCreditFlowTest.php
git commit -m "feat(metrics): GetCreditFlow + CreditFlow DTO"
```

### Task 1.5: `DepartmentVolume` (+ `DepartmentVolumeRow`) + `GetDepartmentVolume`

**Files:**
- Create: `src/DTOs/DepartmentVolumeRow.php`, `src/DTOs/DepartmentVolume.php`, `src/Actions/Metrics/GetDepartmentVolume.php`
- Test: `tests/Feature/Actions/GetDepartmentVolumeTest.php`

**Interfaces:**
- Produces: `GetDepartmentVolume::handle(Company, MetricsPeriod): DepartmentVolume` com `array<int, DepartmentVolumeRow> $rows`. `DepartmentVolumeRow(string $id, string $name, int $total)`. Sem filtros (mostra todos os departamentos; destaque do selecionado é responsabilidade da view).

- [ ] **Step 1: Teste**

```php
<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Actions\Metrics\GetDepartmentVolume;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

beforeEach(fn () => Cache::flush());

it('counts appointments per department in the window', function (): void {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create();
    $company->employees()->attach($user->getKey(), ['department_id' => $department->id]);

    Appointment::factory()->count(2)->create([
        'company_id' => $company->id, 'user_id' => $user->getKey(), 'appointment_at' => now(),
    ]);

    $volume = resolve(GetDepartmentVolume::class)->handle($company, MetricsPeriod::lastMonths(12));

    expect($volume->rows)->toHaveCount(1)
        ->and($volume->rows[0]->total)->toBe(2)
        ->and($volume->rows[0]->name)->toBe($department->name);
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=GetDepartmentVolumeTest`
Expected: FAIL ("class not found").

- [ ] **Step 3: Criar DTOs**

`src/DTOs/DepartmentVolumeRow.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class DepartmentVolumeRow
{
    public function __construct(
        public string $id,
        public string $name,
        public int $total,
    ) {}
}
```

`src/DTOs/DepartmentVolume.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class DepartmentVolume
{
    /**
     * @param  array<int, DepartmentVolumeRow>  $rows
     */
    public function __construct(public array $rows) {}
}
```

- [ ] **Step 4: Criar Action**

`src/Actions/Metrics/GetDepartmentVolume.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\DepartmentVolume;
use TresPontosTech\PanelCompany\DTOs\DepartmentVolumeRow;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Appointment volume per department within the window (distinct from adoption,
 * which measures the share of employees that adhered).
 */
final class GetDepartmentVolume
{
    use BuildsMetricsCacheKey;

    public function handle(Company $tenant, MetricsPeriod $period): DepartmentVolume
    {
        $cacheKey = $this->metricsCacheKey('department_volume', $tenant, $period->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period): DepartmentVolume {
            $rows = Department::query()
                ->where('departments.company_id', $tenant->getKey())
                ->leftJoin('company_employees', 'company_employees.department_id', '=', 'departments.id')
                ->leftJoin('appointments', function ($join) use ($period): void {
                    $join->on('appointments.user_id', '=', 'company_employees.user_id')
                        ->on('appointments.company_id', '=', 'company_employees.company_id')
                        ->whereBetween('appointments.appointment_at', [$period->start, $period->end]);
                })
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->select(['departments.id', 'departments.name', DB::raw('COUNT(appointments.id) as total')])
                ->get()
                ->map(fn ($row): DepartmentVolumeRow => new DepartmentVolumeRow(
                    id: (string) $row->id,
                    name: (string) $row->name,
                    total: (int) $row->total,
                ))
                ->all();

            return new DepartmentVolume($rows);
        });
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=GetDepartmentVolumeTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/DTOs/DepartmentVolume.php app-modules/panel-company/src/DTOs/DepartmentVolumeRow.php app-modules/panel-company/src/Actions/Metrics/GetDepartmentVolume.php app-modules/panel-company/tests/Feature/Actions/GetDepartmentVolumeTest.php
git commit -m "feat(metrics): GetDepartmentVolume + DepartmentVolume DTOs"
```

---

## Fase 2 — Render (tiles + charts)

### Task 2.1: DTO `MetricTile` + view compartilhada `metric-tiles`

**Files:**
- Create: `src/DTOs/MetricTile.php`, `resources/views/filament/widgets/metrics/metric-tiles.blade.php`

**Interfaces:**
- Produces: `MetricTile(string $label, string $value, string $caption, string $tone = 'neutral', ?string $icon = null)`. A view consome `array<int, MetricTile> $tiles` e `int $columns`.

- [ ] **Step 1: Criar DTO**

`src/DTOs/MetricTile.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\DTOs;

final readonly class MetricTile
{
    public function __construct(
        public string $label,
        public string $value,
        public string $caption,
        public string $tone = 'neutral',
        public ?string $icon = null,
    ) {}
}
```

- [ ] **Step 2: Criar a view compartilhada**

`resources/views/filament/widgets/metrics/metric-tiles.blade.php`:

```blade
@php
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900';
    $label = 'text-sm font-semibold text-gray-500 dark:text-gray-400';
    $num = 'font-mono tabular-nums tracking-tight';
    $iconTone = [
        'primary' => 'text-primary-500', 'success' => 'text-emerald-500', 'info' => 'text-blue-500',
        'danger' => 'text-red-500', 'warning' => 'text-amber-500', 'neutral' => 'text-gray-400',
        // literal classes acima — necessárias para o Tailwind v4 detectar no @source
    ];
    $capTone = [
        'primary' => 'text-gray-500 dark:text-gray-400', 'success' => 'text-emerald-600 dark:text-emerald-400',
        'info' => 'text-gray-500 dark:text-gray-400', 'danger' => 'text-red-600 dark:text-red-400',
        'warning' => 'text-amber-600 dark:text-amber-400', 'neutral' => 'text-gray-500 dark:text-gray-400',
    ];
    $cols = ['1' => 'sm:grid-cols-1', '2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-3', '4' => 'sm:grid-cols-2 lg:grid-cols-4'];
@endphp

<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 {{ $cols[(string) ($columns ?? 4)] ?? 'sm:grid-cols-2 lg:grid-cols-4' }}">
        @foreach ($tiles as $tile)
            <div class="{{ $card }} flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="{{ $label }}">{{ $tile->label }}</p>
                        <p class="{{ $num }} mt-1 text-3xl font-bold leading-none text-gray-900 dark:text-white">{{ $tile->value }}</p>
                    </div>
                    @if (filled($tile->icon))
                        <x-filament::icon :icon="$tile->icon" @class(['size-6 shrink-0', $iconTone[$tile->tone] ?? 'text-gray-400']) />
                    @endif
                </div>
                <p class="mt-3 text-xs font-medium {{ $capTone[$tile->tone] ?? 'text-gray-400' }}">{{ $tile->caption }}</p>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
```

- [ ] **Step 3: Commit** (sem teste isolado — coberto pelos widgets que a usam nas próximas tasks)

```bash
git add app-modules/panel-company/src/DTOs/MetricTile.php app-modules/panel-company/resources/views/filament/widgets/metrics/metric-tiles.blade.php
git commit -m "feat(metrics): DTO MetricTile + view compartilhada de tiles"
```

### Task 2.2: `AppointmentStatsTilesWidget`

**Files:**
- Create: `src/Filament/Widgets/Metrics/AppointmentStatsTilesWidget.php`
- Test: `tests/Feature/Filament/Widgets/MetricsWidgetsTest.php` (estender)

**Interfaces:**
- Consumes: `GetAppointmentStats` (Task 1.1), `MetricTile` (Task 2.1), view `metric-tiles` (Task 2.1).

- [ ] **Step 1: Teste**

Adicionar a `MetricsWidgetsTest.php`:

```php
it('renders the appointment stats tiles', function (): void {
    actingAsCompanyOwner();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentStatsTilesWidget::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk()
        ->assertSee(__('panel-company::widgets.appointment_stats.total_scheduled'));
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="renders the appointment stats tiles"`
Expected: FAIL ("class not found").

- [ ] **Step 3: Criar o widget**

`src/Filament/Widgets/Metrics/AppointmentStatsTilesWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetAppointmentStats;
use TresPontosTech\PanelCompany\DTOs\MetricTile;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class AppointmentStatsTilesWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.metrics.metric-tiles';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $stats = resolve(GetAppointmentStats::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        /** @var array<string, string> $l */
        $l = trans('panel-company::widgets.appointment_stats');

        $attendanceTone = match (true) {
            $stats->attendanceRate >= 70 => 'success',
            $stats->attendanceRate >= 40 => 'warning',
            default => 'danger',
        };

        return [
            'columns' => 4,
            'tiles' => [
                new MetricTile($l['total_scheduled'], MetricsNumber::integer($stats->total), $l['total_scheduled_description'], 'primary', 'heroicon-o-calendar'),
                new MetricTile($l['completed'], MetricsNumber::integer($stats->completed), $l['completed_description'], 'success', 'heroicon-o-check-circle'),
                new MetricTile($l['cancelled'], MetricsNumber::integer($stats->cancelled), $l['cancelled_description'], 'danger', 'heroicon-o-x-circle'),
                new MetricTile(
                    $l['attendance_rate'],
                    MetricsNumber::percent($stats->attendanceRate) . '%',
                    __('panel-company::widgets.appointment_stats.attendance_rate_description', [
                        'rate' => MetricsNumber::percent($stats->attendanceRate),
                        'completed' => $stats->completed,
                        'total' => $stats->completed + $stats->cancelled,
                    ]),
                    $attendanceTone,
                    'heroicon-o-chart-bar',
                ),
            ],
        ];
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --compact --filter="renders the appointment stats tiles"`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/Metrics/AppointmentStatsTilesWidget.php app-modules/panel-company/tests/
git commit -m "feat(metrics): AppointmentStatsTilesWidget"
```

### Task 2.3: `EngagementTilesWidget`

**Files:**
- Create: `src/Filament/Widgets/Metrics/EngagementTilesWidget.php`
- Test: `MetricsWidgetsTest.php` (estender)

**Interfaces:**
- Consumes: `GetEngagement` (Task 1.2), `MetricTile`, view `metric-tiles`.

- [ ] **Step 1: Teste**

```php
it('renders the engagement tiles', function (): void {
    actingAsCompanyOwner();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementTilesWidget::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk()
        ->assertSee(__('panel-company::widgets.engagement_stats.active_users'));
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="renders the engagement tiles"`
Expected: FAIL.

- [ ] **Step 3: Criar o widget**

`src/Filament/Widgets/Metrics/EngagementTilesWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetEngagement;
use TresPontosTech\PanelCompany\DTOs\MetricTile;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class EngagementTilesWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.metrics.metric-tiles';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $data = resolve(GetEngagement::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        /** @var array<string, string> $l */
        $l = trans('panel-company::widgets.engagement_stats');

        $utilTone = match (true) {
            $data->utilizationRate >= 70 => 'success',
            $data->utilizationRate >= 40 => 'warning',
            default => 'danger',
        };

        return [
            'columns' => 4,
            'tiles' => [
                new MetricTile($l['active_users'], MetricsNumber::integer($data->activeUsers), __('panel-company::widgets.engagement_stats.active_users_description', ['count' => $data->activeUsers]), 'success', 'heroicon-o-user-group'),
                new MetricTile($l['inactive_users'], MetricsNumber::integer($data->inactiveUsers), __('panel-company::widgets.engagement_stats.inactive_users_description', ['count' => $data->inactiveUsers]), $data->inactiveUsers > 0 ? 'warning' : 'neutral', 'heroicon-o-user-minus'),
                new MetricTile($l['utilization_rate'], MetricsNumber::percent($data->utilizationRate) . '%', __('panel-company::widgets.engagement_stats.utilization_rate_description', ['rate' => MetricsNumber::percent($data->utilizationRate), 'total' => $data->totalEmployees]), $utilTone, 'heroicon-o-chart-pie'),
                new MetricTile($l['first_time_users'], MetricsNumber::integer($data->firstTimeUsers), $l['first_time_users_description'], 'info', 'heroicon-o-star'),
            ],
        ];
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --compact --filter="renders the engagement tiles"`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/Metrics/EngagementTilesWidget.php app-modules/panel-company/tests/
git commit -m "feat(metrics): EngagementTilesWidget"
```

### Task 2.4: `NeverUsedTileWidget` + `EngagementInsightsTilesWidget`

**Files:**
- Create: `src/Filament/Widgets/Metrics/NeverUsedTileWidget.php`, `src/Filament/Widgets/Metrics/EngagementInsightsTilesWidget.php`
- Test: `MetricsWidgetsTest.php` (estender)

**Interfaces:**
- Consumes: `GetInsights` (Task 1.3), `MetricTile`, view `metric-tiles`.

- [ ] **Step 1: Teste**

```php
it('renders never-used and engagement insights tiles', function (): void {
    actingAsCompanyOwner();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\Metrics\NeverUsedTileWidget::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementInsightsTilesWidget::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk();
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="never-used and engagement insights"`
Expected: FAIL.

- [ ] **Step 3: Criar `NeverUsedTileWidget`**

`src/Filament/Widgets/Metrics/NeverUsedTileWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetInsights;
use TresPontosTech\PanelCompany\DTOs\MetricTile;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class NeverUsedTileWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.metrics.metric-tiles';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $data = resolve(GetInsights::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $tone = match (true) {
            $data->neverUsedRate > 50 => 'danger',
            $data->neverUsedRate > 20 => 'warning',
            default => 'success',
        };

        return [
            'columns' => 1,
            'tiles' => [
                new MetricTile(
                    __('panel-company::widgets.insights.not_used_benefit', ['rate' => MetricsNumber::percent($data->neverUsedRate)]),
                    $data->neverUsedCount . '/' . $data->totalEmployees,
                    __('panel-company::widgets.insights.not_used_benefit_description', ['count' => $data->neverUsedCount, 'total' => $data->totalEmployees]),
                    $tone,
                    'heroicon-o-exclamation-circle',
                ),
            ],
        ];
    }
}
```

- [ ] **Step 4: Criar `EngagementInsightsTilesWidget`**

`src/Filament/Widgets/Metrics/EngagementInsightsTilesWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetInsights;
use TresPontosTech\PanelCompany\DTOs\MetricTile;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class EngagementInsightsTilesWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.metrics.metric-tiles';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $data = resolve(GetInsights::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        $variation = $data->volume->variation;

        [$variationLabel, $variationTone, $variationIcon] = match (true) {
            $variation === null => [__('panel-company::widgets.insights.volume_stable'), 'neutral', 'heroicon-o-minus'],
            $variation > 0 => [__('panel-company::widgets.insights.volume_increase', ['rate' => abs($variation)]), 'success', 'heroicon-o-arrow-trending-up'],
            $variation < 0 => [__('panel-company::widgets.insights.volume_decrease', ['rate' => abs($variation)]), 'danger', 'heroicon-o-arrow-trending-down'],
            default => [__('panel-company::widgets.insights.volume_stable'), 'neutral', 'heroicon-o-minus'],
        };

        $tiles = [
            new MetricTile(
                $variationLabel,
                MetricsNumber::integer($data->volume->current),
                __('panel-company::widgets.insights.volume_comparison_description', ['current' => $data->volume->current, 'previous' => $data->volume->previous]),
                $variationTone,
                $variationIcon,
            ),
        ];

        if ($data->topUser !== null) {
            $tiles[] = new MetricTile(
                __('panel-company::widgets.insights.top_user'),
                $data->topUser->name,
                __('panel-company::widgets.insights.top_user_description', ['name' => $data->topUser->name, 'count' => $data->topUser->count]),
                'info',
                'heroicon-o-trophy',
            );
        }

        return ['columns' => 2, 'tiles' => $tiles];
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter="never-used and engagement insights"`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/Metrics/NeverUsedTileWidget.php app-modules/panel-company/src/Filament/Widgets/Metrics/EngagementInsightsTilesWidget.php app-modules/panel-company/tests/
git commit -m "feat(metrics): tiles de insights (nunca usaram, variação, top funcionário)"
```

### Task 2.5: `CreditFlowTilesWidget`

**Files:**
- Create: `src/Filament/Widgets/Metrics/CreditFlowTilesWidget.php`
- Test: `MetricsWidgetsTest.php` (estender)

**Interfaces:**
- Consumes: `GetCreditFlow` (Task 1.4), `MetricTile`, view `metric-tiles`.

- [ ] **Step 1: Teste**

```php
it('renders the credit flow tiles', function (): void {
    actingAsCompanyOwner();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditFlowTilesWidget::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk()
        ->assertSee(__('panel-company::widgets.credit_stats_metrics.distributed'));
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="renders the credit flow tiles"`
Expected: FAIL.

- [ ] **Step 3: Criar o widget**

`src/Filament/Widgets/Metrics/CreditFlowTilesWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditFlow;
use TresPontosTech\PanelCompany\DTOs\MetricTile;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;
use TresPontosTech\PanelCompany\Support\MetricsNumber;

class CreditFlowTilesWidget extends Widget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.metrics.metric-tiles';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $flow = resolve(GetCreditFlow::class)->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());

        /** @var array<string, string> $l */
        $l = trans('panel-company::widgets.credit_stats_metrics');

        return [
            'columns' => 4,
            'tiles' => [
                new MetricTile($l['distributed'], MetricsNumber::integer($flow->distributed), $l['distributed_description'], 'primary', 'heroicon-o-arrow-right-circle'),
                new MetricTile($l['used_in_period'], MetricsNumber::integer($flow->usedInPeriod), $l['used_in_period_description'], 'success', 'heroicon-o-check-badge'),
                new MetricTile($l['in_use'], MetricsNumber::integer($flow->inUse), $l['in_use_description'], 'info', 'heroicon-o-clock'),
                new MetricTile($l['available'], MetricsNumber::integer($flow->available), $l['available_description'], 'neutral', 'heroicon-o-credit-card'),
            ],
        ];
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --compact --filter="renders the credit flow tiles"`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/Metrics/CreditFlowTilesWidget.php app-modules/panel-company/tests/
git commit -m "feat(metrics): CreditFlowTilesWidget"
```

### Task 2.6: `DepartmentVolumeChart` (Chart.js sobre `GetDepartmentVolume`)

**Files:**
- Create: `src/Filament/Widgets/Metrics/DepartmentVolumeChart.php`
- Test: `MetricsWidgetsTest.php` (estender)

**Interfaces:**
- Consumes: `GetDepartmentVolume` (Task 1.5). Lê `departmentId` de `$this->filters` para destacar a barra selecionada.

- [ ] **Step 1: Teste**

```php
it('renders the department volume chart', function (): void {
    actingAsCompanyOwner();

    livewire(TresPontosTech\PanelCompany\Filament\Widgets\Metrics\DepartmentVolumeChart::class)
        ->set('filters', ['month' => now()->format('Y-m')])
        ->assertOk();
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="renders the department volume chart"`
Expected: FAIL.

- [ ] **Step 3: Criar o widget**

`src/Filament/Widgets/Metrics/DepartmentVolumeChart.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetDepartmentVolume;
use TresPontosTech\PanelCompany\DTOs\DepartmentVolumeRow;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class DepartmentVolumeChart extends ChartWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('panel-company::widgets.appointments_by_department.heading');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $volume = resolve(GetDepartmentVolume::class)->handle($tenant, $this->metricsPeriod());
        $selectedDepartmentId = data_get($this->filters, 'departmentId');

        $colors = array_map(
            fn (DepartmentVolumeRow $row): string => filled($selectedDepartmentId) && $row->id === (string) $selectedDepartmentId
                ? 'rgba(139, 92, 246, 0.9)'
                : 'rgba(59, 130, 246, 0.7)',
            $volume->rows,
        );

        return [
            'datasets' => [
                [
                    'label' => __('panel-company::widgets.appointments_by_department.dataset_label'),
                    'data' => array_map(fn (DepartmentVolumeRow $row): int => $row->total, $volume->rows),
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => array_map(fn (DepartmentVolumeRow $row): string => $row->name, $volume->rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]]];
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --compact --filter="renders the department volume chart"`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/Metrics/DepartmentVolumeChart.php app-modules/panel-company/tests/
git commit -m "feat(metrics): DepartmentVolumeChart sobre GetDepartmentVolume"
```

### Task 2.7: Refatorar `AppointmentVolumeChart` (tendência) e `CreditUsageTableWidget`

**Files:**
- Modify: `src/Filament/Widgets/Metrics/AppointmentVolumeChart.php` (já usa `GetSessionsTrend` + `metricsPeriod()`/`metricsFilters()` — apenas garantir `protected static ?int $sort` e título; sem mudança funcional).
- Modify: `src/Filament/Widgets/Metrics/CreditUsageTableWidget.php` — trocar `dateRange()` por `metricsPeriod()`.

**Interfaces:**
- Consumes: `metricsPeriod()` (Task 0.1), `filteredUserIds()` (concern existente).

- [ ] **Step 1: Ajustar `CreditUsageTableWidget::tableQuery()`**

Substituir o início de `tableQuery()` para usar o período unificado:

```php
private function tableQuery(): Builder
{
    $period = $this->metricsPeriod();

    $tenantId = Filament::getTenant()->id;
    $userIds = $this->filteredUserIds();

    return UserCredit::query()
        ->with(['holder', 'appointment.consultant'])
        ->join('appointments', 'appointments.id', '=', 'user_credits.appointment_id')
        ->where('user_credits.company_id', $tenantId)
        ->whereIn('user_credits.status', [UserCreditStatusEnum::Used, UserCreditStatusEnum::InUse])
        ->whereBetween('appointments.appointment_at', [$period->start, $period->end])
        ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_credits.holder_id', $userIds))
        ->select('user_credits.*')
        ->latest('appointments.appointment_at');
}
```

- [ ] **Step 2: Rodar o teste de página (cobertura indireta)**

Run: `php artisan test --compact --filter=MetricsTest`
Expected: PASS (a página renderiza com a tabela e o gráfico de tendência).

- [ ] **Step 3: Commit**

```bash
git add app-modules/panel-company/src/Filament/Widgets/Metrics/CreditUsageTableWidget.php app-modules/panel-company/src/Filament/Widgets/Metrics/AppointmentVolumeChart.php
git commit -m "refactor(metrics): tabela de uso e tendência usam metricsPeriod() unificado"
```

---

## Fase 3 — Página (5 abas, Estrutura C)

### Task 3.1: Reconstruir `Metrics::content()` + labels das abas

**Files:**
- Modify: `src/Filament/Pages/Metrics.php`
- Modify: `lang/pt_BR/resources.php`, `lang/en/resources.php` (chaves de aba)
- Test: `tests/Feature/Filament/MetricsTest.php` (estender)

**Interfaces:**
- Consumes: widgets das Fases 0–2 (reusados da home + novos da Metrics).

- [ ] **Step 1: Adicionar chaves de aba ao lang**

Em `lang/pt_BR/resources.php` → `pages.metrics`, adicionar/renomear:

```php
'tab_sessions' => 'Sessões',
'tab_adoption' => 'Adoção',
'tab_engagement' => 'Engajamento',
'tab_experience' => 'Experiência',
'tab_credits' => 'Créditos',
```

Em `lang/en/resources.php` → `pages.metrics`:

```php
'tab_sessions' => 'Sessions',
'tab_adoption' => 'Adoption',
'tab_engagement' => 'Engagement',
'tab_experience' => 'Experience',
'tab_credits' => 'Credits',
```

- [ ] **Step 2: Estender o teste da página**

Adicionar a `MetricsTest.php`:

```php
it('shows the five metrics tabs', function (): void {
    actingAsCompanyOwner();

    livewire(Metrics::class)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.metrics.tab_sessions'))
        ->assertSee(__('panel-company::resources.pages.metrics.tab_adoption'))
        ->assertSee(__('panel-company::resources.pages.metrics.tab_experience'))
        ->assertSee(__('panel-company::resources.pages.metrics.tab_credits'));
});
```

- [ ] **Step 3: Rodar e ver falhar**

Run: `php artisan test --compact --filter="shows the five metrics tabs"`
Expected: FAIL (abas antigas).

- [ ] **Step 4: Reescrever `content()` e imports**

Substituir o bloco `use ...Widgets...` e o método `content()` de `Metrics.php`. Imports dos widgets:

```php
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\AdoptionFunnelWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CategoryMixWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\CreditKpisWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\DepartmentAdoptionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\SatisfactionWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\StatusBreakdownWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard\TopConsultantsWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentStatsTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\AppointmentVolumeChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditFlowTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\CreditUsageTableWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\DepartmentVolumeChart;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementInsightsTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\EngagementTilesWidget;
use TresPontosTech\PanelCompany\Filament\Widgets\Metrics\NeverUsedTileWidget;
```

Novo `content()`:

```php
public function content(Schema $schema): Schema
{
    return $schema->components([
        $this->getFiltersFormContentComponent(),
        Tabs::make()
            ->persistTabInQueryString()
            ->tabs([
                Tab::make(__('panel-company::resources.pages.metrics.tab_sessions'))
                    ->schema([
                        Grid::make(12)->schema($this->getWidgetsSchemaComponents([
                            AppointmentStatsTilesWidget::class,
                            AppointmentVolumeChart::class,
                            StatusBreakdownWidget::class,
                            CategoryMixWidget::class,
                            DepartmentVolumeChart::class,
                        ])),
                    ]),
                Tab::make(__('panel-company::resources.pages.metrics.tab_adoption'))
                    ->schema([
                        Grid::make(12)->schema($this->getWidgetsSchemaComponents([
                            AdoptionFunnelWidget::class,
                            DepartmentAdoptionWidget::class,
                            NeverUsedTileWidget::class,
                        ])),
                    ]),
                Tab::make(__('panel-company::resources.pages.metrics.tab_engagement'))
                    ->schema([
                        Grid::make(12)->schema($this->getWidgetsSchemaComponents([
                            EngagementTilesWidget::class,
                            EngagementInsightsTilesWidget::class,
                        ])),
                    ]),
                Tab::make(__('panel-company::resources.pages.metrics.tab_experience'))
                    ->schema([
                        Grid::make(12)->schema($this->getWidgetsSchemaComponents([
                            SatisfactionWidget::class,
                            TopConsultantsWidget::class,
                        ])),
                    ]),
                Tab::make(__('panel-company::resources.pages.metrics.tab_credits'))
                    ->schema([
                        Grid::make(12)->schema($this->getWidgetsSchemaComponents([
                            CreditKpisWidget::class,
                            CreditFlowTilesWidget::class,
                            CreditUsageTableWidget::class,
                        ])),
                    ]),
            ]),
    ]);
}
```

> Nota: os widgets reusados da home mantêm seus `columnSpan` (Status=3, CategoryMix=3, Satisfaction=3, DepartmentAdoption=5, TopConsultants=4, AdoptionFunnel=5, CreditKpis=7). Ajuste fino de spans, se necessário, fica para a conferência visual (Fase 4). `Grid::make(12)` é o mesmo contexto da home, então os spans encaixam.

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=MetricsTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app-modules/panel-company/src/Filament/Pages/Metrics.php app-modules/panel-company/lang/ app-modules/panel-company/tests/
git commit -m "feat(metrics): página em 5 abas (Estrutura C) reusando widgets da home + novos"
```

---

## Fase 4 — Limpeza & verificação

### Task 4.1: Remover widgets antigos órfãos

**Files:**
- Delete: `src/Filament/Widgets/Metrics/{AppointmentStatsWidget,AppointmentsByCategoryChart,AppointmentsByDepartmentChart,EngagementStatsWidget,InsightsWidget,CreditStatsWidget}.php`

**Interfaces:**
- Pré-condição: nenhum desses está referenciado em `Metrics::content()` após a Task 3.1.

- [ ] **Step 1: Confirmar que não há referências**

Run: `grep -rn "AppointmentStatsWidget\|AppointmentsByCategoryChart\|AppointmentsByDepartmentChart\|EngagementStatsWidget\|InsightsWidget\|CreditStatsWidget" app-modules/panel-company/src`
Expected: nenhum resultado (os novos têm nomes distintos: `AppointmentStatsTilesWidget`, `DepartmentVolumeChart`).

- [ ] **Step 2: Remover os arquivos**

```bash
git rm app-modules/panel-company/src/Filament/Widgets/Metrics/AppointmentStatsWidget.php \
       app-modules/panel-company/src/Filament/Widgets/Metrics/AppointmentsByCategoryChart.php \
       app-modules/panel-company/src/Filament/Widgets/Metrics/AppointmentsByDepartmentChart.php \
       app-modules/panel-company/src/Filament/Widgets/Metrics/EngagementStatsWidget.php \
       app-modules/panel-company/src/Filament/Widgets/Metrics/InsightsWidget.php \
       app-modules/panel-company/src/Filament/Widgets/Metrics/CreditStatsWidget.php
```

- [ ] **Step 3: Rodar a suíte do módulo**

Run: `php artisan test --compact app-modules/panel-company`
Expected: PASS (sem referências quebradas).

- [ ] **Step 4: Commit**

```bash
git commit -m "chore(metrics): remove widgets antigos substituídos pela nova página"
```

### Task 4.2: Pint, suíte completa e conferência visual

- [ ] **Step 1: Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: sem erros de estilo.

- [ ] **Step 2: Suíte completa do módulo**

Run: `php artisan test --compact app-modules/panel-company`
Expected: PASS (incluindo `CommandDashboardWidgetsTest` e `SessionsTrendWidgetTest` — regressão da home verde).

- [ ] **Step 3: Conferência visual**

Abrir `http://localhost:8000/company/metrics`, navegar pelas 5 abas, alternar mês/intervalo/funcionário/departamento e confirmar que os painéis período-scoped reagem e os snapshots exibem o selo "estado atual". Ajustar `columnSpan` dos widgets se algo ficar desalinhado.

- [ ] **Step 4: Commit (se houver ajustes)**

```bash
git add -A && git commit -m "style(metrics): pint e ajustes finos de layout"
```

---

## Self-review (cobertura do spec)

- §2 #1 filtros reagem → Tasks 0.1, 0.2 (período-scoped) + 0.3 (snapshots com selo). ✓
- §2 #2 Estrutura C 5 abas → Task 3.1. ✓
- §2 #3 render híbrido → tiles (2.1–2.5), SVG reusado (0.2), Chart.js (2.6, 2.7), tabela (2.7). ✓
- §2 #4 consistência total → Actions/DTOs 1.1–1.5; tabela usa `metricsPeriod()` (2.7). ✓
- §2 #5 parametrizar home + regressão → 0.1/0.2 + trava de regressão em cada fase. ✓
- §2 #6 mix donut reusado → CategoryMixWidget em Sessões (3.1). ✓
- §2 #7 default 30 dias na Metrics → DatePickers pré-preenchidos + default 12m só sem filtros (0.1). ✓
- §4.2 todas as novas Actions/DTOs → Fase 1. ✓
- §9 fora de escopo (home intacta, snapshots permanecem snapshot, sem Alpine, sem export) → respeitado. ✓

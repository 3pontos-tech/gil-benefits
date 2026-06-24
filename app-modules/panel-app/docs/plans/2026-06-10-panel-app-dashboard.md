---
type: plan
title: "Dashboard panel-app (Hub de Bem-estar Financeiro)"
module: panel-app
status: completed
date: 2026-06-10
related:
  spec: panel-app/2026-06-10-panel-app-dashboard-design
---

# Dashboard panel-app (Hub de Bem-estar Financeiro) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transformar o dashboard do `panel-app` (página `UserDashboard`) num hub de bem-estar financeiro com hero de jornada (`LifeMoment` + momentum), zona de ação (próxima consultoria + plano/créditos), grid de apoio (temas + materiais) e histórico — na estética Editorial Quente.

**Architecture:** A agregação da jornada vive numa Action invocável (`BuildUserJourneyAction`) que devolve um DTO `final readonly` (`UserJourney`), mantendo os widgets "burros". Seis widgets Filament v5 customizados compõem a `UserDashboard`, cada um com sua view Blade. A identidade visual (fontes Fraunces/Hanken + tokens creme/coral + classes `.hub-*`) é adicionada como CSS puro ao `theme.css` do painel `app`.

**Tech Stack:** Laravel 12, Filament v5, Livewire v4, Tailwind v4, Pest v4. PHP 8.4.

---

## Decisões de arquitetura travadas (ler antes de começar)

1. **Namespace do módulo:** `TresPontosTech\PanelApp\` → `app-modules/panel-app/src/`. Logo: Actions em `src/Actions/`, DTOs em `src/DTOs/`, Widgets em `src/Filament/Widgets/`.
2. **Onde ficam as views dos widgets:** **NÃO** em `app-modules/panel-app/resources/views`. O `theme.css` do painel só faz `@source '../../../../resources/views/**/*'` (nível app) e `app/**`. Views dentro do módulo **não são varridas** pelo Tailwind → classes utilitárias seriam purgadas. Portanto as views novas vão em `resources/views/filament/app/widgets/` (nível app), exatamente como os widgets atuais usam `resources/views/filament/admin/widgets/`.
3. **Estilo:** as classes visuais (`.hub-card`, `.hub-tile`, etc.) são **CSS puro** em `theme.css` (imunes ao purge). As views usam essas classes + utilitários Tailwind de layout (`grid`, `gap`, `flex`) que são varridos por estarem em `resources/views/**`.
4. **Sem caching na Action** nesta entrega (evita testes intermitentes). O `monthly_appointments_left` já tem seu próprio cache de 1 min no model.
5. **Ordem canônica da escada `LifeMoment`** (spec ponto aberto #1): `Endebted → Messy → Payer → Saver → Investor`. Fica como `const STAGES` na Action. Se o produto pedir outra ordem, muda-se só essa constante.
6. **Strings em pt-BR hardcoded nas views** (segue o padrão do `latest-appointment.blade.php` atual, que hardcoda "Próxima consultoria"). Sem novos arquivos de tradução nesta entrega.
7. **Widgets que dependem de appointments** escutam `#[On('appointment-cancelled')]` para refresh (igual ao `UserCurrentPlanWidget` atual).

---

## File Structure

**Criar:**
- `app-modules/panel-app/src/DTOs/UserJourney.php` — DTO imutável da jornada.
- `app-modules/panel-app/src/Actions/BuildUserJourneyAction.php` — monta o `UserJourney` de um `User`.
- `app-modules/panel-app/src/Filament/Widgets/JourneyHeroWidget.php` + view `resources/views/filament/app/widgets/journey-hero.blade.php`.
- `app-modules/panel-app/src/Filament/Widgets/NextAppointmentWidget.php` + view `.../next-appointment.blade.php`.
- `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` + view `.../plan-credits.blade.php`.
- `app-modules/panel-app/src/Filament/Widgets/FinancialTopicsWidget.php` + view `.../financial-topics.blade.php`.
- `app-modules/panel-app/src/Filament/Widgets/SharedMaterialsWidget.php` + view `.../shared-materials.blade.php`.
- Testes em `app-modules/panel-app/tests/Feature/Filament/Widgets/` e `app-modules/panel-app/tests/Unit/Actions/`.

**Modificar:**
- `resources/css/filament/app/theme.css` — fontes + tokens + classes `.hub-*`.
- `app-modules/panel-app/src/Filament/Pages/UserDashboard.php` — novo `getWidgets()` e `getColumns()`.

**Remover (no fim, após grep confirmar que só o dashboard usa):**
- `app-modules/panel-app/src/Filament/Widgets/UserCurrentPlanWidget.php` + view `resources/views/filament/admin/widgets/plans-overview.blade.php`.
- `app-modules/panel-app/src/Filament/Widgets/LatestAppointmentWidget.php` + view `resources/views/filament/admin/widgets/latest-appointment.blade.php`.

> `AppointmentHistoryWidget` é mantido como está (já é full-width e funcional) e apenas reinserido no novo `getWidgets()`. `UserCreditStatsWidget` não é tocado (é usado em `UserCreditsPage`).

---

## Task 1: Fundação visual no theme.css

**Files:**
- Modify: `resources/css/filament/app/theme.css`

Sem TDD (é CSS). Validação por build.

- [ ] **Step 1: Adicionar fontes, tokens e classes do hub**

Edite `resources/css/filament/app/theme.css`, mantendo o conteúdo atual e **acrescentando ao final**:

```css
/* ===== Hub de bem-estar financeiro (Editorial Quente) ===== */
@import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Hanken+Grotesk:wght@400..800&display=swap');

.hub {
    --hub-cream: #FBF7F0;
    --hub-paper: #ffffff;
    --hub-coral: #d8643f;
    --hub-ink: #2b2522;
    --hub-muted: #8a7a64;
    --hub-border: #efe7d9;
    --hub-tile: #f5ead7;
    --hub-line: #f3ecdf;
    font-family: 'Hanken Grotesk', ui-sans-serif, system-ui, sans-serif;
    color: var(--hub-ink);
}

.hub-serif { font-family: 'Fraunces', ui-serif, Georgia, serif; }

.hub-eye {
    font-size: 11px;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #b08968;
    font-weight: 700;
}

.hub-card {
    background: var(--hub-paper);
    border: 1px solid var(--hub-border);
    border-radius: 16px;
    padding: 20px;
    height: 100%;
}

.hub-card--hero { background: linear-gradient(105deg, var(--hub-cream), #f6ecdb); border-color: #ece0cb; }

.hub-tile { background: var(--hub-tile); border-radius: 12px; padding: 12px 14px; }
.hub-tile b { font-family: 'Fraunces', serif; font-size: 26px; display: block; line-height: 1; color: var(--hub-ink); }
.hub-tile span { font-size: 11px; color: var(--hub-muted); }

.hub-chip { border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 600; display: inline-block; }
.hub-chip--on { background: var(--hub-coral); color: #fff; }
.hub-chip--off { background: var(--hub-tile); color: #a08a6c; }

.hub-btn {
    background: var(--hub-coral); color: #fff; border: 0; cursor: pointer;
    border-radius: 11px; padding: 11px 16px; font-size: 13px; font-weight: 700; text-align: center;
}
.hub-btn:disabled { opacity: .5; cursor: not-allowed; }
.hub-btn-ghost { border: 1px solid var(--hub-border); color: var(--hub-muted); background: transparent; border-radius: 11px; padding: 11px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }

.hub-av { border-radius: 50%; background: linear-gradient(135deg, var(--hub-coral), #f0a07e); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; flex: none; }

.hub-ring { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex: none; }
.hub-ring > i { width: 44px; height: 44px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-style: normal; font-weight: 700; font-size: 14px; font-family: 'Fraunces', serif; color: var(--hub-ink); }

.hub-ladder { display: flex; align-items: center; gap: 0; font-size: 12px; flex-wrap: wrap; }
.hub-ladder .seg { flex: 1; min-width: 16px; height: 2px; margin: 0 8px; background: var(--hub-coral); }
.hub-ladder .seg--todo { background: #efe2cc; }
.hub-ladder .past { color: #bcae9a; }
```

- [ ] **Step 2: Buildar e verificar que não quebra**

Run: `npm run build`
Expected: build conclui sem erro (procure por "built in" no output; ausência de erro de CSS).

- [ ] **Step 3: Commit**

```bash
git add resources/css/filament/app/theme.css
git commit -m "feat(panel-app): adiciona fontes e tokens do hub financeiro ao theme"
```

---

## Task 2: DTO UserJourney + BuildUserJourneyAction

**Files:**
- Create: `app-modules/panel-app/src/DTOs/UserJourney.php`
- Create: `app-modules/panel-app/src/Actions/BuildUserJourneyAction.php`
- Test: `app-modules/panel-app/tests/Unit/Actions/BuildUserJourneyActionTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Crie `app-modules/panel-app/tests/Unit/Actions/BuildUserJourneyActionTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\PanelApp\Actions\BuildUserJourneyAction;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

it('builds a journey from the user anamnese and appointments', function (): void {
    $user = User::factory()->create();
    UserAnamnese::factory()->create(['user_id' => $user->id, 'life_moment' => LifeMoment::Saver]);

    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'appointment_at' => now()->subDays(14),
    ]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::InvestmentAdvisory,
        'appointment_at' => now()->subDays(2),
    ]);
    // Mesmo tema repetido não conta duas vezes
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'appointment_at' => now()->subDay(),
    ]);
    // Não-concluída não entra na contagem
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'user_id' => $user->id,
        'category_type' => AppointmentCategoryEnum::RiskAndCompliance,
        'appointment_at' => now()->addDay(),
    ]);

    $journey = app(BuildUserJourneyAction::class)($user);

    expect($journey->stage)->toBe(LifeMoment::Saver)
        ->and($journey->stageIndex)->toBe(3) // Endebted,Messy,Payer,Saver
        ->and($journey->completedConsultations)->toBe(3)
        ->and($journey->topicsCovered)->toHaveCount(2)
        ->and($journey->topicsTotal)->toBe(6)
        ->and($journey->ratingsGiven)->toBe(0);
});

it('handles a user with no anamnese', function (): void {
    $user = User::factory()->create();

    $journey = app(BuildUserJourneyAction::class)($user);

    expect($journey->stage)->toBeNull()
        ->and($journey->stageIndex)->toBeNull()
        ->and($journey->isOnboarded())->toBeFalse()
        ->and($journey->completedConsultations)->toBe(0)
        ->and($journey->topicsCovered)->toBe([]);
});

it('counts ratings given by the user', function (): void {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create(['user_id' => $user->id]);
    AppointmentFeedback::factory()->create(['user_id' => $user->id, 'appointment_id' => $appointment->id, 'rating' => 5]);

    $journey = app(BuildUserJourneyAction::class)($user);

    expect($journey->ratingsGiven)->toBe(1);
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=BuildUserJourneyAction`
Expected: FAIL ("Class ... BuildUserJourneyAction not found").

- [ ] **Step 3: Criar o DTO**

Crie `app-modules/panel-app/src/DTOs/UserJourney.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\User\Enums\LifeMoment;

final readonly class UserJourney
{
    /**
     * @param  list<LifeMoment>  $stages
     * @param  list<AppointmentCategoryEnum>  $topicsCovered
     */
    public function __construct(
        public ?LifeMoment $stage,
        public ?int $stageIndex,
        public array $stages,
        public int $completedConsultations,
        public array $topicsCovered,
        public int $topicsTotal,
        public int $ratingsGiven,
        public ?CarbonInterface $lastConsultationAt,
    ) {}

    public function isOnboarded(): bool
    {
        return $this->stage instanceof LifeMoment;
    }

    public function stageLabel(): string|Htmlable|null
    {
        return $this->stage?->getLabel();
    }

    public function topicsCoveredCount(): int
    {
        return count($this->topicsCovered);
    }

    public function hasCovered(AppointmentCategoryEnum $category): bool
    {
        foreach ($this->topicsCovered as $covered) {
            if ($covered === $category) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Criar a Action**

Crie `app-modules/panel-app/src/Actions/BuildUserJourneyAction.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Actions;

use App\Models\Users\User;
use TresPontosTech\PanelApp\DTOs\UserJourney;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\User\Enums\LifeMoment;

class BuildUserJourneyAction
{
    /**
     * Ordem canônica da escada de maturidade financeira.
     *
     * @var list<LifeMoment>
     */
    public const STAGES = [
        LifeMoment::Endebted,
        LifeMoment::Messy,
        LifeMoment::Payer,
        LifeMoment::Saver,
        LifeMoment::Investor,
    ];

    public function __invoke(User $user): UserJourney
    {
        $stage = $user->anamnese?->life_moment;
        $stageIndex = $stage === null ? null : array_search($stage, self::STAGES, true);

        $completed = $user->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->get(['id', 'category_type', 'appointment_at']);

        /** @var list<AppointmentCategoryEnum> $topicsCovered */
        $topicsCovered = $completed
            ->pluck('category_type')
            ->unique(fn (AppointmentCategoryEnum $category): string => $category->value)
            ->values()
            ->all();

        $ratingsGiven = AppointmentFeedback::query()
            ->where('user_id', $user->getKey())
            ->count();

        return new UserJourney(
            stage: $stage,
            stageIndex: $stageIndex === false ? null : $stageIndex,
            stages: self::STAGES,
            completedConsultations: $completed->count(),
            topicsCovered: $topicsCovered,
            topicsTotal: count(AppointmentCategoryEnum::cases()),
            ratingsGiven: $ratingsGiven,
            lastConsultationAt: $completed->max('appointment_at'),
        );
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=BuildUserJourneyAction`
Expected: PASS (3 testes verdes).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/DTOs app-modules/panel-app/src/Actions app-modules/panel-app/tests/Unit
git commit -m "feat(panel-app): adiciona UserJourney DTO e BuildUserJourneyAction"
```

---

## Task 3: JourneyHeroWidget

**Files:**
- Create: `app-modules/panel-app/src/Filament/Widgets/JourneyHeroWidget.php`
- Create: `resources/views/filament/app/widgets/journey-hero.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/JourneyHeroWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Crie `app-modules/panel-app/tests/Feature/Filament/Widgets/JourneyHeroWidgetTest.php`:

```php
<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('renders the hero with the journey momentum', function (): void {
    UserAnamnese::factory()->create(['user_id' => $this->employee->id, 'life_moment' => LifeMoment::Saver]);
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->count(2)->create(['user_id' => $this->employee->id]);

    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee('Sua jornada financeira')
        ->assertSee('2'); // consultorias concluídas
});

it('shows an onboarding CTA when the user has no anamnese', function (): void {
    livewire(JourneyHeroWidget::class)
        ->assertSuccessful()
        ->assertSee('Complete sua anamnese');
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=JourneyHeroWidget`
Expected: FAIL ("Class ... JourneyHeroWidget not found").

- [ ] **Step 3: Criar o widget**

Crie `app-modules/panel-app/src/Filament/Widgets/JourneyHeroWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use TresPontosTech\PanelApp\Actions\BuildUserJourneyAction;

class JourneyHeroWidget extends Widget
{
    protected string $view = 'filament.app.widgets.journey-hero';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'journey' => app(BuildUserJourneyAction::class)($user),
        ];
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}
}
```

- [ ] **Step 4: Criar a view**

Crie `resources/views/filament/app/widgets/journey-hero.blade.php`:

```blade
@php use TresPontosTech\PanelApp\DTOs\UserJourney; @endphp
@php /** @var UserJourney $journey */ @endphp
<x-filament-widgets::widget>
    <div class="hub">
        <div class="hub-card hub-card--hero">
            <div class="flex flex-wrap items-stretch justify-between gap-6">
                <div class="min-w-[260px] flex-1">
                    <div class="hub-eye">Sua jornada financeira</div>

                    @if($journey->isOnboarded())
                        <div class="hub-serif" style="font-size:32px;font-weight:600;line-height:1.1;margin:8px 0 18px">
                            Você é <span style="font-style:italic;color:var(--hub-coral)">{{ $journey->stageLabel() }}</span>
                        </div>
                        <div class="hub-ladder">
                            @foreach($journey->stages as $i => $stage)
                                @if($i === $journey->stageIndex)
                                    <span class="hub-chip hub-chip--on">● {{ $stage->getLabel() }}</span>
                                @else
                                    <span class="past">{{ $stage->getLabel() }}</span>
                                @endif
                                @if(! $loop->last)
                                    <span class="seg {{ $i >= ($journey->stageIndex ?? -1) ? 'seg--todo' : '' }}"></span>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="hub-serif" style="font-size:28px;font-weight:600;line-height:1.1;margin:8px 0 16px">
                            Comece sua jornada
                        </div>
                        <p style="font-size:13px;color:var(--hub-muted);margin-bottom:14px">
                            Complete sua anamnese para descobrir seu momento financeiro.
                        </p>
                        <a class="hub-btn" href="{{ \TresPontosTech\PanelApp\Filament\Pages\AnamneseWizardPage::getUrl() }}">
                            Complete sua anamnese
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-2 content-center gap-2">
                    <div class="hub-tile"><b>{{ $journey->completedConsultations }}</b><span>consultorias</span></div>
                    <div class="hub-tile"><b>{{ $journey->topicsCoveredCount() }}/{{ $journey->topicsTotal }}</b><span>temas abordados</span></div>
                    <div class="hub-tile"><b>{{ $journey->ratingsGiven }}</b><span>avaliações dadas</span></div>
                    <div class="hub-tile">
                        <b>{{ $journey->lastConsultationAt?->diffForHumans(null, true) ?? '—' }}</b>
                        <span>desde a última</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
```

> Nota: confirme o nome real da página de anamnese (`AnamneseWizardPage`) — está em `app-modules/panel-app/src/Filament/Pages/AnamneseWizardPage.php`. Se o método `getUrl()` exigir tenant, já está no contexto do painel.

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=JourneyHeroWidget`
Expected: PASS (2 testes).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/JourneyHeroWidget.php resources/views/filament/app/widgets/journey-hero.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/JourneyHeroWidgetTest.php
git commit -m "feat(panel-app): adiciona JourneyHeroWidget"
```

---

## Task 4: NextAppointmentWidget

**Files:**
- Create: `app-modules/panel-app/src/Filament/Widgets/NextAppointmentWidget.php`
- Create: `resources/views/filament/app/widgets/next-appointment.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Crie `app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php`:

```php
<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Widgets\NextAppointmentWidget;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('shows the next upcoming appointment', function (): void {
    $consultant = Consultant::factory()->create(['name' => 'Dr. João Silva']);
    Appointment::factory()->withStatus(AppointmentStatus::Active)->create([
        'user_id' => $this->employee->id,
        'consultant_id' => $consultant->id,
        'appointment_at' => now()->addDays(3),
    ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Próxima consultoria')
        ->assertSee('Dr. João Silva');
});

it('ignores past appointments and shows the empty state', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->id,
        'appointment_at' => now()->subDays(3),
    ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Agende sua próxima consultoria');
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=NextAppointmentWidget`
Expected: FAIL ("Class ... NextAppointmentWidget not found").

- [ ] **Step 3: Criar o widget**

Crie `app-modules/panel-app/src/Filament/Widgets/NextAppointmentWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class NextAppointmentWidget extends Widget
{
    protected string $view = 'filament.app.widgets.next-appointment';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 4];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var Appointment|null $appointment */
        $appointment = $user->appointments()
            ->with('consultant')
            ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Active->value])
            ->where('appointment_at', '>=', now())
            ->orderBy('appointment_at')
            ->first();

        $hasConfirmedStatus = $appointment !== null && in_array($appointment->status, [
            AppointmentStatus::Active,
            AppointmentStatus::Completed,
        ], true);

        return [
            'appointment' => $appointment,
            'hasConfirmedStatus' => $hasConfirmedStatus,
            'bookUrl' => AppointmentResource::getUrl('create'),
        ];
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}
}
```

- [ ] **Step 4: Criar a view**

Crie `resources/views/filament/app/widgets/next-appointment.blade.php`:

```blade
@php use TresPontosTech\Consultants\Models\Consultant; @endphp
<x-filament-widgets::widget>
    <div class="hub">
        <div class="hub-card">
            <div class="hub-eye">Próxima consultoria</div>

            @if(! $appointment)
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <p style="font-size:14px;font-weight:600;margin-bottom:4px">Agende sua próxima consultoria</p>
                    <p style="font-size:12px;color:var(--hub-muted);margin-bottom:14px">Você não tem consultorias futuras.</p>
                    <a class="hub-btn" href="{{ $bookUrl }}">Agendar consultoria</a>
                </div>
            @else
                @php $consultant = $appointment->consultant; @endphp
                <div class="mt-3 flex items-center gap-3">
                    <div class="hub-av" style="width:46px;height:46px;font-size:15px">
                        {{ \Illuminate\Support\Str::of($consultant?->name ?? '?')->substr(0, 2)->upper() }}
                    </div>
                    <div class="flex-1">
                        <div style="font-weight:700;font-size:15px">
                            {{ $consultant instanceof Consultant ? $consultant->name : 'Aguardando atribuição' }}
                        </div>
                        <div style="font-size:13px;color:var(--hub-muted)">{{ $appointment->category_type->getLabel() }}</div>
                    </div>
                    <div class="text-right">
                        <div class="hub-serif" style="font-size:18px;font-weight:600">
                            {{ $appointment->appointment_at->format('d/m · H\hi') }}
                        </div>
                        <span class="hub-chip" style="background:#fff1ec;color:#c2553a">
                            {{ $appointment->appointment_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    @if($appointment->meeting_url && $hasConfirmedStatus)
                        <a class="hub-btn" href="{{ $appointment->meeting_url }}" target="_blank" rel="noopener noreferrer">
                            📹 Entrar na reunião
                        </a>
                    @else
                        <span class="hub-btn" style="opacity:.5;cursor:not-allowed">Aguardando confirmação</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=NextAppointmentWidget`
Expected: PASS (2 testes).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/NextAppointmentWidget.php resources/views/filament/app/widgets/next-appointment.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php
git commit -m "feat(panel-app): adiciona NextAppointmentWidget (próxima consultoria futura)"
```

---

## Task 5: PlanCreditsWidget

**Files:**
- Create: `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php`
- Create: `resources/views/filament/app/widgets/plan-credits.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Crie `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`:

```php
<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\Billing\Core\Models\UserCredit;

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
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=PlanCreditsWidget`
Expected: FAIL ("Class ... PlanCreditsWidget not found").

- [ ] **Step 3: Criar o widget**

Crie `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\UserCredit;

class PlanCreditsWidget extends Widget
{
    protected string $view = 'filament.app.widgets.plan-credits';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $monthlyLimit = $this->resolveMonthlyLimit($user);

        $availableCredits = UserCredit::query()
            ->where('holder_id', $user->getKey())
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        return [
            'planName' => $this->resolvePlanName($user),
            'monthlyLeft' => $user->monthly_appointments_left,
            'monthlyLimit' => $monthlyLimit,
            'availableCredits' => $availableCredits,
            'canCreateAppointment' => $user->canCreateAppointment(),
        ];
    }

    private function resolveMonthlyLimit(User $user): int
    {
        $contractualPlan = CompanyPlan::query()
            ->whereIn('company_id', $user->companies()->select('companies.id'))
            ->where('status', CompanyPlanStatusEnum::Active)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->first();

        if ($contractualPlan !== null) {
            return (int) $contractualPlan->monthly_appointments_per_employee;
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->activeSubscription()->with('price')->first();

        return (int) ($subscription?->price?->monthly_appointments ?? 0);
    }

    private function resolvePlanName(User $user): ?string
    {
        $contractualPlan = CompanyPlan::query()
            ->whereIn('company_id', $user->companies()->select('companies.id'))
            ->where('status', CompanyPlanStatusEnum::Active)
            ->with('plan')
            ->first();

        if ($contractualPlan !== null) {
            return $contractualPlan->plan->name;
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->activeSubscription()->with('price.plan')->first();

        return $subscription?->price?->plan?->name;
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}

    public function redirectToAppointmentCreation(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->canCreateAppointment()) {
            Notification::make()
                ->title(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
                ->body(__('panel-app::resources.appointments.pages.create.no_appointments_available'))
                ->danger()
                ->send();

            return;
        }

        redirect()->intended(AppointmentResource::getUrl('create'));
    }
}
```

- [ ] **Step 4: Criar a view**

Crie `resources/views/filament/app/widgets/plan-credits.blade.php`:

```blade
@php
    $pct = $monthlyLimit > 0 ? min(100, (int) round((($monthlyLimit - $monthlyLeft) / $monthlyLimit) * 100)) : 0;
@endphp
<x-filament-widgets::widget>
    <div class="hub">
        <div class="hub-card flex flex-col justify-between">
            <div class="hub-eye">Plano & créditos</div>

            <div class="my-3 flex items-center gap-3">
                <div class="hub-ring" style="background:conic-gradient(var(--hub-coral) 0 {{ $pct }}%, #efe2cc {{ $pct }}% 100%)">
                    <i>{{ $monthlyLeft }}/{{ $monthlyLimit }}</i>
                </div>
                <div style="font-size:13px;color:var(--hub-muted);line-height:1.5">
                    agendamentos<br>este mês<br>
                    <b style="color:var(--hub-ink)">+{{ $availableCredits }} créditos</b> avulsos
                </div>
            </div>

            @if($planName)
                <p style="font-size:12px;color:var(--hub-muted);margin-bottom:10px">{{ $planName }}</p>
            @endif

            <button
                type="button"
                class="hub-btn"
                wire:click="redirectToAppointmentCreation"
                @disabled(! $canCreateAppointment)
            >
                Agendar consultoria
            </button>
        </div>
    </div>
</x-filament-widgets::widget>
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=PlanCreditsWidget`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php resources/views/filament/app/widgets/plan-credits.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php
git commit -m "feat(panel-app): adiciona PlanCreditsWidget"
```

---

## Task 6: FinancialTopicsWidget

**Files:**
- Create: `app-modules/panel-app/src/Filament/Widgets/FinancialTopicsWidget.php`
- Create: `resources/views/filament/app/widgets/financial-topics.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/FinancialTopicsWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Crie `app-modules/panel-app/tests/Feature/Filament/Widgets/FinancialTopicsWidgetTest.php`:

```php
<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Widgets\FinancialTopicsWidget;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('marks covered topics and renders all categories', function (): void {
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $this->employee->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
    ]);

    livewire(FinancialTopicsWidget::class)
        ->assertSuccessful()
        ->assertSee('Temas financeiros')
        ->assertSee(AppointmentCategoryEnum::PersonalFinance->getLabel())
        ->assertSee(AppointmentCategoryEnum::RiskAndCompliance->getLabel());
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=FinancialTopicsWidget`
Expected: FAIL ("Class ... FinancialTopicsWidget not found").

- [ ] **Step 3: Criar o widget**

Crie `app-modules/panel-app/src/Filament/Widgets/FinancialTopicsWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;
use TresPontosTech\PanelApp\Actions\BuildUserJourneyAction;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

class FinancialTopicsWidget extends Widget
{
    protected string $view = 'filament.app.widgets.financial-topics';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $journey = app(BuildUserJourneyAction::class)($user);

        return [
            'categories' => AppointmentCategoryEnum::cases(),
            'journey' => $journey,
        ];
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}
}
```

- [ ] **Step 4: Criar a view**

Crie `resources/views/filament/app/widgets/financial-topics.blade.php`:

```blade
@php use TresPontosTech\PanelApp\DTOs\UserJourney; @endphp
@php /** @var UserJourney $journey */ @endphp
<x-filament-widgets::widget>
    <div class="hub">
        <div class="hub-card">
            <div class="hub-eye">Temas financeiros</div>
            <p style="font-size:12px;color:var(--hub-muted);margin:6px 0 12px">
                Explore áreas que você ainda não abordou
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach($categories as $category)
                    @if($journey->hasCovered($category))
                        <span class="hub-chip hub-chip--on">✓ {{ $category->getLabel() }}</span>
                    @else
                        <span class="hub-chip hub-chip--off">+ {{ $category->getLabel() }}</span>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=FinancialTopicsWidget`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/FinancialTopicsWidget.php resources/views/filament/app/widgets/financial-topics.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/FinancialTopicsWidgetTest.php
git commit -m "feat(panel-app): adiciona FinancialTopicsWidget"
```

---

## Task 7: SharedMaterialsWidget

**Files:**
- Create: `app-modules/panel-app/src/Filament/Widgets/SharedMaterialsWidget.php`
- Create: `resources/views/filament/app/widgets/shared-materials.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/SharedMaterialsWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Crie `app-modules/panel-app/tests/Feature/Filament/Widgets/SharedMaterialsWidgetTest.php`:

```php
<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Widgets\SharedMaterialsWidget;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('lists documents shared with the employee', function (): void {
    $consultant = Consultant::factory()->create();
    $document = Document::factory()->create(['title' => 'Guia de reserva de emergência']);
    DocumentShare::factory()->create([
        'document_id' => $document->id,
        'consultant_id' => $consultant->id,
        'employee_id' => $this->employee->id,
        'active' => true,
    ]);

    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSee('Materiais compartilhados')
        ->assertSee('Guia de reserva de emergência');
});

it('shows an empty state when nothing is shared', function (): void {
    livewire(SharedMaterialsWidget::class)
        ->assertSuccessful()
        ->assertSee('Nenhum material compartilhado ainda');
});
```

> Antes de implementar, confirme que existem as factories `Document::factory()` e `DocumentShare::factory()` (vistas em `app-modules/consultants/database/factories`). Se `DocumentShare` não tiver factory, crie uma mínima seguindo o padrão das demais, ou monte o registro com `DocumentShare::create([...])` no teste.

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter=SharedMaterialsWidget`
Expected: FAIL ("Class ... SharedMaterialsWidget not found").

- [ ] **Step 3: Criar o widget**

Crie `app-modules/panel-app/src/Filament/Widgets/SharedMaterialsWidget.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Widgets\Widget;

class SharedMaterialsWidget extends Widget
{
    protected string $view = 'filament.app.widgets.shared-materials';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $shares = $user->sharedDocuments()
            ->where('active', true)
            ->with('document')
            ->latest()
            ->limit(4)
            ->get();

        return [
            'shares' => $shares,
        ];
    }
}
```

- [ ] **Step 4: Criar a view**

Crie `resources/views/filament/app/widgets/shared-materials.blade.php`:

```blade
<x-filament-widgets::widget>
    <div class="hub">
        <div class="hub-card">
            <div class="hub-eye">Materiais compartilhados</div>

            @if($shares->isEmpty())
                <p style="font-size:13px;color:var(--hub-muted);margin-top:10px">
                    Nenhum material compartilhado ainda
                </p>
            @else
                <div class="mt-2" style="font-size:13px">
                    @foreach($shares as $share)
                        @php $document = $share->document; @endphp
                        @continue($document === null)
                        <div class="flex items-center justify-between"
                             style="padding:9px 0;{{ $loop->last ? '' : 'border-bottom:1px solid var(--hub-line)' }}">
                            <span>📄 {{ $document->title }}</span>
                            <span style="color:var(--hub-coral);font-weight:600">abrir</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
```

> O link real do material (`$document->hasLink() ? $document->link : $document->getFirstMediaUrl('documents')`) pode ser ligado ao "abrir" numa iteração seguinte; nesta entrega o foco é listar. Mantenha simples (YAGNI).

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=SharedMaterialsWidget`
Expected: PASS.

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/SharedMaterialsWidget.php resources/views/filament/app/widgets/shared-materials.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/SharedMaterialsWidgetTest.php
git commit -m "feat(panel-app): adiciona SharedMaterialsWidget"
```

---

## Task 8: Montar a UserDashboard e remover widgets legados

**Files:**
- Modify: `app-modules/panel-app/src/Filament/Pages/UserDashboard.php`
- Delete: `app-modules/panel-app/src/Filament/Widgets/UserCurrentPlanWidget.php`, `app-modules/panel-app/src/Filament/Widgets/LatestAppointmentWidget.php`, `resources/views/filament/admin/widgets/plans-overview.blade.php`, `resources/views/filament/admin/widgets/latest-appointment.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Pages/UserDashboardTest.php`

- [ ] **Step 1: Confirmar que os widgets legados só são usados no dashboard**

Run:
```bash
grep -rn "UserCurrentPlanWidget\|LatestAppointmentWidget\|plans-overview\|latest-appointment" app-modules app resources --include="*.php" --include="*.blade.php"
```
Expected: as únicas referências são em `UserDashboard.php` e nos próprios arquivos/views/testes desses widgets. Se houver uso em outro lugar, **não delete** — apenas remova do dashboard e registre no plano.

- [ ] **Step 2: Escrever o teste do dashboard que falha**

Crie `app-modules/panel-app/tests/Feature/Filament/Pages/UserDashboardTest.php`:

```php
<?php

declare(strict_types=1);

use TresPontosTech\PanelApp\Filament\Pages\UserDashboard;
use TresPontosTech\PanelApp\Filament\Widgets\AppointmentHistoryWidget;
use TresPontosTech\PanelApp\Filament\Widgets\FinancialTopicsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\PanelApp\Filament\Widgets\NextAppointmentWidget;
use TresPontosTech\PanelApp\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\SharedMaterialsWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSubscribedEmployee();
});

it('renders the dashboard with the hub widgets', function (): void {
    livewire(UserDashboard::class)
        ->assertSuccessful()
        ->assertSeeLivewire(JourneyHeroWidget::class)
        ->assertSeeLivewire(NextAppointmentWidget::class)
        ->assertSeeLivewire(PlanCreditsWidget::class)
        ->assertSeeLivewire(FinancialTopicsWidget::class)
        ->assertSeeLivewire(SharedMaterialsWidget::class)
        ->assertSeeLivewire(AppointmentHistoryWidget::class);
});
```

- [ ] **Step 3: Rodar e ver falhar**

Run: `php artisan test --compact --filter=UserDashboardTest`
Expected: FAIL (widgets novos ainda não estão registrados na página).

- [ ] **Step 4: Atualizar a UserDashboard**

Substitua o conteúdo de `app-modules/panel-app/src/Filament/Pages/UserDashboard.php` por:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use Filament\Pages\Dashboard;
use TresPontosTech\PanelApp\Filament\Widgets\AppointmentHistoryWidget;
use TresPontosTech\PanelApp\Filament\Widgets\FinancialTopicsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\JourneyHeroWidget;
use TresPontosTech\PanelApp\Filament\Widgets\NextAppointmentWidget;
use TresPontosTech\PanelApp\Filament\Widgets\PlanCreditsWidget;
use TresPontosTech\PanelApp\Filament\Widgets\SharedMaterialsWidget;

class UserDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return 6;
    }

    public function getWidgets(): array
    {
        return [
            JourneyHeroWidget::make(),
            NextAppointmentWidget::make(),
            PlanCreditsWidget::make(),
            FinancialTopicsWidget::make(),
            SharedMaterialsWidget::make(),
            AppointmentHistoryWidget::make(),
        ];
    }
}
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=UserDashboardTest`
Expected: PASS.

- [ ] **Step 6: Remover os widgets legados (apenas se o Step 1 confirmou)**

```bash
git rm app-modules/panel-app/src/Filament/Widgets/UserCurrentPlanWidget.php \
       app-modules/panel-app/src/Filament/Widgets/LatestAppointmentWidget.php \
       resources/views/filament/admin/widgets/plans-overview.blade.php \
       resources/views/filament/admin/widgets/latest-appointment.blade.php
```

- [ ] **Step 7: Rodar a suíte do módulo inteira + build**

Run: `php artisan test --compact app-modules/panel-app`
Expected: PASS (toda a suíte do panel-app verde).

Run: `npm run build`
Expected: build sem erro.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat(panel-app): monta dashboard do hub financeiro e remove widgets legados"
```

---

## Self-Review (preenchido pelo autor do plano)

**Spec coverage:**
- Hero jornada → Task 3. Próxima consultoria → Task 4. Plano & créditos → Task 5. Temas → Task 6. Materiais → Task 7. Histórico → mantido (Task 8). Backend (Action+DTO) → Task 2. Tema/fontes → Task 1. Wiring + remoção legado → Task 8. ✅ Sem lacunas.
- "Estágio estático honesto": a view do hero usa copy "Você é {estágio}" (sem prometer evolução). ✅
- Estados vazios (sem anamnese, sem próxima, sem materiais): cobertos por testes nas Tasks 3, 4, 7. ✅

**Placeholder scan:** nenhuma das red flags ("TBD", "add error handling", "similar to Task N") — todo passo tem código real. Notas marcadas como "confirme X" são checagens de existência (factories/nomes), não lacunas de implementação. ✅

**Type consistency:** `UserJourney` propriedades (`stage`, `stageIndex`, `stages`, `completedConsultations`, `topicsCovered`, `topicsTotal`, `ratingsGiven`, `lastConsultationAt`) + métodos (`isOnboarded`, `stageLabel`, `topicsCoveredCount`, `hasCovered`) usados de forma idêntica nas views (Tasks 3 e 6) e na Action (Task 2). `BuildUserJourneyAction::STAGES` referenciado só dentro da Action. Widgets seguem o mesmo formato (`protected string $view`, `columnSpan`, `getViewData`). ✅

**Pontos abertos herdados do spec** (não bloqueiam a implementação; default já codificado):
1. Ordem da escada `LifeMoment` = `BuildUserJourneyAction::STAGES` (Endebted→Messy→Payer→Saver→Investor) — confirmar com produto.
2. Denominador de temas = 6 (todas as categorias). Filtrar B2B fica para depois.
3. Link real dos materiais (Task 7 nota) — iteração futura.
```

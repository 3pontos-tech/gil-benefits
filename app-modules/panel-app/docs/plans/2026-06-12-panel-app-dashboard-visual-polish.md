---
type: plan
title: "Polimento visual do dashboard do colaborador"
module: panel-app
status: completed
date: 2026-06-12
related:
  spec: panel-app/2026-06-12-panel-app-dashboard-visual-polish-design
---

# Polimento visual do dashboard do colaborador — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Devolver a informação do plano ao card "Plano & créditos" (nome + status de cara; descrição + features num modal), listar todos os motivos de bloqueio do CTA, e dar contexto ao botão "Aguardando confirmação" via tooltip — tudo sem alterar comportamento.

**Architecture:** A resolução do plano (contratual → fallback assinatura) é consolidada num método `resolvePlan()` que devolve um DTO `final readonly` `PlanSummary`, eliminando a query duplicada atual (`resolvePlanName` + `resolveMonthlyLimit`). O modal usa um `Action` nativo do Filament. As views permanecem "burras".

**Tech Stack:** Laravel 12, Filament v5, Livewire v4, Tailwind v4, Pest v4. PHP 8.4.

---

## Decisões travadas (do spec 2026-06-12)

1. Card layout **A**: nome + badge de status + anel consultas/mês + créditos + CTA. Descrição/features atrás de "Ver plano →" (modal).
2. Modal mostra descrição + features. Features por origem: contratual = consultas/mês; assinatura = consultas/mês + WhatsApp + materiais (quando habilitados).
3. `Price` usa snake_case (`monthly_appointments`, `whatsapp_enabled`, `materials_enabled`) — usar essas (o legado usava camelCase, provável bug silencioso).
4. Mensagem de bloqueio: listar **todos** os motivos; "sem agendamentos" passa a considerar crédito.
5. "Aguardando confirmação" mantém aparência de botão + `title`/tooltip.
6. Sem mudança de comportamento: CTA agendar, cancelar, `canCreateAppointment`/`hasOngoingAppointment` intactos.

---

## File Structure

**Criar:**
- `app-modules/panel-app/src/DTOs/PlanSummary.php` — DTO imutável do plano.
- `resources/views/filament/app/widgets/partials/plan-details.blade.php` — conteúdo do modal.

**Modificar:**
- `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` — `resolvePlan()`, `blockReasons()`, `viewPlanAction()`, `HasActions`/`HasSchemas`.
- `resources/views/filament/app/widgets/plan-credits.blade.php` — layout A + motivos + trigger do modal.
- `resources/views/filament/app/widgets/next-appointment.blade.php` — `title` no botão de espera.
- `app-modules/panel-app/lang/pt_BR/widgets.php` e `app-modules/panel-app/lang/en/widgets.php` — strings novas.
- `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php` — novos casos.
- `app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php` — caso do tooltip.

---

## Task 1: Strings de tradução

**Files:**
- Modify: `app-modules/panel-app/lang/pt_BR/widgets.php`
- Modify: `app-modules/panel-app/lang/en/widgets.php`

Sem TDD (dados). Validação por uso nos testes seguintes.

- [ ] **Step 1: Adicionar grupos ao pt_BR**

Em `app-modules/panel-app/lang/pt_BR/widgets.php`, adicione estes grupos ao array retornado (ao lado de `plans_overview`):

```php
    'plan_status' => [
        'active' => 'Ativo',
        'inactive' => 'Inativo',
        'expired' => 'Expirado',
    ],

    'plan_details' => [
        'view_plan' => 'Ver plano',
        'close' => 'Fechar',
        'monthly_appointments' => '{1} :count consulta por mês|[2,*] :count consultas por mês',
        'whatsapp' => 'Acesso ao WhatsApp',
        'materials' => 'Materiais exclusivos',
    ],

    'next_appointment' => [
        'awaiting_tooltip' => 'O link da reunião aparecerá aqui assim que a consultoria for confirmada.',
    ],
```

- [ ] **Step 2: Adicionar os mesmos grupos ao en**

Em `app-modules/panel-app/lang/en/widgets.php`, adicione:

```php
    'plan_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'expired' => 'Expired',
    ],

    'plan_details' => [
        'view_plan' => 'View plan',
        'close' => 'Close',
        'monthly_appointments' => '{1} :count appointment per month|[2,*] :count appointments per month',
        'whatsapp' => 'WhatsApp access',
        'materials' => 'Exclusive materials',
    ],

    'next_appointment' => [
        'awaiting_tooltip' => 'The meeting link will appear here once the consultation is confirmed.',
    ],
```

- [ ] **Step 3: Commit**

```bash
git add app-modules/panel-app/lang/pt_BR/widgets.php app-modules/panel-app/lang/en/widgets.php
git commit -m "feat(panel-app): strings do card de plano (status, detalhes, tooltip)"
```

---

## Task 2: DTO `PlanSummary`

**Files:**
- Create: `app-modules/panel-app/src/DTOs/PlanSummary.php`

DTO é data holder puro — sem teste dedicado; é exercitado pelos testes do widget (Tasks 3-5).

- [ ] **Step 1: Criar o DTO**

Crie `app-modules/panel-app/src/DTOs/PlanSummary.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\DTOs;

final readonly class PlanSummary
{
    /**
     * @param  'active'|'inactive'|'expired'  $status
     * @param  list<string>  $features
     */
    public function __construct(
        public string $name,
        public string $status,
        public ?string $description,
        public int $monthlyLimit,
        public array $features,
    ) {}
}
```

- [ ] **Step 2: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/DTOs/PlanSummary.php
git commit -m "feat(panel-app): adiciona DTO PlanSummary"
```

---

## Task 3: `PlanCreditsWidget` — nome + status no card

**Files:**
- Modify: `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php`
- Modify: `resources/views/filament/app/widgets/plan-credits.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Adicione ao `PlanCreditsWidgetTest.php` (no topo do arquivo, após o primeiro `it`):

```php
it('shows the plan name and an active status badge', function (): void {
    actingAsEmployee();

    livewire(PlanCreditsWidget::class)
        ->assertSuccessful()
        ->assertSee(__('panel-app::widgets.plan_status.active'));
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="shows the plan name and an active status badge"`
Expected: FAIL (badge de status ainda não renderizada).

- [ ] **Step 3: Reescrever o widget**

Substitua todo o conteúdo de `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` por:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use TresPontosTech\PanelApp\DTOs\PlanSummary;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\UserCredit;

class PlanCreditsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.app.widgets.plan-credits';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    private ?PlanSummary $resolvedPlan = null;

    private bool $planResolved = false;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $plan = $this->plan();

        $availableCredits = UserCredit::query()
            ->where('holder_id', $user->getKey())
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        return [
            'plan' => $plan,
            'monthlyLeft' => $user->monthly_appointments_left,
            'monthlyLimit' => $plan?->monthlyLimit ?? 0,
            'availableCredits' => $availableCredits,
            'canCreateAppointment' => $user->canCreateAppointment(),
            'blockReasons' => $this->blockReasons($user),
        ];
    }

    public function viewPlanAction(): Action
    {
        $plan = $this->plan();

        return Action::make('viewPlan')
            ->link()
            ->label(__('panel-app::widgets.plan_details.view_plan'))
            ->visible($plan instanceof PlanSummary)
            ->modalHeading($plan?->name)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('panel-app::widgets.plan_details.close'))
            ->modalContent(fn (): View => view('filament.app.widgets.partials.plan-details', [
                'plan' => $this->plan(),
            ]));
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

    /**
     * @return list<string>
     */
    private function blockReasons(User $user): array
    {
        $reasons = [];

        if ($user->hasOngoingAppointment()) {
            $reasons[] = __('panel-app::widgets.plans_overview.ongoing_appointment');
        }

        if (! ($user->monthly_appointments_left > 0 || $user->hasAvailableCredit())) {
            $reasons[] = __('panel-app::widgets.plans_overview.no_appointments_available');
        }

        return $reasons;
    }

    private function plan(): ?PlanSummary
    {
        if (! $this->planResolved) {
            /** @var User $user */
            $user = auth()->user();
            $this->resolvedPlan = $this->resolvePlan($user);
            $this->planResolved = true;
        }

        return $this->resolvedPlan;
    }

    private function resolvePlan(User $user): ?PlanSummary
    {
        $contractualPlan = CompanyPlan::query()
            ->whereIn('company_id', $user->companies()->select('companies.id'))
            ->where('status', CompanyPlanStatusEnum::Active)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with('plan')
            ->first();

        if ($contractualPlan !== null && $contractualPlan->plan !== null) {
            $limit = (int) $contractualPlan->monthly_appointments_per_employee;

            return new PlanSummary(
                name: $contractualPlan->plan->name,
                status: 'active',
                description: $contractualPlan->plan->description,
                monthlyLimit: $limit,
                features: [
                    trans_choice('panel-app::widgets.plan_details.monthly_appointments', $limit, ['count' => $limit]),
                ],
            );
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->activeSubscription()->with('price.plan')->first();
        $price = $subscription?->price;
        $plan = $price?->plan;

        if ($subscription === null || $price === null || $plan === null) {
            return null;
        }

        $status = $subscription->ends_at !== null
            ? 'expired'
            : ($subscription->stripe_status === 'active' ? 'active' : 'inactive');

        $limit = (int) $price->monthly_appointments;

        $features = [
            trans_choice('panel-app::widgets.plan_details.monthly_appointments', $limit, ['count' => $limit]),
        ];

        if ($price->whatsapp_enabled) {
            $features[] = __('panel-app::widgets.plan_details.whatsapp');
        }

        if ($price->materials_enabled) {
            $features[] = __('panel-app::widgets.plan_details.materials');
        }

        return new PlanSummary(
            name: $plan->name,
            status: $status,
            description: $plan->description,
            monthlyLimit: $limit,
            features: $features,
        );
    }
}
```

- [ ] **Step 4: Atualizar a view (layout A — topo com nome + status)**

Substitua todo o conteúdo de `resources/views/filament/app/widgets/plan-credits.blade.php` por:

```blade
@php
    $pct = $monthlyLimit > 0 ? min(100, (int) round((($monthlyLimit - $monthlyLeft) / $monthlyLimit) * 100)) : 0;
    $statusColors = [
        'active' => 'text-green-700 bg-green-50 dark:text-green-300 dark:bg-green-500/10',
        'inactive' => 'text-gray-600 bg-gray-100 dark:text-gray-300 dark:bg-white/10',
        'expired' => 'text-danger-700 bg-danger-50 dark:text-danger-300 dark:bg-danger-500/10',
    ];
@endphp
<x-filament-widgets::widget class="h-full">
    <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Plano &amp; créditos</p>
            @if($plan)
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$plan->status] ?? $statusColors['inactive'] }}">
                    {{ __('panel-app::widgets.plan_status.' . $plan->status) }}
                </span>
            @endif
        </div>

        @if($plan)
            <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</p>
        @endif

        <div class="mt-4 flex items-center gap-4">
            <div class="flex size-16 shrink-0 items-center justify-center rounded-full"
                 style="background: conic-gradient(var(--primary-600) {{ $pct }}%, var(--gray-200) {{ $pct }}%);">
                <span class="flex size-12 items-center justify-center rounded-full bg-white text-sm font-bold text-gray-900 dark:bg-gray-900 dark:text-white">
                    {{ $monthlyLeft }}/{{ $monthlyLimit }}
                </span>
            </div>
            <div class="flex-1 text-sm leading-snug text-gray-500 dark:text-gray-400">
                agendamentos restantes este mês
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/5">
            <span class="text-sm text-gray-500 dark:text-gray-400">Créditos avulsos</span>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">+{{ $availableCredits }}</span>
        </div>

        <div class="mt-auto pt-4">
            @if($canCreateAppointment)
                <x-filament::button wire:click="redirectToAppointmentCreation" class="w-full">
                    Agendar consultoria
                </x-filament::button>
            @else
                <x-filament::button disabled class="w-full">
                    Agendar consultoria
                </x-filament::button>
                @foreach($blockReasons as $reason)
                    <p class="mt-2 flex items-start gap-1.5 text-xs text-danger-600 dark:text-danger-400">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 size-4 shrink-0" />
                        <span>{{ $reason }}</span>
                    </p>
                @endforeach
            @endif

            @if($plan)
                <div class="mt-3 text-center">
                    {{ $this->viewPlanAction }}
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
```

- [ ] **Step 5: Rodar e ver passar**

Run: `php artisan test --compact --filter=PlanCreditsWidget`
Expected: PASS (incluindo o teste de status e os do bloco `appointment guard`).

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php resources/views/filament/app/widgets/plan-credits.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php
git commit -m "feat(panel-app): card de plano mostra nome e status; consolida resolução em PlanSummary"
```

---

## Task 4: Modal "Ver plano" (descrição + features)

**Files:**
- Create: `resources/views/filament/app/widgets/partials/plan-details.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Adicione ao `PlanCreditsWidgetTest.php`:

```php
it('opens a modal with the plan description and features', function (): void {
    actingAsEmployee();

    livewire(PlanCreditsWidget::class)
        ->assertActionVisible('viewPlan')
        ->mountAction('viewPlan')
        ->assertSee(__('panel-app::widgets.plan_details.monthly_appointments', ['count' => 1]));
});
```

> Nota: `actingAsEmployee()` monta um `CompanyPlan` ativo com `monthly_appointments_per_employee = 1` (ver `CompanyPlanFactory`), então a feature de consultas/mês usa count = 1.

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="opens a modal with the plan description"`
Expected: FAIL (view do modal ainda não existe → erro de view não encontrada, ou conteúdo ausente).

- [ ] **Step 3: Criar a view do modal**

Crie `resources/views/filament/app/widgets/partials/plan-details.blade.php`:

```blade
@php /** @var \TresPontosTech\PanelApp\DTOs\PlanSummary $plan */ @endphp
<div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
    @if($plan->description)
        <p>{{ $plan->description }}</p>
    @endif

    @if(count($plan->features) > 0)
        <ul class="space-y-2">
            @foreach($plan->features as $feature)
                <li class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-check-circle" class="size-5 shrink-0 text-primary-600 dark:text-primary-400" />
                    <span>{{ $feature }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --compact --filter="opens a modal with the plan description"`
Expected: PASS.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/filament/app/widgets/partials/plan-details.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php
git commit -m "feat(panel-app): modal Ver plano com descrição e features"
```

---

## Task 5: Mensagem de bloqueio — todos os motivos

**Files:**
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`

> O código de `blockReasons()` e a view já foram implementados na Task 3. Esta task adiciona a cobertura de teste que prova os dois cenários (um motivo e dois motivos).

- [ ] **Step 1: Escrever os testes**

Adicione ao `PlanCreditsWidgetTest.php`:

```php
it('lists every applicable block reason at once', function (): void {
    $employee = actingAsEmployee(); // CompanyPlan: 1 consulta/mês, sem créditos

    // consome a cota do mês E cria uma consultoria em andamento
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
```

- [ ] **Step 2: Rodar e ver passar**

Run: `php artisan test --compact --filter=PlanCreditsWidget`
Expected: PASS (toda a suíte do widget verde).

- [ ] **Step 3: Commit**

```bash
git add app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php
git commit -m "test(panel-app): cobre todos os motivos de bloqueio do card de plano"
```

---

## Task 6: Tooltip no botão "Aguardando confirmação"

**Files:**
- Modify: `resources/views/filament/app/widgets/next-appointment.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php`

- [ ] **Step 1: Escrever o teste que falha**

Adicione ao `NextAppointmentWidgetTest.php`:

```php
it('shows a tooltip on the awaiting-confirmation button', function (): void {
    Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create([
            'user_id' => $this->employee->id,
            'appointment_at' => now()->addDays(2),
            'meeting_url' => null,
        ]);

    livewire(NextAppointmentWidget::class)
        ->assertSuccessful()
        ->assertSee('Aguardando confirmação')
        ->assertSee(__('panel-app::widgets.next_appointment.awaiting_tooltip'));
});
```

- [ ] **Step 2: Rodar e ver falhar**

Run: `php artisan test --compact --filter="shows a tooltip on the awaiting-confirmation button"`
Expected: FAIL (o texto do tooltip ainda não está no HTML).

- [ ] **Step 3: Adicionar o `title` no botão**

Em `resources/views/filament/app/widgets/next-appointment.blade.php`, no bloco do botão de espera, troque:

```blade
                    @else
                        <x-filament::button disabled class="w-full">Aguardando confirmação</x-filament::button>
                    @endif
```

por:

```blade
                    @else
                        <x-filament::button
                            disabled
                            class="w-full"
                            title="{{ __('panel-app::widgets.next_appointment.awaiting_tooltip') }}"
                        >
                            Aguardando confirmação
                        </x-filament::button>
                    @endif
```

- [ ] **Step 4: Rodar e ver passar**

Run: `php artisan test --compact --filter=NextAppointmentWidget`
Expected: PASS (toda a suíte do widget verde).

- [ ] **Step 5: Build + commit**

```bash
npm run build
```
Expected: build sem erro.

```bash
git add resources/views/filament/app/widgets/next-appointment.blade.php app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php
git commit -m "feat(panel-app): tooltip explicando o estado Aguardando confirmação"
```

---

## Task 7: Suíte completa + Pint final

- [ ] **Step 1: Rodar a suíte do módulo**

Run: `php artisan test --compact app-modules/panel-app`
Expected: PASS (toda a suíte do panel-app verde).

- [ ] **Step 2: Pint**

Run: `vendor/bin/pint --dirty --format agent`
Expected: `passed`.

- [ ] **Step 3: Commit final (se Pint alterou algo)**

```bash
git add -A
git commit -m "style(panel-app): pint" --allow-empty
```

---

## Self-Review (preenchido pelo autor do plano)

**Spec coverage:**
- Card layout A (nome + status) → Task 3. Modal descrição/features → Task 4. Motivos de bloqueio (todos) → Task 3 (código) + Task 5 (testes). Tooltip → Task 6. DTO `PlanSummary` → Task 2. i18n → Task 1. ✅ Sem lacunas.
- Borda "sem plano" (oculta nome/status/Ver plano) → coberta pelo `@if($plan)` na view (Task 3) e `->visible($plan instanceof PlanSummary)` no action (Task 3). ✅
- Correção do motivo "sem agendamentos" considerando crédito → `blockReasons()` usa `hasAvailableCredit()` (Task 3). ✅

**Placeholder scan:** nenhum "TBD/TODO"; todo passo de código mostra o código. ✅

**Type consistency:** `PlanSummary` (`name`, `status`, `description`, `monthlyLimit`, `features`) usado de forma idêntica no widget (Task 3), no action/modal (Task 4) e na view do modal (Task 4). `blockReasons(): list<string>` consumido como `$blockReasons` na view. `viewPlanAction()` referenciado como `{{ $this->viewPlanAction }}`. ✅

**Comportamento preservado:** `redirectToAppointmentCreation()`, `canCreateAppointment()`, `hasOngoingAppointment()`, `CancelAppointmentAction` — intactos. Os testes do bloco `appointment guard` continuam válidos. ✅

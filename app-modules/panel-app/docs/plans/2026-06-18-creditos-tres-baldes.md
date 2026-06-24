---
type: plan
title: "Card \"Plano & créditos\" — três baldes"
module: panel-app
status: completed
date: 2026-06-18
related:
  spec: panel-app/2026-06-18-creditos-tres-baldes-design
---

# Card "Plano & créditos" — três baldes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Exibir no card do colaborador três baldes de crédito (cota mensal do plano, créditos avulsos próprios e créditos avulsos alocados pela empresa) e corrigir a incoerência da cota mensal.

**Architecture:** O accessor `User::monthlyAppointmentsLeft()` passa a resolver a cota com prioridade CompanyPlan > assinatura (fonte única). O `PlanCreditsWidget` separa os créditos avulsos por `owner_id` e a view (layout C) mostra o total com uma legenda de origem.

**Tech Stack:** PHP 8.4, Laravel 12, Filament v5 (Livewire), Pest v4, Tailwind v4.

## Global Constraints

- Empresa prevalece sobre assinatura individual na cota mensal.
- Rodar `vendor/bin/pint --dirty --format agent` após editar qualquer `.php`.
- Testes com Pest: `php artisan test --compact --filter=...`. Rodar no checkout principal (não em worktree).
- Conventional Commits, sem linha de co-autoria.
- Não introduzir dependências novas. Seguir convenções dos arquivos vizinhos.

---

## File Structure

- `app/Models/Users/User.php` — accessor `monthlyAppointmentsLeft` + novo `resolveMonthlyAppointmentLimit()` (CompanyPlan-first).
- `tests/Feature/UserMonthlyAppointmentsLeftTest.php` — atualmente vazio; recebe o teste da prioridade.
- `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` — `getViewData()` separa avulsos por origem.
- `resources/views/filament/app/widgets/plan-credits.blade.php` — layout C (total + legenda).
- `app-modules/panel-app/lang/pt_BR/widgets.php` e `.../en/widgets.php` — chaves `credits_own` / `credits_company`.
- `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php` — testes dos baldes.

---

## Task 1: Cota mensal prioriza CompanyPlan (corrige #3)

**Files:**
- Modify: `app/Models/Users/User.php` (método `monthlyAppointmentsLeft`, ~linhas 321-383; adicionar `resolveMonthlyAppointmentLimit()`)
- Test: `tests/Feature/UserMonthlyAppointmentsLeftTest.php`

**Interfaces:**
- Consumes: `User::companies()`, `CompanyPlan`, `CompanyPlanStatusEnum::Active`, `User::activeSubscription()`, `AppointmentStatus::Cancelled` (já importados no model).
- Produces: `User::$monthly_appointments_left` (int) com prioridade CompanyPlan > assinatura. Consumido pelo widget e por `BookAppointmentAction`.

- [ ] **Step 1: Escrever o teste que falha**

Substituir todo o conteúdo de `tests/Feature/UserMonthlyAppointmentsLeftTest.php` por:

```php
<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

it('prefers the company plan quota over an individual subscription', function (): void {
    $employee = actingAsEmployee(); // CompanyPlan ativo: 1 agendamento/mês

    // Assinatura individual com cota MAIOR (5) — não deve prevalecer.
    $plan = Plan::factory()->createOne([
        'type' => BillableTypeEnum::User->value,
        'active' => true,
    ]);

    $price = Price::create([
        'billing_plan_id' => $plan->id,
        'billing_scheme' => 'per_unit',
        'tiers_mode' => 'volume',
        'type' => 'recurring',
        'unit_amount_decimal' => 5000,
        'active' => true,
        'provider_price_id' => 'price_individual_test',
        'monthly_appointments' => 5,
        'whatsapp_enabled' => true,
        'materials_enabled' => true,
    ]);

    $employee->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_individual_123',
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});
```

- [ ] **Step 2: Rodar o teste para ver falhar**

Run: `php artisan test --compact --filter="prefers the company plan quota"`
Expected: FAIL — retorna `5` (a lógica atual prioriza a assinatura), esperado `1`.

- [ ] **Step 3: Implementar a prioridade CompanyPlan-first**

Em `app/Models/Users/User.php`, substituir o corpo do closure dentro de `Cache::remember(...)` no método `monthlyAppointmentsLeft()` e extrair a resolução do limite. O método passa a ser:

```php
protected function monthlyAppointmentsLeft(): Attribute
{
    return Attribute::make(
        get: function (): int {
            if ($this->getKey() === null) {
                return 0;
            }

            $cacheKey = $this->getMonthlyAppointmentsLeftCacheKey();

            /** @var int $result */
            $result = Cache::remember($cacheKey, now()->addMinute(), function (): int {
                $monthlyLimit = $this->resolveMonthlyAppointmentLimit();

                if ($monthlyLimit <= 0) {
                    return 0;
                }

                $used = (int) $this->appointments()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->where('status', '!=', AppointmentStatus::Cancelled->value)
                    ->count();

                return max($monthlyLimit - $used, 0);
            });

            return $result;
        }
    )->shouldCache();
}

/**
 * Cota mensal de agendamentos, priorizando o plano da empresa (CompanyPlan)
 * sobre a assinatura individual quando ambos existirem.
 */
private function resolveMonthlyAppointmentLimit(): int
{
    $contractualPlan = CompanyPlan::query()
        ->whereIn('company_id', $this->companies()->select('companies.id'))
        ->where('status', CompanyPlanStatusEnum::Active->value)
        ->whereNull('deleted_at')
        ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
        ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
        ->first();

    if ($contractualPlan !== null) {
        return (int) $contractualPlan->monthly_appointments_per_employee;
    }

    /** @var \TresPontosTech\Billing\Core\Models\Subscriptions\Subscription|null $subscription */
    $subscription = $this->activeSubscription()->with('price')->first();

    return (int) ($subscription?->price?->monthly_appointments ?? 0);
}
```

Observação: este refactor remove a duplicação do cálculo de `used` que existia nos dois ramos (assinatura/CompanyPlan) e inverte a prioridade. Confirmar que `CompanyPlan`, `CompanyPlanStatusEnum`, `Builder` e `AppointmentStatus` continuam importados no topo do arquivo (já são usados pela versão atual).

- [ ] **Step 4: Rodar o teste para ver passar**

Run: `php artisan test --compact --filter="prefers the company plan quota"`
Expected: PASS.

- [ ] **Step 5: Rodar a regressão do consumo de crédito (usa o accessor)**

Run: `php artisan test --compact app-modules/appointments/tests/Feature/Actions/BookAppointmentActionCreditTest.php`
Expected: PASS (sem regressões).

- [ ] **Step 6: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Users/User.php tests/Feature/UserMonthlyAppointmentsLeftTest.php
git commit -m "fix(panel-app): cota mensal prioriza plano da empresa sobre assinatura"
```

---

## Task 2: Card mostra os três baldes (layout C)

**Files:**
- Modify: `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` (`getViewData`)
- Modify: `resources/views/filament/app/widgets/plan-credits.blade.php`
- Modify: `app-modules/panel-app/lang/pt_BR/widgets.php` e `app-modules/panel-app/lang/en/widgets.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`

**Interfaces:**
- Consumes: `User::$monthly_appointments_left` (Task 1), `UserCredit` (`holder_id`, `owner_id`, `status`), `UserCreditStatusEnum::Available`.
- Produces (view data): `creditsTotal` (int), `ownCredits` (int), `companyCredits` (int), além de `plan`, `monthlyLeft`, `monthlyLimit`, `canCreateAppointment`, `blockReasons` (inalterados).

- [ ] **Step 1: Escrever os testes que falham**

Adicionar ao final de `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php` (o `use ...\UserCredit;` já existe no arquivo):

```php
it('splits extra credits by origin (yours vs company)', function (): void {
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

    livewire(PlanCreditsWidget::class)
        ->assertOk()
        ->assertSee(__('panel-app::widgets.plan_credits.extra_credits'))
        ->assertSeeText('7')
        ->assertSeeText('2 seus')
        ->assertSeeText('5 da empresa');
});

it('omits the breakdown legend when there are no extra credits', function (): void {
    actingAsEmployee();

    livewire(PlanCreditsWidget::class)
        ->assertOk()
        ->assertSee(__('panel-app::widgets.plan_credits.extra_credits'))
        ->assertDontSeeText('seus')
        ->assertDontSeeText('da empresa');
});
```

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter="splits extra credits by origin|omits the breakdown legend"`
Expected: FAIL — a legenda "2 seus"/"5 da empresa" ainda não existe na view.

- [ ] **Step 3: Adicionar as chaves de tradução**

Em `app-modules/panel-app/lang/pt_BR/widgets.php`, dentro do array `'plan_credits' => [ ... ]`, adicionar:

```php
        'credits_own' => ':count seus',
        'credits_company' => ':count da empresa',
```

Em `app-modules/panel-app/lang/en/widgets.php`, dentro de `'plan_credits' => [ ... ]`:

```php
        'credits_own' => ':count yours',
        'credits_company' => ':count from the company',
```

- [ ] **Step 4: Separar os avulsos por origem no `getViewData`**

Em `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php`, substituir o `getViewData()` por:

```php
protected function getViewData(): array
{
    /** @var User $user */
    $user = auth()->user();

    $plan = $this->plan();

    $availableCredits = UserCredit::query()
        ->where('holder_id', $user->getKey())
        ->where('status', UserCreditStatusEnum::Available)
        ->get(['owner_id']);

    $ownCredits = $availableCredits->where('owner_id', $user->getKey())->count();
    $companyCredits = $availableCredits->count() - $ownCredits;

    $monthlyLeft = $user->monthly_appointments_left;
    $hasCredit = $availableCredits->isNotEmpty();
    $hasOngoingAppointment = $user->hasOngoingAppointment();
    $canCreateAppointment = ($monthlyLeft > 0 || $hasCredit) && ! $hasOngoingAppointment;

    return [
        'plan' => $plan,
        'monthlyLeft' => $monthlyLeft,
        'monthlyLimit' => $plan?->monthlyLimit ?? 0,
        'creditsTotal' => $availableCredits->count(),
        'ownCredits' => $ownCredits,
        'companyCredits' => $companyCredits,
        'canCreateAppointment' => $canCreateAppointment,
        'blockReasons' => $this->blockReasons($hasOngoingAppointment, $monthlyLeft, $hasCredit),
    ];
}
```

- [ ] **Step 5: Atualizar a view (layout C)**

Em `resources/views/filament/app/widgets/plan-credits.blade.php`, substituir o bloco de "Créditos avulsos":

```blade
        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/5">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('panel-app::widgets.plan_credits.extra_credits') }}</span>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">+{{ $availableCredits }}</span>
        </div>
```

por:

```blade
        <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/5">
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('panel-app::widgets.plan_credits.extra_credits') }}</span>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $creditsTotal }}</span>
        </div>

        @php
            $creditsLegend = collect([
                $ownCredits > 0 ? trans('panel-app::widgets.plan_credits.credits_own', ['count' => $ownCredits]) : null,
                $companyCredits > 0 ? trans('panel-app::widgets.plan_credits.credits_company', ['count' => $companyCredits]) : null,
            ])->filter()->implode(' · ');
        @endphp

        @if($creditsLegend !== '')
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $creditsLegend }}</p>
        @endif
```

- [ ] **Step 6: Rodar os testes da Task 2 para ver passar**

Run: `php artisan test --compact --filter="splits extra credits by origin|omits the breakdown legend"`
Expected: PASS.

- [ ] **Step 7: Rodar a suíte do widget (regressão)**

Run: `php artisan test --compact --filter=PlanCreditsWidget`
Expected: PASS (inclui o teste existente "renders plan name, monthly allowance and available credits", que segue válido — o total continua exibindo o número de avulsos).

- [ ] **Step 8: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php \
        resources/views/filament/app/widgets/plan-credits.blade.php \
        app-modules/panel-app/lang/pt_BR/widgets.php \
        app-modules/panel-app/lang/en/widgets.php \
        app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php
git commit -m "feat(panel-app): card de créditos separa avulsos por origem (você/empresa)"
```

---

## Task 3: Verificação final

- [ ] **Step 1: Rodar a suíte do módulo panel-app**

Run: `php artisan test --compact app-modules/panel-app/tests`
Expected: PASS.

- [ ] **Step 2: Rodar a suíte do módulo billing e appointments (consumidores do accessor)**

Run: `php artisan test --compact app-modules/billing/tests app-modules/appointments/tests`
Expected: PASS.

---

## Self-Review (preenchido pelo autor do plano)

- **Cobertura do spec:** cota mensal CompanyPlan-first → Task 1; três baldes / separação por owner → Task 2; layout C → Task 2 (view); legenda condicional → Task 2 (testes + view); i18n → Task 2; bloqueios inalterados → preservados em `getViewData`/view; "Ver plano" → não tocado. Sem lacunas.
- **Placeholders:** nenhum — todo step tem código/comando reais.
- **Consistência de tipos:** `creditsTotal`/`ownCredits`/`companyCredits` (int) produzidos no `getViewData` (Task 2) e consumidos na view (Task 2). `monthly_appointments_left` (int) inalterado no contrato. Nome `resolveMonthlyAppointmentLimit()` usado só na Task 1.

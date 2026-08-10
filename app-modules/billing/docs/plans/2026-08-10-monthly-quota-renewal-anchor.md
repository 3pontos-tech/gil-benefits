---
type: plan
title: "Renovação de cota mensal ancorada na data de contratação"
module: billing
status: pending
date: 2026-08-10
related:
  adr: billing/2026-08-10-monthly-quota-renewal-anchor
---

# Renovação de cota mensal ancorada na data de contratação — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Trocar a cota mensal de agendamentos de uma janela rolante de 30 dias (`created_at >= now()->subDays(30)`) para ciclos ancorados na data de contratação — plano contratual ancora na `starts_at` da empresa, plano individual ancora na ativação da própria assinatura. Cota não usada não acumula. Quando um cancelamento válido acontece depois da virada, a consulta é devolvida como crédito avulso com validade.

**Architecture:** A aritmética de ciclo vive num value object `QuotaCycle` (billing/Core/Support), única fonte da regra de meses com clamp sem drift. A resolução de "qual plano vale para esta pessoa" sai de quatro cópias espalhadas e vira um resolver único que devolve limite **e** âncora juntos (`QuotaAllowance`). `User::monthlyAppointmentsLeft()` passa a contar dentro do ciclo corrente em vez da janela rolante. A devolução por cancelamento pós-virada reusa o ledger que já existe (`user_credits` + `credit_grants`), ganhando `expires_at` e um `reason` tipado — nenhuma tabela de período é criada.

**Tech Stack:** Laravel 12, Filament v5, Livewire v4, Pest v4, PHP 8.4. Banco: `pgsql` em produção, `sqlite` nos testes — SQL cru precisa funcionar nos dois.

**Leia a ADR antes de começar:** `app-modules/billing/docs/adr/2026-08-10-monthly-quota-renewal-anchor.md`. Ela tem o porquê de cada decisão, as alternativas rejeitadas e 13 exemplos numerados que este plano transforma em teste.

---

## Decisões de arquitetura travadas (ler antes de começar)

Estas foram decididas com o produto e **não são para revisitar durante a implementação**. Três delas contrariam a recomendação técnica original e ainda assim são as escolhidas — estão marcadas com ⚠ para ninguém "corrigir".

1. **Débito no `created_at`, não no `appointment_at`.** Remarcar nunca mexe na cota. (ADR D1)
2. **Meses com clamp sem drift**, sempre calculados a partir da âncora original: 31/jan → 28/fev → **31**/mar. Nunca somar mês em cima do resultado anterior. (ADR D2)
3. **Âncora contratual = `company_plans.starts_at`**, que passa a ser obrigatória no form; nulos recebem `created_at` no backfill. (ADR D3)
4. ⚠ **Âncora individual = coluna nova `billing_subscriptions.quota_anchor_at`**, gravada uma vez na ativação. Não usar `created_at` direto, mesmo sendo "quase igual" — o motivo está na ADR §1.4.5 (o `created_at` é reescrito por comando de sync). (ADR D4)
5. **Cota tem duas origens só:** plano contratual da empresa e assinatura do próprio usuário. Assinatura *de empresa* não gera cota para ninguém — não tente somar. (ADR D5)
6. **Plano contratual tem precedência** sobre assinatura individual. Existe teste cravando. (ADR D6)
7. **A empresa vem do tenant selecionado**, com fallback para `employerCompanyId()`. (ADR D7)
8. **Funcionário que entra no meio do ciclo recebe cota cheia**, sem pró-rata. (ADR D8)
9. **Regra de cancelamento inalterada** (4h). Não tocar em `isLateCancellation()`. (ADR D9)
10. **Teto de 45 dias contado sempre de agora**, inclusive no reagendamento. (ADR D10)
11. **Nada de tabela de períodos.** O ciclo é derivado na leitura. (ADR D11)
12. **Mudança de plano pelo admin vale no ciclo corrente.** É consequência esperada de derivar na leitura, não bug. (ADR D12)
13. ⚠ **Crédito de devolução é consumido antes da cota mensal** — e por isso a decisão duplicada de "qual estoque paga" tem que ser unificada **antes** (Task 8). (ADR D15)
14. ⚠ **Crédito com validade que volta por cancelamento mantém a validade original**, mesmo já vencida. Não estique o prazo. (ADR D16)
15. **A devolução gera `CreditGrant` com `reason = QuotaRefund`** e justificativa montada com as datas do caso. (ADR D17)
16. **Sem migração de dados no cutover.** (ADR D18)

**Fases:** Tasks 1–7 são a fase 1 (âncora e janela) e podem subir sozinhas. Tasks 8–11 são a fase 2 (devolução). Entre as duas existe uma regressão conhecida e aceita: cancelamento pós-virada não devolve nada. Mantenha o intervalo curto.

---

## Task 1: Value object `QuotaCycle`

Puro, sem efeito em comportamento nenhum. É a base de tudo.

**Files:**
- Create: `app-modules/billing/src/Core/Support/QuotaCycle.php`
- Test: `app-modules/billing/tests/Unit/QuotaCycleTest.php`

**Interfaces:**
- Consumes: nada além de `CarbonImmutable`.
- Produces: `QuotaCycle::forAnchor(DateTimeInterface $anchor, ?DateTimeInterface $at = null): self`, com `->start` (inclusivo) e `->end` (**exclusivo**, é o início do ciclo seguinte). Consumido pela Task 3 e pela Task 9.

- [ ] **Step 1: Escrever o teste que falha**

Criar `app-modules/billing/tests/Unit/QuotaCycleTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\CarbonImmutable;
use TresPontosTech\Billing\Core\Support\QuotaCycle;

it('anchors the cycle on the contract day', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-03-10'),
        CarbonImmutable::parse('2026-09-15 14:00'),
    );

    expect($cycle->start->toDateString())->toBe('2026-09-10')
        ->and($cycle->end->toDateString())->toBe('2026-10-10');
});

it('clamps short months without drifting', function (): void {
    $anchor = CarbonImmutable::parse('2026-01-31');

    $starts = collect([
        '2026-02-15', '2026-03-15', '2026-04-15', '2026-05-15',
    ])->map(fn (string $at): string => QuotaCycle::forAnchor($anchor, CarbonImmutable::parse($at))->start->toDateString());

    // Fevereiro encurta, março VOLTA para o dia 31 — sem drift.
    expect($starts->all())->toBe(['2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31']);
});

it('treats the turn instant as the start of the new cycle', function (): void {
    $anchor = CarbonImmutable::parse('2026-03-10');

    $before = QuotaCycle::forAnchor($anchor, CarbonImmutable::parse('2026-09-09 23:59:59'));
    $after = QuotaCycle::forAnchor($anchor, CarbonImmutable::parse('2026-09-10 00:00:00'));

    expect($before->start->toDateString())->toBe('2026-08-10')
        ->and($after->start->toDateString())->toBe('2026-09-10');
});

it('returns the first cycle when the anchor is still in the future', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-12-01'),
        CarbonImmutable::parse('2026-08-10'),
    );

    expect($cycle->start->toDateString())->toBe('2026-12-01');
});

it('contains only dates inside the window', function (): void {
    $cycle = QuotaCycle::forAnchor(
        CarbonImmutable::parse('2026-03-10'),
        CarbonImmutable::parse('2026-09-15'),
    );

    expect($cycle->contains(CarbonImmutable::parse('2026-09-10 00:00')))->toBeTrue()
        ->and($cycle->contains(CarbonImmutable::parse('2026-10-09 23:59')))->toBeTrue()
        ->and($cycle->contains(CarbonImmutable::parse('2026-10-10 00:00')))->toBeFalse()
        ->and($cycle->contains(CarbonImmutable::parse('2026-09-09 23:59')))->toBeFalse();
});
```

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=QuotaCycle`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Implementar o value object**

Criar `app-modules/billing/src/Core/Support/QuotaCycle.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Support;

use DateTimeInterface;
use Illuminate\Support\CarbonImmutable;

/**
 * A janela mensal de cota de uma pessoa, derivada da data de contratação.
 *
 * O passo é de mês-calendário a partir da âncora ORIGINAL, com clamp e sem drift:
 * âncora dia 31 vira 28/fev e volta para 31/mar. Somar mês em cima do resultado
 * anterior faria a data de virada escorregar para trás permanentemente.
 *
 * `start` é inclusivo, `end` é exclusivo — `end` é o instante em que o ciclo
 * seguinte começa, de modo que a virada nunca pertence aos dois.
 */
final readonly class QuotaCycle
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public static function forAnchor(DateTimeInterface $anchor, ?DateTimeInterface $at = null): self
    {
        $anchor = CarbonImmutable::instance($anchor)->startOfDay();
        $at = $at === null ? CarbonImmutable::now() : CarbonImmutable::instance($at);

        // Plano com início no futuro: o primeiro ciclo é o próprio, ainda não começado.
        if ($at->lessThan($anchor)) {
            return new self($anchor, $anchor->addMonthNoOverflow());
        }

        $months = max(0, (int) $anchor->diffInMonths($at));

        // diffInMonths pode errar por um em bordas de mês curto; corrige nos dois sentidos.
        while ($anchor->addMonthsNoOverflow($months)->greaterThan($at)) {
            --$months;
        }

        while ($anchor->addMonthsNoOverflow($months + 1)->lessThanOrEqualTo($at)) {
            ++$months;
        }

        return new self(
            $anchor->addMonthsNoOverflow($months),
            $anchor->addMonthsNoOverflow($months + 1),
        );
    }

    public function contains(DateTimeInterface $moment): bool
    {
        $moment = CarbonImmutable::instance($moment);

        return $moment->greaterThanOrEqualTo($this->start) && $moment->lessThan($this->end);
    }

    public function hasClosed(?DateTimeInterface $at = null): bool
    {
        $at = $at === null ? CarbonImmutable::now() : CarbonImmutable::instance($at);

        return $at->greaterThanOrEqualTo($this->end);
    }
}
```

- [ ] **Step 4: Rodar para ver passar**

Run: `php artisan test --compact --filter=QuotaCycle`
Expected: PASS (5 testes).

- [ ] **Step 5: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/billing/src/Core/Support/QuotaCycle.php \
        app-modules/billing/tests/Unit/QuotaCycleTest.php
git commit -m "feat(billing): value object QuotaCycle com passo mensal e clamp sem drift"
```

---

## Task 2: Resolver único de cota (limite + âncora)

A query de plano contratual ativo está copiada em quatro lugares (ADR §1.4.12). Aqui ela ganha um dono, e passa a devolver **limite e âncora juntos** — porque a partir da Task 3 quem sabe o limite tem que saber também o dia da virada.

**Files:**
- Create: `app-modules/billing/src/Core/DTOs/QuotaAllowance.php`
- Create: `app-modules/billing/src/Core/Actions/ResolveQuotaAllowance.php`
- Modify: `app-modules/billing/src/Core/Models/CompanyPlan.php` (scope `active`)
- Test: `app-modules/billing/tests/Feature/CompanyPlan/ResolveQuotaAllowanceTest.php`

**Interfaces:**
- Consumes: `CompanyPlan`, `CompanyPlanStatusEnum::Active`, `User::companies()`, `User::employerCompanyId()`, `User::activeSubscription()`, `Filament::getTenant()`.
- Produces: `ResolveQuotaAllowance::for(User $user): QuotaAllowance` com `->limit` (int) e `->anchor` (`?CarbonImmutable`). Consumido pela Task 3, pela Task 6 e pela Task 9.

- [ ] **Step 1: Escrever os testes que falham**

Criar `app-modules/billing/tests/Feature/CompanyPlan/ResolveQuotaAllowanceTest.php` cobrindo:

1. **Plano contratual vence a assinatura individual** (ADR exemplo 7) — replicar o cenário de `tests/Feature/UserMonthlyAppointmentsLeftTest.php`, esperando `limit = 1` e `anchor` igual à `starts_at` do `CompanyPlan`.
2. **Duas empresas com plano ativo** (ADR exemplo 8) — anexar o usuário a duas empresas com `CompanyPlan` ativos de limites e `starts_at` diferentes; com o tenant do Filament apontando para a segunda, esperar limite e âncora **da segunda**.
3. **Sem tenant** — mesmo cenário, sem tenant setado, esperar a empresa de `employerCompanyId()` (a que não é a default).
4. **Só assinatura individual** — esperar `limit = price.monthly_appointments` e `anchor = subscription.quota_anchor_at`. Até a Task 5 existir, use `created_at` no teste e ajuste na Task 5 (o teste vai ser reescrito lá).
5. **Nem plano nem assinatura** — `limit = 0`, `anchor = null`.

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=ResolveQuotaAllowance`
Expected: FAIL — classes não existem.

- [ ] **Step 3: Criar o DTO**

`app-modules/billing/src/Core/DTOs/QuotaAllowance.php`:

```php
<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use Illuminate\Support\CarbonImmutable;

/**
 * Quanto a pessoa tem direito por ciclo e em que dia o ciclo dela vira.
 * Os dois andam juntos: o limite vem do plano e a âncora vem do MESMO plano,
 * então resolver um sem o outro deixa a porta aberta para misturar as fontes.
 */
final readonly class QuotaAllowance
{
    public function __construct(
        public int $limit,
        public ?CarbonImmutable $anchor,
    ) {}

    public static function none(): self
    {
        return new self(0, null);
    }

    public function isEmpty(): bool
    {
        return $this->limit <= 0 || ! $this->anchor instanceof CarbonImmutable;
    }
}
```

- [ ] **Step 4: Adicionar o scope `active` no `CompanyPlan`**

Em `app-modules/billing/src/Core/Models/CompanyPlan.php`, adicionar (seguindo o padrão de `#[Scope]` protegido usado em `UserCredit`):

```php
/**
 * @param  Builder<CompanyPlan>  $query
 * @return Builder<CompanyPlan>
 */
#[Scope]
protected function active(Builder $query): Builder
{
    return $query
        ->where('status', CompanyPlanStatusEnum::Active->value)
        ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
        ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
}
```

O `whereNull('deleted_at')` que existe hoje em `User::resolveMonthlyAppointmentLimit()` é redundante (`SoftDeletes` já aplica) e não precisa ser reproduzido.

- [ ] **Step 5: Implementar o resolver**

`app-modules/billing/src/Core/Actions/ResolveQuotaAllowance.php`. Regras, em ordem:

1. Determinar a empresa: `Filament::getTenant()` se for uma `Company`, senão `$user->employerCompanyId()`.
2. Buscar `CompanyPlan::query()->active()->where('company_id', $companyId)->first()`. Se existir, devolver `new QuotaAllowance((int) $plan->monthly_appointments_per_employee, CarbonImmutable::instance($plan->starts_at ?? $plan->created_at))`.
3. Senão, `$user->activeSubscription()->with('price')->first()`. Se existir, devolver o `price->monthly_appointments` com âncora `quota_anchor_at ?? created_at`.
4. Senão, `QuotaAllowance::none()`.

O fallback `?? created_at` nos dois casos é rede de segurança para dado legado; depois das Tasks 4 e 5 ele não deve mais ser exercitado em produção. **Não** remova — a coluna `starts_at` segue nullable no banco.

- [ ] **Step 6: Rodar para ver passar**

Run: `php artisan test --compact --filter=ResolveQuotaAllowance`
Expected: PASS.

- [ ] **Step 7: Trocar os três outros consumidores da query duplicada**

Substituir a query copiada por `CompanyPlan::query()->active()` em:
- `app-modules/company/src/Models/Company.php` → `activeContractualPlan()`
- `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` → `resolvePlan()`
- `app-modules/panel-admin/src/Actions/Engagement/GetEngagementFunnel.php` (~linhas 129-130)

Run: `php artisan test --compact --filter="ActiveContractualPlan|PlanCredits|Engagement"`
Expected: PASS, sem mudança de comportamento.

- [ ] **Step 8: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/billing/src/Core/DTOs/QuotaAllowance.php \
        app-modules/billing/src/Core/Actions/ResolveQuotaAllowance.php \
        app-modules/billing/src/Core/Models/CompanyPlan.php \
        app-modules/company/src/Models/Company.php \
        app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php \
        app-modules/panel-admin/src/Actions/Engagement/GetEngagementFunnel.php \
        app-modules/billing/tests/Feature/CompanyPlan/ResolveQuotaAllowanceTest.php
git commit -m "feat(billing): resolver único de cota devolvendo limite e âncora juntos"
```

---

## Task 3: `monthlyAppointmentsLeft` conta dentro do ciclo

Aqui o comportamento muda de fato.

**Files:**
- Modify: `app/Models/Users/User.php` (`monthlyAppointmentsLeft()`, remover `resolveMonthlyAppointmentLimit()`)
- Test: `tests/Feature/UserMonthlyAppointmentsLeftTest.php`

**Interfaces:**
- Consumes: `ResolveQuotaAllowance` (Task 2), `QuotaCycle` (Task 1).
- Produces: `User::$monthly_appointments_left` (int) — contrato inalterado para `BookAppointmentAction`, `PlanCreditsWidget`, `CreateAppointment` (admin) e `canCreateAppointment()`.

- [ ] **Step 1: Escrever os testes que falham**

Ampliar `tests/Feature/UserMonthlyAppointmentsLeftTest.php`, mantendo o teste de precedência que já existe e adicionando (use `travelTo()` do Pest para fixar o tempo):

1. **Reserva do ciclo anterior não conta no atual** (ADR exemplos 1 e 2): plano com `starts_at` dia 10, agendamento criado em 09/set, "hoje" 11/set → `monthly_appointments_left` volta a 1.
2. **Reserva do ciclo corrente conta**: agendamento criado em 11/set, hoje 15/set → 0.
3. **Cota não acumula** (o caso central do pedido): plano de 1 consulta, nenhum agendamento nos dois ciclos anteriores, hoje no terceiro → 1, nunca 3.
4. **`cancelled` não conta, `cancelled_late` conta** (ADR D9): dois cenários dentro do ciclo corrente.
5. **Funcionário que entra no meio do ciclo** (ADR exemplo 5): cadastro dia 7, virada dia 10, hoje dia 8 → 1.
6. **Sem plano e sem assinatura** → 0 (mantém a premissa dos testes de crédito).

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=UserMonthlyAppointmentsLeft`
Expected: FAIL nos casos 1 e 3 (a janela rolante ainda conta 30 dias para trás).

- [ ] **Step 3: Implementar**

Em `app/Models/Users/User.php`, substituir o closure do `Cache::remember(...)` e **remover** `resolveMonthlyAppointmentLimit()` (a lógica foi para a Task 2):

```php
$result = Cache::remember($cacheKey, now()->addMinute(), function (): int {
    $allowance = resolve(ResolveQuotaAllowance::class)->for($this);

    if ($allowance->isEmpty()) {
        return 0;
    }

    $cycle = QuotaCycle::forAnchor($allowance->anchor);

    $used = (int) $this->appointments()
        ->where('created_at', '>=', $cycle->start)
        ->where('created_at', '<', $cycle->end)
        ->where('status', '!=', AppointmentStatus::Cancelled->value)
        ->count();

    return max($allowance->limit - $used, 0);
});
```

Ajustar os `use` do model: entram `ResolveQuotaAllowance` e `QuotaCycle`, saem `CompanyPlan`, `CompanyPlanStatusEnum` e possivelmente `Builder` (verificar se ainda é usado pelos outros scopes do arquivo antes de remover).

- [ ] **Step 4: Rodar para ver passar**

Run: `php artisan test --compact --filter=UserMonthlyAppointmentsLeft`
Expected: PASS.

- [ ] **Step 5: Rodar os consumidores (regressão)**

Run: `php artisan test --compact app-modules/appointments/tests app-modules/panel-app/tests app-modules/panel-admin/tests`
Expected: PASS. Se `BookAppointmentActionCreditTest` ou `CreateAppointmentCreditTest` quebrarem, o motivo é a premissa "usuário sem empresa/plano tem cota 0" — que continua verdadeira; investigue antes de mexer no teste.

- [ ] **Step 6: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Users/User.php tests/Feature/UserMonthlyAppointmentsLeftTest.php
git commit -m "feat: cota mensal conta dentro do ciclo ancorado, não em janela de 30 dias"
```

---

## Task 4: Âncora contratual obrigatória + backfill

**Files:**
- Modify: `app-modules/panel-admin/src/Filament/Resources/Companies/RelationManagers/ContractualPlansRelationManager.php` (~linha 101)
- Create: migration de backfill em `app-modules/billing/database/migrations/`
- Test: `app-modules/panel-admin/tests/...` (o teste do relation manager, se existir; senão criar um mínimo de validação)

- [ ] **Step 1: Tornar o campo obrigatório**

```php
DatePicker::make('starts_at')
    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.starts_at'))
    ->displayFormat('d/m/Y')
    ->required(),
```

Atenção: a regra de sobreposição no campo `status` usa `$get('starts_at') ?? now()->toDateString()`. Com o campo obrigatório o fallback fica morto, mas **não remova** — a validação de sobreposição roda antes da de obrigatoriedade e um `null` ali causaria erro.

- [ ] **Step 2: Migration de backfill**

Sem alteração de schema (a coluna segue nullable no banco):

```php
public function up(): void
{
    DB::table('company_plans')
        ->whereNull('starts_at')
        ->update(['starts_at' => DB::raw('DATE(created_at)')]);
}

public function down(): void
{
    // Irreversível por design: não há como distinguir o que era nulo antes.
}
```

Verificar que `DATE(created_at)` roda no `pgsql` e no `sqlite`; se der problema no sqlite dos testes, use `date(created_at)` (ambos aceitam minúsculo) ou faça em PHP com `chunkById`.

- [ ] **Step 3: Rodar migrations e a suíte do admin**

Run: `php artisan migrate --pretend` e depois `php artisan test --compact app-modules/panel-admin/tests`
Expected: PASS.

- [ ] **Step 4: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/panel-admin/src/Filament/Resources/Companies/RelationManagers/ContractualPlansRelationManager.php \
        app-modules/billing/database/migrations/
git commit -m "feat(billing): data de início do plano contratual passa a ser obrigatória"
```

---

## Task 5: Âncora individual (`quota_anchor_at`)

**Files:**
- Create: migration `add_quota_anchor_at_to_billing_subscriptions`
- Modify: `app-modules/billing/src/Core/Models/Subscriptions/Subscription.php` (docblock + cast)
- Modify: `app-modules/billing/src/Core/Actions/UpsertSubscription.php`
- Modify: `app-modules/billing/src/Core/Actions/ResolveQuotaAllowance.php` (usar a coluna)
- Test: `app-modules/billing/tests/Feature/Subscription/QuotaAnchorTest.php`

- [ ] **Step 1: Escrever os testes que falham**

```php
it('stamps the quota anchor when the subscription first becomes active', function (): void {
    // PENDING primeiro: não deve gravar âncora.
    // ACTIVE depois: grava com o now() daquele momento.
});

it('never moves the anchor on later events', function (): void {
    // ACTIVE, avança o tempo, DEFAULTER, ACTIVE de novo → âncora original preservada.
});

it('falls back to created_at for legacy subscriptions', function (): void {
    // Linha com quota_anchor_at null → ResolveQuotaAllowance usa created_at.
});
```

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=QuotaAnchor`

- [ ] **Step 3: Migration**

```php
Schema::table('billing_subscriptions', function (Blueprint $table): void {
    $table->timestamp('quota_anchor_at')->nullable()->after('trial_ends_at');
});

// Backfill: assinaturas existentes não têm registro de ativação.
DB::table('billing_subscriptions')
    ->whereNull('quota_anchor_at')
    ->update(['quota_anchor_at' => DB::raw('created_at')]);
```

Nullable de propósito: uma assinatura pode existir em `pending` e nunca ser ativada.

- [ ] **Step 4: Stamp no `UpsertSubscription`**

```php
public function handle(SubscriptionDTO $dto): void
{
    $subscription = Subscription::query()->updateOrCreate(
        ['stripe_id' => $dto->subscriptionExternalId],
        [ /* ...campos atuais, inalterados... */ ]
    );

    // A âncora da cota é gravada uma única vez, na primeira ativação, e nunca
    // mais alterada: é o dia em que o ciclo mensal da pessoa vira. Um retorno de
    // inadimplência (defaulter → active) não deve remarcar essa data.
    if ($dto->status === 'active' && $subscription->quota_anchor_at === null) {
        $subscription->forceFill(['quota_anchor_at' => now()])->save();
    }
}
```

Use `forceFill` — `Subscription` estende o model do Cashier e não declara `$fillable` para essa coluna.

- [ ] **Step 5: Usar a coluna no resolver**

Em `ResolveQuotaAllowance`, o ramo da assinatura passa a ler `quota_anchor_at ?? created_at`.

- [ ] **Step 6: Rodar para ver passar**

Run: `php artisan test --compact --filter="QuotaAnchor|ResolveQuotaAllowance|Webhook"`
Expected: PASS. `HandleBarteWebhookTest` e `SubscriptionWebhookControllerTest` passam pelo `UpsertSubscription` — confirme que seguem verdes.

- [ ] **Step 7: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/billing/database/migrations/ \
        app-modules/billing/src/Core/Models/Subscriptions/Subscription.php \
        app-modules/billing/src/Core/Actions/UpsertSubscription.php \
        app-modules/billing/src/Core/Actions/ResolveQuotaAllowance.php \
        app-modules/billing/tests/Feature/Subscription/QuotaAnchorTest.php
git commit -m "feat(billing): âncora de cota da assinatura gravada na ativação"
```

---

## Task 6: Teto de 45 dias para marcar

**Files:**
- Modify: `app-modules/appointments/src/Models/Appointment.php` (constante)
- Modify: `app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/AppointmentWizard.php` (`availableSlots()` e o `DatePicker` do wizard antigo)
- Modify: `app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/PickSlotStep.php`
- Modify: `app-modules/panel-app/src/Filament/Actions/RescheduleAppointmentAction.php` (~linha 60)
- Modify: `resources/views/filament/app/appointments/wizard/calendar-field.blade.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Actions/` (agendamento e reagendamento)

- [ ] **Step 1: Escrever os testes que falham**

1. `AppointmentWizard::availableSlots()` devolve `[]` para data além de `now() + 45 dias` (ADR exemplo 12).
2. `AppointmentWizard::isBookableSlot()` recusa horário além do teto — é a barreira de servidor contra `mountAction` forjado.
3. O reagendamento também recusa além do teto, contado **de hoje** (ADR D10/Q13): agendamento criado há 40 dias, remarcação para hoje + 30 dias → aceita.

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter="Schedule|Reschedule"`

- [ ] **Step 3: Implementar**

Constante em `Appointment`, ao lado de `BOOKING_LEAD_DAYS`:

```php
/**
 * Até quando dá para marcar. A cota não acumula e só existe uma consulta aberta
 * por vez, então marcar muito à frente apagaria em silêncio os ciclos do meio.
 */
public const BOOKING_HORIZON_DAYS = 45;
```

Em `AppointmentWizard::availableSlots()`, logo depois da checagem de `BOOKING_LEAD_DAYS`:

```php
if ($startDate->startOfDay()->gt(now()->addDays(Appointment::BOOKING_HORIZON_DAYS)->startOfDay())) {
    return [];
}
```

`isBookableSlot()` herda a regra sem alteração, porque ele consulta `availableSlots()`.

Nos pickers, adicionar o limite superior ao lado do `minDate` que já existe: `->maxDate(...)` no `DatePicker` do `AppointmentWizard` e do `RescheduleAppointmentAction`, e `'maxDate' => fn (): string => now()->addDays(Appointment::BOOKING_HORIZON_DAYS)->toDateString()` no `PickSlotStep`, com o blade passando `max: @js($maxDate)` junto do `min` já existente.

- [ ] **Step 4: Rodar para ver passar**

Run: `php artisan test --compact app-modules/panel-app/tests`
Expected: PASS.

- [ ] **Step 5: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add app-modules/appointments/src/Models/Appointment.php \
        app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/ \
        app-modules/panel-app/src/Filament/Actions/RescheduleAppointmentAction.php \
        resources/views/filament/app/appointments/wizard/calendar-field.blade.php \
        app-modules/panel-app/tests/
git commit -m "feat(appointments): teto de 45 dias para agendar e reagendar"
```

---

## Task 7: Fim da fase 1 — verificação

- [ ] **Step 1: Suíte completa**

Run: `php artisan test --compact`
Expected: PASS.

- [ ] **Step 2: Análise estática**

Run: `vendor/bin/phpstan analyse --memory-limit=2G`
Expected: sem novos erros.

- [ ] **Step 3: Conferir manualmente os exemplos 1 a 9 e 13 da ADR** no painel `app`, com um plano contratual de virada conhecida.

> **A fase 1 pode subir aqui.** A partir deste ponto existe a regressão aceita: cancelamento válido depois da virada não devolve nada. A fase 2 resolve.

---

## Task 8: Unificar "qual estoque paga" + ordem de consumo dos créditos

Pré-requisito travado da ADR D15: a decisão está duplicada em dois fluxos e passa a ser uma só, **antes** de inverter a prioridade.

**Files:**
- Create: migration `add_expires_at_to_user_credits`
- Create: `app-modules/billing/src/Core/Enums/CreditGrantReasonEnum.php`
- Create: migration `add_reason_to_credit_grants`
- Modify: `app-modules/billing/src/Core/Models/UserCredit.php` (cast + scope `usable`)
- Modify: `app-modules/billing/src/Core/Models/CreditGrant.php` (cast)
- Modify: `app-modules/billing/src/Core/DTOs/CreditDTO.php` (campo `expiresAt`)
- Modify: `app-modules/billing/src/Core/Actions/Credit/IssueCredits.php`
- Modify: `app-modules/billing/src/Core/Actions/Credit/ConsumeCredit.php`
- Modify: `app/Models/Users/User.php` (`hasAvailableCredit()`)
- Modify: `app-modules/appointments/src/Actions/BookAppointmentAction.php`
- Modify: `app-modules/panel-admin/src/Filament/Resources/Appointments/Pages/CreateAppointment.php`
- Modify: `app-modules/billing/database/factories/CreditGrantFactory.php`
- Test: `app-modules/billing/tests/Feature/Credit/CreditExpiryTest.php`

- [ ] **Step 1: Escrever os testes que falham**

1. **Crédito vencido não conta** em `hasAvailableCredit()` nem em `ConsumeCredit`.
2. **Crédito sem validade continua valendo para sempre** — o crédito-presente não pode expirar (regra explícita do produto).
3. **Ordem**: com um presente eterno e um crédito vencendo em 3 dias, `ConsumeCredit` pega o que vence (ADR exemplo 10).
4. **Ordem entre dois com validade**: pega o de validade mais próxima.
5. **Crédito é consumido antes da cota mensal** (ADR D15.2): pessoa com cota 1 e um crédito com validade → o agendamento consome o crédito e a cota segue em 1.

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=CreditExpiry`

- [ ] **Step 3: Migrations**

```php
// user_credits
$table->timestamp('expires_at')->nullable()->after('transferred_at');

// credit_grants — default garante que qualquer linha existente em qualquer
// ambiente sobreviva à migration.
$table->string('reason')->default('admin_gift')->after('quantity');
```

Índice opcional em `user_credits (holder_id, status, expires_at)` se o plano de query mostrar necessidade; o índice `user_credits_consume_idx` atual cobre `(holder_id, status, created_at)`.

- [ ] **Step 4: Enum**

`CreditGrantReasonEnum: AdminGift = 'admin_gift'`, `QuotaRefund = 'quota_refund'`, implementando `HasLabel` (e `HasColor` se virar badge), com traduções em `app-modules/billing/lang/{pt_BR,en}/enums.php` no mesmo formato de `user_credit_status`.

- [ ] **Step 5: Scope `usable` no `UserCredit`**

```php
/**
 * Crédito que a pessoa pode gastar agora. Validade nula = presente, nunca expira.
 *
 * TODA leitura de saldo tem que passar por aqui: quem decide se pode agendar
 * (`canCreateAppointment`) e quem debita (`ConsumeCredit`) precisam concordar,
 * porque o débito falha em silêncio quando não acha crédito.
 *
 * @param  Builder<UserCredit>  $query
 * @return Builder<UserCredit>
 */
#[Scope]
protected function usable(Builder $query): Builder
{
    return $query
        ->where('status', UserCreditStatusEnum::Available)
        ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
}
```

- [ ] **Step 6: `ConsumeCredit` com a ordem nova**

```php
UserCredit::query()
    ->where('holder_id', $dto->holderId)
    ->usable()
    // Validade mais próxima primeiro; presentes eternos por último. CASE WHEN
    // em vez de `expires_at IS NULL` para funcionar em pgsql e sqlite.
    ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
    ->orderBy('expires_at')
    ->oldest()
    ->first()
    ?->update([
        'status' => UserCreditStatusEnum::InUse,
        'appointment_id' => $dto->appointmentId,
    ]);
```

- [ ] **Step 7: `expiresAt` no `CreditDTO` e no `IssueCredits`**

Adicionar `public string|DateTimeInterface|null $expiresAt = null` ao DTO (por último, para não quebrar chamadas posicionais) e gravar `'expires_at' => $dto->expiresAt` no `create()` do `IssueCredits`.

- [ ] **Step 8: Unificar a decisão de qual estoque paga**

Criar um único ponto de decisão — sugestão: `ResolveAppointmentPaymentSource` em `app-modules/billing/src/Core/Actions/Credit/`, devolvendo um enum ou bool `consumesCredit`. Regra: **crédito utilizável primeiro; cota mensal depois** (ADR D15.2).

Trocar nos dois consumidores:
- `BookAppointmentAction`: o `$hasMonthlyQuota = $user->monthly_appointments_left > 0` e o `if (! $hasMonthlyQuota)` que dispara `CreditConsumed`.
- `panel-admin/.../CreateAppointment.php`: `$this->consumesCredit = $user->monthly_appointments_left <= 0`.

Depois disso, `grep -rn "monthly_appointments_left" app app-modules` não deve mais mostrar decisão de pagamento fora do resolver.

- [ ] **Step 9: `hasAvailableCredit()` usa o scope**

```php
public function hasAvailableCredit(): bool
{
    return $this->credits()->usable()->exists();
}
```

- [ ] **Step 10: Rodar para ver passar**

Run: `php artisan test --compact app-modules/billing/tests app-modules/appointments/tests app-modules/panel-admin/tests`
Expected: PASS.

- [ ] **Step 11: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app-modules/billing app-modules/appointments app-modules/panel-admin app/Models/Users/User.php
git commit -m "feat(billing): validade de crédito, ordem de consumo e decisão única de estoque"
```

---

## Task 9: Devolução por cancelamento pós-virada

**Files:**
- Create: `app-modules/billing/src/Core/Actions/Credit/RefundQuotaAsCredit.php`
- Create: evento/listener seguindo o padrão de `AppointmentCreditReturned`
- Modify: `app-modules/appointments/src/Actions/Transitions/AbstractAppointmentTransition.php` (`cancelProcessStep`)
- Test: `app-modules/appointments/tests/Feature/.../QuotaRefundOnLateCycleCancellationTest.php`

**Interfaces:**
- Consumes: `ResolveQuotaAllowance`, `QuotaCycle`, `IssueCredits`, `CreditGrant`, `CreditGrantReasonEnum::QuotaRefund`.
- Produces: um `CreditGrant` + um `UserCredit` com `expires_at`.

- [ ] **Step 1: Escrever os testes que falham**

1. **Caso central** (ADR exemplo 3): virada dia 10, agendamento criado 09/set, cancelado 11/set com >4h → nasce 1 `UserCredit` com `expires_at` = fim do ciclo corrente (09/out) e um `CreditGrant` com `reason = QuotaRefund`, `admin_user_id = null`, `company_id` = a do agendamento, `justification` contendo as três datas.
2. **Cancelamento no mesmo ciclo não gera crédito** — a cota volta sozinha ao sair da contagem.
3. **`cancelled_late` não gera crédito** em nenhum caso (ADR D9).
4. **Agendamento pago com crédito não gera crédito novo** — o `ReturnCreditOnAppointmentCancelledListener` já devolve aquele; não pode haver duplicação.
5. **Cancelamento por admin pós-virada gera crédito** (admin nunca penaliza, então conta como cancelamento válido).
6. **Validade original preservada** (ADR D16): crédito de devolução usado e depois cancelado volta com a validade que tinha, mesmo vencida.

- [ ] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=QuotaRefund`

- [ ] **Step 3: Implementar a ação**

`RefundQuotaAsCredit::handle(Appointment $appointment): void` faz, em transação:

1. Resolver a `QuotaAllowance` do dono do agendamento. Se vazia, sair.
2. `$debited = QuotaCycle::forAnchor($allowance->anchor, $appointment->created_at)`. Se `! $debited->hasClosed()`, sair — o cancelamento devolve pela contagem normal.
3. `$current = QuotaCycle::forAnchor($allowance->anchor)`.
4. Criar o `CreditGrant` com `reason = QuotaRefund`, `admin_user_id = null`, `company_id = $appointment->company_id`, `target_user_id = $appointment->user_id`, `quantity = 1` e `justification` montada com as datas (reserva, consulta, cancelamento).
5. Chamar `IssueCredits` com `holderId = ownerId = $appointment->user_id`, `companyId = $appointment->company_id`, `grantId`, `quantity = 1`, `expiresAt = $current->end`.

Não reusar `GrantExtraCredit` (ADR D17): ela é admin-only e valida justificativa digitada.

- [ ] **Step 4: Disparar no cancelamento**

Em `cancelProcessStep()`, dentro do ramo `AppointmentStatus::Cancelled` que já dispara `AppointmentCreditReturned`, disparar também a devolução de cota **somente quando o agendamento não foi pago com crédito**:

```php
if ($this->appointment->status === AppointmentStatus::Cancelled) {
    event(new AppointmentCreditReturned((string) $this->appointment->getKey()));

    // Pago com cota mensal (não há UserCredit amarrado) e o ciclo do débito já
    // fechou: devolver pela contagem não devolveria nada, então emite crédito.
    if (! UserCredit::query()->where('appointment_id', $this->appointment->getKey())->exists()) {
        event(new QuotaRefundRequested((string) $this->appointment->getKey()));
    }
}
```

Registrar o listener no `BillingServiceProvider`, junto dos outros de crédito. Como o `AbstractAppointmentTransition` já roda dentro de `DB::transaction`, o listener precisa ser síncrono (ou o job precisa ser despachado `afterCommit`) — siga o que os listeners de crédito atuais fazem.

- [ ] **Step 5: Rodar para ver passar**

Run: `php artisan test --compact app-modules/appointments/tests app-modules/billing/tests`
Expected: PASS.

- [ ] **Step 6: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app-modules/billing app-modules/appointments
git commit -m "feat(billing): cancelamento pós-virada devolve consulta como crédito com validade"
```

---

## Task 10: Telas

**Files:**
- Modify: `app-modules/panel-admin/src/Filament/Resources/CreditGrants/CreditGrantResource.php` e `Pages/ListCreditGrants.php`
- Modify: `app-modules/panel-admin/src/Filament/Resources/Companies/RelationManagers/CreditGrantsRelationManager.php`
- Modify: `app-modules/panel-admin/src/Filament/Resources/Users/RelationManagers/CreditGrantsRelationManager.php`
- Modify: `app-modules/billing/src/Core/Actions/Credit/RevokeCreditGrant.php`
- Modify: `app-modules/panel-app/src/Filament/Pages/UserCreditsPage.php`
- Modify: `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php`
- Test: os testes já existentes desses recursos + novos para o guard de revogação

- [ ] **Step 1: Escrever os testes que falham**

1. Revogar um grant com `reason = QuotaRefund` é bloqueado (`RevokeCreditGrant` lança ou não faz nada — decida e teste).
2. A ação de revogar não aparece na tabela para grants automáticos.
3. `PlanCreditsWidget` não conta crédito vencido.
4. `UserCreditsPage` não lista crédito vencido como disponível e mostra a coluna de validade.

- [ ] **Step 2: Implementar**

- Coluna e `SelectFilter` de `reason` nas três telas de grant. O número "quanto doamos" não pode somar devolução.
- `->visible(fn (CreditGrant $record): bool => $record->reason === CreditGrantReasonEnum::AdminGift)` na ação de revogar, e o mesmo guard dentro de `RevokeCreditGrant` (defesa em profundidade).
- `UserCreditsPage`: aplicar `usable()` onde faz sentido para o saldo, adicionar `TextColumn::make('expires_at')` com placeholder `—` para os eternos, e corrigir o rótulo de `created_at` — hoje é `credits.columns.purchased_at` ("comprado em"), que fica errado para devolução. Renomear a chave de tradução para algo neutro ("recebido em") em `pt_BR` e `en`.
- `PlanCreditsWidget`: trocar o `where('status', Available)` da contagem por `->usable()`.

- [ ] **Step 3: Rodar**

Run: `php artisan test --compact app-modules/panel-admin/tests app-modules/panel-app/tests app-modules/billing/tests`
Expected: PASS.

- [ ] **Step 4: Pint e commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A app-modules/panel-admin app-modules/panel-app app-modules/billing
git commit -m "feat(panel): separa devolução de doação e exibe validade dos créditos"
```

---

## Task 11: Verificação final

- [ ] **Step 1: Suíte completa**

Run: `php artisan test --compact`

- [ ] **Step 2: Análise estática**

Run: `vendor/bin/phpstan analyse --memory-limit=2G`

- [ ] **Step 3: Grep de resíduos**

```bash
grep -rn "subDays(30)" app app-modules            # não deve sobrar nada de cota
grep -rn "monthly_appointments_left" app app-modules  # só leitura de saldo, nunca decisão de pagamento
grep -rn "resolveMonthlyAppointmentLimit" app app-modules  # deve estar vazio
```

- [ ] **Step 4: Percorrer os 13 exemplos da ADR** no painel, com uma empresa de virada conhecida e um usuário com assinatura própria.

---

## Self-Review (preenchido pelo autor do plano)

- **Cobertura da ADR:** D1 → Task 3; D2 → Task 1; D3 → Task 4; D4 → Task 5; D5/D6/D7 → Task 2; D8 → Task 3 (teste 5); D9 → não muda código, coberto por teste em Task 3 e Task 9; D10 → Task 6; D11 → consequência de Tasks 1-3 (nenhuma tabela criada); D12 → consequência, coberto por teste em Task 3; D13/D14 → Task 9; D15 → Task 8; D16 → Task 9 (teste 6); D17 → Tasks 8 e 9; D18 → nenhuma task, por ser ausência de migração. Sem lacunas.
- **Placeholders:** Tasks 1, 3, 5, 8 e 9 têm código real nos pontos não óbvios. As Tasks 2, 6 e 10 descrevem os testes em prosa em vez de cravar o código, porque dependem de helpers de tenant do Filament e de factories que variam por módulo — o agente deve seguir os testes vizinhos de cada pasta como modelo.
- **Consistência de tipos:** `QuotaAllowance` (`int $limit`, `?CarbonImmutable $anchor`) é produzido na Task 2 e consumido nas Tasks 3, 5 e 9. `QuotaCycle` (`start` inclusivo, `end` exclusivo) é produzido na Task 1 e consumido nas Tasks 3 e 9. `CreditDTO::$expiresAt` entra na Task 8 e é usado na Task 9. `User::$monthly_appointments_left` (int) mantém o contrato atual para todos os consumidores.
- **Riscos de ordem:** a Task 8 precisa vir antes da 9 (a devolução usa `expires_at` e o `reason`). A Task 2 precisa vir antes da 3 e da 5. A Task 6 é independente e pode ser paralelizada.
- **O que este plano NÃO faz:** nada de `UserCreditStatusEnum::Expired` sendo atribuído (a correção vem do filtro de leitura, ver ADR §5.4), nada de limite de remarcações, nada de assinatura de empresa gerando cota. Ver ADR §6.

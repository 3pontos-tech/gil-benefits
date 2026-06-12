# Spec — Polimento visual do dashboard do colaborador (`panel-app`)

- **Data:** 2026-06-12
- **Branch:** `feat/panel-app-dashboard-hub`
- **Módulo/painel:** `app-modules/panel-app` · painel `app` (`/app`) · usuário **Employee**
- **Escopo:** ajustes de **apresentação** no dashboard (`UserDashboard`). **Nenhuma regra de billing/agendamento é alterada.**

---

## 1. Contexto

O redesign do dashboard (hub de bem-estar financeiro) enxugou o antigo `UserCurrentPlanWidget` ao criar o `PlanCreditsWidget`, perdendo informação importante do **plano** (nome em destaque, status, descrição e features). Além disso, dois detalhes de apresentação ficaram inconsistentes:

1. O `PlanCreditsWidget` só mostra o nome do plano como texto cinza minúsculo (`@if($planName)`), sem status/descrição/features.
2. O `NextAppointmentWidget` mostra "Aguardando confirmação" como `<button disabled>` — visualmente um botão, mas sem explicação do que significa.
3. O `PlanCreditsWidget` exibe **apenas um** motivo quando o "Agendar consultoria" está bloqueado, mesmo quando há mais de um.

Esta entrega devolve a informação do plano e corrige os dois detalhes, **sem tocar em comportamento** (CTA de agendar, cancelar, `canCreateAppointment`/`hasOngoingAppointment`).

## 2. Decisões do brainstorming (validadas)

| Decisão | Escolha |
|---|---|
| Conteúdo do card de plano (de cara) | **Layout A:** nome + badge de status + anel consultas/mês + créditos + CTA |
| Descrição + features | Atrás de um link **"Ver plano →"** que abre **modal** |
| Estado "Aguardando confirmação" | **Mantém aparência de botão** desabilitado + adiciona **tooltip/`title`** |
| Mensagem de bloqueio | Listar **todos** os motivos aplicáveis |

---

## 3. Mudança 1 — Card "Plano & créditos" (`PlanCreditsWidget`)

### Layout (A)

```
┌─ PLANO & CRÉDITOS ──────────────────────┐
│ Plano Essencial               [ Ativo ] │  ← nome + badge de status
│                                          │
│   ◔ 0/1   consultas/mês                  │  ← anel (mantido)
│           +2 créditos avulsos            │  ← créditos (mantido)
│                                          │
│   [ Agendar consultoria ]                │  ← CTA (comportamento inalterado)
│   ⚠ <motivos de bloqueio, se houver>     │
│   Ver plano →                            │  ← abre modal (descrição + features)
└──────────────────────────────────────────┘
```

### Modal "Ver plano"

`Action` do Filament (link-styled), sem submit (só fechar):
- **Heading:** nome do plano + badge de status.
- **Corpo:** descrição do plano + lista de features.
- **Features por origem do plano:**
  - Plano contratual (empresa): `consultas/mês`.
  - Assinatura (`Price`): `consultas/mês`, `WhatsApp` (`whatsappEnabled`), `Materiais exclusivos` (`materialsEnabled`).

Implementação sugerida: `Action::make('viewPlan')->link()->modalHeading(...)->modalSubmitAction(false)` com `->modalContent(view('filament.app.widgets.partials.plan-details', [...]))` (partial Blade para controle visual) **ou** `->infolist([...])`. O `PlanCreditsWidget` passa a `implements HasActions, HasSchemas` e usa os traits `InteractsWithActions`/`InteractsWithSchemas` (mesmo padrão já adotado no `NextAppointmentWidget`).

### Resolução dos dados do plano

Hoje o widget tem `resolvePlanName()` e `resolveMonthlyLimit()`, que **consultam `CompanyPlan` duas vezes**. Consolidar numa única resolução `resolvePlan(User): ?PlanSummary` (DTO `final readonly`), evitando a query duplicada e mantendo a view "burra".

`PlanSummary` (shape):
```
- name: string
- status: 'active' | 'inactive' | 'expired'
- description: ?string
- monthlyLimit: int
- features: list<PlanFeature>   // {label: string, enabled: bool}
```

Regra de origem (mesma do `UserCurrentPlanWidget` legado): `CompanyPlan` ativo (com guardas de `starts_at`/`ends_at`) → fallback `activeSubscription()->price->plan`.

### Borda

Se `resolvePlan()` retornar `null` (sem plano), o card **oculta** nome/status/"Ver plano" e mantém apenas anel + créditos + CTA.

### Antes / depois (view)

**Antes** (`plan-credits.blade.php`): nome só como texto cinza, sem status/descrição/features.
**Depois:** nome + badge de status no topo; "Ver plano →" abre modal com descrição + features.

### Comportamento esperado (BDD)

```
Cenário: colaborador com plano contratual ativo
  Dado um Employee numa empresa com CompanyPlan ativo
  Então o card mostra o nome do plano e a badge "Ativo"
  E "Ver plano" abre um modal com a descrição e "consultas/mês"

Cenário: colaborador via assinatura
  Dado um Employee com assinatura ativa cujo Price habilita WhatsApp e materiais
  Então o modal lista consultas/mês, WhatsApp e Materiais exclusivos

Cenário: sem plano resolvível
  Dado um Employee sem CompanyPlan ativo e sem assinatura
  Então o card mostra apenas o anel, créditos e o CTA (sem nome/status/"Ver plano")
```

---

## 4. Mudança 2 — Mensagem de bloqueio (mesmo card)

Quando `canCreateAppointment()` é `false`, listar **todos** os motivos aplicáveis (empilhados, com ícone de alerta):

```
$reasons = [];
if (hasOngoingAppointment)                         → "...consultoria em andamento..."
if (! (monthly_left > 0 || hasAvailableCredit))    → "...sem agendamentos disponíveis neste mês."
```

Reaproveita as strings existentes em `panel-app::widgets.plans_overview` (`ongoing_appointment`, `no_appointments_available`). Computado no widget (`blockReasons(): array<string>`) para ficar testável; a view só itera.

> Correção vs. legado: o motivo "sem agendamentos" passa a considerar **crédito** (`hasAvailableCredit`), coerente com a regra real de `canCreateAppointment()`.

### BDD

```
Cenário: bloqueado por dois motivos
  Dado um Employee com uma consultoria em andamento E sem cota/crédito
  Então o card mostra os DOIS avisos empilhados

Cenário: bloqueado só pela cota
  Dado um Employee sem cota e sem crédito, sem consultoria em andamento
  Então o card mostra apenas "sem agendamentos disponíveis neste mês."
```

---

## 5. Mudança 3 — Tooltip no botão "Aguardando confirmação" (`NextAppointmentWidget`)

Mantém a aparência de botão desabilitado; adiciona `tooltip`/`title`:

> "O link da reunião aparecerá aqui assim que a consultoria for confirmada."

O estado confirmado (com `meeting_url` + status Active/Completed) segue como botão "Entrar na reunião" (link), sem mudança. Implementação: `->tooltip(...)` (Action) ou atributo `title="..."` no `<x-filament::button>`.

### Antes / depois

```
Antes:  <button disabled>Aguardando confirmação</button>            (sem explicação)
Depois: <button disabled title="O link da reunião aparecerá aqui    (mesmo visual + tooltip)
                 assim que a consultoria for confirmada.">
          Aguardando confirmação
        </button>
```

---

## 6. Arquivos afetados

| Arquivo | Ação |
|---|---|
| `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` | `HasActions`/`HasSchemas`; `viewPlanAction()`; `resolvePlan()` (consolida resolvePlanName+resolveMonthlyLimit) retornando `PlanSummary`; `blockReasons()` |
| `app-modules/panel-app/src/DTOs/PlanSummary.php` (+ `PlanFeature`) | criar DTO `final readonly` |
| `resources/views/filament/app/widgets/plan-credits.blade.php` | layout A + nome/status + "Ver plano" + todos os motivos |
| `resources/views/filament/app/widgets/partials/plan-details.blade.php` | criar (conteúdo do modal) — ou `infolist` inline |
| `resources/views/filament/app/widgets/next-appointment.blade.php` | `title`/tooltip no botão de espera |
| `app-modules/panel-app/lang/{pt_BR,en}/widgets.php` | strings novas: badge de status, "Ver plano", título do tooltip (reaproveita as de bloqueio) |
| `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php` | atualizar/expandir |
| `app-modules/panel-app/tests/Feature/Filament/Widgets/NextAppointmentWidgetTest.php` | adicionar asserção do tooltip |

## 7. Testes

- **PlanCreditsWidget:** mostra nome + status; "Ver plano" abre modal e exibe descrição/features; features diferentes por origem (contratual vs assinatura); estado sem plano (oculta nome/status); `blockReasons` lista um e dois motivos; CTA habilitado/desabilitado conforme `canCreateAppointment` (comportamento preservado).
- **NextAppointmentWidget:** estado de espera renderiza o `title`; estado confirmado mantém "Entrar na reunião".
- Rodar com `php artisan test --compact` filtrando os widgets.

## 8. Fora de escopo / preservado

- **Preservado, sem alteração:** CTA "Agendar consultoria" (redireciona para `CreateAppointment`), `CancelAppointmentAction`, e as regras `canCreateAppointment()`/`hasOngoingAppointment()`.
- **Fora desta entrega:** a brecha da "consultoria fantasma" (pending vencido que bloqueia para sempre) — tratada na issue #194.

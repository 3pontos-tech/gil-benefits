---
type: spec
title: "Widget \"Plano & créditos\" — três baldes de crédito"
module: panel-app
date: 2026-06-18
related:
  plan: panel-app/2026-06-18-creditos-tres-baldes
---

# Widget "Plano & créditos" — três baldes de crédito

Data: 2026-06-18
Módulo: `panel-app` (dashboard do colaborador)
Componente: `PlanCreditsWidget` + view `plan-credits.blade.php`

## Contexto

O card "Plano & créditos" do dashboard do colaborador hoje mostra dois números:

- um anel com a **cota mensal** do plano (`monthlyLeft / monthlyLimit`);
- uma linha **"Créditos avulsos: +N"** que soma, num único valor, créditos de origens diferentes.

Dois problemas:

1. **Falta de transparência de origem** — o colaborador não distingue os créditos que ele mesmo comprou dos que a empresa comprou e alocou para ele.
2. **Incoerência da cota mensal (bug #3)** — o **limite** do anel vem do plano resolvido em `PlanCreditsWidget::resolvePlan()` (que prioriza `CompanyPlan`), mas o **restante** vem do accessor `User::monthlyAppointmentsLeft()` (que prioriza a assinatura individual). Quando o colaborador tem os dois, o anel mistura fontes e exibe valores como "1/4".

## Modelo de crédito (estado atual, para referência)

Existem dois mecanismos distintos:

- **Cota mensal do plano** (`monthly_appointments`) — renovável; o plano vem de `CompanyPlan` (empresa) ou de assinatura (`activeSubscription`).
- **Créditos avulsos** (`UserCredit`) — não renovam; consumidos um a um. Cada registro tem `owner_id` (quem comprou) e `holder_id` (quem usa).

Consumo ao agendar (`BookAppointmentAction`): usa a cota mensal primeiro; só quando ela zera consome 1 avulso (o mais antigo, FIFO, sem olhar a origem).

Origem dos avulsos do colaborador (`holder_id = colaborador`, `status = Available`):

- `owner_id == colaborador` → **ele comprou** (página "Meus créditos").
- `owner_id != colaborador` (= `company->user_id`) → **a empresa comprou e alocou** (`AllocateCreditToEmployee` move do pool da empresa preservando o `owner`).

## Decisões

1. O card passa a exibir **três baldes**:
   - **Cota mensal do plano** (renovável) — o anel atual.
   - **Seus créditos** (avulsos, `owner = colaborador`).
   - **Créditos da empresa para você** (avulsos, `owner = empresa`).
2. **A empresa prevalece sobre a assinatura individual** na cota mensal. A fonte do limite e do restante passa a ser única e consistente.
3. **Layout C**: mantém o anel da cota mensal e, abaixo, o **total** de avulsos com uma legenda fina detalhando a origem.

### Fora de escopo (YAGNI)

- Não exibir o "pool" de créditos da empresa ainda não distribuídos (não pertence ao colaborador).
- Não adicionar compra/transferência de crédito neste widget.
- Não alterar a ordem de consumo (cota mensal → avulso FIFO).

## Dados (`PlanCreditsWidget::getViewData`)

Os baldes 2 e 3 saem de uma única consulta, particionada em memória (volume por usuário é pequeno).

**Antes:**

```php
$availableCredits = UserCredit::query()
    ->where('holder_id', $user->getKey())
    ->where('status', UserCreditStatusEnum::Available)
    ->count();
```

**Depois:**

```php
$availableCredits = UserCredit::query()
    ->where('holder_id', $user->getKey())
    ->where('status', UserCreditStatusEnum::Available)
    ->get(['owner_id']);

$ownCredits = $availableCredits->where('owner_id', $user->getKey())->count();
$companyCredits = $availableCredits->count() - $ownCredits;
```

Retorno do `getViewData` ganha: `creditsTotal` (= `$availableCredits->count()`), `ownCredits`, `companyCredits`. O `canCreateAppointment` continua usando `creditsTotal > 0`.

### Correção da cota mensal (bug #3)

`User::monthlyAppointmentsLeft()` passa a priorizar **`CompanyPlan` antes da assinatura**, espelhando a ordem de `PlanCreditsWidget::resolvePlan()`. Assim o anel (limite + restante), o booking e qualquer outro consumidor do accessor concordam, e a regra "empresa prevalece" fica valendo em um único ponto de verdade.

## Comportamento esperado (BDD)

**Happy path — colaborador com plano da empresa e os dois tipos de avulso**
- Dado um colaborador com plano da empresa (cota 4/mês, 3 restantes), 2 créditos próprios e 5 alocados pela empresa
- Então o card mostra o anel "3/4", "Créditos avulsos: 7" e a legenda "2 seus · 5 da empresa".

**Empresa prevalece (bug #3)**
- Dado um colaborador com `CompanyPlan` ativo (cota 4) e também uma assinatura individual (cota 2)
- Então o anel usa a cota do `CompanyPlan` (limite e restante da mesma fonte), nunca um misto "1/4".

**Legenda condicional**
- Só créditos próprios → legenda "2 seus".
- Só créditos da empresa → legenda "5 da empresa".
- Nenhum avulso → linha mostra "Créditos avulsos: 0" e **sem legenda**.

**Sem plano, com avulsos**
- Dado um colaborador sem plano resolvido mas com créditos avulsos
- Então o anel e o nome do plano não aparecem, os avulsos aparecem e o botão "Agendar consultoria" fica habilitado (consome avulso).

**Bloqueios de agendamento (compatibilidade — inalterado)**
- Consultoria em andamento → botão desabilitado + motivo, como hoje.
- Sem cota mensal e sem nenhum avulso → botão desabilitado + motivo, como hoje.

**Ação "Ver plano"** — inalterada.

## Layout (C)

Estrutura da view `plan-credits.blade.php`:

```
PLANO & CRÉDITOS                    Ver plano
Plano Empresarial

( 3/4 )  agendamentos restantes este mês
─────────────────────────────────────────
Créditos avulsos                          7
 • 2 seus  ·  5 da empresa            (fina)

[ Agendar consultoria ]   (ou motivos de bloqueio)
```

- Anel e nome do plano: renderizados apenas quando há plano resolvido.
- Total de avulsos sempre visível; legenda só com as origens > 0.
- Botão/bloqueios: bloco atual reaproveitado sem mudança de regra.

## Strings (i18n)

Novas chaves em `panel-app::widgets.plan_credits` (pt_BR e en), seguindo o padrão já criado:
`avulsos_total` ("Créditos avulsos"), `avulsos_own` ("{n} seus"), `avulsos_company` ("{n} da empresa").

## Testes

`PlanCreditsWidgetTest` (feature):
- Exibe a cota mensal do plano da empresa.
- Exibe o total de avulsos e separa por origem (own vs empresa) via `owner_id`.
- Empresa prevalece sobre assinatura na cota mensal quando há ambos.
- Legenda condicional (só seus / só empresa / nenhum).
- Sem plano + com avulsos → permite agendar.
- Bloqueios inalterados (regressão).

`User::monthlyAppointmentsLeft` (feature/unit):
- Com `CompanyPlan` + assinatura simultâneos, retorna a cota do `CompanyPlan`.

## Arquivos afetados

- `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php`
- `resources/views/filament/app/widgets/plan-credits.blade.php`
- `app/Models/Users/User.php` (`monthlyAppointmentsLeft` → CompanyPlan-first)
- `app-modules/panel-app/lang/{pt_BR,en}/widgets.php`
- Testes correspondentes

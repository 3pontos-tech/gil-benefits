---
type: plan
title: "Renovação de cota ancorada — trabalho restante e reconciliação da ADR"
module: billing
status: in_progress
date: 2026-08-14
related:
  adr: billing/2026-08-10-monthly-quota-renewal-anchor
  supersedes_plan: billing/2026-08-10-monthly-quota-renewal-anchor
---

# Renovação de cota ancorada — trabalho restante

**Goal:** Fechar o que falta da renovação de cota ancorada na contratação e reconciliar a ADR e o plano original com as decisões que mudaram durante a implementação. A âncora, a janela por ciclo e a devolução por cancelamento pós-virada já estão implementadas e testadas no working tree da branch `feat/monthly-quota-renewal-anchor`; este plano cobre o resto.

**Architecture:** A aritmética de ciclo vive em `QuotaCycle` (billing/Core/Support). A resolução de "qual contrato vale para esta pessoa" vive em `ResolveQuotaAllowance`, devolvendo limite e âncora juntos num `QuotaAllowance`. `User::monthlyAppointmentsLeft()` conta dentro do ciclo corrente e soma as devoluções carimbadas nele. A devolução por cancelamento pós-virada **não** usa o ledger de créditos: é uma coluna `appointments.quota_refunded_at`, carimbada no cancelamento e lida como termo positivo na cota. A âncora da assinatura individual é carimbada por observer no model, não pelo Action de upsert, porque existe mais de um caminho de escrita.

**Tech Stack:** Laravel 12, Filament v5, Livewire v4, Pest v4, PHP 8.4. Banco: `pgsql` em produção, `sqlite` em memória nos testes.

**Convenção de código desta base:** nenhum comentário dentro do corpo de método — explicação vai em docblock acima do método ou da classe.

---

## Estado atual (já implementado, não commitado)

| Arquivo | O que faz |
| --- | --- |
| `app-modules/billing/src/Core/Support/QuotaCycle.php` | Value object do ciclo. Passo mensal a partir da âncora original, clamp sem drift, `start` inclusivo e `end` exclusivo, `contains()` e `hasClosed()` |
| `app-modules/billing/src/Core/DTOs/QuotaAllowance.php` | Limite e âncora juntos, com `none()` e `isEmpty()` |
| `app-modules/billing/src/Core/Actions/ResolveQuotaAllowance.php` | Empresa pelo tenant do Filament com fallback `employerCompanyId()`; plano contratual tem precedência sobre assinatura própria |
| `app-modules/billing/src/Core/Models/CompanyPlan.php` | Scope `active()`, dono único da consulta que estava copiada em quatro lugares |
| `app-modules/billing/src/Core/Observers/SubscriptionQuotaAnchorObserver.php` | Carimba `quota_anchor_at` na primeira ativação, em `saving`, cobrindo Barte, Stripe/Cashier, console e factory |
| `app-modules/billing/src/Core/Models/Subscriptions/Subscription.php` | Constante `STATUS_ACTIVE`, cast por método `casts()` (mesclado com o `$casts` do Cashier), `#[ObservedBy]` |
| `app/Models/Users/User.php` | `monthlyAppointmentsLeft()` por ciclo mais `quotaRefundsInCycle()`; `resolveMonthlyAppointmentLimit()` removido; cache de 60s entre requests removido |
| `app-modules/appointments/src/Actions/Transitions/AbstractAppointmentTransition.php` | `stampQuotaRefundIfCycleClosed()`, chamado antes de `AppointmentCreditReturned` |
| `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` | Exibe a data de renovação, e resolve o plano pelo mesmo caminho do saldo |
| Migrations | `add_quota_refunded_at_to_appointments`, `add_quota_anchor_at_to_billing_subscriptions`, e os dois backfills separados (`starts_at`, `quota_anchor_at`) |
| Consumidores trocados para o scope | `Company::activeContractualPlan()`, `PlanCreditsWidget::resolvePlan()`, `GetEngagementFunnel::contractedSeatsByCompany()` |

Testes: `QuotaCycleTest` (6), `UserMonthlyAppointmentsLeftTest` (7), `QuotaRefundOnClosedCycleTest` (6), `QuotaAnchorTest` (8), `BackfillStartsAtTest` (3), `ContractualPlansRelationManagerTest` (2), `PlanCreditsWidgetTest` (18, sendo 4 novos).

---

## Decisões que mudaram em relação à ADR de 2026-08-10

Registradas aqui porque a Task 3 é o que as leva para dentro da ADR.

1. **A devolução não usa o ledger de créditos.** A D13 descartou "expressar +1 no ciclo corrente" alegando que exigiria a tabela materializada rejeitada na D11. Não exige: exige saber *quando* o cancelamento aconteceu. Com uma coluna carimbada no cancelamento, **D14, D15, D16 e D17 deixam de existir**, junto com as Tasks 8, 9 e 10 do plano original e o pitfall do débito silencioso do `ConsumeCredit` (§5.5).
2. **O carimbo é feito no cancelamento, não na leitura.** A informação "esta consulta foi paga com cota ou com crédito" só existe naquele instante: `ReturnCreditOnAppointmentCancelledListener` zera `user_credits.appointment_id` logo depois, e pagar com cota nunca escreveu linha em tabela nenhuma. Por isso não é preciso uma coluna `paid_with_credit`.
3. **A âncora da assinatura é carimbada por observer no model.** O plano original mandava carimbar em `UpsertSubscription`, que é só o caminho do Barte — o Stripe entra pelo `WebhookController` do Cashier e escreve direto na tabela.
4. **A justificativa da D4 estava parcialmente errada.** A ADR §1.4.5 usa `SyncSubscriptionToFlammaCompany` como motivo para não confiar no `created_at`. Aquele comando cria assinatura *da empresa*, que nunca chega no caminho da cota (D5). O que sustenta a coluna é que `created_at` é o instante do webhook `PENDING` (antes do pagamento) e é metadado que ninguém corrige para um cliente sem falsificar o histórico da linha.
5. **STORY-376 (e-mail de renovação) sai do escopo.** Todos os critérios de aceite dela dependem de um job de renovação que a D11 elimina. Coerente com o próprio Fora do Escopo do épico, que já lista notificação de renovação como futuro.
6. **STORY-373 está errada sobre plano individual de empresa.** `Company` é `Billable` e tem `subscriptions()`, mas o único campo lido dessa assinatura é `quantity`, em `TenantSeatsCounterAction`. `price.monthly_appointments` de assinatura de empresa nunca foi lido para cota. A D5 vale; o texto da story é que precisa ser corrigido.
7. **A D10 (teto de 45 dias) sai do escopo deste épico.** A ADR justifica o teto dizendo que marcar longe "apaga em silêncio os ciclos do meio" — mas isso já acontece hoje, causado pelo `hasOngoingAppointment()`, que não muda nesta alteração. Como a mudança não piora o cenário, não há o que conter aqui. Travar-se por meses continua possível e continua ruim, só que é problema pré-existente, e limitar a antecedência máxima de agendamento é decisão de produto: precisa de story própria e aval do PO, não de carona num épico de renovação.
8. **Migração: só a D18, sem relatório de impacto.** A reconciliação de ciclo que a STORY-375 pede não é feita. Chegou a ser proposto um command de medição pré-deploy e foi descartado: o tamanho do grupo prejudicado não é uma incógnita empírica, é dedutível. Para alguém piorar precisa estar ao mesmo tempo no último dia de um ciclo de 31 dias e com uma reserva feita numa faixa de cerca de um dia — e as âncoras estão espalhadas pelo mês. O grupo é estruturalmente minúsculo e o dano dele é esperar um dia; nenhuma consulta paga é apagada. O relatório confirmaria o que a fórmula já garante. A estratégia de transição que o Risco 1 do épico pede está **declarada na D18 da ADR**, que é onde estratégia se declara.

### Três bugs de código no plano original

* `use Illuminate\Support\CarbonImmutable` não existe nesta versão — é `Carbon\CarbonImmutable`.
* O teste de clamp espera `start = 28/02` para âncora 31/jan com "agora" em 15/fev; 15/fev ainda pertence ao ciclo que abriu em 31/jan.
* O laço de correção de `diffInMonths` precisa de guarda `$months > 0`, senão decrementa abaixo de zero em borda de mês curto.

---

## Task 1: Âncora contratual obrigatória e backfill

Maior risco de dado errado em produção do que sobrou. Hoje um plano contratual com `starts_at` nulo ancora silenciosamente no `created_at` — o dia em que alguém cadastrou o plano no admin, sem relação com o contrato. É a coluna que define a virada de todos os funcionários da empresa.

**Files:**
- Modify: `app-modules/panel-admin/src/Filament/Resources/Companies/RelationManagers/ContractualPlansRelationManager.php` (linha 101)
- Create: migration de backfill em `app-modules/billing/database/migrations/`
- Test: `app-modules/panel-admin/tests/Feature/Filament/Resources/Companies/` (seguir o teste vizinho do relation manager; se não existir, criar um mínimo de validação)

**Interfaces:**
- Consome: nada novo.
- Produz: garantia de que `ResolveQuotaAllowance` não exercita mais o fallback `?? created_at` no ramo contratual.

- [x] **Step 1: Teste que falha**

Validação de formulário: criar plano contratual sem `starts_at` deve acusar `required`. Usar `livewire()` no relation manager, `->callAction()` de criação com `starts_at => null`, e `assertHasFormErrors(['starts_at' => 'required'])`.

- [x] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=ContractualPlans`

- [x] **Step 3: Tornar obrigatório**

```php
DatePicker::make('starts_at')
    ->label(__('panel-admin::resources.companies.relation_managers.contractual_plans.form.starts_at'))
    ->displayFormat('d/m/Y')
    ->required(),
```

**Não** remover o `$get('starts_at') ?? now()->toDateString()` da regra de sobreposição (linha 78): a validação de sobreposição roda antes da de obrigatoriedade, e um `null` ali quebraria.

- [x] **Step 4: Migration de backfill**

Sem alteração de schema — a coluna segue nullable no banco, e o fallback do resolver segue como rede para dado legado.

```php
public function up(): void
{
    DB::table('company_plans')
        ->whereNull('starts_at')
        ->update(['starts_at' => DB::raw('DATE(created_at)')]);
}
```

O `down()` é irreversível por desenho: depois do update não há como distinguir o que era nulo. Documentar isso no docblock da migration em vez de fingir reversibilidade.

Conferir que `DATE(created_at)` roda em `pgsql` e em `sqlite`; se der problema no sqlite dos testes, fazer em PHP com `chunkById`.

- [x] **Step 5: Verificar**

Run: `php artisan migrate --pretend` e `php artisan test --compact app-modules/panel-admin/tests`

- [x] **Step 6: Pint**

Run: `vendor/bin/pint --dirty --format agent`

---

## Task 2: Data de renovação na tela

É o único item que fecha o problem statement do épico ("saber exatamente quando meu crédito renova"). O épico marca as seis stories como Backend, mas entregar o cálculo certo e invisível deixaria a queixa original de pé. O valor já está calculado dentro do mesmo método que lê a cota, então é exposição, não cálculo novo.

**Files:**
- Modify: `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` (`getViewData()`)
- Modify: `resources/views/filament/app/widgets/plan-credits.blade.php` (perto da linha 53, onde `$monthlyLeft` e `$monthlyLimit` já são exibidos)
- Modify: `app-modules/panel-app/lang/{pt_BR,en}/widgets.php`
- Test: `app-modules/panel-app/tests/Feature/Filament/Widgets/PlanCreditsWidgetTest.php`

**Interfaces:**
- Consome: `ResolveQuotaAllowance`, `QuotaCycle`.
- Produz: chave `renewsAt` no array de `getViewData()`, `null` para quem não tem plano.

- [x] **Step 1: Teste que falha**

1. Empregado com plano contratual ancorado no dia 10, "hoje" 15/set → o widget expõe `renewsAt` igual a 10/out.
2. Usuário sem plano e sem assinatura → `renewsAt` é `null` e o blade não renderiza a linha.
3. Âncora 31/jan, hoje em fevereiro → `renewsAt` é 28/fev, provando que a tela usa o mesmo clamp do cálculo.

- [x] **Step 2: Rodar para ver falhar**

Run: `php artisan test --compact --filter=PlanCreditsWidget`

- [x] **Step 3: Expor a data**

Em `getViewData()`, derivar de `ResolveQuotaAllowance` e `QuotaCycle` sem query nova — a `allowance` já é resolvida no mesmo request pelo `monthly_appointments_left`. Devolver `null` quando `isEmpty()`.

- [x] **Step 4: Exibir no blade**

Linha discreta abaixo do contador de consultas, no formato `d/m`, com chave de tradução em `pt_BR` e `en`. Não renderizar nada quando `renewsAt` for `null`.

- [x] **Step 5: Verificar e Pint**

Run: `php artisan test --compact app-modules/panel-app/tests` e `vendor/bin/pint --dirty --format agent`

---

## Task 3: Reconciliar a ADR e o plano original

Esta é a task mais urgente depois da Task 1: quem pegar o PR hoje vai implementar a Rota A que foi descartada, com um snippet que dobra o benefício.

**Files:**
- Modify: `app-modules/billing/docs/adr/2026-08-10-monthly-quota-renewal-anchor.md`
- Modify: `app-modules/billing/docs/plans/2026-08-10-monthly-quota-renewal-anchor.md` (marcar como superseded por este)

- [x] **Step 1: Reescrever D13 a D17**

Substituir por uma decisão única: devolução por carimbo em `appointments.quota_refunded_at`, feito no cancelamento e lido como termo positivo na cota. Registrar que a D13 original foi decidida sobre a premissa falsa de que isso exigiria a tabela da D11, e que o custo aceito de olhos abertos é auditoria — não nasce registro visível na tela do admin. Anotar a mitigação barata disponível: exibir "+1 devolvido por cancelamento" no widget.

- [x] **Step 2: Corrigir a D4**

Remover `SyncSubscriptionToFlammaCompany` da justificativa (cria assinatura de empresa, que não gera cota pela D5) e manter os dois motivos válidos. Registrar que o carimbo vive em observer no model, não em `UpsertSubscription`, porque o Stripe entra pelo `WebhookController` do Cashier.

- [x] **Step 3: Remover a D10**

Registrar que a perda de ciclos do meio é causada pelo `hasOngoingAppointment()` e já existe hoje, então o teto não é contenção de nada que esta mudança cause. Marcar a decisão como retirada do escopo, apontando que o problema real (travar-se por meses) segue de pé e merece story própria com aval do PO.

- [x] **Step 4: Registrar a D18 ajustada**

D18 mais relatório de impacto read-only antes do deploy, e o dano quantificado: no pior caso uma pessoa espera até um dia a mais, nenhuma consulta paga é apagada, e o caso comum favorece o cliente.

- [x] **Step 5: Registrar as decisões de escopo**

STORY-376 fora do escopo e STORY-373 com texto a corrigir, ambas com o porquê.

- [x] **Step 6: Corrigir §1.4, §3, §5.3, §5.4 e §5.5**

Constraint 1.4.5 (o comando de sync), os exemplos 3, 10 e 11 da §3 (que descrevem crédito com validade), o mapa de código novo da §5.3, a lista de arquivos da §5.4 e o pitfall da §5.5, que deixa de existir porque a Rota B não toca no ledger.

- [x] **Step 7: Anotar os três bugs de código do plano original**

Para que ninguém copie os snippets como estão.

---

## Task 4: Limpezas oportunas

Nenhuma é bloqueante. Fazer em commit separado do resto.

- [x] **Step 0: Remover o cache de 60s de `monthly_appointments_left`**

Feito. O `Cache::remember` cobrava um defeito real — o `CreateAppointment` do admin não checa
consulta em aberto, então dois agendamentos para a mesma pessoa dentro do mesmo minuto liam
cota cheia nas duas vezes e o segundo deixava de consumir crédito — em troca de pouquíssimas
queries: o atributo é lido em quatro lugares, todos de um usuário só, nenhum em laço. Saíram
junto o `forgetMonthlyAppointmentsLeftCache()` e a chamada dele no cancelamento. A memoização
por instância (`shouldCache()`) ficou, e o resíduo dela está registrado na §4 da ADR.

- [x] **Step 1: `SubscriptionFactory` quebrada**

Feito. A factory estava certa; o que não funcionava era chegar até ela. O Cashier sobrescreve
`newFactory()` no model pai, e o `#[UseFactory]` só é lido dentro da implementação padrão
desse método — então o atributo era ignorado em silêncio e `Subscription::factory()`
construía o model do Cashier, com tabela `subscriptions` e chave de dono `company_id`.
Corrigido declarando `newFactory()` no nosso model e removendo o atributo, que ali nunca
teve efeito. Testes novos em `SubscriptionFactoryTest`, cobrindo também os states
`active/trialing/pastDue/canceled` e o `forCompany()`, que eram código morto.

- [ ] **Step 2: Absorver o literal `'active'`** — *não recomendado agora*

`Subscription::STATUS_ACTIVE` já existe e o literal segue em `User::activeSubscription()`,
`GetEngagementFunnel::ACTIVE_SUBSCRIPTION_STATUS` e `TenantSeatsCounterAction`. É arrumação:
não há impacto confirmado, e a string não vai mudar. Fica registrado, não feito.

- [ ] **Step 3: Decisão duplicada de qual estoque paga** — *não recomendado agora*

`BookAppointmentAction:23` (`$hasMonthlyQuota = $user->monthly_appointments_left > 0`) e
`CreateAppointment:39` do admin (`$this->consumesCredit = $user->monthly_appointments_left <= 0`)
decidem a mesma coisa em separado. As duas expressões são **logicamente idênticas** hoje, então
é risco latente e não defeito. O que era defeito de verdade nessa vizinhança — o admin não
checar consulta em aberto somado ao cache de 60s — foi fechado no Step 0.

---

## Task 5: Verificação final

- [ ] **Step 1: Suíte completa**

Run: `php artisan test --compact`

- [ ] **Step 2: Análise estática**

Run: `vendor/bin/phpstan analyse --memory-limit=2G`

- [ ] **Step 3: Grep de resíduos**

```bash
grep -rn "subDays(30)" app app-modules                     # nada de cota deve sobrar
grep -rn "resolveMonthlyAppointmentLimit" app app-modules   # deve estar vazio
grep -rn "monthly_appointments_left" app app-modules        # só leitura de saldo
```

- [ ] **Step 4: Conferir volume antes de rodar as migrations**

Duas contagens, no banco de produção, logo antes do deploy:

```sql
SELECT count(*) FROM company_plans WHERE starts_at IS NULL;
SELECT count(*) FROM billing_subscriptions WHERE stripe_status = 'active';
```

A primeira diz quantas linhas o backfill contratual vai tocar; a segunda, se o volume é da
ordem que se supõe. Vindo pequenas, como se espera, seguir. Vindo em outra ordem de
grandeza, reavaliar se os backfills precisam de janela dedicada — hoje ninguém mediu essas
tabelas, e é a única incógnita real do cutover.

- [ ] **Step 5: Percorrer os exemplos da ADR no painel**

Com uma empresa de virada conhecida e um usuário com assinatura própria. Conferir especialmente o exemplo 3 (devolução pós-virada), que é o comportamento novo, e o exemplo 4 (clamp de 31/jan).

---

## Self-Review

- **Cobertura:** D3 → Task 1; a devolução reescrita, D4, D10 retirada, D18 e as decisões de escopo → Task 3. As D1, D2, D5, D6, D7, D8, D9, D11 e D12 já estão implementadas e testadas no working tree. A D10 foi retirada do escopo e não tem task.
- **Portões de decisão:** nenhum. As duas decisões pendentes foram fechadas: a data na tela entra (Task 2) e o teto de 45 dias sai do épico.
- **Riscos de ordem:** a Task 1 é independente e deve vir primeiro, por ser a única com risco de dado errado em produção. A Task 3 deve vir antes de qualquer outra pessoa pegar o PR, porque hoje o documento manda construir o desenho descartado. A Task 2 depende do `QuotaCycle`, que já existe. A Task 4 é independente de tudo.
- **O que este plano NÃO faz:** não cria tabela de períodos, não emite crédito com validade, não cria `CreditGrantReasonEnum`, não altera `ConsumeCredit` e não mexe nas telas de doação — todos pedaços do desenho descartado, sem pendência associada.

  O que ficou de fora e **tem pendência real** está registrado em issue própria, para não se perder quando o épico fechar:

  | Issue | Assunto | Natureza |
  | --- | --- | --- |
  | [#252](https://github.com/3pontos-tech/gil-benefits/issues/252) | Sem teto de antecedência, agendar longe tranca a pessoa por meses | Problema pré-existente; decisão de produto |
  | [#253](https://github.com/3pontos-tech/gil-benefits/issues/253) | Histórico de ciclos auditável (DoD da STORY-374) não entregue | Must Have por fazer; decisão com o PO |
  | [#254](https://github.com/3pontos-tech/gil-benefits/issues/254) | E-mail de crédito renovado (STORY-376) sem gatilho | Escopo removido; volta se houver decisão |
  | [#255](https://github.com/3pontos-tech/gil-benefits/issues/255) | Assinatura de empresa não gera cota (STORY-373 errada) | Texto de story a corrigir, ou feature nova |
  | [#256](https://github.com/3pontos-tech/gil-benefits/issues/256) | Devolução de cota invisível no admin | Custo aceito da rota escolhida |
  | [#275](https://github.com/3pontos-tech/gil-benefits/issues/275) | A devolução pós-virada deve existir? | **No ar, sem story** — decisão de produto pendente |

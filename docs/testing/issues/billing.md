## Contexto

Módulo de billing integra Stripe via `laravel/cashier` para dois billables (User e Company). Inclui webhook customizado que altera dinamicamente o customer model pelo `metadata.model` do payload, dois middlewares de redirecionamento para não-assinantes e um command de sync com Stripe. Nenhum desses pontos críticos de receita tem teste.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#billing`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/billing/src/Stripe/Subscription/SubscriptionWebhookController.php:12` — `handleWebhook` chama `Cashier::useCustomerModel` com valor derivado do `metadata.model` do payload **antes** do `parent::handleWebhook`. Testar: assinatura Stripe inválida, metadata ausente, morph inválido/não registrado, payload malformado, reprocessamento idempotente (mesmo `event.id` duas vezes) — Feature — M
- [ ] `app-modules/billing/src/Stripe/Subscription/Company/RedirectCompanyIfNotSubscribed.php:13` — middleware bloqueia acesso se sem subscription (exceto `flamma-company` e `stripe/*`); cenários sem teste: sem subscription, `past_due`, `canceled`, `trialing` — Feature — M
- [ ] `app-modules/billing/src/Stripe/Subscription/User/RedirectUserIfNotSubscribed.php:19` — idem para o painel user — Feature — M

### 🟠 Alto
- [ ] `app-modules/billing/src/Stripe/Subscription/Company/CompanyBillingProvider.php:13,29` — `getRouteAction()`/`getSubscribedMiddleware()` sem teste — Feature — P
- [ ] `app-modules/billing/src/Stripe/Subscription/User/UserBillingProvider.php:12,31` — idem — Feature — P
- [ ] `app-modules/billing/src/Core/Commands/SyncStripeResourcesCommand.php:32` — sincroniza Products/Prices com Stripe; sem teste com `Http::fake`; idempotência desconhecida — Feature — M

### 🟡 Médio
- [ ] `app-modules/billing/src/Core/Repositories/ConfigPlanRepository.php:19,53,59` — `all()`, `getPlansFor()`, `getActiveTenantPlan()` sem teste — Unit — P
- [ ] `app-modules/billing/src/Core/Repositories/EloquentPlanRepository.php:29` — cache 15 min por `type`; validar se tenancy é intencional (ver ambiguidade no relatório) — Feature — P

### 🟢 Baixo
- [ ] `app-modules/billing/database/factories/` — adicionar `SubscriptionFactory` e refatorar helpers do `tests/Pest.php` (`actingAsCompanyOwner`, `actingAsSubscribedEmployee`) — Unit — P

## Fluxos de usuário impactados

- **Assinatura** — webhook, ativação local, middlewares de acesso (ver fluxo 4.1 do relatório).

## Pontos críticos específicos

- [ ] Verificação de assinatura Stripe antes do morph switch
- [ ] Idempotência por `event.id`
- [ ] Estados de subscription no middleware: `active`, `trialing`, `past_due`, `canceled`
- [ ] `Http::fake()` para Stripe em bootstrap / grupo `@group stripe`

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#billing`

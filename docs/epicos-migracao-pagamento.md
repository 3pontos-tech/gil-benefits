# Épicos e Stories — Migração de Gateway de Pagamento

## FLM - ÉPICO - XX | Desacoplamento do Stripe e Criação de Camada de Abstração de Pagamento

**Descrição do Épico:**
Refatorar a integração de pagamentos da plataforma, substituindo a dependência direta do Stripe (via Laravel Cashier) por uma camada de abstração agnóstica de provider, permitindo trocar ou adicionar gateways de pagamento (Pagaa, Asaas, Stripe, etc.) com mínimo impacto no código da aplicação.

**Objetivo de Negócio:**
Eliminar o lock-in com o Stripe, reduzir custos operacionais de processamento de pagamentos, aumentar a resiliência da plataforma e permitir negociações estratégicas com diferentes gateways sem necessidade de reescrita do sistema.

**Escopo técnico identificado:**
- 5 tabelas com colunas `stripe_*` acopladas ao provider
- 2 Billable models (`User`, `Company`) via trait `Billable` do Cashier
- 2 Subscription models estendendo `Laravel\Cashier\Subscription`
- 2 BillingProviders Filament dependentes do Stripe Portal
- 2 Middlewares com checagem de `stripe_status`
- 2 Pages de checkout criando Cashier checkout sessions
- 1 WebhookController acoplado ao Cashier
- 1 Command `billing:sync-stripe` direto na API do Stripe
- 1 `BillingProviderEnum` com valor hardcoded `Stripe`

---

## FLM - STORY - XX | Levantamento técnico e mapeamento completo de dependências do Stripe

**Descrição:**
Como time de engenharia, quero mapear todas as dependências diretas e indiretas do Stripe no codebase, para ter uma visão completa do esforço de migração e definir a estratégia de desacoplamento sem surpresas.

**Tarefas:**

- Mapear todos os pontos de uso do `Billable` trait (User, Company)
- Mapear todas as referências a `Cashier::`, `stripe_id`, `stripe_price`, `stripe_status`
- Identificar eventos do Stripe consumidos (webhook events)
- Listar features do Stripe em uso: Checkout Sessions, Billing Portal, Metered Usage, Tax IDs, Promotion Codes, Trial
- Mapear dependências do `composer.json` (`laravel/cashier`, `maartenpaauw/filament-cashier-billing-provider`)
- Documentar fluxos end-to-end: checkout empresa, checkout usuário, webhook processing, sync de planos

**Subtarefas:**

- Executar `grep -r "stripe\|cashier\|Billable\|Cashier" --include="*.php"` e categorizar os resultados
- Identificar quais features do Stripe têm equivalente na Pagaa e quais precisam de workaround
- Listar campos de banco de dados com nomenclatura Stripe-specific
- Avaliar compatibilidade do `maartenpaauw/filament-cashier-billing-provider` com provedores alternativos
- Criar tabela de mapeamento: Feature Stripe → Equivalente abstrato

**Definition of Done:**

- Documento de mapeamento completo com todos os pontos de acoplamento
- Tabela de features Stripe × equivalentes por provider identificada
- Estimativa de esforço por story validada pelo time
- Estratégia de migração (big bang vs. incremental) definida

---

## FLM - STORY - XX | Criar interface de gateway de pagamento (PaymentGatewayInterface)

**Descrição:**
Como sistema, quero ter uma interface central de gateway de pagamento que abstraia operações comuns (criar customer, criar subscription, cancelar, etc.), para que a troca de provider não exija mudanças nos casos de uso da aplicação.

**Tarefas:**

- Definir contratos PHP (interfaces) para as operações de pagamento
- Criar DTOs de entrada e saída agnósticos de provider
- Registrar bindings no container de DI do Laravel
- Criar implementação concreta `StripePaymentGateway` (adaptador do Cashier atual)

**Subtarefas:**

- Criar `PaymentGatewayInterface` com métodos: `createCustomer()`, `updateCustomer()`, `deleteCustomer()`, `createCheckoutSession()`, `createSubscription()`, `cancelSubscription()`, `resumeSubscription()`, `getSubscription()`, `createBillingPortalSession()`
- Criar `SubscriptionDTO` com campos genéricos: `externalId`, `status`, `priceExternalId`, `trialEndsAt`, `endsAt`
- Criar `CustomerDTO` com campos: `externalId`, `email`, `name`, `metadata`
- Criar `CheckoutSessionDTO` com `url`, `sessionId`, `provider`
- Implementar `StripePaymentGateway` delegando para Cashier
- Criar `PaymentServiceProvider` para registrar o gateway ativo via config
- Criar enum `PaymentGatewayDriver` com valores `stripe`, `pagaa`

**Definition of Done:**

- Interface definida e documentada com todos os contratos necessários
- `StripePaymentGateway` implementa a interface sem quebrar comportamento existente
- Binding configurável via `config('billing.gateway')` no container
- Testes unitários do adaptador Stripe com mocks da API

---

## FLM - STORY - XX | Criar interface de webhook handler agnóstica de provider

**Descrição:**
Como sistema, quero receber e processar webhooks de qualquer gateway de pagamento de forma uniforme, para que a adição de um novo provider não exija reescrever a lógica de processamento de eventos.

**Tarefas:**

- Criar contrato `WebhookHandlerInterface` com eventos genéricos
- Refatorar `SubscriptionWebhookController` para delegar ao handler correto
- Implementar `StripeWebhookHandler` encapsulando a lógica atual do Cashier

**Subtarefas:**

- Definir enum `PaymentEvent` com eventos: `SUBSCRIPTION_CREATED`, `SUBSCRIPTION_UPDATED`, `SUBSCRIPTION_CANCELLED`, `SUBSCRIPTION_PAYMENT_FAILED`, `SUBSCRIPTION_PAYMENT_SUCCEEDED`, `CUSTOMER_UPDATED`
- Criar `WebhookHandlerInterface` com `handle(string $event, array $payload): void` e `verifySignature(Request $request): bool`
- Criar `StripeWebhookHandler` extraindo lógica do `SubscriptionWebhookController` atual, incluindo o mecanismo de detecção de modelo via `metadata['model']`
- Criar rota `/webhooks/{provider}` dinâmica para suportar múltiplos providers simultaneamente
- Criar `WebhookHandlerFactory` que resolve o handler correto pelo provider da URL
- Manter `/stripe/webhook` como alias por compatibilidade

**Definition of Done:**

- Webhooks do Stripe processados via nova arquitetura sem regressão
- Nova rota `/webhooks/stripe` funcionando em paralelo com a atual
- Signature verification encapsulada no handler específico de cada provider
- Testes de feature para os eventos críticos: `subscription.created`, `subscription.deleted`, `payment_intent.succeeded`

---

## FLM - STORY - XX | Refatorar schema do banco de dados para nomenclatura agnóstica de provider

**Descrição:**
Como sistema, quero que o banco de dados não tenha referências específicas ao Stripe em nomes de colunas, para que a camada de dados seja reutilizável com qualquer gateway de pagamento.

**Tarefas:**

- Criar migrations para renomear colunas `stripe_*` em todas as tabelas afetadas
- Atualizar todos os models que referenciam as colunas antigas
- Atualizar queries, scopes e relacionamentos

**Subtarefas:**

- Tabela `users` e `companies`: renomear `stripe_id` → `payment_gateway_id`
- Tabela `billing_subscriptions`: renomear `stripe_id` → `gateway_subscription_id`, `stripe_status` → `gateway_status`, `stripe_price` → `gateway_price_id`
- Tabela `billing_subscription_items`: renomear `stripe_id` → `gateway_item_id`, `stripe_product` → `gateway_product_id`, `stripe_price` → `gateway_price_id`
- Adicionar coluna `gateway_provider` (string) em `billing_subscriptions` para identificar o provider da assinatura
- Atualizar `Subscription` model: mapear propriedades do Cashier para as novas colunas via `$casts` e atributos computados
- Atualizar middleware `RedirectCompanyIfNotSubscribed` que checa `stripe_status`
- Atualizar relacionamento `Subscription::price()` que usa `stripe_price` → `provider_price_id`
- Garantir retrocompatibilidade via aliases/accessors durante período de transição

**Definition of Done:**

- Nenhuma coluna com prefixo `stripe_` no banco de dados
- Todos os testes existentes passando após renomeação
- Migration de rollback funcionando para reversão segura
- Models atualizados e funcionando corretamente

---

## FLM - STORY - XX | Desacoplar models User e Company da trait Billable do Cashier

**Descrição:**
Como sistema, quero que os models `User` e `Company` não dependam diretamente da trait `Billable` do Laravel Cashier, para poder usar qualquer gateway de pagamento sem alterar os models de domínio.

**Tarefas:**

- Criar trait `HasPaymentGateway` própria do projeto
- Extrair métodos necessários da trait `Billable` para a nova trait
- Manter compatibilidade com a camada de subscription existente

**Subtarefas:**

- Mapear quais métodos da trait `Billable` são efetivamente usados no projeto (ex: `subscribed()`, `subscription()`, `createAsStripeCustomer()`, `stripeCustomerUrl()`)
- Criar trait `HasPaymentGateway` com apenas os métodos necessários, delegando para `PaymentGatewayInterface`
- Implementar métodos: `getGatewayCustomerId()`, `ensureGatewayCustomerExists()`, `getActiveSubscription()`, `isSubscribed()`, `hasActivePlan()`
- Criar método `hasActivePlan()` em Company consolidando lógica dos dois middlewares
- Remover `use Billable` de `User` e `Company`, substituindo por `use HasPaymentGateway`
- Ajustar `BillingServiceProvider` que sobrescreve `Cashier::useCustomerModel()`
- Manter fallback para o adaptador Stripe funcionar via `PaymentGatewayInterface`

**Definition of Done:**

- `User` e `Company` sem dependência direta do `Billable` trait
- Funcionalidades de billing funcionando via `HasPaymentGateway`
- Nenhum uso direto de `Cashier::` nos models de domínio
- Testes de integração cobrindo `isSubscribed()`, `hasActivePlan()`, `getActiveSubscription()`

---

## FLM - STORY - XX | Refatorar páginas de checkout para usar abstração de gateway

**Descrição:**
Como sistema, quero que as páginas de checkout (`TenantSubscriptionPage` e `UserSubscriptionPage`) usem a interface de gateway, para que a troca de provider não exija alteração nas páginas de pagamento.

**Tarefas:**

- Refatorar `TenantSubscriptionPage::checkout()` para usar `PaymentGatewayInterface`
- Refatorar `UserSubscriptionPage::checkout()` para usar `PaymentGatewayInterface`
- Tratar retorno de `CheckoutSessionDTO` em vez de URL direta do Cashier

**Subtarefas:**

- Extrair lógica de criação de checkout session para use case `CreateCheckoutSessionUseCase`
- Injetar `PaymentGatewayInterface` no use case via constructor DI
- Remover chamadas diretas a `$user->newSubscription()->checkout()` do Cashier
- Parametrizar metadata de `model` no DTO para o webhook handler identificar Company/User
- Tratar features opcionais (metered pricing, trial days, promotion codes, tax IDs) como flags no DTO
- Atualizar views para não assumir que a URL retornada é do Stripe
- Testar fluxo completo de redirect para checkout e retorno (success/cancel URLs)

**Definition of Done:**

- Páginas de checkout sem import ou referência a `Cashier`, `Stripe` ou `Cashier\Checkout`
- Fluxo de checkout empresa e usuário funcionando end-to-end
- URLs de sucesso e cancelamento funcionando corretamente
- Testes de feature cobrindo o fluxo de redirect para checkout

---

## FLM - STORY - XX | Refatorar BillingProviders e Middleware do Filament para usar abstração

**Descrição:**
Como sistema, quero que os `BillingProviders` do Filament e os middlewares de subscrição não dependam de detalhes do Stripe, para manter o portal de billing e controle de acesso funcionando com qualquer provider.

**Tarefas:**

- Refatorar `CompanyBillingProvider` para usar `PaymentGatewayInterface`
- Refatorar `UserBillingProvider` para usar `PaymentGatewayInterface`
- Refatorar `RedirectCompanyIfNotSubscribed` para usar campos agnósticos
- Refatorar `RedirectUserIfNotSubscribed` para usar campos agnósticos

**Subtarefas:**

- Adicionar método `createBillingPortalSession(string $customerId, string $returnUrl): string` à `PaymentGatewayInterface`
- Remover dependência de `config('cashier.portals.company')` e `config('cashier.portals.user')` nos providers
- Implementar o método no `StripePaymentGateway` usando lógica atual dos providers
- Atualizar middleware para checar `gateway_status` em vez de `stripe_status`
- Remover hardcode de `flamma-company` como empresa sem validação — mover para config ou permissão
- Atualizar `RedirectUserIfNotSubscribed` para usar `$tenant->hasActivePlan()` do novo trait
- Testar acesso com empresa com plano ativo, sem plano e com plano cancelado

**Definition of Done:**

- Nenhuma referência a `stripe` ou `cashier` nos middlewares e providers
- Redirect para billing portal funcionando
- Middleware bloqueando corretamente acesso sem subscrição ativa
- Middleware permitindo acesso com plano contratual ativo

---

## FLM - STORY - XX | Generalizar command de sincronização de planos para ser agnóstico de provider

**Descrição:**
Como operador do sistema, quero um comando de sincronização de planos que funcione com qualquer gateway configurado, para manter o banco de dados sincronizado independentemente do provider ativo.

**Tarefas:**

- Refatorar `SyncStripeResourcesCommand` para `SyncPaymentPlansCommand`
- Adicionar suporte a sincronização bidirecional: provider → BD e BD → provider
- Generalizar lógica de criação/atualização de planos

**Subtarefas:**

- Renomear signature de `billing:sync-stripe` para `billing:sync-plans`
- Adicionar opção `--provider` para especificar qual gateway sincronizar (default: configurado em `billing.gateway`)
- Criar método `getProducts(): Collection` na `PaymentGatewayInterface` retornando DTOs genéricos
- Criar método `getPricesForProduct(string $productId): Collection` na interface
- Implementar no `StripePaymentGateway` delegando para a API do Stripe via Cashier
- Remover referências diretas a `Cashier::stripe()` no command
- Manter compatibilidade de alias `billing:sync-stripe` deprecated com warning
- Atualizar `Makefile` targets (`stripe-fresh`, `stripe-listen`, `essentials-seeder`) para usar nova nomenclatura

**Definition of Done:**

- Comando `billing:sync-plans` funcionando com o adaptador Stripe
- Nenhuma referência direta a Stripe no comando refatorado
- Alias `billing:sync-stripe` exibindo deprecation warning e redirecionando
- Makefile atualizado
- Testes do command com mock da interface

---

## FLM - STORY - XX | Criar implementação inicial de novo gateway de pagamento (Pagaa/Asaas)

**Descrição:**
Como sistema, quero ter uma implementação concreta da `PaymentGatewayInterface` para o novo gateway de pagamento escolhido (Pagaa ou Asaas), para validar que a abstração criada está correta e viabilizar a migração de clientes.

**Tarefas:**

- Estudar API do gateway alvo (Pagaa ou Asaas)
- Implementar `PagaaPaymentGateway` (ou `AsaasPaymentGateway`) seguindo a interface
- Criar `PagaaWebhookHandler` seguindo `WebhookHandlerInterface`
- Testar os fluxos principais com credenciais de sandbox

**Subtarefas:**

- Mapear endpoints de API do novo gateway para cada método da `PaymentGatewayInterface`
- Identificar gaps: features sem equivalente no novo gateway (ex: metered billing, tax IDs)
- Implementar `createCustomer()`, `createCheckoutSession()`, `createSubscription()`, `cancelSubscription()`, `createBillingPortalSession()`
- Implementar `PagaaWebhookHandler` com mapeamento de eventos do provider para `PaymentEvent` enum
- Configurar verificação de assinatura dos webhooks do novo provider
- Adicionar credenciais ao `.env.example` com nomenclatura genérica: `PAYMENT_GATEWAY_KEY`, `PAYMENT_GATEWAY_SECRET`, `PAYMENT_GATEWAY_WEBHOOK_SECRET`
- Criar testes de integração com sandbox do novo provider
- Documentar gaps e workarounds necessários

**Definition of Done:**

- `PagaaPaymentGateway` implementa 100% da `PaymentGatewayInterface`
- Fluxo de checkout empresa funcionando em sandbox do novo provider
- Webhooks processados corretamente via novo handler
- Gaps documentados com alternativas implementadas
- Troca de provider via `config('billing.gateway') = 'pagaa'` funcionando sem alteração de código

---

## FLM - STORY - XX | Plano de migração e feature flag para troca de provider em produção

**Descrição:**
Como time de produto, quero uma estratégia de migração controlada entre gateways de pagamento, para garantir que clientes existentes não sejam impactados durante a transição e que possamos reverter rapidamente se necessário.

**Tarefas:**

- Definir estratégia de coexistência dos dois providers durante migração
- Criar feature flag para ativar novo gateway por empresa/usuário
- Planejar migração de customers e subscriptions existentes

**Subtarefas:**

- Avaliar estratégia: migração gradual (por empresa) vs. data cut-over vs. new signups only
- Adicionar coluna `payment_gateway` em `billing_subscriptions` para registrar provider por assinatura
- Criar lógica no `WebhookHandlerFactory` para rotear webhook ao handler correto baseado na coluna `payment_gateway`
- Criar script/command para migrar um customer existente do Stripe para o novo provider: cancelar subscription Stripe, criar no novo provider
- Definir política de rollback: o que fazer se migração falhar (manter Stripe como fallback)
- Documentar procedimento operacional de troca de provider
- Criar checklist de validação pós-migração (health check de subscriptions ativas)

**Definition of Done:**

- Coexistência de subscriptions Stripe e novo provider funcionando simultaneamente
- Processo de migração de customer testado em staging
- Playbook de rollback documentado e testado
- Checklist de validação com critérios de sucesso definidos
- Monitoramento de subscriptions ativas em ambos os providers

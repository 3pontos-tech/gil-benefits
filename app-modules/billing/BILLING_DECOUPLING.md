# Desacoplamento do Stripe — Módulo de Billing

## Motivação

O sistema foi refatorado para remover a dependência direta do Stripe do código de negócio, permitindo que múltiplos provedores de pagamento (Stripe, Barte, Contractual) sejam utilizados de forma intercambiável. O provedor padrão passou a ser a **Barte**.

---

## Arquitetura de Desacoplamento

### Contrato Central — `BillingContract`

`src/Core/Contracts/BillingContract.php`

Define a interface que qualquer provedor de pagamento deve implementar:

```php
interface BillingContract
{
    public function ensureCustomerExists(Company|User $billable): void;
    public function isSubscribed(Company|User $billable, string $planSlug): bool;
    public function hasActivePlan(Company $company): bool;
    public function createCheckout(Company|User $billable, CheckoutData $data): string;
    public function checkoutOpensInNewTab(): bool;
    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string;
    public function hasActiveSubscription(Company|User $billable): bool;
    public function cancelSubscription(Company|User $billable): void;
}
```

Nenhum código de negócio depende do Stripe diretamente — apenas desta interface.

---

### Manager / Factory — `BillingManager`

`src/Core/BillingManager.php`

Estende `Illuminate\Support\Manager`. Instancia o driver correto conforme o provedor solicitado:

```php
class BillingManager extends Manager
{
    public function getDefaultDriver(): string { return 'barte'; }

    public function createStripeDriver(): BillingContract { return new StripeAdapter; }
    public function createBarteDriver(): BillingContract  { return new BarteAdapter(new BarteClient); }

    public function getDriver(BillingProviderEnum $provider): BillingContract { ... }
}
```

---

### Enum de Provedores — `BillingProviderEnum`

`src/Core/Enums/BillingProviderEnum.php`

```php
enum BillingProviderEnum: string
{
    case Stripe       = 'stripe';
    case Barte        = 'barte';
    case Contractual  = 'contractual';  // não implementado ainda

    public static function activeCases(): array
    {
        return [self::Barte];  // Stripe pode ser reativado aqui
    }
}
```

Ativar ou desativar um provedor é questão de atualizar `activeCases()` — sem mudanças em código de negócio.

---

### Adapters — Implementações por Provedor

| Adapter | Localização | Provedor |
|---|---|---|
| `StripeAdapter` | `src/Stripe/Subscription/StripeAdapter.php` | Stripe (via Cashier) |
| `BarteAdapter` | `src/Barte/BarteAdapter.php` | Barte (via `BarteClient`) |

Ambos implementam `BillingContract`. O `StripeAdapter` delega para o Laravel Cashier; o `BarteAdapter` encapsula o `BarteClient` (cliente HTTP da API Barte).

---

## Mudanças no Banco de Dados

### Nova tabela: `billing_customers`

Migração: `2026_04_20_222250_create_billing_customers_table.php`

```
billing_customers
├── billable_type         (morph: User ou Company)
├── billable_id
├── provider              (BillingProviderEnum)
└── provider_customer_id  (ex: cus_xxx no Stripe, UUID no Barte)
```

**Por que existe:** Antes, o `stripe_id` ficava diretamente em `users` e `companies`. Com o desacoplamento, cada billable pode ter customer IDs em múltiplos provedores, e esse mapeamento fica centralizado nesta tabela.

### Tabelas agnósticas

| Tabela | Colunas relevantes |
|---|---|
| `billing_plans` | `provider` (enum), `provider_product_id` |
| `billing_plan_prices` | `provider_price_id` |
| `billing_subscriptions` | morph UUID para `User` e `Company` |

A migração `2026_03_16_120513_make_provider_product_id_nullable_on_billing_plans.php` tornou `provider_product_id` nullable para suportar planos contratuais (sem produto externo).

---

## DTOs Agnósticos

### `CheckoutData`

`src/Core/DTOs/CheckoutData.php`

Representa os dados de checkout sem detalhes de provedor. Passado para `BillingContract::createCheckout()` — cada adapter o interpreta à sua maneira.

```php
final readonly class CheckoutData
{
    public function __construct(
        public string $planSlug,
        public string $priceId,
        public bool $isMetered,
        public int $quantity,
        public ?int $trialDays,
        public bool $allowPromotionCodes,
        public bool $collectTaxIds,
        public string $successUrl,
        public string $cancelUrl,
        public array $metadata = [],
    ) {}
}
```

### `SubscriptionDTO`

`src/Core/DTOs/SubscriptionDTO.php`

Representa o estado de uma subscription de forma agnóstica. Usado para sincronização via eventos de domínio — tanto webhooks do Stripe quanto da Barte produzem este DTO antes de disparar eventos.

---

## Eventos de Domínio — Desacoplamento de Webhooks

`src/Core/Events/Subscription/`

Os webhooks dos provedores são convertidos em eventos agnósticos antes de qualquer persistência:

| Evento | Status correspondente |
|---|---|
| `SubscriptionCreated` | `pending` |
| `SubscriptionActivated` | `active` |
| `SubscriptionDefaulted` | `defaulter` |
| `SubscriptionCancelled` | `inactive` |

O listener `SyncSubscriptionOnStatusChange` escuta todos eles e chama `UpsertSubscription` para persistir — sem conhecer o provedor de origem.

### Fluxo de Webhook Barte

```
POST /webhooks/barte
    → ValidateBarteWebhookSecret (middleware)
    → BarteWebhookController::handle()
    → HandleBarteWebhookJob (fila, 3 tentativas)
    → HandleBarteWebhook::handle()
    → payload → BarteWebhookDto → SubscriptionDTO
    → event(SubscriptionActivated | SubscriptionCreated | ...)
    → SyncSubscriptionOnStatusChange
    → UpsertSubscription::handle()
```

### Fluxo de Webhook Stripe

O `SubscriptionWebhookController` estende o controller do Cashier e sobrescreve `handleWebhook()` apenas para configurar `Cashier::useCustomerModel()` dinamicamente (morph), delegando o restante para o Cashier que dispara os eventos nativamente.

---

## Command de Migração de Dados

`SyncBillingCustomersCommand` — assinatura: `billing:sync-customers`

`src/Core/Commands/SyncBillingCustomersCommand.php`

Migra os `stripe_id` existentes em `users` e `companies` para a nova tabela `billing_customers`. Necessário para clientes que já possuíam subscription ativa no Stripe antes do desacoplamento.

---

## Middleware de Acesso

`src/Stripe/Subscription/User/RedirectUserIfNotSubscribed.php`  
`src/Stripe/Subscription/Company/RedirectCompanyIfNotSubscribed.php`

Verificam subscription iterando sobre `BillingProviderEnum::activeCases()`, suportando múltiplos provedores simultâneos:

```php
collect(BillingProviderEnum::activeCases())
    ->contains(fn ($provider) =>
        $this->billingManager->getDriver($provider)->hasActiveSubscription($tenant)
    );
```

---

## BillingProviders — Integração com Filament

`UserBillingProvider` e `CompanyBillingProvider` implementam `Filament\Billing\Providers\Contracts\BillingProvider`.

Ao acessar o portal de billing, o provider detecta o provedor ativo via `BillingCustomer::getActiveProvider()`, obtém o driver correspondente no `BillingManager` e redireciona para a URL correta — que pode ser o Stripe Customer Portal ou a página interna `BillingManagePage` (Barte).

---

## Service Provider

`src/BillingServiceProvider.php`

Registra:

| Binding | Implementação |
|---|---|
| `PlanRepository::class` | `EloquentPlanRepository::class` |
| `WebhookController::class` | `SubscriptionWebhookController::class` (override do Cashier) |

Configura `Cashier::useCustomerModel()` dinamicamente por painel Filament:
- Painel `company` → `Company::class`
- Painel `app` → `User::class`

---

## Commands de Sincronização

| Command | Assinatura | Responsabilidade |
|---|---|---|
| `SyncStripeResourcesCommand` | `billing:sync-stripe` | Importa produtos e preços do Stripe para `billing_plans` / `billing_plan_prices` |
| `SyncBartePlans` | `barte:play` | Importa planos e preços da Barte |
| `SyncBillingCustomersCommand` | `billing:sync-customers` | Migra `stripe_id` legados para `billing_customers` |

---

## Resumo dos Padrões Aplicados

| Padrão | Onde |
|---|---|
| **Strategy + Interface** | `BillingContract` implementada por `StripeAdapter` e `BarteAdapter` |
| **Manager / Factory** | `BillingManager` instancia drivers por enum |
| **Adapter** | Adapters encapsulam Cashier e `BarteClient` respectivamente |
| **DTO agnóstico** | `CheckoutData`, `SubscriptionDTO` abstraem detalhes do provedor |
| **Event-Driven** | Webhooks → eventos de domínio → listener único de sincronização |
| **Repository** | `PlanRepository` abstrai acesso a planos |
| **Polymorphic Morph** | `billing_customers` e `billing_subscriptions` suportam `User` e `Company` |
| **Configuration-driven** | `BillingProviderEnum::activeCases()` controla os provedores ativos |

---

## Mapa de Arquivos Críticos

| Arquivo | Responsabilidade |
|---|---|
| `src/Core/Contracts/BillingContract.php` | Contrato agnóstico de pagamento |
| `src/Core/BillingManager.php` | Factory de drivers por provedor |
| `src/Stripe/Subscription/StripeAdapter.php` | Implementação Stripe via Cashier |
| `src/Barte/BarteAdapter.php` | Implementação Barte via `BarteClient` |
| `src/Barte/BarteClient.php` | Cliente HTTP da API Barte |
| `src/Core/Models/BillingCustomer.php` | Mapeamento billable → customer ID por provedor |
| `src/Core/Models/Subscriptions/Subscription.php` | Model de subscription (polymorphic) |
| `src/Core/Events/Subscription/` | Eventos de domínio agnósticos |
| `src/Core/Listeners/SyncSubscriptionOnStatusChange.php` | Sincronização via eventos |
| `src/Barte/BarteWebhookController.php` | Entrada de webhooks Barte |
| `src/Stripe/Subscription/SubscriptionWebhookController.php` | Override de webhook Stripe/Cashier |
| `src/Core/Repositories/` | Abstração de acesso a planos |
| `src/Core/Entities/` | Entidades de domínio agnósticas |
| `src/BillingServiceProvider.php` | Registro de bindings e configuração |

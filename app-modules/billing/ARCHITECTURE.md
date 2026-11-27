# Desacoplamento do Stripe — Módulo de Billing

## Motivação

O sistema foi refatorado para remover a dependência direta do Stripe do código de negócio, permitindo que múltiplos provedores de pagamento sejam utilizados de forma intercambiável. O gateway que vende hoje é o **Stripe**.

Cada gateway mora no seu próprio módulo — `integration-virtu`, `integration-barte` — e registra o driver no `BillingManager`. O billing define o contrato e o núcleo agnóstico; ele não implementa gateway.

O Laravel Cashier não faz parte do domínio: nenhum model herda dele e `User`/`Company` não usam o trait `Billable`. Ver o ADR `2026-11-27-gateway-only-through-integration-modules`.

---

## Arquitetura de Desacoplamento

### Contrato Central — `BillingContract`

`src/Core/Contracts/BillingContract.php`

Define a interface que qualquer provedor de pagamento deve implementar:

```php
interface BillingContract
{
    public function ensureCustomerExists(Company|User $billable): void;
    public function createCheckout(Company|User $billable, CheckoutData $data): string;
    public function checkoutOpensInNewTab(): bool;
    public function billingPortal(Company|User $billable, string $returnUrl): BillingPortal;
}
```

O contrato só tem o que fala com o gateway. Se alguém tem acesso não se pergunta ao gateway: lê-se das nossas linhas em `billing_subscriptions` (ver "Regra de acesso").

---

### Manager / Factory — `BillingManager`

`src/Core/BillingManager.php`

Estende `Illuminate\Support\Manager`. Instancia o driver correto conforme o provedor solicitado:

```php
class BillingManager extends Manager
{
    public function getDefaultDriver(): string { return BillingProviderEnum::checkoutCases()[0]->value; }

    public function getDriver(?BillingProviderEnum $provider = null): BillingContract { ... }
}
```

Não existe um `create<Gateway>Driver()` por gateway: cada módulo de integração registra o seu no `boot()` do próprio service provider.

```php
$this->app->booted(function (): void {
    $this->app->make(BillingManager::class)->extend(
        BillingProviderEnum::Barte->value,
        fn (): BillingContract => new BarteAdapter($this->app->make(BarteClient::class)),
    );
});
```

O `BillingServiceProvider` registra o `BillingManager` como **singleton**, e isso não é detalhe: `Manager::extend()` guarda o creator na instância, então um binding não compartilhado perderia o driver no `resolve()` seguinte. O `booted()` existe pelo mesmo motivo — o singleton precisa já estar registrado.

---

### Enum de Provedores — `BillingProviderEnum`

`src/Core/Enums/BillingProviderEnum.php`

O enum expõe dois métodos estáticos que controlam o comportamento do sistema. **Todo o código de negócio consulta esses métodos — nunca referencia um case de provedor diretamente.**

```php
enum BillingProviderEnum: string
{
    case Stripe       = 'stripe';
    case Barte        = 'barte';
    case Contractual  = 'contractual';

    /**
     * Providers whose existing subscriptions are considered valid for access.
     * Includes legacy providers while their plans have not yet expired.
     */
    public static function activeCases(): array
    {
        return [self::Stripe, self::Barte];
    }

    /**
     * Providers available for NEW subscriptions.
     * Change this when migrating to a new gateway — no other changes needed.
     */
    public static function checkoutCases(): array
    {
        return [self::Barte];
    }
}
```

#### Separação de responsabilidades

| Método | Usado em | Comportamento |
|---|---|---|
| `activeCases()` | Middlewares de acesso, `getPlansFor()`, `all()` | Inclui **todos** os providers com assinaturas válidas, inclusive legados |
| `checkoutCases()` | Páginas de nova assinatura, `getCheckoutPlansFor()`, `ensureCustomerExists` nos middlewares | Apenas o(s) provider(s) disponíveis para **novas** assinaturas |

#### Migração entre gateways

Hoje:
- `activeCases()` contém `[Stripe]`: só os planos dele entram na verificação de acesso.
- `checkoutCases()` contém `[Stripe]`, o gateway que vende novas assinaturas.

Quando os planos de um gateway legado expirarem, basta removê-lo de `activeCases()`. Para trocar o gateway de venda, basta atualizar `checkoutCases()` — nenhum outro arquivo precisa ser alterado. `TenantSubscriptionPage` lê `checkoutCases()[0]`, então a posição importa tanto quanto a presença; o docblock do enum explica por que hoje há um só elemento.

---

### Adapters — Implementações por Provedor

| Adapter | Módulo | Provedor |
|---|---|---|
| `VirtuAdapter` | `integration-virtu` | Virtu/Pagaa (via `VirtuClient`) — não vende mais |
| `BarteAdapter` | `integration-barte` | Barte (via `BarteClient`) — não vende mais |
| `StripeAdapter` | `integration-stripe` | Stripe (via `StripeApi`, sobre o `stripe/stripe-php`) — vende hoje |

Todos implementam `BillingContract` e encapsulam o cliente do respectivo gateway. O `StripeAdapter` implementa também todas as capacidades opcionais (cancelamento, compra de crédito, desconto e desconto com duração).

Um adapter que não sabe fazer tudo declara a capacidade em interface separada em vez de lançar exceção: `SupportsSubscriptionCancellation` (billing) e `SupportsCreditPurchase` (módulo `credits`).

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

Representa o estado de uma subscription de forma agnóstica. Usado para sincronização via eventos de domínio — o webhook de cada gateway produz este DTO antes de disparar eventos.

---

## Eventos de Domínio — Desacoplamento de Webhooks

`src/Core/Events/Subscription/`

Os webhooks dos provedores são convertidos em eventos agnósticos antes de qualquer persistência:

| Evento | Status correspondente |
|---|---|
| `SubscriptionCreated` | `pending` |
| `SubscriptionActivated` | `active` |
| `SubscriptionDefaulted` | `defaulter` (mantém o acesso) |
| `SubscriptionCancelled` | `inactive` |

O listener `SyncSubscriptionOnStatusChange` escuta todos eles e chama `UpsertSubscription` para persistir — sem conhecer o provedor de origem. Quando o gateway só conhece o id definitivo depois do pagamento, o DTO traz `checkoutReference` e o upsert move a linha criada no checkout para o id novo, em vez de inserir outra.

### Fluxo de Webhook Stripe

Tudo abaixo mora em `integration-stripe`. O evento é só aviso: o handler lê a assinatura ou a sessão de checkout de novo na API antes de traduzir.

```
POST /webhooks/stripe
    → ValidateStripeWebhookSignature (middleware, Stripe-Signature)
    → StripeWebhookController::handle() (guarda o payload bruto)
    → HandleStripeWebhookJob (fila, 3 tentativas, um processamento por event id)
    → HandleStripeWebhook::handle()
    → StripeApi::retrieveSubscription() | retrieveCheckoutSession()
    → event(SubscriptionActivated | SubscriptionDefaulted | SubscriptionCancelled | ...)
    → SyncSubscriptionOnStatusChange
    → UpsertSubscription::handle()
```

### Fluxo de Webhook Barte

Tudo abaixo mora em `integration-barte`, não aqui.

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

---

## Regra de acesso

`src/Core/Models/Subscriptions/Subscription.php` e `src/Core/Models/Collections/SubscriptionCollection.php`

Quem tem acesso é decidido uma vez só, no núcleo, a partir das nossas linhas — nunca do gateway:

- `Subscription::valid()` responde por uma linha: `active` ou `defaulter` sem período encerrado, ou trial ainda correndo, ou período pago ainda correndo (`ends_at` no futuro). Os status de `STATUSES_WITHOUT_ACCESS` (`pending`, `inactive`) nunca dão acesso. O `defaulter` mantém o acesso enquanto o gateway tenta cobrar (ADR `2026-11-27-delinquency-keeps-access-until-cancellation`).
- `SubscriptionCollection::grantsAccessTo($slug)` e `grantsAnyAccess()` respondem pelo dono, sobre `$billable->subscriptions`.
- `SubscriptionBuilder::grantingAccess()` filtra só pelo status, em SQL. É mais frouxo que `valid()` porque não lê as datas.

---

## Middleware de Acesso

`src/Core/Http/Middleware/RedirectUserIfNotSubscribed.php`  
`src/Core/Http/Middleware/RedirectCompanyIfNotSubscribed.php`

Apesar do nome do diretório antigo, nunca foram do Stripe: iteram sobre o enum de providers e não citam gateway nenhum.

Verificam subscription separando duas responsabilidades:

```php
// 1. Cria customer apenas no gateway de novas assinaturas (checkoutCases)
collect(BillingProviderEnum::checkoutCases())
    ->each(fn ($provider) => $this->billingManager->getDriver($provider)->ensureCustomerExists($tenant));

// 2. Verifica acesso nos planos de todos os providers ativos, lendo as nossas linhas
$hasValidSubscription = array_any(
    $plans,
    fn (PlanEntity $plan): bool => $tenant->subscriptions->grantsAccessTo($plan->slug),
);
```

O acesso não depende de qual gateway vendeu a assinatura; novos customers só são criados no provider que vende hoje.

---

## BillingProviders — Integração com Filament

`src/Core/Filament/UserBillingProvider.php`  
`src/Core/Filament/CompanyBillingProvider.php`

Implementam `Filament\Billing\Providers\Contracts\BillingProvider`.

Ao acessar o portal de billing, o provider detecta o provedor ativo via `BillingCustomer::getActiveProvider()`, obtém o driver correspondente no `BillingManager` e redireciona para a URL correta — que pode ser o Stripe Customer Portal ou a página interna `BillingManagePage`.

---

## Service Provider

`src/BillingServiceProvider.php`

Registra:

| Binding | Implementação |
|---|---|
| `PlanRepository::class` | `EloquentPlanRepository::class` |

E o `BillingManager` como singleton, pelo motivo descrito na seção do Manager.

Não carrega rotas nem registra comando de gateway: cada módulo de integração cuida dos seus — `CreateStripePlanCommand` (`stripe:plan:create`) em `integration-stripe`, `SyncBartePlans` (`barte:play`) em `integration-barte`, `CreateVirtuPlanCommand` em `integration-virtu`.

---

## Resumo dos Padrões Aplicados

| Padrão | Onde |
|---|---|
| **Strategy + Interface** | `BillingContract` implementada por um adapter por gateway |
| **Manager / Factory** | `BillingManager` instancia drivers por enum |
| **Adapter** | Cada adapter encapsula o cliente HTTP do seu gateway |
| **DTO agnóstico** | `CheckoutData`, `SubscriptionDTO` abstraem detalhes do provedor |
| **Event-Driven** | Webhooks → eventos de domínio → listener único de sincronização |
| **Repository** | `PlanRepository` abstrai acesso a planos |
| **Polymorphic Morph** | `billing_customers` e `billing_subscriptions` suportam `User` e `Company` |
| **Configuration-driven** | `BillingProviderEnum::activeCases()` controla acesso de assinantes existentes; `checkoutCases()` controla o gateway de novas assinaturas |

---

## Mapa de Arquivos Críticos

| Arquivo | Responsabilidade |
|---|---|
| `src/Core/Contracts/BillingContract.php` | Contrato agnóstico de pagamento |
| `src/Core/BillingManager.php` | Factory de drivers por provedor |
| `src/Core/Models/BillingCustomer.php` | Mapeamento billable → customer ID por provedor |
| `src/Core/Models/Subscriptions/Subscription.php` | Model de subscription (polymorphic) e regra de acesso por linha |
| `src/Core/Models/Collections/SubscriptionCollection.php` | Regra de acesso por dono |
| `src/Core/Events/Subscription/` | Eventos de domínio agnósticos |
| `src/Core/Listeners/SyncSubscriptionOnStatusChange.php` | Sincronização via eventos |
| `src/Core/Http/Middleware/` | Middlewares de acesso por assinatura |
| `src/Core/Filament/` | BillingProviders do Filament |
| `src/Core/Repositories/` | Abstração de acesso a planos |
| `src/Core/Entities/` | Entidades de domínio agnósticas |
| `src/BillingServiceProvider.php` | Registro de bindings e configuração |

---

## Fronteiras do Módulo

| Módulo | Responsabilidade |
|---|---|
| `billing` | Contrato, manager, planos, preços, assinaturas, customers, middlewares de acesso |
| `integration-stripe`, `integration-virtu`, `integration-barte` | Um gateway cada: adapter, cliente, webhook, comandos |
| `credits` | Crédito de consultoria: emissão, alocação, consumo, compra avulsa |

A regra: **o billing não conhece gateway nem crédito**. Os módulos de integração dependem do billing e do credits — é o lugar certo para essa dependência, porque só eles precisam traduzir um webhook de gateway em pedido de crédito. Um teste de arquitetura garante que nenhum módulo de domínio use `Laravel\Cashier` nem o SDK `Stripe`.

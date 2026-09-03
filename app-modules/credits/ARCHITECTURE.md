# Módulo de Créditos

Crédito é a moeda de consultoria da plataforma: uma linha em `user_credits` vale uma consultoria. O módulo cobre o ciclo inteiro — como o crédito nasce, para quem vai, como é consumido e como volta.

Foi extraído do `billing`, onde ocupava 41 classes dentro de `src/Core` misturado com plano, preço e assinatura.

---

## Regra de dependência

**`credits` não conhece gateway.** A única coisa que ele importa do billing é o `BillingProviderEnum`, em três arquivos: `CreditOrder`, `CreditOrderDTO` e `StartCreditOrder` — todos porque um pedido precisa registrar por onde foi cobrado.

Quem conhece gateway é o módulo de integração, que depende dos dois e implementa `SupportsCreditPurchase`.

---

## Tabelas

| Tabela | Papel |
|---|---|
| `user_credits` | Uma linha = um crédito. `owner_id` (quem pagou), `holder_id` (quem pode usar), `status`, `appointment_id` (único), `grant_id`, `credit_order_id` |
| `credit_grants` | Concessão manual de admin: quem concedeu, para quem, quantos, e a justificativa obrigatória |
| `credit_orders` | Compra avulsa: provider, `checkout_id`, billable (morph), quantidade, valor, status, `paid_at`. Único por `(provider, checkout_id)` |

A separação entre `owner_id` e `holder_id` é o que permite a empresa comprar e distribuir: o dono continua sendo quem pagou, o portador é quem agenda.

---

## Como um crédito nasce

Três origens, um mecanismo. Todas terminam em `IssueCredits`, que cria N linhas `available` numa transação e não sabe o motivo — cada origem preenche os campos que lhe dizem respeito.

| Origem | Caminho |
|---|---|
| Compra avulsa | `StartCreditOrder` → checkout do gateway → webhook → `SettleCreditOrder` |
| Concessão de admin | `GrantExtraCredit` (registra o `CreditGrant` e emite junto) |
| Assinatura (Stripe legado) | `SubscriptionWebhookController` → `IssueCredits` |

---

## Fluxo de compra

Hoje existem **duas formas**, e essa divergência é dívida conhecida:

| Gateway | Ordem | Correlação no webhook |
|---|---|---|
| Barte | cria a `CreditOrder` primeiro, manda o `credit_order_id` no metadata | pelo metadata |
| Virtu | cria o link primeiro, dispara `CreditOrderPlaced`, o listener persiste | por `(provider, checkout_id)` |

`CreditOrderPlaced` e `PersistCreditOrder` existem só para acomodar a segunda forma. Unificar isso — a order sempre primeiro, o `checkout_id` gravado a partir do retorno — elimina os dois e faz qualquer gateway ser correlacionável pelas duas chaves.

Confirmado o pagamento:

```
OrderCreditPurchased
    → DispatchCreditPurchaseJob
    → ProcessCreditPurchaseJob (fila, único por pedido)
    → SettleCreditOrder  (marca paid_at, emite os créditos)
    → CreditsDelivered
    → NotifyOwnerOfCreditsDeliveredListener
```

---

## Ciclo de vida

`available` → `in_use` → `used`, com volta quando a consultoria não acontece.

O acoplamento com `appointments` é por evento, nos dois sentidos:

| Evento | Quem dispara | Efeito |
|---|---|---|
| `CreditConsumed` | `BookAppointmentAction` | `ConsumeCredit` reserva o crédito mais antigo disponível |
| `AppointmentCreditUsed` | transições de agendamento | marca `used` |
| `AppointmentCreditReturned` | cancelamento | devolve para `available` |

---

## Operações de empresa

Disparadas pelo `CompanyCreditPage` (panel-company), todas em fila para não segurar o request:

| Ação | Job |
|---|---|
| Distribuir igualmente entre os colaboradores | `DistributeCreditsEquallyJob` → `AllocateCreditToEmployee` |
| Retomar créditos não usados | `RevokeCreditsFromEmployeesJob` |
| Transferir entre colaboradores | `TransferCreditBetweenEmployees` |

O `CompanyCreditsObserver` observa a `Company`: troca de dono migra os créditos da conta da empresa para o novo dono, também em fila.

No admin, `GrantExtraCredit` concede e `RevokeCreditGrant` revoga: apaga (soft delete) só os créditos da concessão que ainda estão `available`. Os já consumidos ficam — agendamento marcado não se desfaz — e o `CreditGrant` permanece como registro permanente da doação.

---

## Configuração

`config/credits.php` — `price_in_cents` define quanto vale um crédito avulso (`CREDIT_PRICE_IN_CENTS`, padrão 15000). É o valor gravado em `credit_orders.amount_cents`.

---

## Mapa de Arquivos

| Caminho | Responsabilidade |
|---|---|
| `src/Actions/IssueCredits.php` | Único lugar que cria linhas de crédito |
| `src/Actions/ConsumeCredit.php` | Reserva o crédito mais antigo do portador |
| `src/Actions/SettleCreditOrder.php` | Liquida o pedido pago e emite |
| `src/Contracts/SupportsCreditPurchase.php` | Capacidade que o gateway declara para vender crédito |
| `src/Models/UserCredit.php` | O crédito, com os escopos de disponibilidade |
| `src/Models/CreditOrder.php` | Pedido de compra avulsa |
| `src/Models/CreditGrant.php` | Concessão de admin, com justificativa |
| `src/Listeners/` | Descobertos automaticamente pelo `internachi/modular` |

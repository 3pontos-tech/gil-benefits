# Módulo de Vouchers

Voucher é o canal pelo qual uma empresa parceira distribui consultoria sem que a pessoa
passe por pagamento. O admin gera um lote de códigos vinculado ao programa da parceira, e
cada pessoa **já vinculada àquela empresa** informa o seu código dentro do app para receber
um crédito de consultoria.

O módulo não introduz um novo tipo de benefício: o crédito emitido é o mesmo `user_credits`
de sempre, e o voucher é apenas uma **quarta origem de emissão**, ao lado de compra avulsa,
concessão de admin e assinatura legada.

---

## Regra de dependência

**`vouchers` fica acima de `billing` e de `credits`, e nenhum dos dois depende dele.**

O resgate precisa das duas metades: o programa da parceira é um `CompanyPlan` (billing) e o
que ele entrega é um `UserCredit` (credits). Colocar o voucher dentro de `credits` furaria a
regra daquele módulo, que só importa `BillingProviderEnum` do billing; colocar dentro de
`billing` faria o billing conhecer crédito, acoplamento removido no #274.

A única marca que o voucher deixa nos módulos de baixo é a coluna
`user_credits.voucher_redemption_id`, no mesmo formato de `grant_id` e `credit_order_id`.
`CreditDTO` e `IssueCredits` carregam o identificador como string: credits sabe o nome da
origem, nunca a classe.

---

## Tabelas

| Tabela | Papel |
|---|---|
| `voucher_batches` | Lote gerado pelo admin: `company_id`, `company_plan_id`, `quantity`, `expires_at` (prazo de resgate), `created_by`, `notes` |
| `voucher_codes` | Um código: `code` único, `max_redemptions` (1 hoje), `redemptions_count` |
| `voucher_redemptions` | Trilha nominal do resgate, única por `(voucher_code_id, user_id)` |

`company_plan_id` no lote é o que dá ao resgate o alvo do lock e à expiração o escopo do
programa, sem inferir nada por empresa.

---

## O programa da parceira

Um `CompanyPlan` com `kind = credits_only`:

- `ResolveQuotaAllowance` devolve `QuotaAllowance::none()` para ele, então ninguém ganha cota mensal;
- `Company::hasActivePlan()` continua verdadeiro, então `RedirectUserIfNotSubscribed` libera o
  acesso no primeiro passo e `RedirectIfAnamneseNotCompleted` exige a anamnese normalmente —
  sem uma linha de middleware alterada;
- `seats` é a capacidade de resgatadores, e `ends_at` é o prazo do programa.

Quando a parceira passa a pagar de verdade, o caminho é **linha nova** em `company_plans`:
encerrar a de voucher e abrir a contratual. Virar o `kind` da mesma linha manteria a âncora
do ciclo de cota na data do início da cortesia.

---

## Resgate

`RedeemVoucher` roda tudo numa transação, com `lockForUpdate()` na linha de `company_plans`:

```
lock no programa
    → programa vigente e credits_only?
    → a pessoa tem vínculo ativo com a empresa do lote?
    → lote dentro do prazo de resgate?
    → código com uso disponível?
    → pessoa já resgatou algum código deste lote?
    → cria voucher_redemption, incrementa o código, emite 1 crédito
```

O código só vale para quem **já é da empresa do lote**: um código da Incorporadora A na mão
de alguém da Incorporadora B é recusado. Isso também é o que mantém o resgate simples — ele
não cria vínculo nem consome assento, porque a pessoa já ocupava um. `seats` continua sendo
enforçado onde as pessoas entram na empresa, não aqui.

O lock é na linha do programa, e não na do código, porque as três invariantes de capacidade
vivem em linhas diferentes: `max_redemptions` no código, `quantity` no lote e `seats` no
programa. Travar só o código deixaria dois códigos distintos da mesma parceira passarem
juntos pela checagem de vagas.

O crédito nasce com `owner_id = holder_id = ` resgatador e `company_id` da parceira. Auto-detido
de propósito: no pool da parceira ele ficaria sujeito a `RevokeCreditsFromEmployees`, e a
parceira poderia retomar uma consultoria já dada.

`ensureRedeemable()` roda as mesmas checagens sem o lock, para quem precisa validar um código
sem resgatá-lo.

A tela é `RedeemVoucherPage` no painel do app, visível apenas para quem está numa empresa cujo
contrato vigente é `credits_only` — quem tem cota mensal não tem o que resgatar.

---

## Encerramento

Crédito de voucher não tem validade própria — o prazo é o `ends_at` do programa.
`ExpireProgramCreditsJob` roda diariamente, e para cada programa `credits_only` já encerrado
`ExpireProgramCredits` marca como `expired` os créditos daquele programa que ainda estão
`available`. Crédito `in_use` fica intocado: quem agendou dentro do prazo tem a consultoria
honrada, na mesma forma que `RevokeCreditGrant` já pratica.

---

## Mapa de Arquivos

| Caminho | Responsabilidade |
|---|---|
| `src/Actions/GenerateVoucherBatch.php` | Cria o lote e N códigos numa transação, com retry na colisão |
| `src/Actions/RedeemVoucher.php` | Resgate autoritativo sob lock, e a checagem sem lock para o formulário |
| `src/Actions/ExpireProgramCredits.php` | `available` → `expired` nos créditos de um programa encerrado |
| `src/Support/VoucherCodeGenerator.php` | Código aleatório em alfabeto sem caracteres ambíguos |
| `src/Models/VoucherBatch.php` | O lote, e a consulta dos créditos que saíram dele |
| `database/seeders/VoucherProgramPlanSeeder.php` | Item de catálogo que dá nome ao contrato de parceria |

A tela do resgatador vive fora do módulo, em
`app-modules/panel-app/src/Filament/Pages/RedeemVoucherPage.php`, junto das outras páginas do
painel do app.

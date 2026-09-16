# Módulo de Vouchers

Voucher é o canal pelo qual uma empresa parceira distribui consultoria sem que a pessoa
passe por pagamento. O admin gera um lote de códigos vinculado ao programa da parceira, a
parceira entrega as carteirinhas, e **quem recebe se cadastra sozinho no tenant padrão**
informando o código no próprio formulário de cadastro.

Quem resgata **não entra na empresa parceira**. Fica na Flamma como qualquer avulso; o que a
parceira ganha é a visibilidade de quem resgatou e o que fez com a consultoria.

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

As duas marcas que o voucher deixa nos módulos de baixo são colunas em `user_credits`:
`voucher_redemption_id`, no mesmo formato de `grant_id` e `credit_order_id`, e `expires_at`.
`CreditDTO` e `IssueCredits` carregam o identificador como string: credits sabe o nome da
origem, nunca a classe.

Os middlewares que liberam o painel também não importam nada daqui — perguntam
`User::hasActiveVoucherCredit()`, que lê as colunas do próprio ledger.

---

## Tabelas

| Tabela | Papel |
|---|---|
| `voucher_batches` | Lote gerado pelo admin: `company_id`, `company_plan_id`, `quantity`, `expires_at` (prazo de resgate), `created_by`, `notes` |
| `voucher_codes` | Um código: `code` único, `max_redemptions` (1 hoje), `redemptions_count` |
| `voucher_redemptions` | Trilha nominal do resgate, única por `(voucher_code_id, user_id)` |

`company_plan_id` no lote é o que dá ao resgate o alvo do lock e ao crédito a data de
validade, sem inferir nada por empresa.

---

## O programa da parceira

Um `CompanyPlan` com `kind = credits_only`:

- `ResolveQuotaAllowance` devolve `QuotaAllowance::none()` para ele, então ninguém ganha cota mensal;
- `Company::hasActivePlan()` continua verdadeiro, e é isso que dá ao dono da parceira o acesso
  ao `/company` para acompanhar a campanha;
- `ends_at` é o prazo do programa, do qual sai a validade de cada crédito resgatado.

`seats` não é checado no resgate: ninguém é anexado à parceira, então não há assento a ocupar.

Quando a parceira passa a pagar de verdade, o caminho é **linha nova** em `company_plans`:
encerrar a de voucher e abrir a contratual. Virar o `kind` da mesma linha manteria a âncora
do ciclo de cota na data do início da cortesia.

---

## Resgate

`RedeemVoucher` roda tudo numa transação, com `lockForUpdate()` na linha de `company_plans`:

```
lock no programa
    → programa vigente e credits_only?
    → lote dentro do prazo de resgate?
    → código com uso disponível?
    → pessoa já resgatou algum código deste lote?
    → pessoa já tem um voucher de pé?
    → cria voucher_redemption, incrementa o código, emite 1 crédito
```

O código não pergunta de onde a pessoa vem: quem tem a carteirinha resgata. Só duas regras
recortam a pessoa — um resgate por lote, e **um voucher ativo por vez**. A segunda olha o
ledger: barra quem tem crédito de voucher `available` dentro do prazo ou `in_use`, e libera
assim que ele vira `used` ou `expired`.

O lock é na linha do programa, e não na do código, porque as invariantes de capacidade vivem
em linhas diferentes: `max_redemptions` no código e `quantity` no lote.

O crédito nasce com `owner_id = holder_id = ` resgatador e `company_id` do **tenant padrão** —
é onde a pessoa está e onde vai agendar. Auto-detido de propósito: num pool de empresa ele
ficaria sujeito a `RevokeCreditsFromEmployees`, e alguém poderia retomar uma consultoria já
dada. O vínculo com a campanha sobrevive inteiro em `voucher_redemption_id`.

`ensureRedeemable()` roda as checagens sem o lock, e aceita `null` no lugar da pessoa: no
formulário de cadastro ela ainda não existe, então só as regras do código valem ali.

A tela é o próprio formulário de cadastro (`UserRegistration`), com um campo opcional que o
QR da carteirinha já preenche via `?voucher=`.

---

## Validade

O prazo vive **no crédito**, gravado no resgate: quem resgatou no último dia da campanha leva
o mesmo prazo de quem resgatou no primeiro, e encerrar o contrato mais cedo não derruba o que
já foi entregue. A data é a menor entre o `ends_at` do programa e o `expires_at` do lote; sem
nenhuma das duas, o crédito não vence.

`ExpireVoucherCreditsJob` roda diariamente e `ExpireVoucherCredits` marca como `expired` todo
crédito de voucher `available` cuja data passou. Crédito `in_use` fica intocado: quem agendou
dentro do prazo tem a consultoria honrada, na mesma forma que `RevokeCreditGrant` já pratica.

Entre o vencimento e a passagem do job existe uma janela de horas. Ela não vaza: as consultas
que decidem acesso e consumo (`hasAvailableCredit`, `hasActiveVoucherCredit`, `ConsumeCredit`)
aplicam o escopo `notExpired`, que compara a data na hora da pergunta. O job é quem acerta o
status para os relatórios.

Se a pessoa não usou o crédito dentro do prazo, perdeu. Não há devolução nem prorrogação.

---

## Acesso de quem entrou por voucher

Duas portas do painel do app olhavam só para assinatura e contrato de empresa. Ambas ganharam
uma terceira condição, aditiva:

| Middleware | O que mudou |
|---|---|
| `RedirectUserIfNotSubscribed` (billing) | No tenant padrão, além da assinatura avulsa, um voucher vigente basta. Vencido, a pessoa cai na vitrine como qualquer avulso. |
| `RedirectIfAnamneseNotCompleted` (panel-app) | Sem isso, quem entrou por campanha atravessaria a anamnese sem preencher. |

---

## O que a parceira enxerga

`VoucherRedemptionsPage`, no painel da empresa: quem resgatou, de qual campanha, quando, e a
situação da consultoria.

O relatório não sai das páginas de Métricas de propósito — elas filtram
`appointments.company_id`, que num resgate aponta para o tenant padrão, e agrupam por
departamento, que resgatador nenhum tem. O status do crédito responde tudo: `available` é
quem resgatou e não usou, `in_use` é quem agendou, `used` é consultoria realizada e `expired`
é quem deixou o prazo passar.

---

## A carteirinha impressa

A arte vem desenhada numa prancha de 1080 x 1350 px e o template guarda esses números
literais, reescalados em tempo de render pela largura física de `vouchers.card.width_mm`
(120mm por padrão). Conferir o template contra a arte de origem não exige refazer conta.

Frente e verso saem em páginas consecutivas, para impressão frente e verso direta — um
lote de N códigos gera 2N páginas.

Duas restrições do dompdf moldaram o template. Flexbox e gradiente radial não existem,
então o empilhamento virou posicionamento absoluto sobre uma página de tamanho fixo e os
brilhos de fundo saíram. E largura de coluna em `<colgroup>` ou em `<td>` é ignorada
dentro de um bloco absoluto — a tabela acaba dividida em partes iguais —, por isso não há
tabela nenhuma aqui, só blocos posicionados.

O prazo impresso é o de RESGATE, não o da consultoria: o crédito só ganha validade quando
alguém informa o código. Vale a data que fechar primeiro, entre o fim do contrato e o
prazo do lote.

---

## Mapa de Arquivos

| Caminho | Responsabilidade |
|---|---|
| `src/Actions/GenerateVoucherBatch.php` | Cria o lote e N códigos numa transação, com retry na colisão |
| `src/Actions/RedeemVoucher.php` | Resgate autoritativo sob lock, e a checagem sem lock para o formulário |
| `src/Actions/ExpireVoucherCredits.php` | `available` → `expired` nos créditos de voucher fora do prazo |
| `src/Actions/GenerateVoucherQrCodes.php` | Um QR por código, atrás do adapter `QrCodeGenerator` |
| `src/Actions/BuildVoucherBatchPdf.php` | Carteirinha frente e verso, uma por página, reescalada da arte |
| `src/Support/VoucherCodeGenerator.php` | Código aleatório em alfabeto sem caracteres ambíguos |
| `src/Support/VoucherRedemptionUrl.php` | O endereço que o QR carrega: o cadastro, com o código na query |
| `src/Models/VoucherBatch.php` | O lote, e a consulta dos créditos que saíram dele |
| `database/seeders/VoucherProgramPlanSeeder.php` | Item de catálogo que dá nome ao contrato de parceria |

As telas vivem fora do módulo: o resgate em
`app-modules/panel-app/src/Filament/Pages/UserRegistration.php` e o acompanhamento da parceira
em `app-modules/panel-company/src/Filament/Pages/VoucherRedemptionsPage.php`.

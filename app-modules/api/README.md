# Módulo API

API HTTP versionada do produto (`TresPontosTech\Api`). Hoje atende o **app do colaborador**
([gil-benefits-mobile](https://github.com/3pontos-tech/gil-benefits-mobile)) em `/api/v1`. A organização é por versão e
por público (`V1/Employee`; `V1/Company` quando a integração da empresa migrar para cá, #293).

Épico e decisões: `docs/epicos-api.md`. Plano de execução: `docs/plano-execucao-api.md`.

## Estrutura

```
app-modules/api
├── config/api.php                      validade do token, meta do trimestre, URLs temporárias, limites
├── lang/pt_BR                          mensagens e nomes de campos da API
├── routes/api-routes.php               todas as rotas, prefixo api/v1, nomes api.v1.*
├── src/Actions/Employee/<recurso>      acesso, consultas e montagem de respostas próprias do app
├── src/DTOs/Employee/<recurso>         dados que os resources formatam (perfil, jornada, créditos, acesso)
├── src/Enums/Employee/<recurso>
├── src/Exceptions/Employee/<recurso>
├── src/Http/Controllers/V1/Employee/<recurso>
├── src/Http/Middleware                 ForceJsonAndLocale, EnsureAppAccess
├── src/Http/Requests/V1/Employee/<recurso>
├── src/Http/Resources/V1/Employee/<recurso>
├── src/Providers/ApiServiceProvider    limites de requisição e o contrato de erros (exceção de domínio → 422)
├── src/Support                         ApiDates (formato das datas)
└── tests/Feature                       V1/Employee/<recurso>, Actions/Employee/<recurso> e Contract/* por resource
```

Só a camada HTTP (controllers, requests e resources), que é o contrato, carrega a versão. Os recursos são os mesmos
dos controllers: `Appointments`, `Auth`, `Credits`, `Materials`, `Me`, `Notifications`, `Tickets`. O nível `Employee`
é o público; a API da empresa entra ao lado (`Company`) quando sair do módulo `tenant` (#293).

Regras de negócio ficam nos módulos de domínio (`appointments`, `credits`, `support`, `user`, `consultants`) e são as
mesmas que o painel usa. O controller valida, chama a action e formata a resposta; não decide regra.

## Autenticação e acesso

- `POST /api/v1/auth/login` com `email`, `password` e `device_name` devolve
  `{ data: { token, user } }`. O token é um personal access token do Sanctum com a ability `employee` e validade de
  `API_TOKEN_TTL_DAYS` dias (padrão 90, sem renovação). `POST /api/v1/auth/logout` revoga o token atual.
- As demais rotas pedem `Authorization: Bearer <token>`. Não há sessão web (`sanctum.guard = []`).
- Entra quem tem o **benefício pago**, pela mesma régua do painel (`ResolveBenefitAccess`, no `billing`): plano
  contratual da empresa, assinatura própria ou voucher. Consultores e contas sem vínculo ativo ficam de fora. O
  `EnsureAppAccess` confere a cada requisição; sem acesso, o login responde 422 em `email` e as demais rotas, 403,
  com o motivo ("O plano da sua empresa está inativo…" ou "Para usar o app, ative um plano pelo site.").
- A empresa da requisição é a primeira com vínculo ativo em que a pessoa tem o benefício, a empregadora antes da
  padrão (`ResolveAppAccessAction`, a regra inteira de acesso num lugar só). Ela vira o tenant da requisição, e cota, crédito, agendamento, perfil e
  chamado leem essa empresa, como a aberta na tela do painel.
- Limites (`config/api.php`): 120 requisições por minuto por usuário; no login, 5 tentativas por minuto por e-mail e
  IP e 20 por IP, e a conferência de senha leva sempre o mesmo tempo mínimo (`Timebox`), como no painel.
- Trocar a senha por qualquer caminho (app, "esqueci a senha", perfil do painel, admin) desconecta os aparelhos do app
  (`RevokeAppTokensAction`, chamada em cada um desses caminhos); na troca pelo app, o aparelho que pediu continua
  conectado. O rehash do hash no login do painel não desconecta.
- Tokens vencidos saem no `sanctum:prune-expired` diário (`routes/console.php`).

## Recuperação de senha

Pelo app, com um código de 6 dígitos por e-mail (#323), em três rotas públicas:

1. `POST auth/password/forgot` com `email` responde sempre `202 { message }`. Só quem tem acesso ao app (a mesma
   `ResolveAppAccessAction` do login) recebe o código; consultor e quem não tem o benefício seguem pelo link do site.
2. `POST auth/password/verify` com `email` e `code` devolve `{ data: { reset_token, expires_at } }`. Toda falha dá o
   mesmo 422 em `code`. O código aceita espaço e traço, como vem colado do e-mail.
3. `POST auth/password/reset` com `email`, `reset_token`, `password` e `password_confirmation` responde `204`. Token
   vencido, usado ou de outra conta dá 422 em `reset_token`. O reset não emite token: o app volta ao login.

- Nenhuma resposta diz quem tem conta: o corpo é o mesmo, as três rodam num `Timebox` (`password_reset.timebox_ms`,
  400 ms), e os limites contam pelo e-mail normalizado, exista a conta ou não.
- O código vale 15 min, e o 5º erro o descarta. Os erros também contam por conta e não zeram com um código novo:
  com 20, a recuperação por código fica bloqueada até um reset por qualquer caminho. O reenvio espera 60 s.
- Código e token ficam só como HMAC na `password_reset_codes`, uma linha por pessoa, travada (`lockForUpdate`) em
  cada passo. O e-mail com o código vai criptografado na fila. Linhas sem código, token ou erros saem no
  `model:prune` diário.
- Limites: `forgot` com 3 pedidos a cada 15 min por e-mail e IP e 20 por hora por IP; `verify` e `reset` dividem 10
  por minuto por IP.
- Um reset, pelo app ou pelo site, dispara o `PasswordReset`. Os listeners revogam os tokens do app, encerram as
  sessões web, apagam o código pendente e enviam o e-mail de senha alterada.

## Convenções da resposta

| Tema | Regra |
| --- | --- |
| Formato | Sempre JSON, mesmo sem `Accept: application/json`; mensagens em `pt_BR`. |
| Envelope | `{ data: ... }`. Listas paginadas trazem `links` e `meta` (`current_page`, `last_page`, `per_page`, `total`). |
| Datas | Data e hora em ISO 8601 com offset (`2026-10-09T10:00:00-03:00`); data pura em `Y-m-d` (`ApiDates`). |
| Erros | `422` com `{ message, errors: { campo: [...] } }`, inclusive para regras de domínio (horário indisponível, chamado encerrado). Os demais em pt_BR e sem detalhe interno: `401` sem token; `403` sem acesso ao app (com o motivo), sem a ability ou ao remover material que não é seu; `404` "Não encontrado." para recurso inexistente ou de outra pessoa; `405`; `429` com `Retry-After`. |
| Status | `201` só ao criar (encontro, avaliação, material, chamado); atualizações respondem `200` com o recurso, inclusive remover a foto e cancelar o encontro (o app mostra o resultado); logout, troca e redefinição de senha e remoção de material respondem `204`; o pedido de código de recuperação responde `202`. |

## Rotas

| Área | Rotas |
| --- | --- |
| Autenticação | `POST auth/login`, `POST auth/logout`, `POST auth/password/forgot`, `POST auth/password/verify`, `POST auth/password/reset` |
| Perfil | `GET/PATCH me`, `PUT me/password`, `POST/DELETE me/avatar`, `GET me/journey`, `GET/PUT me/anamnese` |
| Agenda | `GET appointments/slots`, `GET/POST appointments`, `GET/PATCH appointments/{id}`, `POST appointments/{id}/cancel`, `POST appointments/{id}/feedback` |
| Créditos | `GET credits` |
| Materiais | `GET/POST materials`, `DELETE materials/{id}`, `PATCH materials/{id}/favorite`, `PATCH materials/{id}/viewed` |
| Notificações | `GET notifications`, `PATCH notifications/{id}/read` |
| Chamados | `GET/POST tickets`, `PATCH tickets/{id}` |

A agenda oferece horários de hoje + 2 dias até hoje + 60 dias (`BookableSlots`, a mesma janela do painel). Um mês
fora dela responde `{}` sem consultar a agenda dos consultores, e agendar ou reagendar fora dela dá 422 em
`appointment_at`.

Cancelar (`POST appointments/{id}/cancel`) não apaga o encontro: ele continua, como `cancelled` ou `cancelled_late`.
O corpo aceita `expected_impact` (`returns_credit` | `loses_credit`), o `cancel_impact` que o alerta do app mostrou. Se
a pessoa confirmou com o crédito voltando e as 4 h acabaram até o pedido chegar (o app espera os 6 s do "Desfazer"),
responde 422 em `expected_impact` e não cancela; ter aceitado perder o crédito nunca impede o cancelamento. Sem o
campo, vale o relógio do momento do pedido, como no painel.

Lista completa, com middlewares: `php artisan route:list --path=api/v1 -v`. O contrato que o app consome está em
`gil-benefits-mobile/docs/api-contract.md`.

## Rodando com o app

1. Suba o ambiente e o backend:

   ```bash
   make env-up              # Postgres, Redis, Mailpit e MinIO
   make essentials-seeder   # bucket do MinIO e banco com as contas de teste
   composer run dev         # php artisan serve (porta 8000), fila, logs e Vite
   ```

   A conta `employee@5pontos.com` (senha `password`) entra no app.

2. No app, copie `.env.example` para `.env` e aponte para o backend:

   | Onde o app roda | `EXPO_PUBLIC_API_URL` | Observação |
   | --- | --- | --- |
   | Navegador (`npx expo start --web`) | `http://127.0.0.1:8000` | O CORS padrão do Laravel já libera `api/*`. |
   | Emulador Android (WSL2) | `http://127.0.0.1:8000` | Rode `adb reverse tcp:8000 tcp:8000` (e `tcp:9000`, para avatares e materiais) depois de abrir o emulador. |
   | Celular na mesma rede | `http://<IP da máquina>:8000` | Suba com `php artisan serve --host=0.0.0.0 --port=8000`. |

3. Avatares e materiais usam URLs temporárias do disco `r2`, que em desenvolvimento aponta para o MinIO do `make env-up`
   (variáveis `CLOUDFLARE_R2_*`). A URL precisa ser alcançável pelo aparelho: no celular, use o IP da máquina no
   endpoint.

## Testes

```bash
php artisan test --compact app-modules/api
```

- `tests/Feature/V1/*`: comportamento de cada rota (regras, erros, acesso).
- `tests/Feature/Contract/*ResourceTest.php`: chaves e formato das datas de cada resource. Mudou uma chave, mudou o
  contrato do app: atualize o teste e o `docs/api-contract.md` do app, e veja a seção de versão abaixo.

Helpers em `tests/Pest.php`: `actingAsApiEmployee()`, `actingAsApiSubscriber()`, `defaultCompanyUser()`.

## Versão do contrato

Mudança incompatível em `/api/v1` (remover ou renomear campo, mudar tipo, endurecer validação) é **MAJOR**
(`RELEASING.md`) e precisa de coordenação com a versão do app publicada. Campo ou rota nova é **MINOR**.

## Adicionando um endpoint

1. Regra no módulo de domínio, como action com `handle()`; o painel deve poder usar a mesma.
2. Request em `Http/Requests/V1/Employee`, com `attributes()` traduzidos em `lang/pt_BR/attributes.php`.
3. Controller fino, sem `try/catch`: a exceção de domínio ganha uma linha em
   `ApiServiceProvider::registerDomainRefusals()` (vira 422 no campo que o app mostra) e entra em `DOMAIN_REFUSALS`,
   para não ser relatada como erro.
4. Resource com datas via `ApiDates`.
5. Rota em `routes/api-routes.php` dentro do grupo autenticado, com `whereUuid` nos ids.
6. Testes em `V1/Employee/<recurso>` e no `Contract/*`.

## Dívidas conhecidas

- A API da empresa (`/api/v1/company/{tenant}/users`) ainda mora no módulo `tenant` (#293).

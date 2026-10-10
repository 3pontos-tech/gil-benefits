# Módulo API

API HTTP versionada do produto (`TresPontosTech\Api`). Hoje atende o **app do colaborador**
([gil-benefits-mobile](https://github.com/3pontos-tech/gil-benefits-mobile)) em `/api/v1`. A organização é por versão e
por público (`V1/Employee`; `V1/Company` quando a integração da empresa migrar para cá, #293).

Épico e decisões: `docs/epicos-api.md`. Plano de execução: `docs/plano-execucao-api.md`.

## Estrutura

```
app-modules/api
├── config/api.php              validade do token, meta do trimestre, URLs temporárias, limites
├── lang/pt_BR                  mensagens e nomes de campos da API
├── routes/api-routes.php       todas as rotas, prefixo api/v1, nomes api.v1.*
├── src/Actions/V1/Employee     consultas e montagem de respostas próprias do app
├── src/DTOs                    dados que os resources formatam (perfil, jornada, créditos)
├── src/Http/Controllers/V1/Employee
├── src/Http/Middleware         ForceJsonAndLocale, EnsureAppAccess
├── src/Http/Requests/V1/Employee
├── src/Http/Resources/V1/Employee
├── src/Support                 ApiDates (formato das datas), EmployeeMaterials
└── tests/Feature               V1/* por área e Contract/* por resource
```

Regras de negócio ficam nos módulos de domínio (`appointments`, `credits`, `support`, `user`, `consultants`) e são as
mesmas que o painel usa. O controller valida, chama a action e formata a resposta; não decide regra.

## Autenticação e acesso

- `POST /api/v1/auth/login` com `email`, `password` e `device_name` devolve
  `{ data: { token, user } }`. O token é um personal access token do Sanctum com a ability `employee` e validade de
  `API_TOKEN_TTL_DAYS` dias (padrão 90, sem renovação). `POST /api/v1/auth/logout` revoga o token atual.
- As demais rotas pedem `Authorization: Bearer <token>`. Não há sessão web (`sanctum.guard = []`).
- `EnsureAppAccess` libera quem tem vínculo ativo com uma empresa (colaborador ou assinante individual na empresa
  padrão) e barra consultores e admins com 403.
- Limites: 120 requisições por minuto por usuário e 5 tentativas de login por minuto por e-mail e IP
  (`config/api.php`).
- Tokens vencidos saem no `sanctum:prune-expired` diário (`routes/console.php`).

## Convenções da resposta

| Tema | Regra |
| --- | --- |
| Formato | Sempre JSON, mesmo sem `Accept: application/json`; mensagens em `pt_BR`. |
| Envelope | `{ data: ... }`. Listas paginadas trazem `links` e `meta` (`current_page`, `last_page`, `per_page`, `total`). |
| Datas | Data e hora em ISO 8601 com offset (`2026-10-09T10:00:00-03:00`); data pura em `Y-m-d` (`ApiDates`). |
| Erros | `422` com `{ message, errors: { campo: [...] } }`, inclusive para regras de domínio (horário indisponível, chamado encerrado); `401` sem token; `403` sem acesso ao app, sem a ability ou ao remover material que não é seu; `404` para recurso de outra pessoa; `429` no limite. |
| Status | `201` só ao criar (encontro, avaliação, material, chamado); atualizações respondem `200` com o recurso; logout, troca de senha e remoções respondem `204`. |

## Rotas

| Área | Rotas |
| --- | --- |
| Autenticação | `POST auth/login`, `POST auth/logout` |
| Perfil | `GET/PATCH me`, `PUT me/password`, `POST/DELETE me/avatar`, `GET me/journey`, `GET/PUT me/anamnese` |
| Agenda | `GET appointments/slots`, `GET/POST appointments`, `GET/PATCH/DELETE appointments/{id}`, `POST appointments/{id}/feedback` |
| Créditos | `GET credits` |
| Materiais | `GET/POST materials`, `DELETE materials/{id}`, `PATCH materials/{id}/favorite`, `PATCH materials/{id}/viewed` |
| Notificações | `GET notifications`, `PATCH notifications/{id}/read` |
| Chamados | `GET/POST tickets`, `PATCH tickets/{id}` |

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
3. Controller fino; exceção de domínio vira `ValidationException` no campo que o app mostra.
4. Resource com datas via `ApiDates`.
5. Rota em `routes/api-routes.php` dentro do grupo autenticado, com `whereUuid` nos ids.
6. Testes em `V1/*` e no `Contract/*`.

## Dívidas conhecidas

- A jornada é montada pelo `BuildUserJourneyAction` do `panel-app`, então este módulo depende dele (#292).
- A API da empresa (`/api/v1/company/{tenant}/users`) ainda mora no módulo `tenant` (#293).

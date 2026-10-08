# Épicos e Stories — API do aplicativo (colaboradores)

> Desenho de 2026-10-02. O contrato que o app já implementa está em `gil-benefits-mobile/docs/api-contract.md`
> (endpoints, formatos, 16 pontos abertos) e as fixtures em `gil-benefits-mobile/src/mocks/fixtures/*.ts` são os
> payloads de referência. Este documento diz **como** o `gil-benefits` entrega esse contrato com o que já existe.
> Revisão de 2026-10-05: módulo único `api` organizado por versão e público (decisão A1), no lugar de um módulo por cliente.
> Revisão de 2026-10-06 (entrevista de decisões): Sanctum aprovado; token de 90 dias sem renovação; público = quem usa o
> `panel-app` (B2B, assinante individual e voucher), lido como "vínculo ativo com alguma empresa"; senha atual obrigatória para trocar senha e e-mail; cancelamento com
> paridade ao painel (`cancel_impact`); "sem data" e thread de chamados ficam na S12; "Pedir mais créditos" sai da v1
> (issue #285); URLs do colaborador sem prefixo; entrega num branch `feat/api-v1` com um PR único em `develop`.

## FLM - ÉPICO - XX | API REST para o aplicativo do colaborador

**Descrição do Épico:**
Expor, como API JSON versionada (`/api/v1`), tudo o que o aplicativo React Native do colaborador precisa: entrar e
sair, perfil e jornada, agenda (listar, agendar, reagendar, cancelar, horários livres), créditos, materiais,
notificações, anamnese e chamados. A API reutiliza os models, enums e actions dos módulos existentes (`appointments`,
`credits`, `consultants`, `support`, `user`, `billing`) e não duplica regra de negócio: onde a regra hoje vive só em
uma página Filament, ela é extraída para uma action do módulo de domínio e passa a ser usada pelo painel e pela API.

**Objetivo de Negócio:**
Trocar as fixtures do app por dados reais sem mudar o app (basta apontar `EXPO_PUBLIC_API_URL`), com a mesma régua de
negócio do painel `panel-app` (cota, créditos, janelas de cancelamento e reagendamento, sigilo da anamnese).

**Escopo técnico identificado:**
- Não existe camada de API para colaborador: só a API de empresa (`api/v1/company/{tenant}/users`, header
  `X-Flamma-Access-Key`), sem grupo de middleware `api`, sem `routes/api.php`, sem Sanctum/Passport, sem JsonResource.
- Guard único `web` (sessão). `last_login_at` é gravado por listener do evento `Login`.
- Regras de agendamento espalhadas: `BookAppointmentAction::handle` (retorna void), `AppointmentWizard::isBookableSlot`
  (lead de 2 dias + slot válido, dentro de um schema Filament), `SchedulesAppointments` e `ReschedulesAppointments`
  (concerns do painel), transições de status em `Actions/Transitions/*` (cancelamento com `cancelled_late` dentro de 4 h).
- Cota mensal via `ResolveQuotaAllowance::for($user, $companyId)` + `User::monthly_appointments_left`; sem tenant
  Filament a resolução cai em `employerCompanyId()`.
- Materiais: `documents` + `document_shares`, sem estado por usuário (visto/favorito) e sem vínculo com encontro.
- Notificações: só Filament database notifications, payload sem ids para deep link.
- Jornada: `BuildUserJourneyAction` + DTO `UserJourney`, sem trimestre, sem histórico mensal persistido.
- Anamnese: `user_anamneses` com as quatro colunas de texto `NOT NULL`.
- Chamados: `CreateSupportTicketAction::execute(DTO)` e `TransitionSupportTicketStatusAction::execute`, sem thread.

---

## Decisões de arquitetura

| # | Decisão | Motivo |
| --- | --- | --- |
| A1 | **Módulo único `app-modules/api`** (`TresPontosTech\Api`): toda API HTTP do produto vive nele, organizada por **versão e público**, não por cliente: `src/Http/Controllers/V1/Employee/*` (o app do colaborador, e qualquer outro cliente que fale pelo colaborador), `src/Http/Controllers/V1/Company/*` (a integração da empresa, hoje em `tenant`), e `Requests/`, `Resources/`, `Middleware/` compartilhados. Rotas em `routes/api-routes.php` com um grupo por público; `config/api.php`; `lang/pt_BR`; `tests/Feature/V1/{Employee,Company}`. A API de tenant **continua em `tenant` até ser tocada**; quando for, o controller e o middleware migram para `api/.../Company` sem mudar rota nem header (S11 registra a dívida). | Um público novo é uma pasta e um grupo de rotas, não um módulo; erros, resources, limites e testes de contrato ficam num lugar só. |
| A2 | **Laravel Sanctum** (personal access tokens, um por dispositivo, ability `employee`, expiração 90 dias, `sanctum:prune-expired` diário). `User` ganha `HasApiTokens`. **Dependência nova: precisa de aprovação** (regra do AGENTS.md). | É o padrão do ecossistema para app nativo; Passport seria excesso. Sem stateful domains (sem SPA). |
| A3 | Rotas `Route::prefix('api/v1')->name('api.v1.employee.')` com middleware `[ForceJsonAndLocale, ThrottleRequests:api-employee, SubstituteBindings]`; as protegidas somam `auth:sanctum`, `ability:employee` e `EnsureAppAccess` (vínculo ativo com alguma empresa). | Módulos não têm grupo `api`; o `SubstituteBindings` precisa ser explícito. `ForceJsonAndLocale` fixa `Accept: application/json` e `app()->setLocale('pt_BR')` para as mensagens de validação chegarem em português (o app as exibe como vêm). |
| A4 | **Eloquent API Resources** para todas as respostas (`MeResource`, `JourneyResource`, `AppointmentResource`, `CreditsResource`, `MaterialResource`, `NotificationResource`, `AnamneseResource`, `SupportTicketResource`), sempre embrulhados em `data`; listas com `->paginate()` (o app lê `data` e `meta`, ignora `links`). | Orientação do CLAUDE.md para APIs; a API de tenant não usa porque só devolve 201/204. |
| A5 | Erros no formato padrão do Laravel para JSON: `401 {"message":"Unauthenticated."}`, `403 {"message"}`, `404 {"message"}`, `422 {"message","errors":{campo:[...]}}`, `429 {"message"}`. Nada de handler customizado. | É exatamente o que o app já trata (`lib/api.ts`). |
| A6 | Datas: `appointment_at`, `*_at` em ISO 8601 com offset no fuso da aplicação (`->toIso8601String()` após `setTimezone(config('app.timezone'))`); datas puras em `Y-m-d`; ids UUID; chaves `snake_case`. | Contrato do app. |
| A7 | Autorização sempre pelo usuário do token: toda query parte de `$request->user()`; `AppointmentPolicy`, `DocumentPolicy`, `SupportTicketPolicy` com `view/update/delete` = dono. O `company_id` de contexto é `employerCompanyId()` (nenhuma rota recebe tenant). | Sem tenant Filament, as actions de cota e crédito já caem em `employerCompanyId()`. |
| A8 | Regras que hoje estão em páginas Filament passam para actions do módulo de domínio e o painel passa a usá-las: `ScheduleAppointmentForUserAction` (lead + slot + cota/crédito + `BookAppointmentAction`), `RescheduleAppointmentAction`, `CancelAppointmentForUserAction`, `Appointment::canBeCancelled()`. `BookAppointmentAction::handle` passa a **retornar** o `Appointment`. | Uma régua só para painel e app; a API não pode depender de um schema Filament. |
| A9 | URLs de arquivo (avatar, logo, materiais) são temporárias (60 min, sem `Content-Disposition: attachment` para o app abrir inline). | Já é o mecanismo do avatar e do logo; o app guarda `me` por 10 min e materiais por 1 min. |
| A10 | Rate limit: `api-employee` 120 req/min por usuário (IP quando anônimo); `api-login` 5/min por e-mail+IP. | Alinha com o `rateLimit(5)` do login Filament. |
| A11 | Versionamento de release (RELEASING.md): Sanctum traz uma migration aditiva e nenhuma env obrigatória → cada story entra como **MINOR** (`feat:`). As migrations deste épico são todas aditivas ou `nullable` (sem perda de dado). | Nenhum passo manual de deploy. |
| A12 | Testes: Pest feature por endpoint em `app-modules/api/tests/Feature/V1/Employee`, com `Sanctum::actingAs($user, ['employee'])` sobre os helpers `actingAsEmployee()` / `actingAsSubscribedEmployee()` de `tests/Pest.php`, e um teste de contrato por resource (`assertJsonStructure` com as chaves do app). | Regra do repositório: toda mudança testada programaticamente. |

### Pontos abertos do contrato → decisão proposta

| Ponto | Decisão proposta neste desenho |
| --- | --- |
| 1 Auth | **Sanctum aprovado** (A2), token de 90 dias sem renovação. Login por `email` + `password` + `device_name`; falha de credencial responde `422` em `email` com `__('auth.failed')` (convenção Laravel; o app mostra sob o campo). Pode entrar quem tem vínculo ativo com alguma empresa, inclusive a padrão (B2B, assinatura individual, voucher); admin e dono sem vínculo, e consultores, recebem `422` em `email` "Esta conta não tem acesso ao aplicativo.". |
| 2 "Sem data" | **Confirmado fora da v1** (`appointment_at` continua obrigatório). Story separada (S12) cria a ação de painel "Pedir nova data" que zera a data e notifica; o app já tolera `null`. |
| 3 Materiais | Duas tabelas novas: `document_user_states` (visto/favorito por usuário) e `appointment_document` (materiais do encontro). A segunda nasce vazia até o painel do consultor ganhar o vínculo. |
| 4 Biometria | **Não existe endpoint.** O app guarda o token no SecureStore e a biometria só o desbloqueia (mudança no app, regra 4.2). `POST /v1/auth/biometric` sai do contrato. |
| 5 Trimestre | `quarter { completed, goal, starts_at, ends_at }` calculado em `BuildUserJourneyAction` sobre encontros concluídos no trimestre civil corrente; `goal` em `config('api.quarter_goal', 4)`. |
| 6 `can_cancel` | **Paridade com o painel.** `Appointment::canBeCancelled()` = status ∈ {pending, active} **e** `appointment_at` futuro; o resource expõe também `cancel_impact: 'returns_credit' \| 'loses_credit' \| null` (`isLateCancellation()`). Dentro das 4 h o cancelamento vira `cancelled_late` e o crédito é perdido, como no painel; o app troca o texto do alerta conforme `cancel_impact` (mudança no app). |
| 7 Derivados | `member_since` = `company_employees.created_at` do vínculo com a empresa empregadora; `anamnese_completed` = cinco respostas preenchidas; `duration_minutes` = `config('google-calendar.default_event_duration', 60)`; `owner_type` = `owner_id === company_id ? 'company' : 'user'`; `monthly_quota` de `ResolveQuotaAllowance` + `QuotaCycle`. |
| 8 Notificações | Os três envios ao colaborador passam `->viewData(['appointment_id' => …])`; `NotificationResource` sobe `viewData.appointment_id`/`document_id` para o topo de `data`. A notificação de ata publicada (`PublishAppointmentRecordAction`, TODO) entra com `appointment_id`. |
| 9 Token expirado | Sanctum responde `401` para token revogado/expirado; nada a fazer. |
| 10 Assuntos | Mantêm-se os seis casos de `AppointmentCategoryEnum`; sem caso novo. O app segue com a redação do Figma. |
| 11 Consultor | **Confirmado no código**: o painel (`SchedulesAppointments::scheduleReviewAction`) só chama `BookAppointmentAction`; `AssignConsultantAction` é acionada pela equipe no painel admin. A API cria `pending` sem consultor e não atribui. |
| 12 Jornada extra | `health_history` (6 meses) e `focus` calculados on the fly pela mesma fórmula do score, com cache de 1 h por usuário; sem tabela nova na v1. |
| 13 Pedir créditos | **Fora da v1.** O botão sai do app (flag `CREDITS_REQUEST_ENABLED`, código preservado) e o fluxo é discutido na issue [#285](https://github.com/3pontos-tech/gil-benefits/issues/285): chamado ao comercial, aviso ao gestor da empresa ou checkout. |
| 14 Senha | **Confirmado:** a API exige `current_password` em `PUT /v1/me/password` e na troca de e-mail (mesma regra do `EditProfile`); a tela 30 do app ganha o campo "Senha atual". Após trocar, os outros tokens do usuário são revogados. |
| 15 Anamnese | As quatro colunas de texto viram `nullable`; `PUT /v1/me/anamnese` faz merge parcial; o wizard do painel continua exigindo tudo na validação do formulário. |
| 16 Chamados | **Confirmado:** sem thread na v1; o app mostra `status · data`. `support_ticket_messages` fica na S12. |

---

## FLM - STORY - XX | S1 · Fundação: módulo `api`, Sanctum, rotas, erros e testes base

**Descrição:**
Como time de engenharia, quero a infraestrutura da API (módulo, autenticação por token, grupo de rotas, limites, locale
e esqueleto de testes), para que as stories de endpoint só adicionem controllers, requests e resources.

**Tarefas:**

- Obter aprovação e instalar `laravel/sanctum` (`composer require laravel/sanctum` +
  `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`). Não usar `install:api`: ele cria
  `routes/api.php` e o grupo global; as rotas ficam no módulo e `bootstrap/app.php` segue sem `api:`.
- Criar o módulo: `php artisan make:module api --no-interaction`; provider `ApiServiceProvider` registra
  config, lang, rate limiters e policies.
- `User` recebe `HasApiTokens`; migration `personal_access_tokens` (do Sanctum).
- Estrutura por público desde o início: `Controllers/V1/Employee`, grupo de rotas `employee` (Sanctum) e um grupo `company`
  vazio apontando para a futura migração da API de tenant (A1).
- Middleware `EnsureAppAccess` sobre `User::canUseApp()`: **vínculo ativo em `company_employees` com qualquer empresa,
  inclusive a padrão** (`companies()->wherePivot('active', true)->exists()`); senão `403 {"message":"Conta sem acesso ao
  aplicativo."}`. Cobre as três portas de entrada (plano da empresa, assinatura individual, voucher) sem exigir role
  `employee` nem empresa cliente. Diferente do `panel-app`, admin e dono de empresa **sem vínculo** não entram: o app não
  tem o que mostrar a eles; para testar, recebem um vínculo de colaborador numa empresa de teste.
- Middleware `ForceJsonAndLocale`: `Accept: application/json` + `setLocale('pt_BR')`.
- Rate limiters `api-employee` e `api-login` (A10).
- `config/api.php`: `token_ttl_days` (90), `quarter_goal` (4), `media_url_ttl_minutes` (60), `slots_cache_seconds` (60).
- Agendar `sanctum:prune-expired --hours=24` em `routes/console.php`.
- Base de testes: trait/helper `actingAsApiEmployee()` em `tests/Pest.php` (reusa `actingAsEmployee()` e chama
  `Sanctum::actingAs($user, ['employee'])`).

**Subtarefas:**

- `php artisan route:list --path=api/v1` mostra o grupo com os middlewares certos.
- Teste: rota protegida sem token → 401 `{"message":"Unauthenticated."}`; com token sem ability → 403; admin sem vínculo → 403; assinante individual (só empresa padrão) → 200.
- Teste: validação responde em português (`422` com mensagem de `lang/pt_BR/validation.php`).
- Pint, Rector e Larastan verdes no módulo (`phpstan.neon` do módulo como nos demais).

**Definition of Done:**

- `GET /api/v1/ping` (ou a primeira rota real) responde 200 autenticado e 401 anônimo em teste Pest.
- Nenhuma rota do painel mudou de comportamento (sessão `web` intacta).
- Dependência Sanctum aprovada e registrada no `composer.lock`.

---

## FLM - STORY - XX | S2 · Autenticação: login, logout e tokens por dispositivo

**Descrição:**
Como colaborador, quero entrar com e-mail e senha e continuar conectado neste aparelho, para usar o app sem repetir o login.

**Endpoints:**

| Método | Rota | Request | Resposta |
| --- | --- | --- | --- |
| POST | `/v1/auth/login` | `{ email, password, device_name }` | `200 { data: { token, user: Me } }` · `422 email` (credencial inválida ou conta sem acesso) · `429` |
| POST | `/v1/auth/logout` | — | `204` (revoga só o token atual) |

**Tarefas:**

- `LoginRequest`: `email` required/email/max:255, `password` required, `device_name` required/string/max:64.
- `LoginController::store`: busca `User` por e-mail, `Hash::check`; falha → `ValidationException::withMessages(['email' => __('auth.failed')])`.
  Em seguida `User::canUseApp()` (senão `422` em `email` "Esta conta não tem acesso ao aplicativo.") e
  `event(new Illuminate\Auth\Events\Login('sanctum', $user, false))` para o listener `RecordLastLogin` gravar `last_login_at`.
- Token: `$user->createToken($deviceName, ['employee'], now()->addDays(config('api.token_ttl_days')))`; limitar a
  um token por `device_name` (apaga o anterior com o mesmo nome).
- `LogoutController`: `$request->user()->currentAccessToken()->delete()`.
- Throttle `api-login` no `POST /auth/login`.

**Subtarefas:**

- Teste: login válido devolve token e `data.user` no formato de `MeResource`; `last_login_at` atualizado.
- Teste: senha errada → 422 em `email`; usuário sem vínculo com empresa alguma → 422 em `email`; assinante individual entra; 6 tentativas → 429.
- Teste: logout invalida o token (próxima chamada 401) sem afetar o token de outro dispositivo.
- Teste: token expirado → 401.

**Definition of Done:**

- O app entra com um usuário real, `EXPO_PUBLIC_API_URL` apontando para o ambiente local, e o "Sair" revoga o token.
- `POST /v1/auth/biometric` **não** existe; a remoção do contrato fica registrada no `api-contract.md` do app.

---

## FLM - STORY - XX | S3 · Perfil: `GET/PATCH /me`, avatar e senha

**Descrição:**
Como colaborador, quero ver e editar meus dados (nome, e-mail, telefone, foto, senha), para manter o cadastro em dia pelo app.

**Endpoints:**

| Método | Rota | Request | Resposta |
| --- | --- | --- | --- |
| GET | `/v1/me` | — | `{ data: Me }` |
| PATCH | `/v1/me` | um de `{ name }`, `{ email, current_password }`, `{ phone_number }` (E.164 ou `null`) | `{ data: Me }` · `422` |
| PUT | `/v1/me/password` | `{ current_password, password, password_confirmation }` | `204` · `422` |
| POST | `/v1/me/avatar` | multipart `avatar` (jpeg/png/webp, ≤ 5120 KB) | `{ data: Me }` |
| DELETE | `/v1/me/avatar` | — | `{ data: Me }` |

**Tarefas:**

- `MeResource`: `id, name, email, avatar_url` (`getFilamentAvatarUrl()`, 60 min), `phone_number` (`detail`),
  `company { id, name, slug, logo_url }` (empresa empregadora via `employerCompanyId()`), `department { id, category, name }`
  (`company_employees.department_id`), `member_since` (pivot `created_at`, `Y-m-d`), `anamnese_completed`, `life_moment`,
  `last_login_at`, `created_at`. Eager load: `detail`, `anamnese`, `media`, empresa com `media`.
- `UpdateMeRequest`: `sometimes` por campo; `email` `unique:users,email` ignorando o próprio + `current_password:sanctum`
  obrigatório quando `email` vier; `phone_number` `nullable|regex:/^\+[1-9]\d{9,14}$/`. Pelo menos um campo.
- `UpdateMeAction` (módulo `user`): atualiza `users` e `detail()->updateOrCreate(['user_id'], ['phone_number'])`.
- `UpdatePasswordRequest`: `current_password:sanctum`, `password` `confirmed` + `Password::defaults()`; após salvar,
  `$user->tokens()->where('id', '!=', currentAccessToken()->id)->delete()`.
- Avatar: `addMediaFromRequest('avatar')->toMediaCollection('user_avatar')` (singleFile substitui) e
  `clearMediaCollection('user_avatar')`.

**Subtarefas:**

- Teste de contrato de `MeResource` (todas as chaves acima, `avatar_url` nulo sem mídia).
- Teste: `PATCH` de telefone fora do E.164 → 422 `phone_number`; e-mail sem `current_password` → 422.
- Teste: troca de senha revoga os outros tokens e mantém o atual.
- Teste: upload inválido (pdf) → 422; `DELETE` sem avatar → 200 idempotente.

**Definition of Done:**

- Telas 24, 29, 30 e 42 do app funcionam contra o ambiente local.
- Decisão 14 refletida no app (campo "Senha atual" na tela 30).

---

## FLM - STORY - XX | S4 · Jornada: `GET /me/journey` com trimestre, histórico e foco

**Descrição:**
Como colaborador, quero ver minha saúde financeira, o progresso do trimestre e o que mais pesa agora, para acompanhar a consultoria.

**Tarefas:**

- `JourneyResource` sobre `UserJourney`: `stage, stage_index, stages[], completed_consultations, topics_covered[],
  topics_total, ratings_given, pending_ratings, last_consultation_at, completed_this_month, health_score,
  health_score_previous_month, quarter { completed, goal, starts_at, ends_at }, health_history[] { month: 'YYYY-MM', score }, focus[] { label, status_label, tone }`.
- Estender `BuildUserJourneyAction` (ou criar `BuildUserJourneyHistoryAction` ao lado) com:
  - `quarter`: encontros `completed` com `appointment_at` no trimestre civil corrente; `goal` do config.
  - `health_history`: o mesmo cálculo de `healthScorePreviousMonth` generalizado para "score no fim do mês M", para os
    últimos seis meses (o mais antigo primeiro).
  - `focus`: até três itens derivados dos componentes do score: estágio (`LifeMoment` com descrição e tone `warning`
    para `endebted`/`messy`, `info` para `payer`, `success` para `saver`/`investor`), cobertura de assuntos
    (`"{n} de 6 assuntos"`, tone por faixa) e avaliações pendentes (`"{n} encontros sem avaliação"`, `warning` se > 0).
    Rótulos em `api/lang/pt_BR/journey.php`.
- Cache: `Cache::remember("journey:{$user->id}", 3600, …)`, invalidado nos eventos de encontro concluído, feedback criado e anamnese salva.

**Subtarefas:**

- Testes de unidade para `quarter` (bordas de trimestre), `health_history` (seis meses, ordem) e `focus` (cada regra).
- Teste de contrato de `JourneyResource`.

**Definition of Done:**

- Home (tile 68, "2 de 4 encontros no trimestre") e folha 39 do app renderizam com dados reais.
- Ponto 12 do contrato fechado sem tabela nova.

---

## FLM - STORY - XX | S5 · Agenda: listar, detalhar, horários, agendar, reagendar e cancelar

**Descrição:**
Como colaborador, quero ver meus encontros, escolher um horário livre, agendar, reagendar e cancelar dentro das regras,
para gerir minha consultoria pelo app com a mesma régua do painel.

**Endpoints:**

| Método | Rota | Request | Resposta |
| --- | --- | --- | --- |
| GET | `/v1/appointments` | `?status=upcoming\|pending\|history` (opcional) | `Paginated<Appointment>` (50/página, ordenado por `appointment_at`) |
| GET | `/v1/appointments/{id}` | — | `{ data: Appointment }` · `404` |
| GET | `/v1/appointments/slots` | `?month=YYYY-MM` | `{ data: { "2026-10-07T09:00:00-03:00": "09:00", … } }` (chave = instante ISO com offset; o app devolve a chave no POST/PATCH) |
| POST | `/v1/appointments` | `{ category_type, appointment_at, notes? }` | `201 { data: Appointment }` · `422` (`credit`, `appointment_at`, `category_type`) |
| PATCH | `/v1/appointments/{id}` | `{ appointment_at }` | `{ data: Appointment }` (consultor ocupado → `pending`, `consultant: null`) · `422` (`appointment`, `appointment_at`) |
| DELETE | `/v1/appointments/{id}` | — | `200 { data: Appointment }` (status `cancelled`) · `422` |
| POST | `/v1/appointments/{id}/feedback` | `{ rating 1-5, comment? }` | `201 { data: Appointment }` · `422` (planejado; sem tela no app ainda) |

**Tarefas:**

- `AppointmentResource`: `id, category_type, category_label, appointment_at, duration_minutes, status, meeting_url`
  (só quando `active` e futuro, como o painel), `consultant { id, name, avatar_url }` (media `avatars` do consultor; null
  enquanto pendente), `notes, feedback { rating, comment }, record { published_at, content }` (só `isPublished()`),
  `can_reschedule, can_cancel, cancel_impact` (`returns_credit` | `loses_credit` | `null` quando não cancelável),
  `materials[] { id, title, type, shared_at }` (pivot `appointment_document`, S7), `created_at`.
  Eager load `consultant.media, feedback, record, documents`.
- `Appointment::canBeCancelled()` (status pending/active e futuro, a regra de visibilidade do painel) e
  `Appointment::cancelImpact()` (`isLateCancellation()`) ao lado de `canBeRescheduled()`; o painel passa a consultar os mesmos métodos.
- Extrair do `panel-app` para `appointments/src/Actions`:
  - `ScheduleAppointmentForUserAction::handle(User, BookAppointmentDTO): Appointment` — `canCreateAppointment()`
    (senão `ValidationException` em `credit` com `BookingBlockReasons`), lead de `BOOKING_LEAD_DAYS` e slot pertencente a
    `GetAvailableSlotsAction::handle($dia)` (senão `appointment_at`), então `BookAppointmentAction::handle` (que passa a
    retornar o `Appointment`) e a mesma atribuição de consultor que `SchedulesAppointments` faz hoje.
  - `RescheduleAppointmentAction::handle(Appointment, CarbonInterface): Appointment` — dono, `canBeRescheduled()`,
    slot válido, `update`, `SyncAppointmentScheduleAction` com actor `User`, reverte se a sincronização falhar.
  - `CancelAppointmentForUserAction::handle(Appointment, User): Appointment` — `canBeCancelled()` senão 422;
    `current_transition->handle(new TransitionData(cancellationActor: CancellationActor::User, cancelledBy: $user))`,
    que resolve `cancelled` ou `cancelled_late` (crédito perdido) exatamente como o painel.
  - `SchedulesAppointments`, `ReschedulesAppointments` e `CancelAppointmentAction` do painel passam a delegar a elas.
- Slots por mês: do primeiro dia elegível (`max(hoje + lead, início do mês)`) ao fim do mês, `GetAvailableSlotsAction::handle($dia, 60)`
  por dia com `Cache::remember` de `config('api.slots_cache_seconds')`; filtrar horários já passados; mesclar num único map.
- `StoreAppointmentRequest`: `category_type` `Rule::enum(AppointmentCategoryEnum)`, `appointment_at` `date` e
  `after:` hoje + lead, `notes` `nullable|string|max:1000`. `RescheduleAppointmentRequest`: só `appointment_at`.
- Feedback: `StoreFeedbackRequest` (`rating` 1-5, `comment` nullable ≤ 1000); só com status `completed` e sem feedback.
- `AppointmentPolicy` (dono) registrada no provider do módulo.

**Subtarefas:**

- Testes com `consultantAvailableOn()`: slots do mês respeitam lead e fuso; agendar consome cota primeiro e depois crédito (`in_use`);
  sem cota nem crédito → 422 `credit`; horário fora dos slots → 422 `appointment_at`.
- Testes: reagendar fora da janela de 4 h → 422; cancelar com mais de 4 h devolve o crédito (`available`) e responde `cancelled`; com menos → `cancelled_late`, crédito `used`, `cancel_impact` era `loses_credit` antes.
- Teste: encontro de outro usuário → 404.
- Teste de contrato de `AppointmentResource` (incluindo `materials: []`).
- Verificar no painel que agendar/reagendar/cancelar continuam iguais após a extração (testes existentes do `panel-app`).

**Definition of Done:**

- Telas 10–17 e o fluxo 34–38 do app operam contra o ambiente local, com cota e créditos reais.
- `BookAppointmentAction::handle` retorna `Appointment`; nenhuma regra ficou só em classe Filament.

---

## FLM - STORY - XX | S6 · Créditos: saldo, cota do mês e pedido de mais créditos

**Descrição:**
Como colaborador, quero ver meus créditos e a cota do mês, para saber quantos encontros ainda posso marcar.

**Endpoints:**

| Método | Rota | Resposta |
| --- | --- | --- |
| GET | `/v1/credits` | `{ data: { monthly_quota { limit, left, renews_at }, credits[] } }` |

**Tarefas:**

- `CreditsResource`: `monthly_quota` de `ResolveQuotaAllowance::for($user, $companyId)` (`limit`), `User::monthly_appointments_left`
  (`left`) e `QuotaCycle::forAnchor(...)->end` (`renews_at`, `Y-m-d`); `null` quando o plano é só créditos.
  `credits[]` = `UserCredit::heldBy(collect([$user]))->forCompany($company)` incluindo expirados, cada um
  `{ id, status, owner_type, expires_at, used_at, appointment_id, grant_id, created_at, updated_at }`.
- Alinhar a contagem de "disponíveis" com `notExpired()` (hoje o widget do painel e `hasAvailableCredit()` divergem).
- **Fora desta story:** `POST /v1/credits/request` (issue #285). O app esconde a ação até a decisão.

**Subtarefas:**

- Testes: `left` após um agendamento; `renews_at` no fim do ciclo do plano; `owner_type` por `owner_id`.

**Definition of Done:**

- Tela 25 e tile de créditos da Home com dados reais.

---

## FLM - STORY - XX | S7 · Materiais: listar, favoritar, marcar visto, enviar e remover

**Descrição:**
Como colaborador, quero ver os materiais do consultor e os meus, favoritar, saber o que é novo e enviar documentos,
para ter tudo da consultoria num lugar só.

**Endpoints:**

| Método | Rota | Request | Resposta |
| --- | --- | --- | --- |
| GET | `/v1/materials` | — | `Paginated<Material>` (compartilhados ativos ∪ próprios) |
| POST | `/v1/materials` | multipart `file` (pdf, jpg, png, svg, xlsx, docx; ≤ 100 MB) + `title` | `201 { data: Material }` · `422` |
| PATCH | `/v1/materials/{id}/favorite` | `{ favorite: bool }` | `{ data: Material }` |
| PATCH | `/v1/materials/{id}/viewed` | — | `{ data: Material }` (`viewed_at` só na primeira vez) |
| DELETE | `/v1/materials/{id}` | — | `204` · `403` (material do consultor) · `404` |

**Tarefas:**

- Migration `document_user_states` (`id uuid, document_id, user_id, viewed_at, favorited_at, timestamps`, unique
  `(document_id, user_id)`), model `DocumentUserState` e `Document::states()`. **O vínculo material ↔ encontro
  (`appointment_document`) saiu da v1** e virou a issue #296 (envolve o painel do consultor e regras de visibilidade);
  o app remove o bloco "Materiais relacionados" até lá (gil-benefits-mobile#2) e a API segue devolvendo `materials: []`.
- `MaterialResource`: `id, title, type` (`DocumentExtensionTypeEnum`), `link`, `file { name, mime_type, size, url }`
  (media `documents`, URL temporária 60 min inline), `uploaded_by` (`documentable_type` = users → `employee`, senão
  `consultant`), `shared_by { id, name }` (consultor do `document_shares` ou o próprio usuário), `shared_at`
  (`document_shares.created_at` ou `documents.created_at`), `viewed_at`, `favorited_at` (do estado do usuário).
- Listagem: query única com `union` dos dois conjuntos ou duas coleções mescladas e ordenadas por `shared_at` desc;
  eager load `media, shares.consultant, states(user)`.
- `UploadMaterialAction` (módulo `consultants`, reutilizado pelo `CreateSharedDocument` do painel): cria `Document`
  (`documentable` = usuário, `type` pelo MIME, `active`) e `addMediaFromRequest('file')->toMediaCollection('documents')`.
- Favorito/visto: `updateOrCreate` em `document_user_states`.
- Remoção: `DocumentPolicy::delete` = `documentable` é o usuário; soft delete + desativar shares.

**Subtarefas:**

- Testes: lista traz compartilhado e próprio, não traz share inativo nem documento inativo; favoritar/visto idempotentes;
  remover material do consultor → 403; upload de MIME fora do enum → 422.
- Teste de contrato de `MaterialResource`.

**Definition of Done:**

- Telas 18–23 e "Enviar doc" do app funcionam com arquivos reais no R2.
- O vínculo com encontros fica na #296.

---

## FLM - STORY - XX | S8 · Notificações: lista, não lidas e deep links

**Descrição:**
Como colaborador, quero ver minhas notificações no app e abrir o encontro ou o material relacionado, para agir a partir do aviso.

**Endpoints:**

| Método | Rota | Resposta |
| --- | --- | --- |
| GET | `/v1/notifications` | `Paginated<Notification>` + `meta.unread` |
| PATCH | `/v1/notifications/{id}/read` | `{ data: Notification }` |

**Tarefas (revisadas em 2026-10-08):**

- Cada aviso ao colaborador é uma notificação do Laravel que estende `App\Notifications\ContentNotification` e devolve um
  `NotificationContent` (tipo em `NotificationKind`, título, texto, tom em `NotificationTone`, `appointment_id`/`document_id`).
  A base converte o conteúdo para cada canal: no banco, o formato do Filament (o sino do painel não muda) mais `kind` e
  os ids; a coluna `type` guarda o `kind`. Push, no futuro, vira um canal que traduz o mesmo conteúdo.
- Escopo: os avisos que o colaborador já recebe no painel (encontro confirmado, cancelado, cancelado em cima da hora,
  realizado e créditos entregues). Ata publicada, material compartilhado e outros avisos que faltam ficaram para a #299.
- `NotificationResource`: `id, type (kind), data { title, body, status, icon, format, appointment_id?, document_id? },
  read_at, created_at`. A lista filtra pelos tipos que o app exibe (`NotificationKind::shownInEmployeeApp()`),
  com `meta.unread`; `read` marca só na primeira vez.

**Subtarefas:**

- Testes: `meta.unread` cai ao marcar como lida; notificação de outro usuário → 404; payload de cancelamento carrega `appointment_id`.

**Definition of Done:**

- Tela 27 e os sinos do app abrem encontro e material pelos ids reais.

---

## FLM - STORY - XX | S9 · Anamnese: `GET/PUT /me/anamnese` com salvamento progressivo

**Descrição:**
Como colaborador, quero responder meu perfil financeiro aos poucos pelo app, para não perder o que já escrevi.

**Tarefas:**

- Migration: `user_anamneses.main_motivation`, `money_relationship`, `plans_monthly_expenses`, `tried_financial_strategies`
  e `life_moment` passam a `nullable` (mantendo os tipos), para representar o estado "nada respondido ainda".
  `down()` seguro: só volta a `NOT NULL` se não houver nulos.
- `AnamneseResource`: `life_moment, main_motivation, money_relationship, plans_monthly_expenses, tried_financial_strategies, updated_at`
  (todos `null` quando não há linha).
- `UpdateAnamneseRequest`: todos `sometimes|nullable`; `life_moment` `Rule::enum(LifeMoment)`; textos `string|max:5000`.
- `SaveAnamneseAction` ganha um modo de merge parcial (ou nova `UpsertAnamneseAnswersAction`), preservando o
  `updateOrCreate` por `user_id`; o wizard do painel segue exigindo tudo na validação do formulário.
- `MeResource.anamnese_completed` = cinco campos preenchidos; `RedirectIfAnamneseNotCompleted` passa a usar a mesma
  verificação (hoje é "existe linha").

**Subtarefas:**

- Testes: `PUT` parcial cria a linha com nulos; completar os cinco liga `anamnese_completed`; enum inválido → 422.
- Teste de regressão do wizard do painel.

**Definition of Done:**

- Tela 26 do app salva ao sair de cada campo e o progresso sobrevive a trocar de aparelho.

---

## FLM - STORY - XX | S10 · Chamados: listar, abrir e finalizar

**Descrição:**
Como colaborador, quero abrir e acompanhar chamados pelo app, para pedir ajuda sem sair dele.

**Endpoints:**

| Método | Rota | Request | Resposta |
| --- | --- | --- | --- |
| GET | `/v1/tickets` | — | `Paginated<Ticket>` (mais recente primeiro) |
| POST | `/v1/tickets` | `{ category, subject ≤ 255, description }` | `201 { data: Ticket }` · `422` |
| PATCH | `/v1/tickets/{id}` | `{ status: "closed" }` | `{ data: Ticket }` · `404` · `422` |

**Tarefas:**

- `SupportTicketResource`: `id, protocol, category, subject, description, status, created_at, updated_at`.
- Listagem: `SupportTicket::query()->withoutGlobalScopes()->where('user_id', $user->id)->latest()->paginate(20)`
  (mesmo filtro do `SupportTicketResource` do painel); registrar o motivo do `withoutGlobalScopes` em docblock.
- Criação: `CreateSupportTicketAction::execute(new CreateSupportTicketDTO(category, subject, description, userId, companyId,
  url: null, browser: 'app', device: 'mobile', environment: app()->environment()))`.
- Finalizar: `SupportTicketPolicy::update` (dono) + `TransitionSupportTicketStatusAction::execute($ticket, Closed)`;
  `InvalidTransitionException` → 422 `status` "Este chamado já foi encerrado.". Só `closed` é aceito do colaborador.

**Subtarefas:**

- Testes: criação gera protocolo `SUP-AAAA-NNNN` e dispara `DispatchSupportTicketJob`; finalizar duas vezes → 422;
  chamado de outro usuário → 404; `status: resolved` → 422.

**Definition of Done:**

- Telas 31 e o modal de novo chamado do app operam contra o ambiente local.

---

## FLM - STORY - XX | S11 · Contrato, documentação e ambiente para o app

**Descrição:**
Como time, quero garantir que a API e o app falem o mesmo contrato e que qualquer pessoa suba os dois localmente.

**Tarefas:**

- Teste de contrato por resource (`assertJsonStructure` com as chaves listadas em cada story), agrupado em
  `tests/Feature/Contract/*Test.php` do módulo.
- `config/cors.php`: liberar `api/*` para `http://localhost:8081` (só para o app rodando no navegador em desenvolvimento).
- README do módulo (`app-modules/api/README.md`): como rodar com o app — `make env-up` ou `php artisan serve --port=8000`;
  no app `EXPO_PUBLIC_API_URL=http://127.0.0.1:8000` com `adb reverse tcp:8000 tcp:8000` (emulador no WSL) ou o IP da máquina no celular.
- Atualizar `gil-benefits-mobile/docs/api-contract.md` e o app: remover `POST /v1/auth/biometric` (biometria destrava o token
  local, regra 4.2), `current_password` em `PATCH /me` (e-mail) e `PUT /me/password` (tela 30 ganha "Senha atual"),
  `cancel_impact` no Appointment (alerta de cancelamento com o texto certo), `company` = Flamma para assinante individual
  no texto "Benefício oferecido por…", e `POST /v1/credits/request` marcado como estacionado (#285).
- Anotar em `RELEASING.md` a convenção: mudança incompatível em `/api/v1` é MAJOR; aditiva é MINOR.
- Registrar a dívida: mover `tenant/src/Http/Controllers/Api/v1/UsersController` e `VerifyTenantTokenMiddleware` para
  `api/.../V1/Company` na próxima mudança da integração da empresa (mesmas rotas e header; os testes vão junto).

**Definition of Done:**

- Suíte Pest do módulo verde no CI; app com `.env` apontando para o local passa pelas telas 01–45 (menos 33).

---

## FLM - STORY - XX | S12 · (Opcional, pós-v1) Encontro "sem data" e thread de chamados

**Descrição:**
Como consultor, quero pedir ao colaborador uma nova data para um encontro, e como colaborador quero trocar mensagens
num chamado — os dois estados que o Figma desenha e o backend ainda não tem.

**Tarefas:**

- `appointments.appointment_at` nullable + ação de painel "Pedir nova data" (zera a data, mantém `pending`, libera o
  horário no Zap/Google, notifica com `appointment_id`); a API já devolve `appointment_at: null` e o app mostra "Sem data".
- `support_ticket_messages` (`ticket_id, author_type, author_id, body, created_at`) + `GET/POST /v1/tickets/{id}/messages`;
  o painel admin ganha a resposta inline.

**Definition of Done:**

- Decidido com produto antes de começar; não bloqueia a v1.

---

## Entrega e versão

Decisão de 2026-10-06: **um branch `feat/api-v1` e um único PR em `develop`** ao fim da v1 (S1–S11). Para a revisão
não virar um bloco de centenas de arquivos, os commits seguem a ordem das stories (`feat(api): S1 …`, `feat(api): S2 …`)
e cada um passa o `make check` sozinho; o PR descreve as migrations (todas aditivas) e a dependência nova. A release é
**uma MINOR** (nenhuma env obrigatória, nenhum passo manual).

| Ordem | Stories | Observação |
| --- | --- | --- |
| 1 | S1, S2, S3 | O app entra, vê e edita o perfil. |
| 2 | S4, S5, S6 | Home, Agenda, agendamento e créditos reais (maior esforço: extração das actions). |
| 3 | S7, S8 | Materiais e notificações (duas tabelas novas). |
| 4 | S9, S10, S11 | Anamnese, chamados, contrato e documentação; abre o PR. |
| depois | S12 | Produto decide. |

**Riscos e dependências:** aprovação do Sanctum (S1); o fluxo atual de atribuição de consultor precisa ser confirmado
antes de S5; `GetAvailableSlotsAction` por dia pode ficar lento com muitos consultores (cache por dia e,
se necessário, um `GetAvailableSlotsForMonthAction` que leia o Zap uma vez); URLs temporárias do R2 exigem
`AWS_URL`/credenciais corretas no ambiente que o app aponta.

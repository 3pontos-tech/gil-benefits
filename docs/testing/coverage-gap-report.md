# Relatório de Gaps de Cobertura de Testes — Gil Benefits

> Auditoria read-only realizada em **2026-04-23** na branch `develop` (HEAD `96420e7`).
> Escopo: Unit + Feature apenas. E2E (browser) ignorado. `integration-highlevel/`
> excluído do `phpunit.xml` — fora de escopo. DTOs, Enums sem lógica, Providers,
> migrations, Requests sem lógica e getters triviais não são listados.

---

## 1. Sumário executivo

Com **96 testes** (19 Feature + 2 Unit no root; 60 Feature + 4 Unit nos módulos), a
cobertura está concentrada em `appointments`, `tenant` (widgets) e `user` (import).
Os gaps relevantes concentram-se em pontos que **não aparecem em nenhum teste**:

1. 🔴 **`billing`**: `SubscriptionWebhookController` (`app-modules/billing/src/Stripe/Subscription/SubscriptionWebhookController.php:12`) lê `metadata.model`
   do payload **antes** do `parent::handleWebhook` e alterna o `Cashier::useCustomerModel`.
   Assinatura inválida, metadata ausente/malformada e eventos duplicados não são testados.
2. 🔴 **Tenancy em `appointments`**: `BookAppointmentAction::handle` (`app-modules/appointments/src/Actions/BookAppointmentAction.php:11`)
   e `GetAvailableConsultantsAction::handle` (`app-modules/appointments/src/Actions/GetAvailableConsultantsAction.php:14`)
   não escopam por `company_id`. `Consultant::all()` global permite vazamento entre tenants.
3. 🔴 **`company`**: o módulo **não tem diretório de testes**. `CreateCompanyAction`,
   `AttachToDefaultCompany` e o listener `AttachUserToDefaultCompanyListener` ficam sem cobertura.
4. 🟠 **`integration-google-calendar`**: `SyncConsultantCalendarAction`, `UpsertBlockedScheduleAction`,
   `RemoveCancelledGoogleEventAction`, `RemoveStaleBlockedSchedulesAction` e `SyncConsultantCalendarJob`
   sem teste; paginação, idempotência e caminhos `retryable=false` (401/403 `invalid_grant`) cobertos parcialmente.
5. 🟠 **`tenant`**: `VerifyTenantTokenMiddleware` (rotas de API com token de integração),
   `TenantSecretKeyRotationAction` e `CompanyPolicy` sem teste dedicado.

Recomendação imediata: adicionar `Http::fake()` e `Queue::fake()` opt-in no bootstrap,
criar factories faltantes (`Subscription`, `TenantMember`) e três arch tests para
tenancy/isolamento (detalhes na seção 7).

---

## 2. Panorama

### Contagem por módulo

| Módulo                        | Feature | Unit | Arch | Factories | Gap factory |
|-------------------------------|---------|------|------|-----------|-------------|
| appointments                  | 24      | 0    | 0    | 3         | —           |
| billing                       | 8       | 0    | 0    | 3         | `Subscription` |
| company                       | **0**   | 0    | 0    | 1         | —           |
| consultants                   | 5       | 0    | 0    | 3         | —           |
| integration-google-calendar   | 4       | 3    | 0    | 0         | N/A         |
| panel-admin                   | 4       | 0    | 0    | 0         | N/A         |
| panel-app                     | 2       | 0    | 0    | 0         | N/A         |
| panel-company                 | 3       | 0    | 0    | 0         | N/A         |
| panel-consultant              | 4       | 0    | 0    | 0         | N/A         |
| permissions                   | 5       | 1    | 0    | 2         | —           |
| tenant                        | 8       | 0    | 0    | 0         | `TenantMember` |
| user                          | 4       | 0    | 0    | 1         | —           |
| **Root (`tests/`)**           | 19      | 2    | 0    | —         | —           |

### Configuração global (`tests/Pest.php:32-203`)

- Trait: `RefreshDatabase` aplicada em `Feature`, `E2E` e `../app-modules/*/tests` (linha 32-34).
- Grupo Pest `browser` em E2E (linha 36-37) — coerente com ignorar E2E.
- Helpers: `actingAsAdmin`, `actingAsSuperAdmin`, `actingAsCompanyOwner`,
  `actingAsEmployee`, `actingAsSubscribedEmployee`, `actingAsConsultant` (linhas 74-203).
- **Ausente**: `Http::fake`, `Queue::fake`, `Notification::fake`, `Mail::fake`, `Bus::fake` globais.

---

## 3. Gaps por módulo

### <a id="appointments"></a>3.1 `appointments`

Testes existentes cobrem os 5 steps da state machine (Draft, Pending, Scheduling,
Active — falta Done), `BookAppointmentAction`, `AssignConsultantAction`,
`GetAvailableConsultantsAction`, `MarkAppointmentsAsCompleted`, 3 pages Admin,
fluxo completo de `AppointmentRecord` e mails.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/appointments/src/Actions/BookAppointmentAction.php:11` | Não valida se `payload->userId` pertence à company do tenant atual — vazamento cross-tenant | 🔴 | Agendamento | Feature | P |
| `app-modules/appointments/src/Actions/GetAvailableConsultantsAction.php:14` | `Consultant::all()` sem filtro por company; retorna consultants de outras empresas | 🔴 | Agendamento | Feature | P |
| `app-modules/appointments/src/Actions/GetAvailableSlotsAction.php:10` | Sem teste dedicado (cobertura indireta apenas); não escopa por company | 🔴 | Agendamento | Feature | P |
| `app-modules/appointments/src/Actions/StateMachine/AbstractAppointmentStep.php:32` | `cancel()` não guarda contra appointments já cancelados (dupla notificação + duplicado dispatch de `DeleteAppointmentCalendarEventJob`) | 🟠 | Agendamento | Feature | P |
| `app-modules/appointments/src/Actions/StateMachine/AppointmentDoneStep.php` | Sem teste de step Done (cenário final sem side-effects?) | 🟡 | Agendamento | Feature | P |
| `app-modules/appointments/src/Jobs/GenerateAppointmentRecordJob.php:55` | `GenerateJobTest` existe, mas não exercita rate limiter (`Redis::throttle`) nem falha do Prism AI | 🟠 | Prontuário | Feature | M |
| `app-modules/appointments/src/Policies/AppointmentRecordPolicy.php` | Testado parcialmente; faltam cenários por painel (`actingAs($u, 'panel_id')` para Admin vs Consultant) | 🟡 | RBAC | Feature | P |
| `app-modules/appointments/src/Events/AppointmentBooked.php`, `AppointmentCompleted.php`, `AppointmentCancelled.php` | Assertar despacho em cada step (hoje implícito) via `Event::fake` | 🟢 | Observer | Feature | P |

### <a id="billing"></a>3.2 `billing`

Repositório Eloquent de Plan com teste. Tudo relacionado a Stripe/Cashier (webhook,
middlewares, billing providers e sync command) **sem teste**.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/billing/src/Stripe/Subscription/SubscriptionWebhookController.php:12` | `handleWebhook` altera `Cashier::useCustomerModel` via `metadata.model` **antes** do `parent::handleWebhook` — testar: assinatura inválida, metadata ausente, morph inválido, payload malformado, reprocessamento idempotente | 🔴 | Assinatura | Feature | M |
| `app-modules/billing/src/Stripe/Subscription/Company/RedirectCompanyIfNotSubscribed.php:13` | Middleware redireciona a não-assinantes exceto `flamma-company` e `stripe/*`; sem teste — cenários: tenant sem subscription, com subscription `past_due`, `canceled`, `trialing` | 🔴 | Assinatura + acesso | Feature | M |
| `app-modules/billing/src/Stripe/Subscription/User/RedirectUserIfNotSubscribed.php:19` | Middleware user idem; sem teste | 🔴 | Assinatura + acesso | Feature | M |
| `app-modules/billing/src/Stripe/Subscription/Company/CompanyBillingProvider.php:13` | `getRouteAction()`/`getSubscribedMiddleware()` sem teste — qual rota é retornada por estado | 🟠 | Billing portal | Feature | P |
| `app-modules/billing/src/Stripe/Subscription/User/UserBillingProvider.php:12` | idem | 🟠 | Billing portal | Feature | P |
| `app-modules/billing/src/Core/Commands/SyncStripeResourcesCommand.php:32` | Sincroniza Product/Price com Stripe — sem teste com `Http::fake`/Stripe mock; idempotência desconhecida | 🟠 | Administração de planos | Feature | M |
| `app-modules/billing/src/Core/Repositories/ConfigPlanRepository.php:19,53,59` | `all()`, `getPlansFor()`, `getActiveTenantPlan()` sem teste (apenas `EloquentPlanRepository` coberto) | 🟡 | Repositórios | Unit | P |
| `app-modules/billing/src/Core/Repositories/EloquentPlanRepository.php:29` | `getPlansFor()` com cache 15 min por `type` apenas — sem isolamento por tenant. Verificar se cache global é intencional | ❓ | Repositórios | Feature | P |
| `app-modules/billing/database/factories/` | **Ausente** `SubscriptionFactory` (Cashier `Subscription`) — testes criam via `$company->subscriptions()->create([...])` | 🟢 | Test fixtures | Unit | P |

### <a id="company"></a>3.3 `company`

**O módulo não tem diretório `tests/`.** Toda lógica de criação e vinculação de
Company fica sem teste dedicado.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/company/src/Actions/CreateCompanyAction.php:12` | Cria Company + attach user + assign role `CompanyOwner` — precisa testar transação, rollback, idempotência | 🔴 | Onboarding | Feature | M |
| `app-modules/company/src/Actions/AttachToDefaultCompany.php:12` | `firstOrCreate('flamma-company')` + attach user + assign role — cenários: company já existe, user já vinculado, role duplicada | 🟠 | Onboarding | Feature | P |
| `app-modules/company/src/Listeners/AttachUserToDefaultCompanyListener.php:12` | Assinatura `UserRegistered` → dispatch action; testar role dinâmica do event | 🟠 | Onboarding | Feature | P |
| `app-modules/company/src/Models/Company.php` | Uso de `Billable`, `HasUuids`, `InteractsWithMedia`; se há scopes de tenant, testar — ❓ a validar com o time | ❓ | Tenancy | Feature | P |

### <a id="consultants"></a>3.4 `consultants`

Filament Resources (Create/Edit/List) cobertos; Mail testado. Ações, Observers e Policies sem teste.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/consultants/src/Observers/ConsultantObserver.php:12` | `created()` executa `firstOrCreate` em User e associa role Consultant; `password = $consultant->email` — validar segurança e idempotência em re-criação | 🔴 | Onboarding consultant | Feature | P |
| `app-modules/consultants/src/Observers/MediaObserver.php:11` | Valida extensão de media em collection `documents`; sem teste de rejeição de extensão inválida | 🟠 | Upload de documentos | Feature | P |
| `app-modules/consultants/src/Actions/UpsertDocumentShareAction.php:12` | `updateOrCreate(DocumentShare)` — testar compartilhamento novo vs atualização (toggle active) | 🟠 | Document share | Feature | P |
| `app-modules/consultants/src/Policies/ConsultantPolicy.php` | `viewAny`/`view` baseado em permission — testar por painel (Admin vs Consultant vs Company) | 🟡 | RBAC | Feature | P |
| `app-modules/consultants/src/Policies/DocumentPolicy.php` | idem; somar isolamento por company do consultant dono do documento | 🟠 | RBAC | Feature | P |

### <a id="integration-google-calendar"></a>3.5 `integration-google-calendar`

`CreateCalendarEventAction` e `DeleteCalendarEventAction` (+ jobs) com testes. Sync,
upsert e remoção de blocked schedules sem teste.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/integration-google-calendar/src/Actions/SyncConsultantCalendarAction.php:18` | Orquestra paginação de `listEvents`, upsert/cancel/stale cleanup; idempotência na re-execução | 🔴 | Sync Calendar | Feature | G |
| `app-modules/integration-google-calendar/src/Actions/UpsertBlockedScheduleAction.php:13` | Converte `GoogleEventDTO` em BlockedSchedule (Zap); conflito com Appointments, all-day/multi-day | 🟠 | Sync Calendar | Feature | M |
| `app-modules/integration-google-calendar/src/Actions/RemoveCancelledGoogleEventAction.php:13` | Remove blocked schedule pelo `google_event_id` cancelado | 🟡 | Sync Calendar | Feature | P |
| `app-modules/integration-google-calendar/src/Actions/RemoveStaleBlockedSchedulesAction.php:12` | Apaga blocked schedules não sincronizados na última rodada | 🟡 | Sync Calendar | Feature | P |
| `app-modules/integration-google-calendar/src/Jobs/SyncConsultantCalendarJob.php:24` | Job com retry 3x e backoff [10,60,300]; sem teste de non-retryable e sucesso | 🟠 | Sync Calendar | Feature | M |
| `app-modules/integration-google-calendar/src/GoogleCalendarClient.php:20,45,84,103` | `getAccessToken`/`listEvents`/`createEvent`/`deleteEvent` — exercitar 401/403 `invalid_grant` (retryable=false), 429 quota, 410 Gone no delete (silencioso), paginação `nextPageToken` | 🟠 | Client wrapper | Unit/Feature | M |
| `app-modules/integration-google-calendar/src/Providers/...` | `google-calendar:sync` com `.everyTenMinutes()` — testar Schedule registration via `Artisan::call`+`Schedule` assertions | 🟢 | Scheduling | Feature | P |

### <a id="permissions"></a>3.6 `permissions`

`SyncPermissionsCommand`, `RolePolicy`, Filament Role CRUD e `Unit/ModelsTest` cobertos.
Gaps em sub-helpers e enum.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/permissions/src/Commands/SyncPermissions/SyncPermissionsCommand.php:54,72,122,161` | `syncRoles`/`syncRolesPermissions`/`syncPermissions`/`syncSuperAdminPermissions` — testar idempotência de execução repetida (mesmo DB, dois runs) e mudança de config | 🟠 | RBAC bootstrap | Feature | M |
| `app-modules/permissions/src/Commands/SyncPermissions/ModelPayload.php` / `RolePermissions.php` | Helpers internos sem teste — se `match`/lógica, unit test | 🟡 | RBAC | Unit | P |
| `app-modules/permissions/src/Roles.php:25,38` | `getColor()` e `getLabel()` com `match` por role — unit test por case | 🟢 | UI enum | Unit | P |
| `app-modules/permissions/src/RolePolicy.php` | Testado para SuperAdmin; faltam cenários negativos para os demais 6 roles | 🟡 | RBAC | Feature | P |

### <a id="tenant"></a>3.7 `tenant`

Filament widgets e pages cobertos; API v1 (Create/Delete external user) tem testes.
Middleware, action de rotação de chave e policy sem teste.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/tenant/src/Http/Middleware/VerifyTenantTokenMiddleware.php:17` | 401 sem header, 403 token inválido, 403 token vale para outro tenant na URL, 200 caso válido — nenhum cenário isolado | 🔴 | API externa | Feature | P |
| `app-modules/tenant/src/Actions/TenantSecretKeyRotationAction.php:8` | `generate(Company)` gera UUID e chama `generateToken()` — testar rotação, invalidação da chave anterior | 🟠 | Tenant admin | Feature | P |
| `app-modules/tenant/src/Policies/CompanyPolicy.php:12` | `viewAny`/`view`/`create` baseados em permission — testar por painel | 🟡 | RBAC | Feature | P |
| `app-modules/tenant/database/factories/` | **Ausente** `TenantMemberFactory`; tests criam via attach manual | 🟢 | Test fixtures | Unit | P |

### <a id="user"></a>3.8 `user`

`ImportUsersFromFileAction` tem teste composto; WelcomeUserMail testado como Mailable.
Sub-actions do pipeline de import, listener e job não têm teste dedicado.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/user/src/Actions/ParseUsersFromFileAction.php:10` | Parse CSV/XLSX com `SimpleExcelReader`; sem teste: colunas extras, headers com caixa diferente, linhas em branco | 🟠 | Importação | Feature | P |
| `app-modules/user/src/Actions/ValidateUserImportAction.php:20` | Validações de REQUIRED_COLUMNS + duplicatas cross-company; sem teste isolado | 🟠 | Importação | Feature | P |
| `app-modules/user/src/Actions/PersistImportedUsersAction.php:22` | `chunk` cria Users e envia welcome mail — testar rollback em falha no meio do chunk | 🔴 | Importação | Feature | M |
| `app-modules/user/src/Actions/SaveAnamneseAction.php:19` | Persiste UserAnamnese (LifeMoment enum, motivations) — sem teste | 🟡 | Anamnese | Feature | P |
| `app-modules/user/src/Jobs/ImportUsersJob.php:26` | Queue job com timeout 600s — sem teste de execução | 🟠 | Importação | Feature | P |
| `app-modules/user/src/Listeners/SendWelcomeEmailListener.php:13` | `if (blank($email))` branch não testado | 🟢 | Onboarding | Feature | P |
| `app-modules/user/src/Http/Controllers/DownloadImportTemplateController.php:9` | `__invoke()` retorna CSV template — sem teste de schema (headers corretos) | 🟢 | Importação | Feature | P |
| `app-modules/user/src/Filament/Actions/ImportUsersAction.php` | Filament Action com FileUpload — sem teste via Livewire | 🟠 | Importação | Feature | M |

### <a id="panel-admin"></a>3.9 `panel-admin`

`CreateCompanyTest`, `EditUserProfileTest`, `ListCompaniesTest`, `ViewAppointmentTest`
cobrem um CRUD básico. Ampla superfície sem teste: `AssignRoleAction`, filtros
reativos da `AppointmentResource`, Consultant/Plan/Price/ContractualPlan/Tag Resources,
widgets `Metrics/*`, listeners de notificação, policies do cluster Partners.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/panel-admin/src/Filament/Resources/Permissions/Actions/AssignRoleAction.php:14` | Valida duplicidade, `visible()` só para SuperAdmin; sem teste | 🔴 | RBAC | Feature | P |
| `app-modules/panel-admin/src/Filament/Resources/Appointments/Tables/AppointmentsTable.php` | Filtros `live(debounce: 500)` por user/consultant/status/company_id/date_range — testar combinações | 🟠 | Admin | Feature | M |
| `app-modules/panel-admin/src/Filament/Resources/Appointments/Schemas/AppointmentForm.php` | `live()` + `afterStateUpdated` em `appointment_at` recarrega consultants — sem teste | 🟠 | Admin | Feature | M |
| `app-modules/panel-admin/src/Filament/Resources/Companies/Schemas/CompanyForm.php` | Slug gerado reativo (`live(onBlur,debounce:500)`) — teste do `afterStateUpdated` | 🟡 | Admin | Feature | P |
| `app-modules/panel-admin/src/Filament/Resources/Consultants/ConsultantResource.php` | Tabs com `hidden(fn ($operation))`, SpatieMediaLibraryFileUpload — **nenhum teste deste Resource** | 🟠 | Admin | Feature | M |
| `app-modules/panel-admin/src/Filament/Resources/ContractualPlans/*` | CRUD sem teste | 🟡 | Admin | Feature | P |
| `app-modules/panel-admin/src/Filament/Resources/Plans/*` | CRUD sem teste (há teste em `billing` para CRUD de Plan do Admin) | 🟡 | Admin | Feature | P |
| `app-modules/panel-admin/src/Filament/Widgets/AppointmentsStatsOverview.php:23,28` | `syncFilters()` via `#[On]` + `getStats()` com `selectRaw count() filter` — sem teste | 🟠 | Dashboard | Feature | M |
| `app-modules/panel-admin/src/Filament/Widgets/Metrics/KPIsOverview.php:20` | Todos os `Metrics/*` (AppointmentsByCategory/ByStatus/Volume/Rankings) sem teste de query | 🟡 | Dashboard | Feature | M |
| `app-modules/panel-admin/src/Filament/Widgets/QuickActions.php:21` | `getViewData()` sem teste | 🟢 | Dashboard | Feature | P |
| `app-modules/panel-admin/src/Listeners/NotifyAdminsOfAppointmentBookedListener.php:17` | 4 listeners (Booked/Cancelled/Completed/UserRegistered) — só `tests/Feature/Listeners/AdminNotificationsTest.php` parcial; validar destinatários (só admins) e idempotência | 🟠 | Notificações | Feature | M |
| `app-modules/panel-admin/src/Actions/GetAdminUsersAction.php` | Utilizado pelos listeners; validar que retorna só roles Admin/SuperAdmin | 🟡 | Notificações | Unit | P |
| `app-modules/panel-admin/src/Policies/BetterMailPolicy.php` / `InboundWebhookPolicy.php` | Policies de plugins FilamentBetterEmail/Webhook — sem teste | 🟡 | RBAC | Feature | P |

### <a id="panel-app"></a>3.10 `panel-app`

`ListDocumentsTest`, `UserRegistrationTest` cobrem parcialmente. Middleware,
wizard multi-step, ações de feedback e widgets dashboard sem teste.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/panel-app/src/Http/Middleware/RedirectIfAnamneseNotCompleted.php:15` | Middleware: sem anamnese → redireciona para wizard. Sem teste — cenários: user já fez, user em rota permitida, user sem detail | 🔴 | Onboarding | Feature | P |
| `app-modules/panel-app/src/Filament/Pages/AnamneseWizardPage.php:40,45,113` | `mount`/`form`/`submit` multi-step; `isTenantSubscriptionRequired() = false` — testar navegação de steps e persistência via `SaveAnamneseAction` | 🟠 | Onboarding | Feature | G |
| `app-modules/panel-app/src/Filament/Actions/FeedbackAction.php:19` | `visible()`: status=Completed && sem feedback; StarRating form field | 🟠 | Feedback | Feature | P |
| `app-modules/panel-app/src/Filament/Actions/ViewAppointmentRecordAction.php` | Sem teste | 🟡 | Prontuário | Feature | P |
| `app-modules/panel-app/src/Filament/Pages/UserDashboard.php` | Página principal sem teste de renderização/autorização | 🟡 | Dashboard | Feature | P |
| `app-modules/panel-app/src/Filament/Pages/UserSubscriptionPage.php` | Página de assinatura sem teste | 🟠 | Assinatura | Feature | M |
| `app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/AppointmentWizard.php` | Wizard 3 steps com `reactive()` + `afterStateUpdated` — sem teste de transição entre steps e validação | 🟠 | Agendamento | Feature | M |
| `app-modules/panel-app/src/Filament/Widgets/AppointmentHistoryWidget.php` / `LatestAppointmentWidget.php` / `UserAccountWidget.php` | Widgets sem teste (query por tenant atual) | 🟡 | Dashboard | Feature | P |
| `app-modules/panel-app/src/Filament/Forms/Components/StarRating.php` | Campo custom Filament — teste de render e estado | 🟢 | Form | Unit/Feature | P |

### <a id="panel-company"></a>3.11 `panel-company`

`CreateAndAttachActionTest`, `EditCompanyTest`, `RegisterCompanyTest` cobrem parte.
Faltam ações de tenant admin e widgets (widgets podem estar cobertos pelo módulo `tenant`).

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/panel-company/src/Filament/Actions/TenantSeatsCounterAction.php:17` | Sem teste; validar contagem correta de seats utilizados/disponíveis | 🟠 | Tenant admin | Feature | P |
| `app-modules/panel-company/src/Filament/Actions/TenantSecretKeyRotationPanelAction.php:18` | Executa `TenantSecretKeyRotationAction` — testar autorização + rotação | 🟠 | Tenant admin | Feature | P |
| `app-modules/panel-company/src/Filament/Pages/Tenancy/RegisterTenant.php` | `RegisterCompanyTest` cobre fluxo base; validar isolamento de owner vs employee existente | 🟡 | Onboarding | Feature | P |
| `app-modules/panel-company/src/Filament/Pages/Tenancy/EditTenantProfile.php` | Testado em `tenant/tests/Feature/Filament/Pages/EditTenantProfileTest.php` | ✅ | — | — | — |
| `app-modules/panel-company/src/Filament/Widgets/*` | 4 widgets com testes em `tenant/tests/Feature/Filament/Widgets/` | ✅ | — | — | — |

### <a id="panel-consultant"></a>3.12 `panel-consultant`

`UploadDocumentTest`, `ListDocumentTest`, `ListAppointmentsTest`, `ViewAppointmentTest`.
Ações de prontuário e share sem teste.

| Arquivo:linha | Artefato | Risco | Fluxo impactado | Tipo de teste | Esforço |
|---|---|---|---|---|---|
| `app-modules/panel-consultant/src/Filament/Actions/CreateAppointmentRecordAction.php:25` | FileUpload PDF/DOCX 10MB + `authorize()` via Gate + dispatch `CreateAppointmentRecordFromUploadAction` | 🟠 | Prontuário | Feature | M |
| `app-modules/panel-consultant/src/Filament/Actions/ShareDocumentFilamentAction.php:24` | Compartilhar documento com employee; valida autorização | 🟠 | Document share | Feature | M |
| `app-modules/panel-consultant/src/Filament/Actions/ReviewAppointmentRecordAction.php` | Revisão/publicação do prontuário — sem teste | 🟠 | Prontuário | Feature | M |
| `app-modules/panel-consultant/src/Filament/Actions/DownloadDocumentFilamentAction.php` | Download com checagem de permissão | 🟡 | Document share | Feature | P |
| `app-modules/panel-consultant/src/Filament/Actions/ViewPreviousRecordSummaryAction.php` | Visualização — sem teste | 🟡 | Prontuário | Feature | P |
| `app-modules/panel-consultant/src/Filament/Resources/Documents/RelationManagers/SharedDocumentRelationManager.php:35` | RelationManager: toggle active/deactivate, Delete, Restore, Bulk ForceDelete — sem teste | 🟠 | Document share | Feature | M |
| `app-modules/panel-consultant/src/Filament/Pages/ConsultantDashboard.php`, `ConsultantSchedule.php`, `EditConsultantProfile.php` | Pages sem teste | 🟡 | Dashboard | Feature | M |
| `app-modules/panel-consultant/src/Filament/Widgets/ConsultantAppointmentHistoryWidget.php`, `ConsultantLatestAppointmentWidget.php`, `ConsultantStatsOverview.php` | Widgets sem teste (queries escopadas no consultant logado) | 🟡 | Dashboard | Feature | P |

---

## 4. Fluxos de usuário não cobertos (ordem de risco)

### Fluxo 4.1 — Assinatura (Stripe → webhook → acesso)

```
  USER                              SYSTEM
   │                                  │
   │  👆 escolhe plano                 │
   │ ─────────────────────────────►   │
   │                                  │  [Stripe Checkout]  ✓
   │                                  │  [Webhook recebido] ⚙️
   │                                  │    SubscriptionWebhookController@handleWebhook
   │                                  │    ❌ assinatura não verificada antes do morph switch
   │                                  │    [Cashier] morph customer ← metadata.model
   │                                  │    [DB] subscription persistida
   │                                  │
   │   "Acesso liberado"              │
   │ ◄────────────────────────────────│
   │                                  │
   │  👆 acessa página protegida       │
   │ ─────────────────────────────►   │
   │                                  │  [RedirectCompany/UserIfNotSubscribed]
   │                                  │  ❌ estado past_due / canceled / trialing sem teste
```

Passos sem cobertura: verificação de assinatura do webhook, idempotência,
metadata maliciosa, estados `past_due`/`canceled`/`trialing` no middleware.

### Fluxo 4.2 — Agendamento → Sync Calendar

```
  [Employee]       [System]                 [Google]
      │                │                       │
      │ 👆 agenda      │                       │
      │ ─────────────► BookAppointmentAction   │
      │                ❌ não valida company_id│
      │                ──► state=Pending       │
      │                                         │
      │                AssignConsultantAction  │
      │                ──► state=Scheduling    │
      │                                         │
      │                AppointmentSchedulingStep│
      │                ──► dispatch            │
      │                  CreateAppointmentCalendarEventJob
      │                                         │
      │                [Job tries=3, backoff [10,60,300]]
      │                ─────────────────────────►
      │                                        createEvent
      │                ◄───────────────────────
      │                  google_event_id + meet_url
      │
      │ 👆 cancela
      │ ─────────────► AbstractAppointmentStep::cancel
      │                ❌ sem guarda para já-cancelled
      │                ──► DeleteAppointmentCalendarEventJob
      │                     └─ 410 Gone tratado?
```

Passos sem cobertura: tenancy em Book/GetAvailable, double-cancel,
race local vs remoto, 410 Gone no delete (idempotência), 401/403 invalid_grant.

### Fluxo 4.3 — Onboarding externo (API tenant)

```
  [3rd-party]  ───POST /api/v1/tenants/{slug}/users───►  [Laravel]
                header: X-Tenant-Token: <chave>
                                                        VerifyTenantTokenMiddleware
                                                        ❌ sem teste: 401 sem header
                                                        ❌ sem teste: 403 token inválido
                                                        ❌ sem teste: 403 token cross-tenant
                                                        ✅ 200 válido (indireto no teste existente)
                                                        UsersController@store
                                                        → CreateExternalUserAction
```

### Fluxo 4.4 — Onboarding do usuário (Anamnese)

```
  [User]  ──login──►  AppPanelProvider
                      └─ middleware RedirectIfAnamneseNotCompleted ❌ sem teste
                         ├─ anamnese feita → Dashboard
                         └─ não feita → AnamneseWizardPage ❌ multi-step sem teste
                               ├─ step 1 (life moment)
                               ├─ step 2 (motivations)
                               └─ submit → SaveAnamneseAction ❌ sem teste
```

### Fluxo 4.5 — Importação em massa

```
  CSV/XLSX ─► ParseUsersFromFileAction ─► ValidateUserImportAction ─► PersistImportedUsersAction
                    ❌                              ❌                            ⚠ parcial
                                                                                 ↓ chunk
                                                                                 create User + attach company + mail
                                                                                 ❌ rollback parcial não testado
```

### Fluxo 4.6 — Consultant onboarding (Observer chain)

```
  Admin cria Consultant
    │
    ▼
  ConsultantObserver@created        ❌ sem teste
    ├─ firstOrCreate User (password = email !!)
    ├─ consultant.user().associate().save()
    └─ event(UserRegistered, Consultant)
                │
                ▼
              SendWelcomeEmailListener  ❌ branch blank($email) sem teste
              AttachUserToDefaultCompanyListener  ❌ sem teste
              NotifyAdminsOfUserRegisteredListener  ⚠ parcial
```

---

## 5. Quick wins

Itens de esforço **P** com alto valor imediato:

1. **Factories faltantes** (`SubscriptionFactory`, `TenantMemberFactory`) — reduz
   duplicação nos helpers do `tests/Pest.php`.
2. **`SendWelcomeEmailListener:13`** — um teste cobrindo branch `if (blank($email))`.
3. **`VerifyTenantTokenMiddleware:17`** — 3 testes isolados (401/403/200).
4. **`AssignRoleAction:14`** (panel-admin) — 1 teste de visibilidade SuperAdmin + 1 de duplicação.
5. **`Roles::getColor()/getLabel()`** — Pest dataset com cada case do enum.
6. **Assinatura de eventos nos Steps** — asserts com `Event::fake` em `Scheduling/Completed/Cancelled`.

---

## 6. Recomendações estruturais

### 6.1 Bootstrap de testes (`tests/Pest.php`)

Adicionar por grupo ou global opt-in:

- `Http::preventStrayRequests()` global e `Http::fake()` nos grupos de integração
  (`@group google`, `@group stripe`, `@group resend`).
- `Queue::fake()` opt-in em testes que só precisam assertar despacho.
- `Notification::fake()` / `Mail::fake()` em grupos `@group notifications`.
- Helpers adicionais: `actingAsCompanyManager`, `actingAsConsultantOnPanel($panel)`.

### 6.2 Arch tests (Pest 4)

Candidatos em `tests/Arch/`:

- `arch('filament não contém regras de negócio')` →
  `expect('app-modules/*/src/Filament')->not->toUse(['DB', 'Illuminate\\Database\\Eloquent\\Builder'])`
  forçando delegação a `Actions/`.
- `arch('actions entre módulos não importam modelos de outro módulo')` →
  `expect('app-modules/*/src/Actions')->not->toUse('TresPontosTech\\*\\Models\\*')` exceto os do próprio módulo.
- `arch('queries com company_id')` → validação via grep + asserts sobre arquivos
  que usam `Consultant::`, `Appointment::`, `Company::` em `src/Filament/**`.
- `arch('repositories implementam interface')` → `expect(...\\Repositories\\Eloquent*)
  ->toImplement(...\\Repositories\\*Repository)`.

### 6.3 Grupos Pest

Adotar `@group tenancy`, `@group webhook`, `@group state-machine`,
`@group google`, `@group stripe`, `@group resend` para permitir execução focada:
`./vendor/bin/pest --parallel --group=tenancy`.

### 6.4 Policies por painel

Todo teste de Policy deve rodar em cada painel pertinente via
`filament()->setCurrentPanel($panel)` + `actingAs($user)`, validando o mesmo
método em Admin vs App vs Company vs Consultant quando aplicável.

### 6.5 Stripe webhook

- Testar com `Http::fake()` simulando assinatura do header Stripe e payload JSON
  (`customer.subscription.created`, `.updated`, `.deleted`, `invoice.payment_failed`).
- Testar metadata ausente, `metadata.model` inválido/inexistente no morph map.
- Testar idempotência: dois POSTs com mesmo `event.id` não devem duplicar subscription.

---

## 7. Design Patterns envolvidos

- **Template Method** (`AbstractAppointmentStep:15`): contrato `handle =
  processStep + notify` com override por concreto. Testes devem cobrir cada
  implementação (`Strategy`) + `cancel()` fora de ordem.
- **Observer** (`ConsultantObserver`, `MediaObserver`): Laravel aplica via
  `#[ObservedBy]`. Testar side-effect direto; `Event::fake` só para eventos de domínio.
- **Command** (Jobs): decidir por `Bus::fake` (apenas dispatch) vs
  `Queue::fake`/sync queue (executar) conforme o objetivo.
- **Repository** (billing): Actions dependem da interface `PlanRepository`.
  Testes de Actions podem mockar a interface; repo Eloquent recebe teste próprio
  em Feature com `RefreshDatabase`.
- **Webhook/Adapter** (Stripe): teste o adapter (`SubscriptionWebhookController`)
  com payloads fake, não a SDK.

Alternativa descartada: cobrir Filament por browser (Dusk/Pest Browser) — o projeto
não adota; `livewire()`/`Livewire::test()` cobre lógica reativa com rapidez.

---

## 8. Anexos — comandos

```bash
# executar tudo (paralelo, sem browser)
make test

# focar em um módulo
./vendor/bin/pest --parallel --filter=Appointments
./vendor/bin/pest --parallel --testsuite=Modules

# rodar só os arquivos alterados
./vendor/bin/pest --dirty

# grupos sugeridos (após adicionar os atributos @group)
./vendor/bin/pest --group=tenancy
./vendor/bin/pest --group=webhook
./vendor/bin/pest --group=state-machine
./vendor/bin/pest --group=google

# qualidade
make check        # rector + pint + pest
make phpstan      # Larastan level max

# ouvir webhook Stripe local (integração manual)
make stripe-listen
```

---

## 9. Ambiguidades (❓ a validar com o time)

- `EloquentPlanRepository::getPlansFor()` usa cache **global** por `type`
  (15 min). Se existem planos restritos a companies específicas, isso é bug;
  se planos são globais, está correto mas vale documentar no teste.
- `Company` model — se há global scope de tenancy aplicado em outros módulos,
  precisa ser verificado e testado. A auditoria não encontrou `BootsTenantScope`
  ou trait equivalente.
- `ConsultantObserver` define `password = $consultant->email`. É intencional
  para fluxo de reset? Precisa confirmação antes de testar "persistência de
  credenciais fracas" como bug vs feature.

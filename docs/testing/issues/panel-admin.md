## Contexto

Painel administrativo global (SuperAdmin/Admin). Cobertura atual: 4 testes Feature (CRUD Company, ViewAppointment, EditUserProfile). Ampla superfície Filament sem teste: filtros reativos da `AppointmentResource`, Resources de Consultant/Plan/Price/ContractualPlan, widgets `Metrics/*`, action `AssignRoleAction`, listeners de notificação para admins.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#panel-admin`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/panel-admin/src/Filament/Resources/Permissions/Actions/AssignRoleAction.php:14` — valida duplicidade, `visible()` só para SuperAdmin; sem teste — Feature — P

### 🟠 Alto
- [ ] `app-modules/panel-admin/src/Filament/Resources/Appointments/Tables/AppointmentsTable.php` — filtros `live(debounce: 500)` por user/consultant/status/company_id/date_range — testar combinações — Feature — M
- [ ] `app-modules/panel-admin/src/Filament/Resources/Appointments/Schemas/AppointmentForm.php` — `live()` + `afterStateUpdated` em `appointment_at` recarrega consultants — Feature — M
- [ ] `app-modules/panel-admin/src/Filament/Resources/Consultants/ConsultantResource.php` — Tabs `hidden(fn ($operation))`, SpatieMediaLibraryFileUpload — sem nenhum teste do Resource — Feature — M
- [ ] `app-modules/panel-admin/src/Filament/Widgets/AppointmentsStatsOverview.php:23,28` — `syncFilters()` via `#[On('appointments-table-filters-changed')]` + `getStats()` com `selectRaw count() filter` — Feature — M
- [ ] `app-modules/panel-admin/src/Listeners/NotifyAdminsOfAppointmentBookedListener.php:17` (e 3 irmãos) — validar destinatários e idempotência; hoje coberto parcialmente por `tests/Feature/Listeners/AdminNotificationsTest.php` — Feature — M

### 🟡 Médio
- [ ] `app-modules/panel-admin/src/Filament/Resources/Companies/Schemas/CompanyForm.php` — slug gerado reativo (`live(onBlur,debounce:500)`) — Feature — P
- [ ] `app-modules/panel-admin/src/Filament/Resources/ContractualPlans/*` — CRUD sem teste — Feature — P
- [ ] `app-modules/panel-admin/src/Filament/Resources/Plans/*` — CRUD sem teste (há teste em `billing` para o mesmo Resource — confirmar se duplicata) — Feature — P
- [ ] `app-modules/panel-admin/src/Filament/Widgets/Metrics/*` (5 widgets) — queries agregadas sem teste — Feature — M
- [ ] `app-modules/panel-admin/src/Actions/GetAdminUsersAction.php` — retorna só Admin/SuperAdmin — Unit — P
- [ ] `app-modules/panel-admin/src/Policies/BetterMailPolicy.php` / `InboundWebhookPolicy.php` — Feature — P

### 🟢 Baixo
- [ ] `app-modules/panel-admin/src/Filament/Widgets/QuickActions.php:21` — `getViewData()` — Feature — P

## Fluxos de usuário impactados

- **Admin global** — gestão de planos, roles, consultants.
- **Notificações admin** — eventos Appointment e UserRegistered.

## Pontos críticos específicos

- [ ] Authorização: ações restritas a SuperAdmin/Admin
- [ ] Filtros reativos não quebram com combinações diferentes
- [ ] Listeners enviam notificação apenas para admins e não duplicam

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#panel-admin`

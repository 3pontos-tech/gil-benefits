## Contexto

Módulo central de agendamento. Contém a state machine (Draft → Pending → Scheduling → Active → Completed / Cancelled), pipeline de `AppointmentRecord` (prontuário via IA) e integração com `integration-google-calendar`. Cobertura atual boa em steps e actions principais, mas pontos críticos de tenancy por `company_id` e edges da state machine permanecem sem teste.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#appointments`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/appointments/src/Actions/BookAppointmentAction.php:11` — não valida se `payload->userId` pertence à company do tenant atual; permite agendar para user de outro tenant — Feature — P
- [ ] `app-modules/appointments/src/Actions/GetAvailableConsultantsAction.php:14` — `Consultant::all()` global (sem escopo por company); vaza consultants de outros tenants — Feature — P
- [ ] `app-modules/appointments/src/Actions/GetAvailableSlotsAction.php:10` — sem teste dedicado; confirmar isolamento por company — Feature — P

### 🟠 Alto
- [ ] `app-modules/appointments/src/Actions/StateMachine/AbstractAppointmentStep.php:32` — `cancel()` sem guarda para appointment já cancelado; gera dupla notificação + dispatch redundante de `DeleteAppointmentCalendarEventJob` — Feature — P
- [ ] `app-modules/appointments/src/Jobs/GenerateAppointmentRecordJob.php:55` — exercitar `Redis::throttle` (rate limit) e falha do provedor IA (Prism) — Feature — M
- [ ] Eventos `AppointmentBooked/Completed/Cancelled` — assertar despacho em cada step via `Event::fake` — Feature — P

### 🟡 Médio
- [ ] `app-modules/appointments/src/Actions/StateMachine/AppointmentDoneStep.php` — sem teste específico — Feature — P
- [ ] `app-modules/appointments/src/Policies/AppointmentRecordPolicy.php` — testar por painel (`filament()->setCurrentPanel`) Admin vs Consultant — Feature — P

## Fluxos de usuário impactados

- **Agendamento** — passos de tenancy e double-cancel sem cobertura (ver fluxo 4.2 do relatório).
- **Prontuário** — resiliência a falha da IA sem teste.

## Pontos críticos específicos

- [ ] Tenancy por `company_id` validada em Book/Assign/GetAvailable
- [ ] State machine: transição inválida + dupla cancelamento
- [ ] Policy por painel via `actingAs($u)` + `setCurrentPanel`
- [ ] Dispatch de `DeleteAppointmentCalendarEventJob` condicionado a `google_event_id`

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#appointments`

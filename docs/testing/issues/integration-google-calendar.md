## Contexto

Integração com Google Calendar via service account (JWT Bearer). Client wrapper com 4 métodos (`getAccessToken`, `listEvents`, `createEvent`, `deleteEvent`), 6 actions e 3 jobs com retry [10, 60, 300]. `Create/Delete` actions e seus jobs estão cobertos; sync cíclico (10 min) e helpers de BlockedSchedule (Zap) sem teste.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#integration-google-calendar`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/integration-google-calendar/src/Actions/SyncConsultantCalendarAction.php:18` — orquestra paginação (`nextPageToken`), upsert de BlockedSchedule, remoção de cancelled e stale cleanup. Testar: idempotência em re-execução, paginação com ≥2 páginas, evento cancelled no meio da página — Feature — G

### 🟠 Alto
- [ ] `app-modules/integration-google-calendar/src/Actions/UpsertBlockedScheduleAction.php:13` — conversão `GoogleEventDTO` → BlockedSchedule (Zap); all-day, multi-day, conflito com `Appointment` existente — Feature — M
- [ ] `app-modules/integration-google-calendar/src/Jobs/SyncConsultantCalendarJob.php:24` — retry 3x, backoff [10,60,300]; testar sucesso, `retryable=false` não relança, `retryable=true` relança — Feature — M
- [ ] `app-modules/integration-google-calendar/src/GoogleCalendarClient.php:20,45,84,103` — exercitar 401/403 `invalid_grant` (retryable=false), 429 quota (retryable=true), 410 Gone silencioso no delete, paginação com `nextPageToken` — Unit/Feature — M

### 🟡 Médio
- [ ] `app-modules/integration-google-calendar/src/Actions/RemoveCancelledGoogleEventAction.php:13` — remove blocked schedule por `google_event_id` — Feature — P
- [ ] `app-modules/integration-google-calendar/src/Actions/RemoveStaleBlockedSchedulesAction.php:12` — apaga blocked schedules não sincronizados — Feature — P

### 🟢 Baixo
- [ ] Schedule `.everyTenMinutes()` do command `google-calendar:sync` — assertar registration — Feature — P

## Fluxos de usuário impactados

- **Agendamento → Calendar** — sync de bloqueios, criação e cancelamento (ver fluxo 4.2 do relatório).

## Pontos críticos específicos

- [ ] Idempotência de sync em re-execução com mesmos eventos
- [ ] Caminhos de erro externo: 401/403/410/429/quota
- [ ] `Http::fake()` com respostas JSON Google em `tests/Pest.php` (`@group google`)
- [ ] Paginação com `nextPageToken` sem loop infinito

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#integration-google-calendar`

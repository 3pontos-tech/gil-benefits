## Contexto

Painel do usuário final (employee com subscription ativa). Usa tenancy por `Company` e requer subscription. Cobertura atual: 2 testes (registration, list documents). Middleware de anamnese, wizard multi-step, ações de feedback e widgets do dashboard não têm testes.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#panel-app`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/panel-app/src/Http/Middleware/RedirectIfAnamneseNotCompleted.php:15` — sem anamnese → redireciona ao wizard. Cenários: user já fez, user em rota permitida, user sem `Detail`, user em rota `stripe/*` — Feature — P

### 🟠 Alto
- [ ] `app-modules/panel-app/src/Filament/Pages/AnamneseWizardPage.php:40,45,113` — `mount`/`form`/`submit` multi-step; `isTenantSubscriptionRequired() = false` — navegação, validação, persistência via `SaveAnamneseAction` — Feature — G
- [ ] `app-modules/panel-app/src/Filament/Actions/FeedbackAction.php:19` — `visible()`: status=Completed && sem feedback; StarRating — Feature — P
- [ ] `app-modules/panel-app/src/Filament/Pages/UserSubscriptionPage.php` — página de assinatura sem teste — Feature — M
- [ ] `app-modules/panel-app/src/Filament/Resources/Appointments/Schemas/AppointmentWizard.php` — Wizard 3 steps com `reactive()` + `afterStateUpdated` — Feature — M

### 🟡 Médio
- [ ] `app-modules/panel-app/src/Filament/Actions/ViewAppointmentRecordAction.php` — Feature — P
- [ ] `app-modules/panel-app/src/Filament/Pages/UserDashboard.php` — renderização + autorização — Feature — P
- [ ] `app-modules/panel-app/src/Filament/Widgets/AppointmentHistoryWidget.php` / `LatestAppointmentWidget.php` / `UserAccountWidget.php` — queries escopadas por tenant — Feature — P

### 🟢 Baixo
- [ ] `app-modules/panel-app/src/Filament/Forms/Components/StarRating.php` — render + estado — Unit/Feature — P

## Fluxos de usuário impactados

- **Onboarding** — anamnese obrigatória (ver fluxo 4.4 do relatório).
- **Agendamento** — wizard multi-step (fluxo 4.2).
- **Feedback** — após consulta Completed.

## Pontos críticos específicos

- [ ] Middleware de anamnese não bloqueia rotas permitidas
- [ ] Wizard persiste dados entre steps
- [ ] Widgets escopam por tenant atual (`filament()->getTenant()`)

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#panel-app`

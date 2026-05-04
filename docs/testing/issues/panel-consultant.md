## Contexto

Painel do consultant com Resources de Appointments (view-only) e Documents. Cobertura atual: 4 testes (Upload/List Document, List/View Appointments). Ações customizadas (prontuário, share, download) e RelationManager de documentos compartilhados sem teste.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#panel-consultant`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🟠 Alto
- [ ] `app-modules/panel-consultant/src/Filament/Actions/CreateAppointmentRecordAction.php:25` — FileUpload PDF/DOCX max 10MB + `authorize()` via Gate + dispatch `CreateAppointmentRecordFromUploadAction` — Feature — M
- [ ] `app-modules/panel-consultant/src/Filament/Actions/ShareDocumentFilamentAction.php:24` — compartilha documento com employee; autorização e validação — Feature — M
- [ ] `app-modules/panel-consultant/src/Filament/Actions/ReviewAppointmentRecordAction.php` — revisão/publicação do prontuário — Feature — M
- [ ] `app-modules/panel-consultant/src/Filament/Resources/Documents/RelationManagers/SharedDocumentRelationManager.php:35` — toggle active/deactivate, Delete, Restore, Bulk ForceDelete — Feature — M

### 🟡 Médio
- [ ] `app-modules/panel-consultant/src/Filament/Actions/DownloadDocumentFilamentAction.php` — Feature — P
- [ ] `app-modules/panel-consultant/src/Filament/Actions/ViewPreviousRecordSummaryAction.php` — Feature — P
- [ ] `app-modules/panel-consultant/src/Filament/Pages/ConsultantDashboard.php`, `ConsultantSchedule.php`, `EditConsultantProfile.php` — Feature — M
- [ ] `app-modules/panel-consultant/src/Filament/Widgets/ConsultantAppointmentHistoryWidget.php`, `ConsultantLatestAppointmentWidget.php`, `ConsultantStatsOverview.php` — Feature — P

## Fluxos de usuário impactados

- **Prontuário** — upload + geração de draft + publicação.
- **Document share** — compartilhamento + toggle + download.

## Pontos críticos específicos

- [ ] Autorização Gate antes do dispatch de Actions
- [ ] Validação de FileUpload (tipo e tamanho)
- [ ] Soft delete awareness no RelationManager
- [ ] Scopeamento: consultant só vê seus próprios documentos/appointments

## Critério de aceite

- Todos os itens 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#panel-consultant`

## Contexto

Módulo `user` contém pipeline de importação em massa (Parse → Validate → Persist), listener de welcome email, job de importação e controller de download de template CSV. `ImportUsersFromFileAction` tem teste composto, mas sub-actions, job e listener não têm testes isolados.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#user`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/user/src/Actions/PersistImportedUsersAction.php:22` — `chunk` cria Users + attach company + envia welcome mail; testar rollback parcial em falha no meio do chunk — Feature — M

### 🟠 Alto
- [ ] `app-modules/user/src/Actions/ParseUsersFromFileAction.php:10` — CSV/XLSX; colunas extras, headers com caixa diferente, linhas em branco, encoding — Feature — P
- [ ] `app-modules/user/src/Actions/ValidateUserImportAction.php:20` — REQUIRED_COLUMNS + duplicatas cross-company — Feature — P
- [ ] `app-modules/user/src/Jobs/ImportUsersJob.php:26` — job com timeout 600s; testar execução e falha — Feature — P
- [ ] `app-modules/user/src/Filament/Actions/ImportUsersAction.php` — Filament Action com FileUpload (teste via Livewire) — Feature — M

### 🟡 Médio
- [ ] `app-modules/user/src/Actions/SaveAnamneseAction.php:19` — persistência de `UserAnamnese` com `LifeMoment` cast e `motivations` — Feature — P

### 🟢 Baixo
- [ ] `app-modules/user/src/Listeners/SendWelcomeEmailListener.php:13` — branch `if (blank($email))` sem teste — Feature — P
- [ ] `app-modules/user/src/Http/Controllers/DownloadImportTemplateController.php:9` — headers do CSV esperados — Feature — P

## Fluxos de usuário impactados

- **Importação em massa** — Parse/Validate/Persist (ver fluxo 4.5 do relatório).
- **Welcome email** — branch de email vazio.

## Pontos críticos específicos

- [ ] Tenancy: `PersistImportedUsersAction` não cria Users em company errada
- [ ] Rollback transacional em chunk com erro

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#user`

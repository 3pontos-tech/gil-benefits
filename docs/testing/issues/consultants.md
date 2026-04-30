## Contexto

Módulo de consultants gerencia perfis, documentos e compartilhamentos. Usa `ConsultantObserver` para criar User automaticamente e disparar `UserRegistered`, além de `MediaObserver` para validar uploads. CRUD Filament coberto; side-effects dos observers e policies sem teste.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#consultants`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/consultants/src/Observers/ConsultantObserver.php:12` — `created()` faz `firstOrCreate` em User com `password = $consultant->email` (texto plano do email). Testar: criação nova, reutilização de user existente por email, despacho do evento `UserRegistered` com `Roles::Consultant`, segurança do password (❓ a validar com o time se é intencional) — Feature — P

### 🟠 Alto
- [ ] `app-modules/consultants/src/Observers/MediaObserver.php:11` — valida extensão do media em collection `documents`; testar rejeição de extensão inválida, aceitação das válidas, modelos fora da collection — Feature — P
- [ ] `app-modules/consultants/src/Actions/UpsertDocumentShareAction.php:12` — `updateOrCreate(DocumentShare)`; testar criação nova, atualização de existente, toggle `active` — Feature — P
- [ ] `app-modules/consultants/src/Policies/DocumentPolicy.php` — isolamento por consultant dono + company do share; testar viewAny/view por painel — Feature — P

### 🟡 Médio
- [ ] `app-modules/consultants/src/Policies/ConsultantPolicy.php` — `viewAny`/`view` por painel (Admin vs Consultant vs Company) — Feature — P

## Fluxos de usuário impactados

- **Consultant onboarding** — side-effects do observer (ver fluxo 4.6 do relatório).
- **Document share** — upsert + toggle active.

## Pontos críticos específicos

- [ ] Idempotência do observer ao recriar consultant com mesmo email
- [ ] `UserRegistered` dispatch disparado corretamente
- [ ] RBAC por painel em policies
- [ ] Validação de extensão de arquivo (MediaLibrary)

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#consultants`

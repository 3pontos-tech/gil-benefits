## Contexto

Módulo responsável pelo agregado Company (tenant de 1º nível). O módulo **não possui diretório `tests/`** — toda lógica de criação, vinculação e fluxo de onboarding de empresa está sem teste dedicado.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#company`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/company/src/Actions/CreateCompanyAction.php:12` — cria Company + attach user + assign role `CompanyOwner`. Testar: cenário feliz, rollback em falha de attach, idempotência em chamada repetida, violação de slug único — Feature — M

### 🟠 Alto
- [ ] `app-modules/company/src/Actions/AttachToDefaultCompany.php:12` — `firstOrCreate('flamma-company')` + attach + role. Cenários: company já existe, user já vinculado, role duplicada, role diferente — Feature — P
- [ ] `app-modules/company/src/Listeners/AttachUserToDefaultCompanyListener.php:12` — reage a `UserRegistered`; testar com cada role do enum — Feature — P

### 🟡 Médio
- [ ] `app-modules/company/src/Models/Company.php` — se houver global scopes de tenancy, adicionar assertions — Feature — P

## Fluxos de usuário impactados

- **Onboarding** — registro de user externo + attach à `flamma-company` (fluxo default) ou a Company própria (CompanyOwner) não testado.

## Pontos críticos específicos

- [ ] Transação consistente em `CreateCompanyAction` (owner + role + subscription)
- [ ] Listener não duplica role ao reexecutar o evento
- [ ] `flamma-company` seed/idempotência

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#company`

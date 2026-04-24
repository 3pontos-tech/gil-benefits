## Contexto

Painel do CompanyOwner (admin do tenant). Tem tenancy por `Company`. Cobertura atual: 3 testes Feature (CreateAndAttach, EditCompany, RegisterCompany). Widgets cobertos pelo módulo `tenant`. Faltam ações de administração do tenant.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#panel-company`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🟠 Alto
- [ ] `app-modules/panel-company/src/Filament/Actions/TenantSeatsCounterAction.php:17` — validar contagem correta de seats utilizados/disponíveis — Feature — P
- [ ] `app-modules/panel-company/src/Filament/Actions/TenantSecretKeyRotationPanelAction.php:18` — executa `TenantSecretKeyRotationAction`; testar autorização + rotação efetiva — Feature — P

### 🟡 Médio
- [ ] `app-modules/panel-company/src/Filament/Pages/Tenancy/RegisterTenant.php` — coberto parcialmente por `RegisterCompanyTest`; validar isolamento de owner vs employee existente — Feature — P

## Fluxos de usuário impactados

- **Tenant admin** — rotação de chave de integração + contagem de seats.
- **Onboarding** — registro de tenant com owner existente.

## Pontos críticos específicos

- [ ] Autorização: só CompanyOwner pode rodar ações administrativas
- [ ] Rotação de chave não corrompe estado
- [ ] Contagem de seats reflete subscription + users attached

## Critério de aceite

- Todos os itens 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#panel-company`

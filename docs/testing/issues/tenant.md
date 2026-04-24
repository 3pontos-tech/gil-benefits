## Contexto

Módulo de tenant cobre dashboard/pages/widgets do painel Company e a API v1 externa (`/api/v1/tenants/{tenant}/users`) protegida por `VerifyTenantTokenMiddleware`. Widgets têm bons testes; middleware, action de rotação de chave e policy sem teste dedicado.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#tenant`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🔴 Crítico
- [ ] `app-modules/tenant/src/Http/Middleware/VerifyTenantTokenMiddleware.php:17` — testar: 401 sem header `X-Tenant-Token`, 403 com token inválido, 403 quando token é válido mas aponta para **outro tenant** na URL, 200 com caso feliz — Feature — P

### 🟠 Alto
- [ ] `app-modules/tenant/src/Actions/TenantSecretKeyRotationAction.php:8` — `generate(Company)` gera UUID e chama `generateToken()`; testar rotação efetiva e invalidação da chave anterior — Feature — P

### 🟡 Médio
- [ ] `app-modules/tenant/src/Policies/CompanyPolicy.php:12` — `viewAny/view/create` por painel; cenários negativos para cada role — Feature — P

### 🟢 Baixo
- [ ] `app-modules/tenant/database/factories/` — adicionar `TenantMemberFactory` — Unit — P

## Fluxos de usuário impactados

- **API externa** — 3rd-party com token de integração (ver fluxo 4.3 do relatório).

## Pontos críticos específicos

- [ ] Isolamento por tenant (token de tenant A não pode acessar tenant B)
- [ ] Rotação de chave invalida chamadas com chave antiga
- [ ] Policy por painel

## Critério de aceite

- Todos os itens 🔴 e 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#tenant`

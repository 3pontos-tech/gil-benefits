## Contexto

Módulo RBAC sobre `spatie/laravel-permission`, com comando `sync:permissions` que orquestra permissions dinâmicas a partir de `PermissionsEnum::buildPermissionFor()`. Command principal e policy do Role cobertos; sub-helpers e o enum `Roles` com métodos `match` sem teste.

## Objetivo

Fechar gaps identificados na auditoria (`docs/testing/coverage-gap-report.md#permissions`).

**Fora de escopo**: browser, DTOs, Enums sem lógica, código já coberto por PHPStan level max / Pint / Rector.

## Gaps priorizados

### 🟠 Alto
- [ ] `app-modules/permissions/src/Commands/SyncPermissions/SyncPermissionsCommand.php:54,72,122,161` — testar **idempotência**: rodar 2x e garantir mesmo estado; mudança de config entre runs; remoção/renomeação de permission — Feature — M

### 🟡 Médio
- [ ] `app-modules/permissions/src/Commands/SyncPermissions/ModelPayload.php` / `RolePermissions.php` — helpers com lógica de match por role — Unit — P
- [ ] `app-modules/permissions/src/RolePolicy.php` — cenários negativos para os 6 roles não-SuperAdmin — Feature — P

### 🟢 Baixo
- [ ] `app-modules/permissions/src/Roles.php:25,38` — `getColor()` e `getLabel()` com `match` — Pest dataset com cada case — Unit — P

## Fluxos de usuário impactados

- **Bootstrap RBAC** — sincronização após deploy; consistência entre ambientes.

## Pontos críticos específicos

- [ ] Idempotência do `sync:permissions`
- [ ] RolePolicy nega acesso para role não-SuperAdmin em todos os painéis

## Critério de aceite

- Todos os itens 🟠 concluídos.
- `make test` verde.
- `make phpstan` sem regressão.
- `make check` verde no CI.

## Referências

- Relatório: `docs/testing/coverage-gap-report.md#permissions`

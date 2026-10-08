# Plano de execução — API v1 do colaborador

> Companheiro executável de `docs/epicos-api.md` (o quê e por quê) e da página de especificação com os snippets
> (https://claude.ai/artifact/F6pLfrvfMPe4rqcegTzwvs, o como). Este documento diz **em que ordem, com que gate e com que
> entregável** cada passo acontece. Marque as caixas conforme avança; ele é o checklist do PR.
>
> Escrito em 2026-10-07. Decisões já tomadas (2026-10-06): Sanctum, token de 90 dias, acesso por vínculo ativo, senha atual
> obrigatória, cancelamento com paridade ao painel, S12 e #285 fora da v1, URLs sem prefixo, um PR em `develop`.

## 0. Resumo

| | |
| --- | --- |
| **Objetivo** | O app troca as fixtures pela API real só mudando `EXPO_PUBLIC_API_URL`. |
| **Escopo** | Módulo `app-modules/api`, 28 endpoints em `/api/v1`, 3 migrations aditivas, 3 actions extraídas do painel, testes Pest por endpoint e de contrato, README. Ajustes correspondentes no app. |
| **Fora de escopo** | "Encontro sem data" e thread de chamados (S12); "Pedir mais créditos" (#285); push notifications; API de tenant migrando para o módulo (dívida registrada). |
| **Repositórios** | `gil-benefits` (branch `feat/api-v1` a partir de `develop`) e `gil-benefits-mobile` (`main`, commits só após teste no emulador). |
| **Entrega** | Um PR em `develop`, commits na ordem das stories, cada um verde no `make check`; release **MINOR** pela régua do `RELEASING.md`. |
| **Estimativa** | ~17 dias de desenvolvimento no backend + ~2 no app + ~2 de homologação e release, para uma pessoa. Detalhe por fase abaixo. |

## 1. Pré-requisitos (meio dia)

- [ ] `develop` atualizado e `make check` verde antes de qualquer mudança (linha de base).
- [ ] Ambiente local sobe (`make env-up` ou `php artisan serve --host=0.0.0.0 --port=8000`) com Postgres; `.env` com `FILESYSTEM_DISK`/`MEDIA_DISK` apontando para um disco que gere URL temporária (R2 de dev ou `local` com `php artisan storage:link` para testar sem R2).
- [ ] Credenciais de R2 de desenvolvimento disponíveis, ou decisão de testar materiais só com `Storage::fake('r2')` até a homologação.
- [ ] Branch: `git checkout develop && git pull && git checkout -b feat/api-v1`.
- [ ] Dependência aprovada: `composer require laravel/sanctum` e `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`. **Não** rodar `install:api`.
- [ ] Módulo: `php artisan make:module api --no-interaction`; `composer dump-autoload`; confirmar que `TresPontosTech\Api\Providers\ApiServiceProvider` aparece em `extra.laravel.providers` do `app-modules/api/composer.json`.
- [ ] App: `.env` com `EXPO_PUBLIC_API_URL=http://127.0.0.1:8000`; `adb reverse tcp:8000 tcp:8000` no emulador. Confirmar que, com a API ainda vazia, o app mostra o estado de erro com "Tentar de novo" (e não trava).
- [ ] Abrir o PR como **draft** já no primeiro commit, com este checklist colado na descrição; ele vai sendo marcado.

**Gate 0:** `php artisan route:list --path=api/v1` lista o grupo vazio sem erro; `make check` segue verde.

## 2. Fase 1 — Fundação, autenticação e perfil (S1–S3, ~3,5 dias)

### S1 · Fundação (1 dia)

- [ ] `bootstrap/app.php`: aliases `ability`/`abilities` do Sanctum (única mudança fora do módulo nesta story).
- [ ] `User`: trait `HasApiTokens`; método `canUseApp()` = vínculo ativo em `company_employees` com qualquer empresa.
- [ ] `config/api.php` (`token_ttl_days`, `quarter_goal`, `media_url_ttl_minutes`, `slots_cache_seconds`); `ApiServiceProvider` com config, lang, migrations e os rate limiters `api-employee` e `api-login`.
- [ ] Middlewares `ForceJsonAndLocale` e `EnsureAppAccess`.
- [ ] `routes/api-routes.php` com o grupo e uma rota protegida de fumaça (`GET /api/v1/me` já pode ser ela, devolvendo `MeResource` mínimo).
- [ ] `routes/console.php`: `Schedule::command('sanctum:prune-expired --hours=24')->daily()`.
- [ ] `tests/Pest.php`: `actingAsApiEmployee()` e `actingAsApiSubscriber()`.
- [ ] `lang/pt_BR/auth.php` do módulo (`no_access`).
- [ ] Testes: 401 sem token; 403 token sem ability; 403 admin sem vínculo; 200 assinante individual; 422 em português.

**Gate 1a:** `php artisan test --compact app-modules/api` verde; painel intacto (`php artisan test --compact app-modules/panel-app`).

### S2 · Autenticação (1 dia)

- [ ] `LoginRequest`, `LoginController` (422 em `email` para credencial ou conta sem acesso; um token por `device_name`; evento `Login` para o `RecordLastLogin`), `LogoutController` (revoga só o token atual).
- [ ] Throttle `api-login` na rota de login, fora do `api-employee`.
- [ ] Testes: login devolve token + `Me`; `last_login_at` gravado; senha errada; sem vínculo; 429 na sexta tentativa; logout não derruba outro aparelho; token expirado → 401 (viajar no tempo com `$this->travel(91)->days()`).
- [ ] **Verificação no app:** entrar com um usuário real do banco local; "Sair" no Perfil revoga o token (próxima abertura pede login).

### S3 · Perfil (1,5 dia)

- [ ] `MeResource` completo (empresa empregadora, departamento, `member_since`, `anamnese_completed`, `life_moment`, URLs temporárias). Helpers no `User`: `employerCompany()`, `departmentIn(Company)`.
- [ ] `UpdateMeRequest` (um campo por vez; e-mail exige `current_password:sanctum`; telefone E.164), `UpdateMeAction` no módulo `user` (users + `detail()->updateOrCreate`).
- [ ] `UpdatePasswordRequest` + `PasswordController` (revoga os outros tokens).
- [ ] `StoreAvatarRequest` + `AvatarController` (`user_avatar`, singleFile).
- [ ] Testes de contrato do `MeResource` e os casos de validação listados na S3 do épico.
- [ ] **Verificação no app:** Perfil mostra dados reais; editar nome/telefone; trocar e-mail e senha pedem a senha atual (o campo entra no app na Fase 5; até lá, validar por `curl`); foto sobe e volta como URL temporária.

**Gate 1b:** telas 24, 29, 30 e 42 do app funcionando contra o ambiente local (com o app ainda sem o campo "Senha atual", a troca de senha falha com 422 esperado: isso confirma a regra). Commits `feat(api): S1 …`, `S2 …`, `S3 …` no branch.

## 3. Fase 2 — Jornada, agenda e créditos (S4–S6, ~6,5 dias)

É a fase de maior risco porque mexe no fluxo do painel. A ordem abaixo protege o painel antes de tocá-lo.

### S4 · Jornada (1,5 dia)

- [ ] DTOs `QuarterProgress`, `HealthPoint`, `FocusItem`; `UserJourney` ganha `quarter`, `healthHistory`, `focus`.
- [ ] `BuildUserJourneyAction`: `quarter()`, `healthHistory()` (generalização de `healthScorePreviousMonth` para "score no fim do mês M"), `focus()`; cache `journey:{user}` de 1 h invalidado em encontro concluído, feedback criado e anamnese salva (listeners dos eventos existentes).
- [ ] `JourneyResource` + `JourneyController`.
- [ ] Testes de unidade das três funções (bordas de trimestre, seis meses em ordem, cada regra do foco) + contrato.
- [ ] **Verificação no app:** Home (tile 68, "n de 4 encontros no trimestre") e folha 39 com dados reais.

### S5 · Agenda (4 dias)

Ordem obrigatória:

1. [ ] **Testes de caracterização do painel** (meio dia): antes de extrair, garantir que `SchedulesAppointments`, `ReschedulesAppointments` e `CancelAppointmentAction` do `panel-app` têm testes cobrindo: agendar com cota, agendar com crédito, bloqueio sem saldo, slot inválido, reagendar dentro/fora da janela, cancelar cedo (crédito volta), cancelar tarde (`cancelled_late`, crédito perdido). Onde faltar, escrever o teste contra o comportamento atual.
2. [ ] `Appointment::canBeCancelled()`; `BookAppointmentAction::handle` passa a retornar `Appointment` (chamadas existentes ignoram o retorno; nada quebra).
3. [ ] Extrair para `appointments/src/Actions`: `ScheduleAppointmentForUserAction`, `RescheduleAppointmentAction`, `CancelAppointmentForUserAction`. Painel passa a delegar. Rodar os testes do passo 1: **têm de continuar verdes sem alteração**.
4. [ ] `AppointmentResource` (+ `MaterialSummaryResource` para `materials[]`, vazio até a S7), `StoreAppointmentRequest`, `RescheduleAppointmentRequest`, `AppointmentController`, `SlotController` (cache por dia), `FeedbackController` + `StoreFeedbackRequest`.
5. [ ] Testes da API (lista da S5 do épico) com `consultantAvailableOn()`; teste de contrato do `AppointmentResource`; 404 para encontro alheio.
6. [ ] **Verificação no app:** Agenda (10–14), detalhe (15–17), fluxo de agendamento (34–38) e reagendamento contra o local, com cota e crédito reais; cancelar um encontro a mais de 4 h devolve o crédito na tela de Créditos.

### S6 · Créditos (1 dia)

- [ ] `CreditsResource` + `CreditsController`; conferir os nomes reais em `ResolveQuotaAllowance`/`QuotaAllowance`/`QuotaCycle` (o snippet da página marca isso).
- [ ] Alinhar "disponíveis" com `notExpired()` (hoje `hasAvailableCredit()` e o widget do painel divergem): ajuste no widget + teste.
- [ ] Testes: `left` após agendar, `renews_at` no fim do ciclo, `owner_type`, expirados listados com status `expired`.
- [ ] **Verificação no app:** tela 25 e tile da Home.

**Gate 2:** suíte do `panel-app` verde após a extração; app opera agenda e créditos ponta a ponta no local. Commits `feat(appointments): extract …` (separado) e `feat(api): S4/S5/S6 …`.

## 4. Fase 3 — Materiais e notificações (S7–S8, ~3 dias)

### S7 · Materiais (2 dias)

- [ ] Migration `document_user_states`; model e `Document::states()`. (`appointment_document` saiu da v1: #296.)
- [ ] `UploadMaterialAction` no módulo `consultants`; `CreateSharedDocument` do painel passa a usá-la (teste de regressão).
- [ ] `MaterialResource`, `StoreMaterialRequest`, `MaterialController` (index/store/destroy), `MaterialStateController` (favorite/viewed).
- [ ] Testes da S7 + contrato. Upload em teste com `UploadedFile::fake()` e `Storage::fake('r2')`.
- [ ] **Verificação no app:** telas 18–23, favoritar com estrela cheia, "Enviar doc" pela Home e pelo segmento "Meus", remover só os próprios.

### S8 · Notificações (1 dia)

- [ ] `->viewData(['appointment_id' => …])` nos três envios ao colaborador; notificação de material compartilhado com `document_id`; notificação de ata publicada em `PublishAppointmentRecordAction` (fecha o TODO).
- [ ] `NotificationResource` (sobe os ids de `viewData`), `NotificationController` (`meta.unread` via `additional`, `markAsRead`).
- [ ] Testes: `meta.unread` cai ao ler; 404 para notificação alheia; payloads carregam os ids.
- [ ] **Verificação no app:** tela 27 abre encontro e material pelos ids reais; badge do sino.

**Gate 3:** materiais reais abrem no visualizador do aparelho pela URL temporária; notificações geradas por um agendamento feito no app aparecem com deep link.

## 5. Fase 4 — Anamnese, chamados, contrato e PR (S9–S11, ~3,5 dias)

### S9 · Anamnese (1 dia)

- [ ] Migration das colunas `nullable` (com `down()` que recusa se houver nulos).
- [ ] `UserAnamnese::isComplete()`; `RedirectIfAnamneseNotCompleted` e `MeResource` passam a usá-lo.
- [ ] `UpsertAnamneseAnswersAction` (merge parcial, `updateOrCreate` por `user_id`); `AnamneseResource`, `UpdateAnamneseRequest`, `AnamneseController`.
- [ ] Testes: parcial cria com nulos; completar liga a flag; enum inválido; regressão do wizard do painel.
- [ ] **Verificação no app:** tela 26 salva ao sair do campo e o progresso aparece em outro aparelho/emulador.

### S10 · Chamados (1 dia)

- [ ] `SupportTicketResource`, `StoreTicketRequest`, `UpdateTicketRequest` (`status` só `closed`), `TicketController` (`withoutGlobalScopes` + `user_id`, como o painel; docblock explicando).
- [ ] Testes: protocolo, job despachado (`Queue::fake()`), finalizar duas vezes, chamado alheio, `resolved` recusado.
- [ ] **Verificação no app:** tela 31, novo chamado, finalizar.

### S11 · Contrato, documentação e PR (1,5 dia)

- [ ] `tests/Feature/Contract/*ResourceTest.php` para Me, Journey, Appointment, Credits, Material, Notification, Anamnese, Ticket (chaves + formato de datas).
- [ ] `config/cors.php`: `api/*` liberado para `http://localhost:8081` (app no navegador, dev).
- [ ] `app-modules/api/README.md`: como rodar com o app, variáveis, comandos.
- [ ] `RELEASING.md`: linha sobre `/api/v1` (incompatível = MAJOR, aditiva = MINOR).
- [ ] Registrar a dívida da API de tenant (mover para `api/.../V1/Company` quando tocada) numa issue.
- [ ] `php artisan route:list --path=api/v1` confere 28 rotas com os middlewares certos; `make check` verde; Larastan do módulo no nível dos demais.
- [ ] PR sai de draft: descrição com dependência nova, as três migrations, "nenhuma env obrigatória", link para o épico e para a página de especificação, este checklist marcado.

**Gate 4:** suíte inteira verde no CI (`_pint`, `_rector`, `_phpstan`, `_pest`); revisão do PR; merge em `develop`.

## 6. Fase 5 — Ajustes no app (`gil-benefits-mobile`, ~2 dias)

Podem começar em paralelo à Fase 2; só a verificação final depende da API no ar.

- [ ] Tela 30: campo "Senha atual" (`current_password`) com o mesmo `FieldRow`/zod; tela 29: pedir senha atual ao trocar e-mail (pode ser um `AlertModal` com campo ou um passo na própria tela).
- [ ] `ApiAppointment` ganha `cancel_impact`; alerta de cancelamento troca o texto por impacto ("O crédito volta para a sua conta" / "Faltam menos de 4 h: este crédito será perdido"); snackbar idem.
- [ ] Biometria: destravar o token local (regra 4.2 do guideline passa a **Now**); remover `POST /v1/auth/biometric` do contrato e da fixture.
- [ ] Copy "Benefício oferecido pela {empresa}" com `company` = empresa padrão (assinante individual): texto neutro quando o slug for o padrão.
- [ ] Home: sugestão "Complete seu perfil financeiro" quando `anamnese_completed` for falso (decisão pendente: opção 1 recomendada).
- [ ] `docs/api-contract.md`: decisões 1, 6, 14; `cancel_impact`; biometria; `credits/request` estacionado (#285) — parte já feita.
- [ ] `.env.example` e CLAUDE.md: como apontar para o backend local (`adb reverse tcp:8000`).
- [ ] Teste no emulador contra a API local; só então commit (`feat:`), sem coautoria.

## 7. Fase 6 — Homologação e release (~2 dias)

- [ ] Deploy de `develop` no ambiente de homologação; `php artisan migrate --force` roda as três migrations.
- [ ] Build de homologação do app: `eas build --profile preview --platform android` com `EXPO_PUBLIC_API_URL` do ambiente; instalar no aparelho de teste.
- [ ] Roteiro de homologação (uma pessoa, ~2 h): entrar, editar perfil e foto, trocar senha, agendar, reagendar, cancelar cedo e tarde, ver créditos, abrir e favoritar material, enviar documento, notificações com deep link, anamnese parcial e completa, abrir e finalizar chamado, sair.
- [ ] Monitoramento: `EXPO_PUBLIC_SENTRY_DSN` no build de homologação; conferir que erros de API aparecem com e-mails e tokens mascarados.
- [ ] Release: PR `develop → main`, tag `vX.Y.0` (MINOR), notas com "dependência: laravel/sanctum; migrations: 3 aditivas; env: nenhuma; passo manual: nenhum".
- [ ] Pós-deploy: `sanctum:prune-expired` agendado aparece em `php artisan schedule:list`; logs sem 500 nas primeiras horas; app de produção apontado na build seguinte.

**Gate 5:** roteiro de homologação sem falha; release publicada; app em produção falando com a API.

## 8. Cadência e commits

Um commit por story, cada um verde no `make check`. Ordem:

```
feat(api): S1 scaffold the api module with Sanctum, access middleware and rate limits
feat(api): S2 add login and logout with device tokens
feat(api): S3 expose and update the employee profile, avatar and password
feat(api): S4 add quarter, history and focus to the journey
test(panel-app): characterise booking, rescheduling and cancelling before extraction
feat(appointments): extract schedule, reschedule and cancel actions from the panel
feat(api): S5 add appointments, slots, booking, rescheduling, cancelling and feedback
feat(api): S6 add the credits summary
feat(api): S7 add materials with per-user state and uploads
feat(api): S8 add notifications with deep-link ids
feat(api): S9 add the anamnese with progressive saving
feat(api): S10 add support tickets
docs(api): S11 contract tests, README and release notes
```

Rotina diária: `git pull --rebase origin develop` no início (o branch vive algumas semanas), `make check` antes de cada commit, atualizar o checklist do PR ao fechar cada story, e testar no app a tela correspondente antes de seguir.

## 9. Riscos e mitigação

| Risco | Sinal | Mitigação |
| --- | --- | --- |
| Extração das actions muda o comportamento do painel | Teste de caracterização quebra | Passo 1 da S5 antes de tocar; a extração é mover código, não reescrever. |
| `GetAvailableSlotsAction` por dia lento com muitos consultores | `/slots` acima de 1 s | Cache por dia (60 s); se persistir, `GetAvailableSlotsForMonthAction` lendo o Zap uma vez. |
| Nomes de `QuotaAllowance`/`QuotaCycle` diferentes do snippet | Larastan acusa | Conferir o arquivo antes de escrever o `CreditsResource`. |
| URL temporária falha em dev sem R2 | 500 em `avatar_url`/`file.url` | Disco `local` em dev com `temporaryUrl` suportado, ou `Storage::fake('r2')` nos testes e R2 só na homologação. |
| Tenant implícito (`Filament::getTenant()` nulo na API) | Cota errada para quem tem mais de uma empresa | `employerCompanyId()` como contexto único; teste com usuário em duas empresas. |
| Rate limit de login bloqueia o próprio time nos testes | 429 inesperado | `RateLimiter::clear` no `beforeEach` dos testes de login; em dev, `php artisan cache:clear`. |
| Branch longo diverge de `develop` | Conflitos no rebase | Rebase diário; o módulo é novo, conflitos ficam restritos a `User.php`, `bootstrap/app.php` e às actions extraídas. |

**Rollback:** tudo é aditivo. Reverter o PR remove rotas e módulo; as migrations têm `down()` (as tabelas novas caem; as colunas voltam a `NOT NULL` só se não houver nulos, e a de anamnese é a única que pode exigir limpeza manual).

## 10. Definition of Done da feature

- [ ] 28 rotas em `php artisan route:list --path=api/v1`, todas com teste Pest e, por resource, teste de contrato.
- [ ] `make check` e CI verdes; Larastan do módulo no nível dos demais módulos.
- [ ] Painel sem mudança de comportamento (suítes de `panel-app`, `panel-admin`, `appointments`, `credits`, `support` verdes).
- [ ] App na build de homologação completa o roteiro da Fase 6 com a API real.
- [ ] `docs/epicos-api.md`, `app-modules/api/README.md`, `RELEASING.md` e o contrato do app atualizados.
- [ ] Release MINOR publicada com notas; `sanctum:prune-expired` agendado em produção.
- [ ] Pendências registradas: #285 (créditos), S12 (sem data, thread), dívida da API de tenant, sugestão de anamnese na Home (se não entrar na Fase 5).

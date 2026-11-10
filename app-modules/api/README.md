# Módulo API

API HTTP versionada do produto (`TresPontosTech\Api`). Hoje atende o **app do colaborador**
([gil-benefits-mobile](https://github.com/3pontos-tech/gil-benefits-mobile)) em `/api/v1`. A organização é por versão e
por público (`V1/Employee`; `V1/Company` quando a integração da empresa migrar para cá, #293).

Épico e decisões: `docs/epicos-api.md`. Plano de execução: `docs/plano-execucao-api.md`.

## Estrutura

```
app-modules/api
├── config/api.php                      validade do token, meta do trimestre, URLs temporárias, limites
├── lang/pt_BR                          mensagens e nomes de campos da API
├── routes/api-routes.php               todas as rotas, prefixo api/v1, nomes api.v1.*
├── src/Actions/Employee/<recurso>      acesso, consultas e montagem de respostas próprias do app
├── src/DTOs/Employee/<recurso>         dados que os resources formatam (perfil, jornada, créditos, acesso)
├── src/Enums/Employee/<recurso>
├── src/Exceptions/Employee/<recurso>
├── src/Http/Controllers/V1/Employee/<recurso>
├── src/Http/Middleware                 ForceJsonAndLocale, EnsureAppAccess
├── src/Http/Requests/V1/Employee/<recurso>
├── src/Http/Resources/V1/Employee/<recurso>
├── src/Providers/ApiServiceProvider    limites de requisição e o contrato de erros (exceção de domínio → 422)
├── src/Support                         ApiDates (formato das datas)
└── tests/Feature                       V1/Employee/<recurso>, Actions/Employee/<recurso> e Contract/* por resource
```

Só a camada HTTP (controllers, requests e resources), que é o contrato, carrega a versão. Os recursos são os mesmos
dos controllers: `Appointments`, `Auth`, `Credits`, `Materials`, `Me`, `Notifications`, `Tickets`. O nível `Employee`
é o público; a API da empresa entra ao lado (`Company`) quando sair do módulo `tenant` (#293).

Regras de negócio ficam nos módulos de domínio (`appointments`, `credits`, `support`, `user`, `consultants`) e são as
mesmas que o painel usa. O controller valida, chama a action e formata a resposta; não decide regra.

## Autenticação e acesso

- `POST /api/v1/auth/login` com `email`, `password` e `device_name` devolve
  `{ data: { token, user } }`. O token é um personal access token do Sanctum com a ability `employee` e validade de
  `API_TOKEN_TTL_DAYS` dias (padrão 90, sem renovação). `POST /api/v1/auth/logout` revoga o token atual.
- As demais rotas pedem `Authorization: Bearer <token>`. Não há sessão web (`sanctum.guard = []`).
- Entra quem tem o **benefício pago**, pela mesma régua do painel (`ResolveBenefitAccess`, no `billing`): plano
  contratual da empresa, assinatura própria ou voucher. Consultores e contas sem vínculo ativo ficam de fora. O
  `EnsureAppAccess` confere a cada requisição; sem acesso, o login responde 422 em `email` e as demais rotas, 403,
  com o motivo ("O plano da sua empresa está inativo…" ou "Para usar o app, ative um plano pelo site.").
- A empresa da requisição é a primeira com vínculo ativo em que a pessoa tem o benefício, a empregadora antes da
  padrão (`ResolveAppAccessAction`, a regra inteira de acesso num lugar só). Ela vira o tenant da requisição, e cota, crédito, agendamento, perfil e
  chamado leem essa empresa, como a aberta na tela do painel.
- Limites (`config/api.php`): 120 requisições por minuto por usuário; no login, 5 tentativas por minuto por e-mail e
  IP e 20 por IP, e a conferência de senha leva sempre o mesmo tempo mínimo (`Timebox`), como no painel.
- Trocar a senha por qualquer caminho (app, "esqueci a senha", perfil do painel, admin) desconecta os aparelhos do app
  (`RevokeAppTokensAction`, chamada em cada um desses caminhos); na troca pelo app, o aparelho que pediu continua
  conectado. O rehash do hash no login do painel não desconecta.
- Tokens vencidos saem no `sanctum:prune-expired` diário (`routes/console.php`).

## Recuperação de senha

Pelo app, com um código de 6 dígitos por e-mail (#323), em três rotas públicas:

1. `POST auth/password/forgot` com `email` responde sempre `202 { message }`. Só quem tem acesso ao app (a mesma
   `ResolveAppAccessAction` do login) recebe o código; consultor e quem não tem o benefício seguem pelo link do site.
2. `POST auth/password/verify` com `email` e `code` devolve `{ data: { reset_token, expires_at } }`. Toda falha dá o
   mesmo 422 em `code`. O código aceita espaço e traço, como vem colado do e-mail.
3. `POST auth/password/reset` com `email`, `reset_token`, `password` e `password_confirmation` responde `204`. Token
   vencido, usado ou de outra conta dá 422 em `reset_token`. O reset não emite token: o app volta ao login.

- Nenhuma resposta diz quem tem conta: o corpo é o mesmo, as três rodam num `Timebox` (`password_reset.timebox_ms`,
  400 ms), e os limites contam pelo e-mail normalizado, exista a conta ou não.
- O código vale 15 min, e o 5º erro o descarta. Os erros também contam por conta e não zeram com um código novo:
  com 20, a recuperação por código fica bloqueada até um reset por qualquer caminho. O reenvio espera 60 s.
- Código e token ficam só como HMAC na `password_reset_codes`, uma linha por pessoa, travada (`lockForUpdate`) em
  cada passo. O e-mail com o código vai criptografado na fila. Linhas sem código, token ou erros saem no
  `model:prune` diário.
- Limites: `forgot` com 3 pedidos a cada 15 min por e-mail e IP e 20 por hora por IP; `verify` e `reset` dividem 10
  por minuto por IP.
- Um reset, pelo app ou pelo site, dispara o `PasswordReset`. Os listeners revogam os tokens do app, encerram as
  sessões web, apagam o código pendente e enviam o e-mail de senha alterada.

## Push notifications

Só vão ao celular os avisos que a pessoa precisa receber na hora, porque saber depois a prejudica (#349): encontro
confirmado, alterado e cancelado pela equipe, lembrete antes do encontro, material novo do consultor e crédito perto
de vencer. Os outros avisos ficam no sino e no e-mail.

- **Token do aparelho.** O app registra o token da Expo com `PUT me/push-token` e o apaga com `DELETE me/push-token`.
  - As duas rotas respondem `204` e são idempotentes.
  - O corpo do `PUT` tem `token` (`ExponentPushToken[...]` ou `ExpoPushToken[...]`) e `platform` (`android` ou `ios`).
  - O token fica preso à sessão, o token Bearer: sair, trocar a senha, entrar de novo no mesmo aparelho e o prune o
    apagam junto, pela FK em cascata. Um 403 de acesso também encerra a sessão no servidor (`EnsureAppAccess`).
  - Um token que era de outra conta passa para quem entrou. Todo `PUT` atualiza o `updated_at`, e é por ele que um
    recibo antigo de "aparelho não existe" deixa de apagar um token registrado de novo.
- **Quem vira push.** O aviso é uma `ContentNotification` cujo `NotificationContent` tem `push`, um `NotificationPush`
  com título e texto próprios para a tela de bloqueio, prioridade e validade. A base acrescenta o canal `expo-push`,
  registrado aqui pelo nome, então os módulos de domínio pedem o push sem importar a API.
- **Payload.** O canal monta um `ExpoPushMessage` (DTO), e só ele conhece o formato da Expo.
  - `data` traz `type` (o `NotificationKind`) e, quando houver, `notification_id`, `appointment_id`, `document_id` e
    `change`.
  - O `notification_id` só vem quando o aviso também está no sino; o lembrete é só push.
  - O `change` (`time`, `consultant`, `link` ou `unconfirmed`) só vem no `appointment_changed`.
  - O `channelId` do Android sai de `NotificationKind::androidChannel()`: `appointments` para os avisos de encontro,
    `general` para os outros. **Os ids estão congelados**, porque o app cria os canais e a importância de um canal não
    muda depois de criado.
  - O link da sala nunca vai no payload. O teste `Contract/PushPayloadContractTest` fixa o contrato de cada tipo.
- **Envio.** O `SendPushNotificationJob` vai para a fila `api.push.queue` (`default`, ou `PUSH_QUEUE`) depois do
  commit.
  - Ele confere o acesso ao app na hora do envio (`ResolveAppAccessAction`) e não envia um aviso que passou da
    validade.
  - Manda pela Expo Push API, que entrega pelo FCM e pelo APNs. A entrega é "pelo menos uma vez": conexão, 429 e 5xx
    se repetem no cliente e depois no job (5 tentativas, até uns 20 minutos); um 4xx falha de vez.
  - A resposta da Expo vira `ExpoPushTicket`. Um `DeviceNotRegistered` apaga o token. Um erro de configuração
    (`InvalidCredentials`, `MismatchSenderId`, `MessageTooBig`) é relatado como `PushConfigurationException`, e o
    Nightwatch o captura.
  - O `push:check-receipts` confere os recibos (`ExpoPushReceipt`) a cada 15 minutos. Nenhum log leva o token.
- **Configuração.** O `EXPO_ACCESS_TOKEN` é opcional enquanto a segurança reforçada do push estiver desligada no
  projeto da Expo; com ela ligada, todo envio sem o token falha.
- **Agendamentos:**
  - O `SendAppointmentRemindersJob` roda a cada 5 minutos e reserva cada lembrete com um update condicional em
    `appointments.reminder_sent_at`. Confirmar ou remarcar para dentro da próxima hora já conta como lembrete
    (`AppointmentReminder`).
  - O `WarnExpiringCreditsJob` roda de hora em hora, das 9h às 20h, e avisa uma semana antes e na véspera. As marcas
    ficam em `user_credits`, então uma rodada perdida é recuperada na seguinte.
- **Encontro alterado.** A equipe edita um encontro e a tela chama `NotifyAppointmentChangedAction` com o
  `AppointmentSnapshot` de antes; a regra de qual mudança contar é `AppointmentChange::between()`.

### Deploy por ambiente

Faça em homologação e depois em produção. O pipeline normal (`migrate --force`) entrega o código; o resto é conferir,
porque nada novo é obrigatório. O lado do app (contas Expo, Firebase e Apple, `google-services.json`, build) está em
`gil-benefits-mobile/docs/release-checklist.md`.

1. **Migrations**, que rodam no deploy:
   - `push_tokens` e `push_tickets`;
   - `appointments.reminder_sent_at`;
   - `user_credits.expiry_warned_week_before_at` e `expiry_warned_day_before_at`.
2. **Worker:** precisa consumir a fila `default` (ou a de `PUSH_QUEUE`, se definida). Depois do deploy, rode
   `php artisan queue:restart`.
3. **Scheduler:** precisa rodar a cada minuto (`schedule:run`), como já precisa hoje. O `php artisan schedule:list`
   deve mostrar:
   - o lembrete, a cada 5 minutos;
   - o `push:check-receipts`, a cada 15 minutos;
   - o aviso de créditos, de hora em hora, das 9h às 20h.
4. **`EXPO_ACCESS_TOKEN` (opcional):**
   - Crie o token na conta Expo dona do projeto daquele ambiente: homologação usa o projeto de teste, e produção, o da
     empresa.
   - Defina no `.env` do servidor e rode `php artisan config:cache` e `php artisan queue:restart`.
   - Só ligue a segurança reforçada do push na Expo depois que todos os servidores tiverem o token. A partir daí, todo
     envio sem ele falha com `UNAUTHORIZED`.
5. **Conferência:**
   - Entre no app daquele ambiente com uma conta, permita as notificações e confirme um encontro dela no admin.
   - O push chega, e o log mostra um `push.sent` com `accepted` maior que zero.
6. **Se não chegar:**
   - Uma `PushConfigurationException` no Nightwatch indica chave FCM ou credencial errada no projeto da Expo.
   - `push.ticket_error` e `push.receipt_error` no log trazem o código da Expo.
   - Sem `push.sent`, confira o worker e se a conta tem um token registrado em `push_tokens`.

## Convenções da resposta

| Tema | Regra |
| --- | --- |
| Formato | Sempre JSON, mesmo sem `Accept: application/json`; mensagens em `pt_BR`. |
| Envelope | `{ data: ... }`. Listas paginadas trazem `links` e `meta` (`current_page`, `last_page`, `per_page`, `total`). |
| Datas | Data e hora em ISO 8601 com offset (`2026-10-09T10:00:00-03:00`); data pura em `Y-m-d` (`ApiDates`). |
| Erros | `422` com `{ message, errors: { campo: [...] } }`, inclusive para regras de domínio (horário indisponível, chamado encerrado). Os demais em pt_BR e sem detalhe interno: `401` sem token; `403` sem acesso ao app (com o motivo), sem a ability ou ao remover material que não é seu; `404` "Não encontrado." para recurso inexistente ou de outra pessoa; `405`; `429` com `Retry-After`. |
| Status | `201` só ao criar (encontro, avaliação, material, chamado); atualizações respondem `200` com o recurso, inclusive remover a foto e cancelar o encontro (o app mostra o resultado); logout, troca e redefinição de senha e remoção de material respondem `204`; o pedido de código de recuperação responde `202`. |

## Rotas

| Área | Rotas |
| --- | --- |
| Autenticação | `POST auth/login`, `POST auth/logout`, `POST auth/password/forgot`, `POST auth/password/verify`, `POST auth/password/reset` |
| Perfil | `GET/PATCH me`, `PUT me/password`, `POST/DELETE me/avatar`, `GET me/journey`, `GET/PUT me/anamnese`, `PUT/DELETE me/push-token` |
| Agenda | `GET appointments/slots`, `GET/POST appointments`, `GET/PATCH appointments/{id}`, `POST appointments/{id}/cancel`, `POST appointments/{id}/feedback` |
| Créditos | `GET credits` |
| Materiais | `GET/POST materials`, `DELETE materials/{id}`, `PATCH materials/{id}/favorite`, `PATCH materials/{id}/viewed` |
| Notificações | `GET notifications`, `PATCH notifications/{id}/read` |
| Chamados | `GET/POST tickets`, `PATCH tickets/{id}` |

A agenda oferece horários de hoje + 2 dias até hoje + 60 dias (`BookableSlots`, a mesma janela do painel). Um mês
fora dela responde `{}` sem consultar a agenda dos consultores, e agendar ou reagendar fora dela dá 422 em
`appointment_at`.

Cancelar (`POST appointments/{id}/cancel`) não apaga o encontro: ele continua, como `cancelled` ou `cancelled_late`.
O corpo aceita `expected_impact` (`returns_credit` | `loses_credit`), o `cancel_impact` que o alerta do app mostrou. Se
a pessoa confirmou com o crédito voltando e as 4 h acabaram até o pedido chegar (o app espera os 6 s do "Desfazer"),
responde 422 em `expected_impact` e não cancela; ter aceitado perder o crédito nunca impede o cancelamento. Sem o
campo, vale o relógio do momento do pedido, como no painel.

Lista completa, com middlewares: `php artisan route:list --path=api/v1 -v`. O contrato que o app consome está em
`gil-benefits-mobile/docs/api-contract.md`.

## Rodando com o app

1. Suba o ambiente e o backend:

   ```bash
   make env-up              # Postgres, Redis, Mailpit e RustFS
   make essentials-seeder   # bucket do RustFS e banco com as contas de teste
   composer run dev         # php artisan serve (porta 8000), fila, logs e Vite
   ```

   A conta `employee@5pontos.com` (senha `password`) entra no app.

2. No app, copie `.env.example` para `.env` e aponte para o backend:

   | Onde o app roda | `EXPO_PUBLIC_API_URL` | Observação |
   | --- | --- | --- |
   | Navegador (`npx expo start --web`) | `http://127.0.0.1:8000` | O CORS padrão do Laravel já libera `api/*`. |
   | Emulador Android (WSL2) | `http://127.0.0.1:8000` | Rode `adb reverse tcp:8000 tcp:8000` (e `tcp:9000`, para avatares e materiais) depois de abrir o emulador. |
   | Celular na mesma rede | `http://<IP da máquina>:8000` | Suba com `php artisan serve --host=0.0.0.0 --port=8000`. |

3. Avatares e materiais usam URLs temporárias do disco `r2`, que em desenvolvimento aponta para o RustFS do `make env-up`
   (variáveis `CLOUDFLARE_R2_*`). A URL precisa ser alcançável pelo aparelho: no celular, use o IP da máquina no
   endpoint.

## Testes

```bash
php artisan test --compact app-modules/api
```

- `tests/Feature/V1/*`: comportamento de cada rota (regras, erros, acesso).
- `tests/Feature/Contract/*ResourceTest.php`: chaves e formato das datas de cada resource. Mudou uma chave, mudou o
  contrato do app: atualize o teste e o `docs/api-contract.md` do app, e veja a seção de versão abaixo.

Helpers em `tests/Pest.php`: `actingAsApiEmployee()`, `actingAsApiSubscriber()`, `defaultCompanyUser()`.

## Versão do contrato

Mudança incompatível em `/api/v1` (remover ou renomear campo, mudar tipo, endurecer validação) é **MAJOR**
(`RELEASING.md`) e precisa de coordenação com a versão do app publicada. Campo ou rota nova é **MINOR**.

## Adicionando um endpoint

1. Regra no módulo de domínio, como action com `handle()`; o painel deve poder usar a mesma.
2. Request em `Http/Requests/V1/Employee`, com `attributes()` traduzidos em `lang/pt_BR/attributes.php`.
3. Controller fino, sem `try/catch`: a exceção de domínio ganha uma linha em
   `ApiServiceProvider::registerDomainRefusals()` (vira 422 no campo que o app mostra) e entra em `DOMAIN_REFUSALS`,
   para não ser relatada como erro.
4. Resource com datas via `ApiDates`.
5. Rota em `routes/api-routes.php` dentro do grupo autenticado, com `whereUuid` nos ids.
6. Testes em `V1/Employee/<recurso>` e no `Contract/*`.

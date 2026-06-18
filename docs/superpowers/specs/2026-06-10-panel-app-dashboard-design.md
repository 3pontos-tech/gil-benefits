# Spec — Redesign do Dashboard do `panel-app` (Hub de Bem-estar Financeiro)

- **Data:** 2026-06-10
- **Módulo:** `app-modules/panel-app`
- **Painel:** `app` (id `app`, path `/app`) — usuário **Employee** (colaborador/cliente B2C)
- **Página alvo:** `TresPontosTech\App\Filament\Pages\UserDashboard`
- **Estética escolhida:** Editorial Quente (light) — Fraunces (display) + Hanken Grotesk (texto), paleta creme/coral sobre `#F1785A`
- **Abordagem de layout:** A — "Jornada em destaque" (hero → ação → apoio → histórico)

---

## 1. Contexto

O dashboard atual (`UserDashboard.php`, grid de 6 colunas) renderiza apenas 3 widgets: `UserCurrentPlanWidget` (plano + CTA agendar), `LatestAppointmentWidget` (próxima consultoria) e `AppointmentHistoryWidget` (tabela das últimas 5). É funcional, porém **transacional**: trata o colaborador como alguém que "agenda consultas", não como alguém em uma **jornada de bem-estar financeiro**.

Vários dados ricos que o usuário já possui ficam **invisíveis** no dashboard:
- `anamnese.life_moment` (momento de vida financeiro — `LifeMoment`)
- categorias de consultoria já exploradas (`AppointmentCategoryEnum`)
- feedbacks dados, recência, volume de consultorias concluídas
- documentos compartilhados pelos consultores (`sharedDocuments`)
- créditos avulsos (`UserCredit`)

**Objetivo:** transformar o dashboard num **hub de bem-estar financeiro** — rico em informação, com narrativa de progresso, sem perder a fluidez de UX nem a conversão de agendamento. Trabalho **full-stack**: pode introduzir atributos computados e queries novas.

### Decisões do brainstorming (já validadas)

| Decisão | Escolha |
|---|---|
| Alma da tela | Hub de bem-estar financeiro (progresso + ação) |
| Ambição | Full-stack (novos cálculos no backend permitidos) |
| Layout | A — Jornada em destaque |
| Estética | Editorial Quente (light) |
| Estágio de maturidade | **Estático honesto** — snapshot da anamnese; sem evolução automática nesta entrega |

> **Validação por protótipo (2026-06-10):** três realizações estruturais do layout foram prototipadas (A — Jornada em destaque; B — Hero imersivo + trilho; C — Bento mosaico). **Vencedora: A**, por ser a mais fiel ao conceito de hub com hierarquia emoção → ação → detalhe. Protótipo descartável já removido.

---

## 2. Arquitetura de informação (layout)

```
┌──────────────────────────────────────────────────────────────────────┐
│  Saudação: "Bom dia, {nome}"  · data ·            plano: {nome} ativo  │  (chrome)
├──────────────────────────────────────────────────────────────────────┤
│  HERO · SUA JORNADA FINANCEIRA                              [full]      │
│  ┌───────────────────────────────────┐  ┌──────────────────────────┐  │
│  │ "Você é {estágio}"  (Fraunces)    │  │ 8 consultorias           │  │
│  │ escada LifeMoment ● você-está-aqui│  │ 4/6 temas abordados      │  │
│  │ Endiv.─Bagunç.─Pagador─[Poup.]─Inv│  │ 5 avaliações · 2 sem.    │  │
│  └───────────────────────────────────┘  └──────────────────────────┘  │
├──────────────────────────────────────────────────────────────────────┤
│  ZONA DE AÇÃO                                                          │
│  ┌── Próxima consultoria [2fr] ──────┐  ┌── Plano & créditos [1fr] ─┐ │
│  │ avatar · consultor · categoria    │  │  anel 3/4 mês · +3 crédito│ │
│  │ data · contagem regressiva        │  │  [Agendar consultoria]    │ │
│  │ [Entrar reunião] [Reagendar]      │  │                           │ │
│  └───────────────────────────────────┘  └───────────────────────────┘ │
├──────────────────────────────────────────────────────────────────────┤
│  GRID DE APOIO                                                         │
│  ┌── Temas financeiros [1fr] ────────┐  ┌── Materiais compart. [1fr]┐ │
│  │ 6 chips: abordados ✓ / faltam +   │  │  últimos docs do consultor│ │
│  └───────────────────────────────────┘  └───────────────────────────┘ │
├──────────────────────────────────────────────────────────────────────┤
│  HISTÓRICO DE CONSULTORIAS (timeline enxuta + ações)        [full]     │
└──────────────────────────────────────────────────────────────────────┘
```

Grid responsivo: hero e histórico ocupam largura total; zona de ação `2fr/1fr`; grid de apoio `1fr/1fr`. Em telas pequenas tudo colapsa para 1 coluna na ordem vertical acima.

---

## 3. Inventário de widgets

Cada widget é uma classe Filament v5 customizada (`Filament\Widgets\Widget`) com sua própria view Blade. Todos leem de um único DTO de jornada quando aplicável (ver §4) para manter a view "burra".

### 3.1 `JourneyHeroWidget` ✦ (novo)
- **Mostra:** estágio atual (`LifeMoment` → label/ícone via enum) numa escada visual com "você-está-aqui"; 4 tiles de momentum (consultorias concluídas, temas X/6, avaliações dadas, recência da última consultoria).
- **Fonte:** DTO `UserJourney` (§4).
- **Estado vazio:** sem anamnese, o middleware `RedirectIfAnamneseNotCompleted` já redireciona quem tem assinatura ativa; ainda assim, fallback: esconder a escada e mostrar CTA "Complete sua anamnese" quando `life_moment` for `null`.
- **Span:** full.

### 3.2 `NextAppointmentWidget` (evolui o atual `LatestAppointmentWidget`)
- **Mostra:** próxima consultoria (avatar/nome do consultor, categoria, data, contagem regressiva, badge de status); ações: entrar na reunião (se `meeting_url` e status confirmado), reagendar/cancelar.
- **Fonte:** `auth()->user()->appointments()->whereFuture/pending->orderBy(appointment_at)->first()` (mesma lógica do widget atual).
- **Estado vazio:** card de incentivo "Agende sua próxima consultoria" + CTA.
- **Span:** 2fr.

### 3.3 `PlanCreditsWidget` (evolui parte do `UserCurrentPlanWidget`)
- **Mostra:** anel de agendamentos do mês (`monthly_appointments_left` / total do plano), créditos avulsos disponíveis, status do plano, CTA "Agendar consultoria" (habilitado conforme `canCreateAppointment()`), avisos quando bloqueado.
- **Fonte:** lógica existente do `UserCurrentPlanWidget` (CompanyPlan → fallback Subscription) + `credits()` (status `Available`).
- **Span:** 1fr.

### 3.4 `FinancialTopicsWidget` ✦ (novo)
- **Mostra:** as 6 categorias (`AppointmentCategoryEnum`) como chips; abordadas com ✓ (cor da categoria), não abordadas em outline com "+". Clique numa não abordada → inicia agendamento naquela categoria (discovery → conversão).
- **Fonte:** DTO `UserJourney.topicsCovered`.
- **Span:** 1fr.

### 3.5 `SharedMaterialsWidget` (novo, leve)
- **Mostra:** últimos 3–4 documentos compartilhados pelos consultores (título, ícone por tipo, link/abrir). "Ver tudo" → `SharedDocumentResource`.
- **Fonte:** `auth()->user()->sharedDocuments()` (`DocumentShare` ativos) → `document`.
- **Estado vazio:** "Nenhum material compartilhado ainda".
- **Span:** 1fr.

### 3.6 `AppointmentHistoryWidget` (mantém, re-estiliza)
- **Mostra:** timeline/tabela enxuta das últimas consultorias com ações existentes (`ViewAppointmentRecordAction`, `FeedbackAction`, `CancelAppointmentAction`).
- **Fonte:** já existente.
- **Span:** full.

---

## 4. Backend novo — Action + DTO + atributos (os ✦)

Para não inchar o model `User`, a agregação da jornada fica numa **Action** dedicada que devolve um **DTO `final readonly`**.

```
app-modules/panel-app/src/Actions/BuildUserJourneyAction.php   (invokável)
app-modules/panel-app/src/DTOs/UserJourney.php                 (final readonly)
```

**DTO `UserJourney`** (shape):
```
- stage: ?LifeMoment              // anamnese.life_moment
- stageIndex: ?int                // posição na escada (para o "você-está-aqui")
- stages: array<LifeMoment>       // ordem canônica da escada (ver §9, ponto aberto)
- completedConsultations: int     // appointments status = Completed
- topicsCovered: array<AppointmentCategoryEnum>   // categorias distintas em concluídas
- topicsTotal: int                // denominador (6 ou subset — ver §9)
- ratingsGiven: int               // feedbacks do usuário
- lastConsultationAt: ?CarbonInterface  // recência
```

**`BuildUserJourneyAction`** — uma query enxuta por métrica, sobre relações já existentes (`appointments`, `anamnese`, `feedbacks`). Cacheável por curto período (alinhar com o cache de 1 min de `monthly_appointments_left`).

Sem novas migrations — **nenhuma mudança de schema** nesta entrega.

---

## 5. Componentes & estética

- **Tema:** estender `resources/css/filament/app/theme.css` com:
  - `@import` das fontes Fraunces + Hanken Grotesk (ou self-host via Bunny/local para performance — decidir na implementação).
  - Tokens CSS de cor (creme `#FBF7F0`, papel `#fff`, coral terroso `#d8643f`, texto `#2b2522`, mutado `#8a7a64`, bordas `#efe7d9`).
  - Classes utilitárias dos cards do hub (ex.: `.hub-card`, `.hub-eyebrow`, `.hub-tile`).
- **Views Blade** dos widgets em `app-modules/panel-app/resources/views/filament/widgets/*` — HTML semântico + Tailwind, usando os tokens. Liberdade visual sem brigar com o Filament (envelopados em `x-filament-widgets::widget` quando fizer sentido, ou container próprio).
- **Manter** `primary` `#F1785A` do painel; o coral terroso `#d8643f` é variação de acento para o hero/CTAs.

---

## 6. Comportamento esperado (BDD)

**Cenário: usuário com anamnese e histórico**
- **Dado** um Employee com `life_moment = saver`, 8 consultorias concluídas em 2 categorias e 1 agendamento futuro
- **Então** o hero exibe "Você é poupador" com a escada marcando Poupador
- **E** os tiles mostram 8 consultorias, 2/6 temas, contagem de avaliações e recência
- **E** a zona de ação mostra a próxima consultoria com contagem regressiva
- **E** "Temas financeiros" destaca as 2 abordadas e oferece as 4 restantes

**Cenário: sem próxima consultoria**
- **Dado** um Employee sem agendamentos futuros
- **Então** o `NextAppointmentWidget` mostra estado vazio com CTA "Agendar"
- **E** o `PlanCreditsWidget` reflete agendamentos restantes/créditos

**Cenário: sem anamnese (borda)**
- **Dado** um Employee sem assinatura ativa e sem anamnese (não redirecionado)
- **Então** o hero esconde a escada e mostra CTA "Complete sua anamnese"
- **E** os tiles de momentum ainda funcionam (independem da anamnese)

**Cenário: bloqueado para agendar**
- **Dado** um Employee sem agendamentos restantes e sem créditos
- **Então** o CTA "Agendar" fica desabilitado com o aviso correspondente (comportamento atual preservado)

**Compatibilidade:** as ações existentes (ver registro IA, avaliar, cancelar) seguem funcionando no histórico; nenhum fluxo de agendamento/billing é alterado.

---

## 7. Antes / depois

**Antes** (`UserDashboard::getWidgets()`):
```php
return [
    UserCurrentPlanWidget::class,    // 4 col
    LatestAppointmentWidget::class,  // 2 col
    AppointmentHistoryWidget::class, // full
];
```

**Depois:**
```php
return [
    JourneyHeroWidget::class,        // full  (novo ✦)
    NextAppointmentWidget::class,    // 2fr   (evolui LatestAppointment)
    PlanCreditsWidget::class,        // 1fr   (evolui UserCurrentPlan)
    FinancialTopicsWidget::class,    // 1fr   (novo ✦)
    SharedMaterialsWidget::class,    // 1fr   (novo)
    AppointmentHistoryWidget::class, // full  (re-estiliza)
];
```

---

## 8. Escopo da 1ª entrega

**Dentro:**
- Os 6 widgets acima no `UserDashboard`.
- `BuildUserJourneyAction` + DTO `UserJourney`.
- Extensão do `theme.css` (fontes + tokens + classes do hub).
- Estados vazios e responsividade.
- Testes (ver §10).

**Fora (futuro):**
- Dark mode completo (direção 3).
- Refazer anamnese / evolução de estágio.
- Aplicar a mesma linguagem visual ao `panel-company` (próxima rodada).
- Demais páginas do `panel-app` (perfil, billing, créditos, etc.).

---

## 9. Pontos abertos (confirmar na implementação)

1. **Ordem canônica da escada `LifeMoment`.** A declaração do enum é `Endebted, Payer, Messy, Saver, Investor`, com "Messy" (bagunçado) entre Payer e Saver — incomum para uma escada de maturidade. **Proposta default:** `Endividado → Bagunçado → Pagador → Poupador → Investidor`. Confirmar a ordem desejada com o produto.
2. **Denominador de "temas".** `AppointmentCategoryEnum` tem 6 casos, mas `MergersAndAcquisitions` e `RiskAndCompliance` são tipicamente B2B. Decidir se o "X/6" usa todas as 6 ou um subconjunto relevante ao colaborador.
3. **Carregamento de fontes.** `@import` Google Fonts vs self-host (performance/privacidade).

---

## 10. Testes

- **Unit:** `BuildUserJourneyAction` com factories — cenários: sem anamnese, sem consultorias, múltiplas categorias, contagem de feedbacks, recência.
- **Feature (Livewire/Filament):** `livewire(UserDashboard::class)` autenticado como Employee — assert de presença dos widgets e dos estados vazios.
- Rodar com `php artisan test --compact` filtrando pelos novos arquivos.

---

## 11. Arquivos afetados

| Arquivo | Ação |
|---|---|
| `app-modules/panel-app/src/Filament/Pages/UserDashboard.php` | editar `getWidgets()`/colunas |
| `app-modules/panel-app/src/Filament/Widgets/JourneyHeroWidget.php` | criar ✦ |
| `app-modules/panel-app/src/Filament/Widgets/NextAppointmentWidget.php` | criar (a partir do `LatestAppointmentWidget`) |
| `app-modules/panel-app/src/Filament/Widgets/PlanCreditsWidget.php` | criar (a partir do `UserCurrentPlanWidget`) |
| `app-modules/panel-app/src/Filament/Widgets/FinancialTopicsWidget.php` | criar ✦ |
| `app-modules/panel-app/src/Filament/Widgets/SharedMaterialsWidget.php` | criar |
| `app-modules/panel-app/src/Actions/BuildUserJourneyAction.php` | criar |
| `app-modules/panel-app/src/DTOs/UserJourney.php` | criar |
| `app-modules/panel-app/resources/views/filament/widgets/*.blade.php` | criar views |
| `resources/css/filament/app/theme.css` | estender (fontes, tokens, classes) |
| `tests/...` (panel-app) | criar testes unit + feature |

> Nota: o `UserCurrentPlanWidget`/`LatestAppointmentWidget` atuais podem ser **refatorados em vez de duplicados** — decisão de implementação (manter compatibilidade onde forem usados fora do dashboard).

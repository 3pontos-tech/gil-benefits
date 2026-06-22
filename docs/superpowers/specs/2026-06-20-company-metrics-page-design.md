# Design — Refatoração e melhoria da página de Métricas (`/company/metrics`)

> Branch: `feature/company-metrics-page` · Data: 2026-06-20 · Módulo: `app-modules/panel-company`

## 1. Contexto

A home do painel da empresa (`CommandDashboard`, rota `/company`) foi refeita no PR #202: 8 widgets sob medida, 100% sobre a camada reutilizável `Actions/Metrics` + DTOs + cache de 5 min, visual polido. A página **Métricas** ficou para trás — continua com o conjunto de widgets antigo (`Filament/Widgets/Metrics/*`), dos quais **só 2** (`AppointmentVolumeChart`, `AppointmentsByCategoryChart`) usam a camada de Actions; o resto roda **query crua inline**.

A página de Métricas, por outro lado, tem o que a home não tem: **filtros** (funcionário, departamento), **período flexível** (mês ou intervalo), **abas** e métricas de **engajamento/insights/uso de crédito** mais profundas.

**Objetivo:** elevar a página de Métricas ao padrão visual e arquitetural da home, transformando-a no **superconjunto** (tudo que a Métricas já tem **+** tudo que vem da home), organizado em abas, **sem perder** os diferenciais de filtro/período/drill-down.

### Distinções conceituais a preservar

- **Consultor ≠ funcionário.** "Top consultores" = profissionais que atendem; "Top funcionário" = quem consome o benefício. Métricas distintas.
- **Adoção ≠ volume.** Adoção por departamento = % que aderiu; Volume por departamento = nº de consultas. Ambas existem e são distintas.

## 2. Decisões travadas (brainstorming)

| # | Decisão | Escolha |
|---|---|---|
| 1 | Reação aos filtros | **Tudo que pode, reage.** Snapshots (Funil, Saldo de crédito) ficam como "estado atual", não-filtráveis. |
| 2 | Arquitetura de informação | **Estrutura C** — 5 abas: **Sessões · Adoção · Engajamento · Experiência · Créditos**. Cada métrica aparece uma vez só. Sem aba "Resumo" (a home já cumpre esse papel ao lado). |
| 3 | Renderização | **Híbrido** — SVG sob medida (formas), Chart.js (`ChartWidget`, gráficos densos), tiles sob medida estilo home (números), `TableWidget` (tabela). |
| 4 | Escopo arquitetural | **Consistência total** — extrair toda métrica de query crua para Actions/DTOs + cache 5 min. |
| 5 | Reúso dos visuais da home | **Parametrizar os widgets da home** (1 classe por visual, reusada nas 2 páginas). `HasMetricsDateRange` ganha default de 12 meses quando a página não fornece filtros → home idêntica. **Testes atuais da home = trava de regressão.** |
| 6 | Mix por categoria | **Donut SVG** reusando `CategoryMixWidget` da home. |
| 7 | Default de período na Métricas | Últimos **30 dias** (comportamento atual). |

## 3. Arquitetura

```
            ┌────────────────────────────────────────────────────┐
            │  Página (Filament)                                 │
            │  CommandDashboard (home)      │   Metrics (5 abas)  │
            │  sem filtros  → default 12m   │   filtros form      │
            └───────────────┬───────────────────────────┬────────┘
                            │ period + filters           │
                            ▼                             ▼
            ┌────────────────────────────────────────────────────┐
            │  Widgets — 1 classe por visual, parametrizada       │
            │  HasMetricsDateRange + InteractsWithPageFilters      │
            │  SVG sob medida (ChartGeometry)  │  Chart.js (densos)│
            └───────────────────────────────┬────────────────────┘
                                            │ handle(tenant, period, filters)
                                            ▼
            ┌────────────────────────────────────────────────────┐
            │  Actions/Metrics  →  DTOs (dados puros)             │
            │  Cache::remember(5 min) via BuildsMetricsCacheKey    │
            └───────────────────────────────┬────────────────────┘
                                            │ Eloquent / query scopes
                                            ▼
                                  ┌──────────────────────┐
                                  │  Banco (tenant)      │
                                  └──────────────────────┘
```

### 3.1 Parametrização dos widgets da home (antes/depois)

```php
// ANTES — preso a 12m / sem filtro
$trend = resolve(GetStatusBreakdown::class)
    ->handle($tenant, MetricsPeriod::lastMonths(12), MetricsFilters::none());

// DEPOIS — lê da página; default 12m quando não há filtros (caso da home)
$data = resolve(GetStatusBreakdown::class)
    ->handle($tenant, $this->metricsPeriod(), $this->metricsFilters());
```

O `HasMetricsDateRange::metricsPeriod()`/`dateRange()` passa a usar **`MetricsPeriod::lastMonths(12)` como default** quando a página não fornece `month`/`startDate`/`endDate`. A home (`CommandDashboard`) não tem filtros form → cai no default → **saída byte-a-byte idêntica**. A Métricas tem os DatePickers pré-preenchidos (30 dias) → usa o intervalo filtrado.

> **Trava de regressão:** os testes atuais dos widgets da home devem continuar verdes **sem qualquer alteração**. Qualquer mudança neles é sinal de regressão.

### 3.2 Snapshots e o selo "estado atual"

`AdoptionFunnelWidget` e `CreditKpisWidget` ignoram período/filtros (estado atual da empresa). Quando reusados na Métricas e houver filtro ativo, exibem um selo **◷ estado atual** deixando claro que não reagem.

## 4. Componentes

### 4.1 Widgets da home reusados (parametrizar — 1 classe, 2 páginas)

| Widget | Action (já existe) | Render | Observação |
|---|---|---|---|
| `StatusBreakdownWidget` | `GetStatusBreakdown` | SVG donut | parametrizar período/filtros |
| `CategoryMixWidget` | `GetCategoryMix` | SVG donut | parametrizar; **substitui** o `AppointmentsByCategoryChart` antigo |
| `DepartmentAdoptionWidget` | `GetDepartmentAdoption` | SVG | parametrizar |
| `SatisfactionWidget` | `GetSatisfaction` | SVG gauge | parametrizar |
| `TopConsultantsWidget` | `GetTopConsultants` | SVG/lista | parametrizar |
| `AdoptionFunnelWidget` | `GetAdoptionFunnel` | SVG (snapshot) | + selo estado atual |
| `CreditKpisWidget` | `GetCreditTotals` + `GetCreditSeries` | SVG + sparkline (snapshot) | + selo estado atual |

> **`SessionsTrendWidget` da home NÃO é reusado na Métricas.** Na Métricas a tendência é densa (intervalo diário) e usa Chart.js — ver 4.3. A home mantém sua versão SVG intacta.

### 4.2 Novas Actions + DTOs (extrair query crua)

| Action | DTO | Conteúdo | Substitui |
|---|---|---|---|
| `GetAppointmentStats` | `AppointmentStats` | total · concluídas · canceladas · taxa de comparecimento | `AppointmentStatsWidget` |
| `GetEngagement` | `EngagementData` | ativos · inativos · total · utilização % · 1ª vez | `EngagementStatsWidget` |
| `GetInsights` | `InsightsData` | nunca usaram (qtd/total/%) · variação vs período anterior · top funcionário | `InsightsWidget` |
| `GetCreditFlow` | `CreditFlow` | distribuídos · usados no período · em uso · disponíveis (escopo de período — distinto do snapshot `GetCreditTotals`) | `CreditStatsWidget` |
| `GetDepartmentVolume` | `DepartmentVolume` | volume de consultas por depto (distinto de `GetDepartmentAdoption`) | `AppointmentsByDepartmentChart` |

Todas seguem o padrão das Actions existentes: `final`, recebem `(Company, MetricsPeriod, MetricsFilters)`, usam `ResolveScopedUserIds` e `BuildsMetricsCacheKey` (cache 5 min com período+filtros na chave), retornam **DTO de dados puros** (sem SVG). DTOs em `src/DTOs/` (convenção: pasta `DTOs`, classes `final readonly`).

### 4.3 Widgets de render na Métricas

- **Tiles sob medida (estilo home):** Estatísticas de consultas, Engajamento, Fluxo de créditos, e os tiles de Insights (Nunca usaram, Variação, Top funcionário). View Blade própria + **partial compartilhada** de tile (ícone/valor/legenda/mini-sparkline), espelhando o padrão do `CreditKpisWidget`.
- **Chart.js (`ChartWidget`):** Tendência de sessões (`GetSessionsTrend` — refina o atual `AppointmentVolumeChart`) e Volume por departamento (`GetDepartmentVolume`).

### 4.4 Tabela — exceção

`CreditUsageTableWidget` continua `TableWidget` (precisa de `Builder`, não DTO). O filtro de período + usuários sai para um **query scope** no `UserCredit` (ex.: `scopeMetricsUsage`), alimentado pelo concern. Alinha com "não use Repository — reuse via scopes".

## 5. Layout das 5 abas (Estrutura C)

Grid de 12 colunas, como na home.

```
ABA "Sessões"
  ┌──────────────────────────────────────────────────────────────┐
  │ [Estatísticas de consultas — tiles]                  span 12  │
  ├───────────────────────────────────────────┬──────────────────┤
  │ [Tendência de sessões — Chart.js]  span 8 │ [Status — donut] 4│
  ├───────────────────────────────────────────┼──────────────────┤
  │ [Mix por categoria — donut]        span 6 │ [Volume/depto —   │
  │                                            │  Chart.js]   span 6│
  └───────────────────────────────────────────┴──────────────────┘

ABA "Adoção"
  [Funil ◷ — SVG] span 7   │   [Adoção por depto — SVG] span 5
  [Nunca usaram — tile destaque]                          span 12

ABA "Engajamento"
  [Engajamento — tiles]                                   span 12
  [Variação vs período — tile] span 6 │ [Top funcionário — tile] span 6

ABA "Experiência"
  [Satisfação / NPS — gauge] span 5 │ [Top consultores — SVG/lista] span 7

ABA "Créditos"
  [Saldo de créditos ◷ — KPIs SVG + sparkline]            span 12
  [Fluxo de créditos no período — tiles]                  span 12
  [Tabela de uso de créditos]                             span 12
```

`◷` = estado atual (não reage aos filtros).

## 6. Comportamento esperado (BDD)

**Cenário: filtro de mês altera as métricas período-scoped**
- **Dado** que estou na página de Métricas
- **Quando** seleciono o mês "Maio/2026"
- **Então** Estatísticas, Tendência, Status, Mix, Volume/depto, Adoção/depto, Satisfação, Top consultores, Engajamento, Insights e Fluxo de créditos refletem Maio/2026
- **E** Funil de adoção e Saldo de créditos permanecem no estado atual, exibindo o selo "◷ estado atual".

**Cenário: filtro de departamento escopa por funcionários do depto**
- **Dado** filtros vazios
- **Quando** seleciono o departamento "Engenharia"
- **Então** as métricas período-scoped consideram apenas consultas/créditos dos funcionários de Engenharia
- **E** os snapshots (Funil, Saldo) seguem company-wide.

**Cenário: compatibilidade com a home (regressão)**
- **Dado** a home `/company` sem filtros form
- **Quando** os widgets parametrizados renderizam
- **Então** o período default é 12 meses e a saída é idêntica à atual
- **E** todos os testes atuais dos widgets da home passam sem alteração.

**Cenário: período sem dados**
- **Dado** um período sem consultas/feedback/créditos
- **Quando** os widgets renderizam
- **Então** cada um mostra seu estado vazio (0/—) sem erro (mesmos edge cases já tratados na home: donut omite fatias mínimas, funil 0% no topo, etc.).

## 7. Testes

- **TDD** (red-green-refactor) para cada nova Action/DTO e cada widget.
- **Novas Actions:** testes unit/feature com factories — happy path, filtro por funcionário, filtro por departamento, período vazio, comparação de período anterior (Insights).
- **Widgets:** testes Filament (`livewire`) — render na Métricas, reação a `month`/`startDate`/`endDate`/`userId`/`departmentId`, selo de snapshot.
- **Regressão da home:** suíte atual dos 8 widgets verde **sem alteração**.
- **Ambiente:** rodar no **checkout principal** (worktree gera `ViewException` falsa). Testes HTTP de `/company` exigem `CompanyPlan` ativo (`CompanyPlan::factory()->active()`).
- **Pint** ao final (`vendor/bin/pint --dirty --format agent`). PHPStan não cobre `panel-company` (módulo sem `phpstan.neon`).

## 8. Faseamento (detalhado no plano de implementação)

0. **Fundação:** default 12m no `HasMetricsDateRange`; parametrizar os 7 widgets da home reusados; manter testes da home verdes.
1. **Camada:** novas Actions/DTOs (`GetAppointmentStats`, `GetEngagement`, `GetInsights`, `GetCreditFlow`, `GetDepartmentVolume`) + scope da tabela, com testes.
2. **Render:** partial de tile sob medida + widgets de número; refinar Chart.js (tendência, volume).
3. **Página:** remontar `Metrics` nas 5 abas (Estrutura C) reusando widgets parametrizados + novos.
4. **Limpeza:** remover widgets antigos órfãos (`AppointmentStatsWidget`, `AppointmentsByCategoryChart`, `EngagementStatsWidget`, `InsightsWidget`, `CreditStatsWidget`), i18n, conferência visual em `/company/metrics`.

## 9. Fora de escopo

- Alterar a home `/company` (além da parametrização interna invisível dos widgets).
- Tornar Funil/Saldo de crédito período-scoped (decisão: permanecem snapshot).
- Tooltip Alpine em SVG (híbrido usa Chart.js nos densos).
- Exportação/relatórios e novas métricas além do superconjunto atual.

# Handoff — Home institucional conforme o Figma (node 8298:404)

> Cole este arquivo como primeira mensagem no Claude Code rodando dentro do WSL,
> em `~/dev/gil-benefits`.

---

## Tarefa

Reconstruir a home (`/`) conforme o Figma
`https://www.figma.com/design/tYn1SioOuQ8mb8uhFKBMmf/Flamma----Web?node-id=8298-404`
(frame "Página institucional", 1920 × 7655), com o mesmo sistema de animação da
página Para Empresas.

Decisões já tomadas com o Renan:

- **Copy do Figma é pra valer** — substituir os textos atuais e reordenar as seções.
- **Assets exportados do Figma** — os links de download estão no fim deste arquivo.

## Por que isto é uma reconstrução, não um ajuste

A home no ar diverge do Figma em quase tudo:

| | Figma | No ar hoje |
|---|---|---|
| Hero | "O benefício que cuida do bem-estar financeiro do seu time." | "Ofereça educação financeira pessoal como benefício corporativo" |
| Seção 2 | "Por que confiar no Flamma?" — 3 cards com foto | não existe |
| Seção 3 | "Como o Flamma chega até o seu time?" — passos 01/02/03 | "Como funciona?" (sem os números) |
| Seção 4 | "O estresse financeiro custa caro" — 2 colunas empresa/colaborador | "O Desafio do RH…" (sem as colunas) |
| Ordem | hero → confiar → como chega → estresse → plano → CTA → FAQ | home → how-it-works → challenge → assessment → CTA → pricing → FAQ |
| Grafismos | seta e estrela em 4 pontos | nenhum |
| Animações | (protótipo do time de design) | nenhuma |

O FAQ é o único que mapeia limpo — e já usa Alpine com `x-data="{ open: false }"`.

---

## Entregáveis neste pacote

### 1. `home.blade.php`

As 7 seções na ordem do Figma, com a copy do Figma, usando os tokens Tailwind
que o projeto já tem (`text-brand-primary`, `bg-elevation-01dp`,
`border-outline-light`, `text-high`, `text-medium`, `text-dark`, `text-light`,
`bg-icon-light`).

Diferente da página Para Empresas, **as classes de animação (`fm-*`) estão
inline no markup** — como o Blade é novo, isso é mais robusto que o
auto-tagging por JS.

Verificações já feitas:

- 5 `@foreach` / 5 `@endforeach`, 7 `<section>` abertas e fechadas, chaves `{{ }}` balanceadas
- **30 expressões PHP extraídas e validadas com `php -l` — 0 erros**

Pontos de atenção ao integrar:

- Os CTAs são `<a>` com Tailwind explícito. Se o projeto tiver um componente de
  botão (a home atual renderiza um com `group/button`), **troque por ele** e
  mantenha a classe `fm-btn`.
- A seção `#pricing` mantém `<livewire:pricing-calculator />` — o componente que
  já existe, não mexer.
- Os `href` de "Fale com a gente" apontam para `#contato`, que **não existe
  ainda**. Defina o destino real (âncora, rota de contato ou WhatsApp).
- O Figma escreve o CTA final como "Cota;áo gratuita" — typo do arquivo.
  No Blade está corrigido para "Cotação gratuita".

### 2. `flamma-motion.css` + `flamma-motion.js`

**Substituem** o `companies-motion.css` / `.js` — cobrem as duas páginas com um
escopo único (`.fm-page`). A home traz a classe no Blade; a página Para Empresas
recebe pelo JS, com a mesma lógica de auto-tagging já validada.

O que a home ganha além do que a Para Empresas já tinha:

- **Accordion do FAQ** animado por `grid-template-rows: 0fr → 1fr`, que anima a
  altura sem medir o conteúdo em JS e sem `max-height` chutado (que faz resposta
  longa cortar e resposta curta demorar). Tem fallback em `@supports` para
  Firefox antigo.
- **Números 01/02/03** crescem 8% junto com o hover do card.
- Os grafismos (seta e estrela) entram como `<img>` com `fm-bob` / `fm-spin`.

Verificado com Chromium headless:

| Checagem | Resultado |
|---|---|
| Folha parseia | 65 regras, 0 erros de JS |
| `fm-reveal` / `-left` / `-scale` | opacity 0 → 1, transform → none |
| Accordion | **0 px fechado → 58 px aberto** |
| `fm-spin` | `animation-name: fm-spin`, 26s |

> Um bug foi encontrado e corrigido nessa verificação: o painel do FAQ dependia
> da classe `grid` do Tailwind estar no markup. Sem `display: grid` explícito no
> próprio CSS, o painel abria e **nunca fechava**. Corrigido — não remova o
> `display: grid` de `.fm-faq-panel`.

**Como ligar:**

```bash
cp flamma-motion.css resources/css/
cp flamma-motion.js  resources/js/
# e remova os companies-motion.* se já os tiver adicionado
```
```js
// resources/js/app.js
import './flamma-motion.js';
```
```css
/* resources/css/app.css — ou o theme.css do painel guest */
@import './flamma-motion.css';
```

---

## Assets — baixar antes de subir a página

Os links do Figma expiram em ~7 dias. Rode isto na raiz do projeto:

```bash
mkdir -p public/img/home public/svg/home

curl -sL -o public/img/home/hero.png                "https://www.figma.com/api/mcp/asset/950d79db-65ff-457f-b360-22d04a914729.png"
curl -sL -o public/img/home/card-especialistas.png  "https://www.figma.com/api/mcp/asset/7ef10853-d0b1-4d2c-b5f9-2c5e95a2cd28.png"
curl -sL -o public/img/home/card-realidade.png      "https://www.figma.com/api/mcp/asset/df1a753e-1ba8-404d-87ab-b4bde400948b.png"
curl -sL -o public/img/home/card-sigilo.png         "https://www.figma.com/api/mcp/asset/e007080b-9255-41fa-bccd-385a3e3bcbcd.png"
curl -sL -o public/img/home/challenge.png           "https://www.figma.com/api/mcp/asset/acc845fc-aed9-405f-87c5-63fcffd5b835.png"
curl -sL -o public/img/home/plan.png                "https://www.figma.com/api/mcp/asset/d373dc26-a77d-47e1-bce4-5a484578949f.png"
curl -sL -o public/img/home/cta.png                 "https://www.figma.com/api/mcp/asset/37bd1a9b-e46f-4d9e-9393-34d0c366f83c.png"

curl -sL -o public/svg/home/icon-users.svg          "https://www.figma.com/api/mcp/asset/7421f2cb-25d9-4f76-8090-d42bcc6c5b19.svg"
curl -sL -o public/svg/home/icon-target.svg         "https://www.figma.com/api/mcp/asset/e92561c3-2cfc-4625-892f-d60743bf9e20.svg"
curl -sL -o public/svg/home/icon-lock-key.svg       "https://www.figma.com/api/mcp/asset/1662d554-2770-446e-834f-984144b0815a.svg"
curl -sL -o public/svg/home/deco-arrow.svg          "https://www.figma.com/api/mcp/asset/c5eceac3-1864-4a0e-baee-1496a65dc4fa.svg"
curl -sL -o public/svg/home/deco-star.svg           "https://www.figma.com/api/mcp/asset/6c0d5ab8-434c-40a0-9378-6f224e88371b.svg"

# confira que nenhum veio vazio ou como HTML de erro
file public/img/home/* public/svg/home/*
```

Se algum link já tiver expirado, exporte o nó direto no Figma (os ids estão nos
comentários do `home.blade.php`) ou peça ao time de design.

As fotos vêm em PNG e são pesadas. **Converta para WebP** antes de commitar,
como o resto do projeto já faz (`/img/companies/*.webp`), e ajuste as extensões
no Blade.

---

## Tokens do Figma (conferidos via MCP)

```
Rose/Rose-Primary        #fd0342
Text/color-text-high     #09090a      Text/color-text-light   #fdfdfd
Text/color-text-dark     #09090a      Text/color-text-medium  #4f4f4f
Elevation/surface        #fbfbff      Elevation/01dp          #f7f8fc
Outline/light            #e8e9f1      Icon/light              #f5f5ff

Hero: linear-gradient(136.19deg, #FD0342 15.42%, #FF803C 84.58%)

Space Grotesk — line-height 1.5, letter-spacing 0
  Bold 64 (números) / 48 (h1, h2) / 32 (h3 de seção) / 24 / 20
  Regular 20 (lead) · Medium 16 (corpo)
```

---

## Ordem sugerida

1. Baixar os assets (links expiram).
2. Plugar `flamma-motion.*` e remover os `companies-motion.*`; confirmar que a
   página Para Empresas continua animando igual.
3. Instalar o `home.blade.php` na página da home, resolvendo os pontos de
   atenção (componente de botão, destino do `#contato`).
4. Conferir contra o Figma seção a seção via MCP
   (fileKey `tYn1SioOuQ8mb8uhFKBMmf`, node `8298:404`).

⚠️ **Antes de mexer em tamanho de fonte, confirme se a Space Grotesk está
carregada** — o tema Filament traz Inter. É o mesmo item pendente do handoff da
página Para Empresas.

Para a conferência visual, rode com o Vite fora do modo watch e o Debugbar
desligado — com HMR a página se recarrega sozinha e atrapalha a inspeção.

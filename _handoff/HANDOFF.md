# Handoff — Para Empresas: deixar igual ao Figma + animações

> Cole este arquivo inteiro como primeira mensagem no Claude Code rodando
> dentro do WSL, em `~/dev/gil-benefits`.

---

## Tarefa

Deixar `http://127.0.0.1:8000/para-empresas` igual ao Figma
`https://www.figma.com/design/tYn1SioOuQ8mb8uhFKBMmf/Flamma----Web?node-id=8175-1300`
(frame "Página para empresas", 1920 × 7642), incluindo as animações.

Escopo acordado: **layout/espaçamento/tipografia + animações + assets**.
Responsivo não é prioridade (o Figma só tem a versão 1920).

## Contexto já levantado (não precisa refazer)

### 1. O protótipo do time de design

`https://glittery-flan-ac4ee1.netlify.app/` **não é uma referência solta — é a
página inteira já implementada** em HTML/CSS/JS puro, fiel ao Figma e comentada
pelo autor. Os três arquivos (`index.html`, `styles.css`, `script.js`) foram
extraídos e conferidos: 214 seletores, cobertura completa. Eles estão salvos
junto deste handoff e são a **fonte de verdade das medidas**.

Sistema de movimento dele:

| Peça | Como funciona |
|---|---|
| Reveal on scroll | `IntersectionObserver`, `threshold .14`, `rootMargin -8%`; 4 variantes (up / left / right / scale), atrasos 80 / 160 / 240 ms |
| Grafismos | estrela gira 360° em 26 s; seta flutua 14 px em 3.6 s |
| Hover | card sobe 10 px + sombra; foto zoom 1.05 em 1.2 s; botão com brilho diagonal em 600 ms; ícone do card −8° + 1.1; marcador do pilar gira 90° |
| Extras | voltar-ao-topo após 600 px; `prefers-reduced-motion` desliga tudo |
| Easing | sempre `cubic-bezier(.22, .68, .18, 1)` |

Há uma **rede de segurança importante**: se a aba abre em segundo plano o
`IntersectionObserver` não dispara callbacks e a página fica em branco (tudo em
`opacity: 0`). O fallback em `visibilitychange` / `pageshow` resolve — não
remover.

### 2. Como a página está hoje

Filament Guest Page (`App\Filament\Guest\Pages\CompaniesPage`), Livewire +
Tailwind, com uma calculadora de preço Livewire (`pricing-calculator`).

Os assets **já estão no projeto**: `/img/companies/*.webp`,
`/svg/companies/*.svg`, e os grafismos como SVG inline no Blade.

Seções, por id: `#empresas`, `#por-que-investir`, `#como-funciona`,
`#consultor`, `#privacidade`, `#simulador`, `#contratar`.

**A página não tem nenhuma animação hoje.** Esse é o maior gap.

---

## Parte 1 — camada de movimento (pronta, só plugar)

`companies-motion.css` e `companies-motion.js` acompanham este handoff.
Portam o sistema acima para o markup que já existe. **Não exigem mudança no
Blade** — o JS acha os elementos pelos ids das seções e pelas classes
utilitárias do Tailwind, e aplica classes `fm-*`.

```bash
cp companies-motion.css resources/css/
cp companies-motion.js  resources/js/
```
```js
// resources/js/app.js
import './companies-motion.js';
```
```css
/* resources/css/app.css — ou o theme.css do painel guest */
@import './companies-motion.css';
```

O binding foi validado rodando o script na página real:
reveal 9 · left 4 · right 3 · scale 3 · spin 3 · setas 2 · cards 3 ·
pilares 3 · zooms 5 — tudo casou.

Dois detalhes que já estão tratados e **não devem ser "simplificados"**:

- As setas já vêm com `rotate-180` do Tailwind. Uma animação de `transform`
  substituiria o transform da classe e desrotacionaria a seta no meio do
  movimento — por isso existe o keyframe `fm-bob-180`, com a rotação dentro.
- Os marcadores dos pilares usam o mesmo `viewBox` das estrelas (347×347).
  O script exclui os que estão dentro de `#consultor li`, senão girariam
  sozinhos em vez de girar no hover.

O JS reexecuta em `livewire:navigated` / `livewire:update` e é idempotente.

**Primeira coisa a fazer:** plugar, subir a página e conferir que as animações
rodam e que nada ficou invisível.

---

## Parte 2 — ajustes de layout (pendentes, é o trabalho de verdade)

### a) Largura do conteúdo — afeta a página inteira

Hoje: `mx-auto max-w-[1676px] px-4 sm:px-6 lg:px-8`
→ em 1920 px o conteúdo fica com **1612 px** (1676 − 64 de padding).
No Figma o conteúdo tem **1676 px**, com margem lateral de 122 px.

```diff
- mx-auto max-w-[1676px] px-4 sm:px-6 lg:px-8
+ mx-auto max-w-[1800px] px-5 sm:px-8 lg:px-[62px]
```

**Tudo na página está ~4 % mais estreito que o Figma por causa disto.**
Começar por aqui, porque muda a medida de todo o resto.

### b) Cards do fluxo ("Do fechamento à primeira sessão")

Divergência visual real. No Figma cada card tem três camadas:

1. fundo `Rectangle2` (vetor com gradiente próprio);
2. sombra elíptica (`Ellipse114` / `Ellipse116`; no card "Sigilo" é um retângulo,
   `Rectangle1226`);
3. a foto do produto com uma **máscara rosa em `mix-blend-mode`** por cima —
   `lighten` nos cards 1 e 3, `plus-lighter` no card 2.

Hoje: `linear-gradient(180deg,#FE4A39,#FF7B33,#FBBFDE)` + `object-contain p-[6%]`
+ uma elipse borrada. Parecido de longe, não é o mesmo card.

As medidas exatas de cada camada estão no `index.html` de referência — o palco
é 482.229 × 327.671 e cada peça tem left/top/width/height próprios, além dos
`mask-size` / `mask-position`.

### c) Seção "Mais que uma ferramenta"

Figma: os dois boxes brancos são posicionados em % **sobre** o grafismo em
escada — esquerda 18.6 %, topo 31.7 % e 60 %, largura 53.5 % de um palco de
1309 × 763.
Hoje: grid de 2 colunas com `lg:pl-32`. Os boxes não caem sobre os degraus.

### d) Privacidade

Figma: a foto sangra até 1799.42 numa prancheta de 1920 — ou seja, 1.42 px
**para fora** — e a seta fica ancorada na foto em `top 41.26% / width 29.83%`.
Hoje: `lg:-mr-16 xl:-mr-32 2xl:-mr-56`, aproximação por breakpoint que não
mantém o sangramento constante.

O `styles.css` de referência resolve com
`right: calc(-1.42px - var(--pad-x) - max(0px, (100vw - var(--container)) / 2))`.

### e) CTA final ("Leve saúde financeira")

Figma: colunas `64 / 754 / 32 / 761 / 65`.
Hoje: `flex-1` nos dois lados (50/50).

### f) ⚠️ Conferir a fonte — checar ANTES de mexer em tamanhos

O tema Filament carrega **Inter**. O Figma inteiro é **Space Grotesk**
(400 / 500 / 700). Não deu pra medir o `font-family` computado na sessão
anterior. Se a Space Grotesk não estiver carregada na guest page, nenhum ajuste
de tamanho vai fazer a tipografia bater.

---

## Tokens do Figma (conferidos via MCP)

```
Rose/Rose-Primary        #fd0342
Text/color-text-high     #09090a
Text/color-text-dark     #09090a
Text/color-text-medium   #4f4f4f
Text/color-text-light    #fdfdfd
Elevation/surface        #fbfbff
Elevation/01dp           #f7f8fc
Outline/light            #e8e9f1
Icon/light               #f5f5ff
Gray/Gray-900            #39393a

Space Grotesk — line-height 1.5, letter-spacing 0, em todos os tamanhos:
  Bold 64 / 48 / 44 / 40 / 24 / 20 / 16
  Medium 16 · Regular 20
```

---

## Como verificar

O Figma MCP está disponível — `get_design_context` / `get_screenshot` no node
`8175:1300` (fileKey `tYn1SioOuQ8mb8uhFKBMmf`) para comparar seção a seção.

Aviso da sessão anterior: com o Vite em watch a página se recarrega sozinha e
atrapalha a inspeção. Para a conferência visual, rodar com `npm run build` e
assets estáticos, e com o Debugbar desligado.

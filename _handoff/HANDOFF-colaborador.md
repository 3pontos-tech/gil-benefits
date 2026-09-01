# Handoff — Página do colaborador (Figma node 8298:3441)

> Cole este arquivo como primeira mensagem no Claude Code rodando dentro do WSL,
> em `~/dev/gil-benefits`.

---

## Tarefa

Criar a página do colaborador conforme o Figma
`https://www.figma.com/design/tYn1SioOuQ8mb8uhFKBMmf/Flamma----Web?node-id=8298-3441`
(frame "Pagina colaborador", 1920 × 7948), com o mesmo sistema de animação das
outras páginas.

**É uma página nova.** Confirmei que `http://127.0.0.1:8000/colaborador` retorna
404 — não existe rota nem view.

## O que precisa ser criado além do Blade

A `/para-empresas` é uma Filament Guest Page (`App\Filament\Guest\Pages\CompaniesPage`,
slug `para-empresas`). Siga o mesmo padrão para a nova página — provavelmente
algo como `App\Filament\Guest\Pages\CollaboratorPage` com slug `colaborador`,
apontando para a view deste pacote. **Confirme o padrão real lendo a
CompaniesPage antes de criar** — eu não tive acesso aos arquivos e estou
inferindo pelo HTML renderizado.

---

## Estrutura (ordem do Figma)

| # | Seção | Node | Observação |
|---|---|---|---|
| 1 | Hero "Cuide do seu dinheiro…" | 8298:3487 | 2 CTAs lado a lado (273px cada), sobre foto de fundo 1920×1156 |
| 2 | "O que é o Flamma?" | 8298:3443 | só texto, coluna 822 |
| 3 | "Do acesso à primeira sessão" | 8451:1039 | **3 cards reusados** da /para-empresas |
| 4 | "Mais que uma ferramenta…" + 3 pilares | 8298:3472 / 8298:3653 | **reusado** da /para-empresas |
| 5 | "Privacidade em primeiro lugar." | 8298:3446 | **reusado**, coluna 693 |
| 6 | "Escolha seu plano" | 8298:3587 | **NOVO** — 2 cards de preço |
| 7 | "Sua empresa já tem o Flamma?" | 8451:1092 | foto 700×587 à direita |
| 8 | "Você e seus colegas…" | 8451:1101 | foto 708×588 à **esquerda** |
| 9 | "Comece agora" | 8451:1110 | card final, foto 761×556 |

### ⚠️ Reuso pesado com a /para-empresas

Três blocos (3, 4 e 5) são **os mesmos componentes** da /para-empresas, com copy
levemente diferente:

- Os 3 cards do fluxo usam **as mesmas fotos** (`card-contract`, `card-reality`,
  `card-secrecy`), só mudam rótulo e texto: Contratação / **Ativação** /
  **Consultoria** (na /para-empresas é Contratação / Realidade / Sigilo).
- Os 3 pilares são idênticos, palavra por palavra.
- O bloco de privacidade é idêntico, só muda a largura da coluna (693 aqui,
  822 lá).

O Blade deste pacote é **self-contained** de propósito: a /para-empresas ainda
não foi ajustada, então extrair componentes agora seria construir sobre algo que
vai mudar. **Depois que as duas páginas estiverem no ar, esses três blocos são
os candidatos óbvios a virar componentes Blade** (`<x-flamma.flow-cards>`,
`<x-flamma.pillars>`, `<x-flamma.privacy>`). Vale abrir um item pra isso.

---

## Verificações já feitas no `colaborador.blade.php`

- 4 `@foreach` / 4 `@endforeach`; 9 `<section>` abertas e fechadas; `{{ }}` balanceadas
- **23 expressões PHP extraídas e validadas com `php -l` — 0 erros**

## Pontos de atenção ao integrar

- Os CTAs são `<a>` com Tailwind explícito. Se houver componente de botão no
  projeto, troque mantendo a classe `fm-btn`. Note que o hero tem **dois estilos**:
  "Conhecer planos" é outline (borda clara, texto rose) e "Indicar minha empresa"
  é sólido rose.
- Os `href` apontam para âncoras internas (`#planos`, `#cadastro`, `#indicar`).
  **"Fazer meu cadastro" e "Indicar minha empresa" provavelmente precisam de
  destino real** (formulário, rota, WhatsApp). Pergunte antes de assumir.
- O preço usa `bg-gradient-to-br from-brand-primary to-brand-secondary bg-clip-text
  text-transparent`. Confirme que esses dois tokens existem no tema — a home usa
  o mesmo par, então devem existir.
- O fundo do hero (`hero-bg.png`) é uma foto de 1920×1156 com recorte em degrau
  no alpha do PNG, mesma técnica do bloco de privacidade da /para-empresas.
  Em telas < lg ele é escondido; confira se o hero ainda lê bem sem ele.
- A foto final reaproveita `/img/companies/cta.webp` (é a mesma imagem do Figma).
  Se a /para-empresas ainda não tiver sido aplicada, confirme que o arquivo existe.

---

## Assets — baixar antes de subir a página

Links do Figma expiram em ~7 dias. Rode na raiz do projeto:

```bash
mkdir -p public/img/colaborador public/svg/colaborador

# fotos
curl -sL -o public/img/colaborador/hero-bg.png   "https://www.figma.com/api/mcp/asset/c2d2df2a-bc71-415f-930a-c566c3d76136.png"
curl -sL -o public/img/colaborador/cadastro.png  "https://www.figma.com/api/mcp/asset/e80a6621-09f6-483e-bb04-c34469f2ee39.png"
curl -sL -o public/img/colaborador/colegas.png   "https://www.figma.com/api/mcp/asset/8cea9712-83dd-495e-bade-6172c10440b0.png"

# ícones dos planos
curl -sL -o public/svg/colaborador/calendar-check.svg "https://www.figma.com/api/mcp/asset/360019d7-781f-4dba-9b89-79297a0006fb.svg"
curl -sL -o public/svg/colaborador/book-open-text.svg "https://www.figma.com/api/mcp/asset/cfae0a3c-8550-4847-8e14-8bf59f309dad.svg"
curl -sL -o public/svg/colaborador/robot.svg          "https://www.figma.com/api/mcp/asset/138f4284-a24f-4e64-b2c2-8265c0106ec0.svg"

# ícone novo do card "Ativação"
curl -sL -o public/svg/colaborador/check-circle.svg   "https://www.figma.com/api/mcp/asset/6b0a7f81-5947-429d-b23c-6ffb04ea9427.svg"

# grafismos decorativos
curl -sL -o public/svg/colaborador/deco-arrow-1.svg   "https://www.figma.com/api/mcp/asset/341ac659-db92-405b-bb78-bd1a40de58a5.svg"
curl -sL -o public/svg/colaborador/deco-arrow-2.svg   "https://www.figma.com/api/mcp/asset/dd03f127-2b84-4fb3-8055-1329ab71595a.svg"
curl -sL -o public/svg/colaborador/deco-star.svg      "https://www.figma.com/api/mcp/asset/06a81abe-e105-475c-9eed-502b021ff8f5.svg"

# confira que nenhum veio vazio ou como HTML de erro
file public/img/colaborador/* public/svg/colaborador/*
```

**Reaproveitados** (já devem existir no projeto, da /para-empresas):
`/img/companies/card-contract.webp`, `card-reality.webp`, `card-secrecy.webp`,
`cta.webp`; `/svg/companies/handshake.svg`, `users.svg`.

> Se `users.svg` não existir em `/svg/companies/`, exporte o nó `8451:1085` do
> Figma — na /para-empresas o terceiro card usa `lock-key.svg`, mas aqui é o
> ícone de usuários.

As fotos vêm em PNG e são pesadas (o hero tem ~1.5 MB). **Converta para WebP**
antes de commitar, como o resto do projeto já faz, e ajuste as extensões no Blade.

---

## Tokens do Figma (conferidos via MCP)

```
Rose/Rose-Primary        #fd0342
Text/color-text-high     #09090a      Text/color-text-light   #fdfdfd
Text/color-text-dark     #09090a      Text/color-text-medium  #4f4f4f
Elevation/surface        #fbfbff      Elevation/01dp          #f7f8fc
Outline/light            #e8e9f1      Icon/light              #f5f5ff

Preço: linear-gradient(165.6deg, #fd0342 15.42%, #ff803c 84.58%)

Space Grotesk — line-height 1.5, letter-spacing 0
  Bold 48 (h1, h2) / 44 / 40 / 32 / 24 / 20
  Medium 20 (lead) · Medium 16 (corpo) · Bold 16 (botões)
```

---

## Ordem sugerida

1. Baixar os assets (links expiram) e converter para WebP.
2. Ler a `CompaniesPage` para confirmar o padrão, e criar a Page + rota
   `colaborador`.
3. Instalar o `colaborador.blade.php`, resolvendo os destinos dos CTAs.
4. Conferir contra o Figma seção a seção via MCP
   (fileKey `tYn1SioOuQ8mb8uhFKBMmf`, node `8298:3441`).

A camada de movimento (`flamma-motion.css` / `.js`) já cobre esta página — ela
usa o escopo `.fm-page`, que o Blade traz no wrapper. Se ela ainda não estiver
instalada, veja o `HANDOFF-home.md`.

⚠️ **Space Grotesk**: continua sendo o item pendente das três páginas. O tema
Filament traz Inter. Confirme antes de mexer em tamanho de fonte.

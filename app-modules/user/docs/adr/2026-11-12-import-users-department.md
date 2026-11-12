---
type: adr
title: "Importe de usuários associa cada pessoa a um departamento existente da empresa, por nome, com comparação exata após normalizar"
module: user
status: accepted
date: 2026-11-12
author: RichardGL11
affects:
  - user
  - company
  - panel-company
  - panel-admin
---

# ADR: Departamento no importe de usuários

**Status:** aceita · **Data:** 2026-11-12 · **Decidido por:** Richard (produto/tech)

Implementa as histórias FLM-588 (departamento na planilha) e FLM-589 (modelo com os
departamentos da empresa), a partir do spike FLM-587. PR #355.

## Contexto

O importe em massa (CSV ou XLSX) cadastra pessoas numa empresa: valida a planilha inteira na
requisição e, se não houver erro, um job na fila `users-import` grava em lotes de 100. Até aqui a
pessoa entrava sem departamento, e o RH precisava associar uma a uma depois.

Departamento é texto livre cadastrado por cada empresa (`departments`, com soft delete), e o
vínculo fica em `company_employees.department_id`. A planilha só pode trazer o nome, então o
problema é casar texto digitado por uma pessoa com os nomes cadastrados, sem errar de
departamento e sem aceitar o que não existe.

## Decisões

### 1. Coluna `department` opcional, cabeçalho padronizado

- A coluna é opcional. Planilha antiga, sem ela, continua válida, e célula vazia cadastra a
  pessoa sem departamento.
- Todo cabeçalho é lido sem espaços nas pontas e em minúsculas, então `Department`, ` EMAIL ` e
  `Tax_Id` funcionam.
- **Cabeçalho repetido depois dessa padronização é erro de estrutura** (`email` e `EMAIL` na
  mesma planilha). Sem essa checagem, o leitor ficaria com o valor da última coluna sem avisar
  ninguém. Cabeçalhos em branco (colunas vazias no fim da planilha) não contam.

### 2. Comparação exata depois de normalizar, sem busca aproximada

O nome da planilha e o nome cadastrado passam pela mesma normalização e só casam se ficarem
**idênticos**:

1. decomposição Unicode de compatibilidade (NFKD), que também iguala caracteres de largura
   total (`Ｖｅｎｄａｓ`) aos comuns;
2. remoção das marcas combinantes (`\p{Mn}`), ou seja, dos acentos;
3. minúsculas;
4. espaços nas pontas removidos e espaços internos repetidos reduzidos a um.

Uma célula que fica vazia depois disso (só espaços, inclusive invisíveis) conta como sem
departamento. Emoji e escritas não latinas são preservados e comparados como estão.

**Por que ignorar acento**, divergindo da RN03 da FLM-588 (que ignorava só caixa e espaços): a
planilha costuma ser preenchida à mão ou exportada de outro sistema, e "Operacoes" contra
"Operações" é o erro mais comum. Rejeitar isso gera retrabalho sem proteger de nada, porque o
caso perigoso (dois departamentos diferentes que viram o mesmo nome) é tratado na decisão 3.

**Por que não há busca aproximada** (distância de edição, `LIKE`, sugestão automática): um nome
digitado errado precisa virar erro na linha, nunca uma pessoa no departamento errado. Pelo mesmo
motivo **nenhum departamento é criado** a partir da planilha.

**Por que a comparação é em memória**: uma empresa tem poucas dezenas de departamentos. Uma
consulta carrega todos, e a comparação acontece em PHP. Índice de texto ou índice funcional no
banco não ajudaria, porque não é busca por texto e a normalização (NFKD + acentos) não tem
equivalente simples no Postgres.

### 3. Só departamentos ativos da própria empresa; ambiguidade é erro

- A consulta parte de `$company->departments()`, então departamento de outra empresa nunca
  casa, e departamento excluído (soft delete) conta como inexistente.
- Se dois departamentos ativos da empresa ficam com o mesmo nome normalizado (por exemplo
  "Operações" e "Operacoes"), a linha é recusada com uma mensagem que lista os candidatos.

### 4. Tudo ou nada, com o erro na linha

O departamento entra na mesma validação dos outros campos: qualquer erro em qualquer linha
bloqueia o importe inteiro, e nada é gravado. Cada linha com problema mostra linha, e-mail e
mensagem. Quando algum nome não é encontrado, a lista dos departamentos cadastrados aparece
**uma vez só, no topo** do modal, em vez de repetida em cada linha.

### 5. O `department_id` é resolvido na validação e vai pronto para o job

A requisição que valida a planilha também resolve o `department_id` de cada linha, e o job só
grava esse id. Assim:

- departamento renomeado entre a validação e o job não muda o resultado: a pessoa vai para o
  departamento que estava na planilha no momento da validação;
- departamento excluído (soft delete) nesse intervalo deixa a pessoa vinculada a ele, que é o
  mesmo estado de quem já estava nesse departamento quando ele foi excluído;
- o job não consulta os departamentos de novo.

Não existe exclusão definitiva de departamento pela interface. Se acontecer por fora nesse
intervalo, a chave estrangeira recusa o lote, o job falha e quem iniciou o importe vê a
notificação de falha.

### 6. Só cadastro, sem realocação

O importe cadastra pessoas novas. E-mail ou CPF que já existe no sistema continua sendo erro,
então a planilha **não** serve para mudar alguém de departamento.

### 7. Modelo com os departamentos numa segunda aba, gerado na hora

- O botão de modelo, dentro do modal de importe, gera um XLSX por requisição com duas abas:
  `Funcionários` (cabeçalho e uma linha de exemplo com o primeiro departamento da empresa) e
  `Departamentos` (os nomes cadastrados, ou um aviso quando não há nenhum).
- **Não é uma lista suspensa** porque nem o OpenSpout nem o `spatie/simple-excel` gravam
  validação de dados. A segunda aba é a alternativa que as bibliotecas atuais suportam.
- **As células são gravadas como texto.** O OpenSpout transforma em fórmula qualquer texto que
  comece com `=`, então um departamento chamado `=HYPERLINK(...)` viraria fórmula no Excel de
  quem baixasse o modelo.
- **A rota pública de download foi removida** em vez de ganhar autenticação. O download agora é
  uma ação do Filament: herda a autenticação e o tenant do painel, e só lista os departamentos
  da empresa do painel. O arquivo temporário é apagado depois do envio.

### 8. Notificação de conclusão

O job grava o resultado numa chave de cache por usuário (`import_done_{userId}`), e a página
consulta essa chave a cada 3 segundos para mostrar sucesso ("Todos os N usuários foram
importados com sucesso.") ou falha.

## Consequências

- A FLM-588 e a FLM-589 ficam atendidas, com duas diferenças conscientes: a RN03 também ignora
  acento (decisão 2), e a rota do modelo foi removida em vez de autenticada (decisão 7).
- O payload do job leva o `department_id` de cada linha.
- Limites conhecidos da notificação: a chave é por usuário, então dois importes simultâneos da
  mesma pessoa compartilham a mesma chave, e o aviso se perde se a página for recarregada antes
  da consulta. A troca por notificação no banco, enviada pelo job a quem iniciou o importe, fica
  para outro PR.

## Em aberto com o PO

- **D1:** o que são "departamentos padrões". Hoje não existe esse conceito no código; se forem um
  conjunto inicial por empresa, é outra história.
- **D2:** o cabeçalho é só `department`. Aceitar `departamento` como apelido é uma mudança
  pequena no parser, caso o PO peça.

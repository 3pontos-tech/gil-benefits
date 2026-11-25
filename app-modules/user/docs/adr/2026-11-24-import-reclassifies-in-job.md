---
type: adr
title: "A classificação do importe roda na requisição e de novo no job, por lote, dentro da transação"
module: user
status: accepted
date: 2026-11-24
author: RichardGL11
affects:
  - user
  - tenant
  - panel-company
  - panel-admin
related:
  adr: user/2026-11-24-import-existing-people
---

# ADR: Importe reclassifica as linhas na hora de gravar

**Status:** aceita · **Data:** 2026-11-24 · **Decidido por:** Richard (produto/tech)

Story FLM-611 do épico FLM-110. As regras de cada linha estão em
[`2026-11-24-import-existing-people.md`](2026-11-24-import-existing-people.md).

## Contexto

A planilha é lida e validada na requisição, e a gravação roda depois, no `ImportUsersJob` (fila
`users-import`, `tries = 1`, lotes de 100). Entre as duas etapas alguém pode se cadastrar sozinho,
outro RH pode importar a mesma pessoa, ou a pessoa pode ser contratada por outra empresa. Antes, o
insert em lote batia no índice único de `users.email` ou `user_details.tax_id`, o lote era desfeito
e o job falhava, com os lotes anteriores já gravados. Com o importe vinculando gente existente, a
janela de risco cresce.

## Decisões

**D1. Uma action só classifica as linhas** (`ClassifyImportRowsAction`), chamada em três lugares:

- na requisição (`ImportUsersAction` e `ImportUsersFromFileAction`), para a prévia e para a conta
  de assentos;
- no começo do job, sobre a planilha inteira, para tirar os repetidos;
- em cada lote de 100 linhas que entram na empresa, **dentro da transação**, antes de criar,
  vincular ou reativar.

O job recebe as linhas da planilha, não a classificação da requisição. Assim o resultado gravado é
sempre o do momento da gravação, e quem apareceu depois do envio é vinculado ou recusado em vez de
duplicado.

**D2. Uma nova tentativa por lote.** Se mesmo assim o lote bater no índice único ou em
`EmployedElsewhere` (alguém mudou entre a classificação e o insert, dentro da mesma transação), a
transação é desfeita, o lote é classificado de novo e gravado mais uma vez. A reativação lê o
vínculo com `lockForUpdate` e só reativa se ele ainda for de funcionário inativo; se alguém o
ativou ou promoveu no meio do caminho, o lote também é refeito, e a linha sai como ignorada. Uma segunda falha
derruba o job, que avisa o RH como antes. E-mails e eventos saem só depois do commit do lote, então
o trabalho desfeito não manda nada.

**D3. A checagem de "funcionário de outra empresa" é feita em lote.** `TenantMember::employedElsewhere()`
aplica a mesma regra de `activeEmployerElsewhere()` (papel funcionário, ativo, vínculo não
excluído, fora do balde, empresa excluída ainda conta) para uma lista de pessoas numa consulta só,
porque a classificação roda até três vezes por importação.

**D4. Assentos só na requisição.** O limite é checado antes de enfileirar, sobre quem entraria
naquele momento. O job não checa de novo: duas importações quase simultâneas, ou uma linha que
deixa de ser "outra empresa" antes do job, podem passar do limite. É o mesmo comportamento de
antes, e o caso é raro.

**D5. O resultado vai para o cache em JSON** (contagem por grupo e a lista por linha), lido pelo
`ChecksImportCompletion`. Um número puro no cache ainda é aceito: é o que um job terminado antes do
deploy deixou lá, e vale por até uma hora.

## Consequências

- O preview pode diferir do resultado final; o que vale é o do job, mostrado na notificação final.
- Cada lote faz algumas consultas a mais (classificação dentro da transação), em troca de não
  gravar cadastro duplicado nem derrubar o job por um cadastro novo.

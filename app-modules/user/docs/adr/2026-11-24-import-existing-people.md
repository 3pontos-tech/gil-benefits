---
type: adr
title: "Importe de usuários classifica cada linha e aproveita quem já tem cadastro, em vez de barrar a planilha"
module: user
status: accepted
date: 2026-11-24
author: RichardGL11
affects:
  - user
  - company
  - appointments
  - panel-company
  - panel-admin
related:
  adr: company/2026-10-30-single-active-employer
---

# ADR: Importe de usuários aproveita os cadastros existentes

**Status:** aceita · **Data:** 2026-11-24 · **Decidido por:** Richard (produto/tech)

Implementa o épico FLM-110 (stories FLM-606 a FLM-612). A parte técnica (onde a classificação
roda e como a gravação resiste a mudanças no meio do caminho) está em
[`2026-11-24-import-reclassifies-in-job.md`](2026-11-24-import-reclassifies-in-job.md).

## Contexto

Até aqui, qualquer linha com e-mail ou CPF já cadastrado, ou repetida na planilha, barrava a
importação inteira. O RH apagava as linhas à mão e subia de novo, e quem já tinha conta (por
exemplo, quem se cadastrou sozinho) não podia entrar na empresa pela planilha.

## Decisões

**D1. Cada linha recebe uma classificação** (`ImportRowOutcome`). Uma linha é de cadastro
existente quando o e-mail **ou** o CPF já está na base. A ordem de avaliação é a da tabela.

| Linha | Resultado |
| --- | --- |
| E-mail ou CPF repetido de uma linha anterior da planilha, mesmo que essa linha também tenha sido ignorada | ignorada; a primeira ocorrência vale |
| E-mail e CPF fora da base | cria a pessoa, como antes |
| E-mail de uma pessoa e CPF de outra | não importada, aviso de suporte |
| Mesma pessoa de uma linha anterior, achada pela outra chave | ignorada |
| Usuário excluído, `user_details` do CPF excluído, ou conta ativa cujo único `user_details` foi excluído | não importada, aviso de suporte |
| Membro ativo da empresa, qualquer papel | ignorada |
| Dono ou gerente inativo da empresa | ignorada |
| Achada só pelo e-mail, com o CPF da conta diferente do CPF da linha | não importada, aviso de suporte |
| Funcionário inativo da empresa | reativado |
| Funcionário ativo de outra empresa (fora do balde) | não importada, aviso de suporte |
| Demais cadastros ativos (só no balde, sem vínculo, dono ou gerente de outra empresa), achados pelo e-mail e pelo CPF | vinculado como funcionário |
| Os mesmos, achados só pelo CPF (o e-mail da linha é outro) | vinculado, com aviso de que o acesso continua pelo e-mail do cadastro |

A "mesma pessoa pela outra chave" (o e-mail dela numa linha, o CPF dela em outra) é ignorada
porque, sem isso, as duas linhas tentariam criar o mesmo vínculo, o índice único derrubaria o lote
e a pessoa ocuparia dois assentos.

O CPF identifica a pessoa; o e-mail, não. Por isso as duas chaves sozinhas têm pesos diferentes:

- **Achada só pelo CPF.** É o caso comum de quem se cadastrou sozinho com o e-mail pessoal e aparece
  na planilha com o corporativo. Vincula, e o relatório diz que o acesso continua pelo e-mail do
  próprio cadastro, sem mostrar esse e-mail (é dado da pessoa, não do RH). O onboarding vai para o
  e-mail do cadastro.
- **Achada só pelo e-mail, com outro CPF.** Ou o CPF da linha está errado, ou o e-mail é de outra
  pessoa. Vai para o suporte em vez de vincular ou reativar. Membro ativo, dono ou gerente da
  empresa continua ignorado: a linha não muda nada.

**D2. O que continua barrando a planilha inteira:** erros de formato (inclusive CPF de 11 dígitos
com dígito verificador errado ou todos os dígitos iguais), colunas obrigatórias,
departamento inexistente ou ambíguo, e telefone repetido entre pessoas diferentes. Telefone
repetido numa linha já ignorada por e-mail ou CPF não conta: a mesma pessoa listada duas vezes
costuma repetir o telefone, e essa linha não entra.

**D3. Só funcionário inativo é reativado.** Dono ou gerente inativo é ignorado: reativar pela
planilha de funcionários devolveria acesso ao painel da empresa sem ninguém pedir. "Removido" pelo
painel apaga o vínculo de vez (`detach`), então essa pessoa volta como **vinculada**.

**D4. Vincular e reativar não mexem no cadastro.** Nome, telefone, RG, senha e
`user_details.company_id` ficam como estão. No vínculo vale o departamento da linha. Na
reativação, o departamento da linha substitui o anterior, e célula vazia mantém o anterior.

**D5. Dono ou gerente de outra empresa é vinculado normalmente**, como manda a D2 do ADR
`company/2026-10-30-single-active-employer`: esses papéis não contam como empregador, e a empresa
que importa só altera o próprio vínculo com a pessoa. Consultor e admin também não têm regra
especial: o caso é raro e decidimos não tratar.

**D6. O aviso de suporte nunca nomeia a outra empresa.** Ela é outro tenant. O RH vê só que a
pessoa precisa do suporte e o motivo (outra empresa, cadastro excluído, dados divergentes).

**D7. Assentos contam só quem entra:** criados, vinculados e reativados. Linhas ignoradas e com
aviso de suporte não consomem assento. Uma planilha em que ninguém entra não é barrada por
assento, mesmo com a empresa já acima do limite: o RH vê o motivo de cada linha.

**D8. E-mails.** Quem é criado recebe as boas-vindas com senha temporária e o onboarding, como
antes. Quem é vinculado ou reativado não recebe senha nova. O evento `ExistingUserImported` existe
para isso: o `UserImported` leva o onboarding com os passos da senha temporária, e essa pessoa não
recebeu senha. O listener do onboarding (módulo appointments) manda a versão sem senha.

**D9. Resultado para o RH.** A notificação final traz a contagem de cada grupo ("5 importados,
2 vinculados, 1 ignorado, 1 precisa do suporte") e um modal lista linha, e-mail e motivo de cada
linha que não foi simplesmente criada, incluindo vinculados e reativados, para o RH conferir um
vínculo por e-mail ou CPF digitado errado. Se nenhuma linha entra na empresa, o modal aparece já
no envio e nada vai para a fila.

## Consequências

- A importação passou a poder esbarrar no empregador único; o ADR
  `company/2026-10-30-single-active-employer` foi atualizado.
- O texto de ajuda do importe descreve as regras acima.
- Ficam fora: atualizar dados de quem já existe pela planilha, transferir pessoa de outra
  empresa, baixar o relatório das linhas em arquivo e e-mail avisando a pessoa do vínculo.
- Risco aceito: um CPF digitado errado, que ainda passe no dígito verificador e coincida com o de
  outra pessoa, a vincula à empresa. As defesas são o dígito verificador, o aviso próprio do vínculo
  achado só pelo CPF e a lista por linha do resultado. Um e-mail errado sozinho não vincula ninguém:
  com CPF diferente, vai para o suporte.

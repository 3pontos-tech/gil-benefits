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

Implementa o épico FLM-110 (stories FLM-606 a FLM-612).

## Contexto

Até aqui, qualquer linha com e-mail ou CPF já cadastrado, ou repetida na planilha, barrava a
importação inteira. O RH apagava as linhas à mão e subia de novo, e quem já tinha conta (por
exemplo, quem se cadastrou sozinho) não podia entrar na empresa pela planilha.

## Decisões

**D1. Cada linha recebe uma classificação.** Uma linha é de cadastro existente quando o e-mail
**ou** o CPF já está na base.

| Linha | Resultado |
| --- | --- |
| E-mail e CPF fora da base | cria a pessoa, como antes |
| E-mail ou CPF repetido de uma linha anterior | ignorada; a primeira ocorrência vale |
| Membro ativo da empresa (qualquer papel) | ignorada |
| Funcionário inativo da empresa | reativado |
| Cadastro ativo sem vínculo de funcionário em outra empresa | vinculado, sem novo cadastro |
| Funcionário ativo de outra empresa | não importado, aviso de suporte |
| Usuário ou `user_details` excluído (soft delete) | não importado, aviso de suporte |
| E-mail de uma pessoa e CPF de outra | não importado, aviso de suporte |

**D2. Erros de formato, departamento e telefone repetido entre pessoas diferentes continuam
barrando a planilha inteira.** Telefone repetido numa linha que já é repetida por e-mail ou CPF
não conta, porque essa linha é ignorada.

**D3. Vincular e reativar não mexem no cadastro.** Nome, telefone, RG, senha e
`user_details.company_id` ficam como estão. Vale só o departamento da linha no vínculo; na
reativação, célula de departamento vazia mantém o departamento anterior.

**D4. Dono ou gerente de outra empresa é vinculado normalmente**, como manda a D2 do ADR
`company/2026-10-30-single-active-employer`. A empresa só altera o próprio vínculo com a pessoa.

**D5. Assentos contam só quem entra:** criados, vinculados e reativados.

**D6. E-mails.** Quem é criado recebe as boas-vindas com senha temporária e o onboarding, como
antes. Quem é vinculado ou reativado não recebe senha; o evento `ExistingUserImported` manda o
onboarding sem o trecho da senha temporária.

**D7. A classificação roda na requisição e de novo no job.** Na requisição, ela alimenta a
prévia e a conta de assentos. No job, cada lote de 100 é classificado de novo dentro da
transação, então quem se cadastrou ou foi contratado por outra empresa depois do envio é
vinculado ou recusado, sem gravar cadastro duplicado. Se mesmo assim o lote bater no índice
único ou em `EmployedElsewhere`, a transação é desfeita e o lote é classificado e gravado mais
uma vez.

**D8. Resultado para o RH.** A notificação final traz a contagem de cada grupo, e um modal lista
linha, e-mail e motivo de cada linha que não foi simplesmente criada. Se nenhuma linha entra na
empresa, o modal aparece já no envio e nada vai para a fila.

## Consequências

- "Removido" pelo painel da empresa apaga o vínculo de vez (`detach`), então essa pessoa volta
  como **vinculada**, não como reativada. Reativação vale para o vínculo que ainda existe com
  `active = false`.
- O texto de ajuda do importe descreve as regras acima.
- Ficam fora: atualizar dados de quem já existe pela planilha, transferir pessoa de outra
  empresa e baixar o relatório das linhas em arquivo.

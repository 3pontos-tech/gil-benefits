# Guia de Releases

Este documento define o padrão de versionamento e o processo de release do **gil-benefits**.

## Versionamento (SemVer para apps)

Usamos [SemVer](https://semver.org/lang/pt-BR/) no formato `MAJOR.MINOR.PATCH`, adaptado para um SaaS interno (não é uma lib pública). A régua é:

| Parte | Quando incrementar | Exemplo |
|-------|--------------------|---------|
| **MAJOR** (`7.0.0`) | Marco de produto **ou** mudança que exige coordenação (ver rubrica abaixo) | `6.4.2` → `7.0.0` |
| **MINOR** (`6.1.0`) | Funcionalidade nova, retrocompatível, deploy normal (`feat:`) | `6.0.1` → `6.1.0` |
| **PATCH** (`6.0.1`) | Correção ou mudança interna, sem impacto de uso (`fix:`, `chore:`, `refactor:`) | `6.1.0` → `6.1.1` |

> **Regra de ouro:** MAJOR **não** é sobre "o código mudou muito". É sobre *"esse deploy exige coordenação / não pode ser um deploy silencioso, ou é um marco de produto"*. Feature nova retrocompatível é **MINOR**, não MAJOR.

## Quando é MAJOR? (v6 → v7)

É **MAJOR** se a release tiver **qualquer um** destes:

- **Migration destrutiva / irreversível** — dropar ou renomear coluna/tabela em uso, mudar tipo com perda de dado, migration sem `down()` seguro.
- **Backfill obrigatório de dados** — a release só funciona depois de rodar um comando de migração de dados (além do `migrate`).
- **Novo requisito de infra/deploy** — passa a exigir novo serviço (Redis/fila), bump de versão de PHP, **nova env var obrigatória sem default**, ou qualquer passo manual no deploy.
- **Quebra de contrato de integração** — mudança incompatível no que uma integração (ex.: `integration-google-calendar`) ou uma API consumida por outro app (`panel-app`/mobile) espera ou retorna.
- **Remoção de feature ou mudança de fluxo** que quebra o uso estabelecido — remover recurso do qual usuários dependem, ou mudar um fluxo de forma que exige avisar/retreinar usuários.
- **Marco de produto** (decisão de negócio) — reescrita grande, novo módulo central, redesign completo, mesmo sem quebra técnica.

Senão: `feat:` → **MINOR**; `fix:`/`chore:`/`refactor:` → **PATCH**.

### O teste das 3 perguntas

Antes de escolher a versão, responda:

1. **Dá pra subir com o pipeline normal (só `migrate --force`), sem passo manual, env nova ou aviso pra ninguém?** — se **não**, é candidato a MAJOR.
2. **Se precisar de rollback, os dados sobrevivem?** — se **não** (migration destrutiva), MAJOR.
3. **Algum usuário ou integração vai *parar de funcionar* como funcionava?** — se **sim**, MAJOR.

Se as três respostas forem tranquilas → é **MINOR** (tem feature nova) ou **PATCH** (só correção).

## Convenção de commits

Usamos [Conventional Commits](https://www.conventionalcommits.org/pt-br/), com escopo opcional por módulo:

```
feat(admin-panel): adiciona histórico de agendamentos
fix(panel-app): corrige cálculo de saldo
chore: atualiza dependências
refactor: extrai action de sincronização
```

Mapeamento para a versão da próxima release:

- `feat:` → sugere **MINOR**
- `fix:` / `chore:` / `refactor:` / `docs:` / `test:` → sugere **PATCH**
- Qualquer commit que dispare a rubrica de MAJOR acima → **MAJOR** (sinalize no PR)

## Processo de release

1. **Confira o que muda em produção.** Rode um diff contra `develop`/`main` e verifique migrations, novas env vars, mudanças de config e necessidade de backfill.
2. **Escolha a versão** aplicando o teste das 3 perguntas.
3. **Merge na `main`** (via PR, com os `wip` já limpos/squashados).
4. **Crie a tag e a release** no GitHub a partir da `main`:
   ```bash
   git checkout main && git pull
   git tag -a vX.Y.Z -m "vX.Y.Z"
   git push origin vX.Y.Z
   ```
5. **Deploy** — o pipeline roda `php artisan migrate --force`. Se a release for MAJOR por exigir passo manual, **documente-o nas notas da release**.
6. **Escreva as notas da release** listando: o que mudou, e **os passos de produção** (migrations, env, comandos) — mesmo que sejam "nenhum".

## Notas

- **Não renumeramos releases já publicadas.** A sequência histórica (v1–v6) fica como está; a régua acima vale **daqui pra frente**.
- Histórico das versões: até a v6 o MAJOR era incrementado a cada release (o que inflou o número). A partir da **v6.1.0** passamos a usar a régua deste documento.

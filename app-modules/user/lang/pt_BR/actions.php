<?php

declare(strict_types=1);

return [
    'import_users' => [
        'label' => 'Importar Funcionários',
        'file' => [
            'label' => 'Planilha (CSV ou XLSX)',
            'helper_text' => 'Colunas obrigatórias: name (nome do funcionário), email (email do funcionário), tax_id (CPF do funcionário). Opcionais: phone_number (número de celular do funcionário), document_id (RG do funcionário), department (nome de um departamento já cadastrado na empresa; o modelo traz a lista na aba Departamentos).',
            'helper_rules' => 'Quem já faz parte da empresa (funcionário ativo, dono ou gerente) e linhas repetidas (mesmo e-mail ou CPF de uma linha anterior, ou a mesma pessoa duas vezes) são ignorados. Funcionário inativo da empresa é reativado. Quem já tem cadastro no :app e não é funcionário ativo de outra empresa é vinculado pelo e-mail ou pelo CPF, sem novo cadastro e sem trocar a senha; achado pelo CPF, o acesso continua pelo e-mail do próprio cadastro. Não são importados, e precisam do suporte: funcionário ativo de outra empresa, cadastro excluído, e-mail e CPF de cadastros diferentes, e e-mail já cadastrado com outro CPF.',
            'helper_note' => 'Nota: o modelo contém uma linha de exemplo com dados fictícios que deve ser apagada antes de preencher com os dados reais dos funcionários.',
            'hint_action' => 'Baixar Modelo',
        ],
        'errors' => [
            'seat_limit' => 'Limite de assentos excedido. Disponíveis: :available, solicitados: :requested.',
        ],
        'notifications' => [
            'empty' => [
                'title' => 'Nenhum usuário importado',
                'body' => 'A planilha está vazia ou todas as linhas foram ignoradas.',
            ],
            'none_joining' => [
                'title' => 'Nenhum usuário importado',
                'body' => 'Nenhuma linha da planilha entra na empresa. Veja o motivo de cada uma.',
            ],
            'completed' => [
                'title' => 'Importação concluída',
            ],
            'started' => [
                'title' => 'Importação em andamento',
                'body' => 'Você receberá uma notificação quando o processo for concluído.',
            ],
        ],
        'outcomes' => [
            'create' => 'Cadastrado.',
            'link' => 'Já tinha cadastro no :app e foi vinculado à empresa.',
            'link_by_tax_id' => 'Já tinha cadastro no :app com outro e-mail, achado pelo CPF, e foi vinculado à empresa. O acesso continua pelo e-mail do próprio cadastro.',
            'reactivate' => 'Funcionário inativo da empresa, reativado.',
            'duplicate_in_sheet' => 'Ignorado: a mesma pessoa (e-mail ou CPF) já aparece numa linha anterior da planilha.',
            'already_member' => 'Ignorado: já faz parte da empresa.',
            'employed_elsewhere' => 'Não importado: a pessoa está vinculada a outra empresa. Contate o suporte.',
            'deleted_account' => 'Não importado: o cadastro desta pessoa foi excluído. Contate o suporte.',
            'conflicting_data' => 'Não importado: o e-mail e o CPF pertencem a cadastros diferentes. Contate o suporte.',
            'tax_id_mismatch' => 'Não importado: este e-mail já está cadastrado com outro CPF. Se o CPF da planilha estiver certo, contate o suporte.',
        ],
        'summary' => [
            'imported' => ':count importado|:count importados',
            'linked' => ':count vinculado|:count vinculados',
            'reactivated' => ':count reativado|:count reativados',
            'skipped' => ':count ignorado|:count ignorados',
            'needs_support' => ':count precisa do suporte|:count precisam do suporte',
            'none_joined' => 'Nenhuma pessoa entrou na empresa: :parts.',
        ],
        'result_modal' => [
            'title' => 'Resultado da Importação',
            'rows' => 'linha(s)',
        ],
    ],
];

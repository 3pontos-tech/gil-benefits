<?php

declare(strict_types=1);

return [
    'import_users' => [
        'label' => 'Importar Funcionários',
        'file' => [
            'label' => 'Planilha (CSV ou XLSX)',
            'helper_text' => 'Colunas obrigatórias: name (nome do funcionário), email (email do funcionário), tax_id (CPF do funcionário). Opcionais: phone_number (número de celular do funcionário), document_id (RG do funcionário), department (nome de um departamento já cadastrado na empresa; o modelo traz a lista na aba Departamentos).',
            'helper_rules' => 'Quem já é funcionário ativo da empresa e linhas repetidas (mesmo e-mail ou CPF de uma linha anterior) são ignorados. Funcionário inativo da empresa é reativado. Quem já tem cadastro no :app sem empresa é vinculado, sem novo cadastro e sem trocar a senha. Quem está em outra empresa ou teve o cadastro excluído não é importado: contate o suporte para essas pessoas.',
            'helper_note' => 'Nota: o modelo contém uma linha de exemplo com dados fictícios que deve ser apagada antes de preencher com os dados reais dos funcionários.',
            'hint_action' => 'Baixar Modelo',
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
            'started' => [
                'title' => 'Importação em andamento',
                'body' => 'Você receberá uma notificação quando o processo for concluído.',
            ],
        ],
        'outcomes' => [
            'create' => 'Cadastrado.',
            'link' => 'Já tinha cadastro no :app e foi vinculado à empresa.',
            'reactivate' => 'Funcionário inativo da empresa, reativado.',
            'duplicate_in_sheet' => 'Ignorado: e-mail ou CPF repetido de uma linha anterior da planilha.',
            'already_member' => 'Ignorado: já faz parte da empresa.',
            'employed_elsewhere' => 'Não importado: a pessoa está vinculada a outra empresa. Contate o suporte.',
            'deleted_account' => 'Não importado: o cadastro desta pessoa foi excluído. Contate o suporte.',
            'conflicting_data' => 'Não importado: o e-mail e o CPF pertencem a cadastros diferentes. Contate o suporte.',
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
        ],
    ],
];

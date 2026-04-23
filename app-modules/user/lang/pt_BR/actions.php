<?php

declare(strict_types=1);

return [
    'import_users' => [
        'label' => 'Importar Funcionários',
        'file' => [
            'label' => 'Planilha (CSV ou XLSX)',
            'helper_text' => 'Colunas obrigatórias: name (nome do funcionário), email (email do funcionário), tax_id (CPF do funcionário), phone_number (número de celular do funcionário). Opcional: document_id (RG do funcionário).',
            'helper_note' => 'Nota: o modelo contém uma linha de exemplo com dados fictícios que deve ser apagada antes de preencher com os dados reais dos funcionários.',
            'hint_action' => 'Baixar Modelo',
        ],
        'notifications' => [
            'empty' => [
                'title' => 'Nenhum usuário importado',
                'body' => 'A planilha está vazia ou todas as linhas foram ignoradas.',
            ],
            'started' => [
                'title' => 'Importação em andamento',
                'body' => 'Você receberá uma notificação quando o processo for concluído.',
            ],
        ],
    ],
];

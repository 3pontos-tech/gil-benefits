<?php

declare(strict_types=1);

return [
    'pages' => [
        'edit_tenant' => [
            'label' => 'Configurações da Empresa',
            'members_heading' => 'Lista de Membros ativos',
            'invite_member' => 'Convidar Membro',
            'deactivate' => 'Desativar',
            'activate' => 'Ativar',
            'status' => 'Status',
            'active' => 'Ativo',
            'inactive' => 'Inativo',
        ],
    ],
    'actions' => [
        'create_and_attach' => [
            'name' => 'Nome',
            'password' => 'Senha',
            'details' => 'Detalhes',
            'cpf' => 'CPF',
            'rg' => 'RG',
            'phone' => 'Telefone',
        ],
        'secret_key_rotation' => [
            'label' => 'Gerar nova chave',
            'new_key_generated' => 'Nova chave gerada: ',
        ],
    ],
];

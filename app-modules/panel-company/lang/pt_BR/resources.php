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
            'member_name' => 'Nome',
            'member_role' => 'Função',
            'form_name' => 'Nome da Empresa',
            'form_tax_id' => 'CNPJ',
            'form_integration_access_key' => 'Chave de Acesso de Integração',
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
        'seats_counter' => [
            'label' => 'Assentos: %s/%s',
        ],
        'logo' => [
            'label' => 'Logo da Empresa',
            'notification' => 'Logo alterado com sucesso.',
        ],
    ],
];

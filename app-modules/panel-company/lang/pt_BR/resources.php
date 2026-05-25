<?php

declare(strict_types=1);

return [
    'pages' => [
        'credits' => [
            'navigation_label' => 'Créditos',
            'title' => 'Créditos',
            'columns' => [
                'holder' => 'Colaborador',
                'status' => 'Status',
                'owner' => 'Comprado por',
                'transferred_at' => 'Distribuído em',
            ],
        ],
        'metrics' => [
            'title' => 'Métricas',
            'navigation_label' => 'Métricas',
            'filter_start_date' => 'Data inicial',
            'filter_end_date' => 'Data final',
            'tab_consultations' => 'Consultorias',
            'tab_engagement' => 'Engajamento',
            'tab_credits' => 'Créditos',
            'filter_user' => 'Colaborador',
            'filter_user_placeholder' => 'Todos os colaboradores',
            'filter_department' => 'Departamento',
            'filter_department_placeholder' => 'Todos os departamentos',
        ],
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
            'member_department' => 'Departamento',
            'form_name' => 'Nome da Empresa',
            'form_tax_id' => 'CNPJ',
            'form_integration_access_key' => 'Chave de Acesso de Integração',
            'make_manager' => 'Tornar Gerente',
            'remove_manager' => 'Remover Gerente',
            'make_manager_notification' => ':name agora é Gerente.',
            'remove_manager_notification' => ':name não é mais Gerente.',
            'assign_department' => 'Atribuir Departamento',
            'assign_department_notification' => 'Departamento atribuído com sucesso.',
            'department' => 'Departamento',
        ],
    ],
    'departments' => [
        'navigation_label' => 'Departamentos',
        'model_label' => 'Departamento',
        'plural_model_label' => 'Departamentos',
        'form' => [
            'name' => 'Nome',
            'category' => 'Categoria',
        ],
        'table' => [
            'name' => 'Nome',
            'category' => 'Categoria',
            'created_at' => 'Criado em',
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
        'purchase_credits' => [
            'label' => 'Comprar Créditos',
            'quantity' => 'Quantidade',
        ],
        'revoke_all_credits' => [
            'label' => 'Revogar Créditos',
            'disabled_tooltip' => 'Não há créditos distribuídos para revogar.',
            'queued_notification' => 'Os créditos estão sendo revogados.',
        ],
        'distribute_equally' => [
            'label' => 'Distribuição Igualitária',
            'disabled_tooltip' => 'Não há créditos disponíveis suficientes para distribuir igualitariamente. Os créditos devem estar com o dono da empresa.',
            'queued_notification' => 'Os créditos estão sendo distribuídos.',
        ],
        'distribute_manually' => [
            'label' => 'Distribuir Créditos',
            'employee' => 'Colaborador',
            'quantity' => 'Quantidade',
            'notice' => 'Os créditos disponíveis do dono da empresa serão transferidos para o colaborador selecionado.',
            'disabled_tooltip' => 'O dono da empresa não possui créditos disponíveis para distribuir.',
            'success_notification' => 'Créditos distribuídos com sucesso.',
        ],
        'transfer_credit' => [
            'label' => 'Transferir',
            'employee' => 'Transferir para',
        ],
    ],
];

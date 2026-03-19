<?php

declare(strict_types=1);

return [
    'navigation_group' => [
        'billing' => 'Faturamento',
        'administration' => 'Administração',
    ],
    'contractual_plans' => [
        'navigation_label' => 'Planos Contratuais',
        'model_label' => 'Plano Contratual',
        'plural_model_label' => 'Planos Contratuais',
        'form' => [
            'name' => 'Nome',
            'slug' => 'Slug',
            'description' => 'Descrição',
            'type' => 'Tipo',
            'active' => 'Ativo',
        ],
        'table' => [
            'type' => 'Tipo',
            'name' => 'Nome',
            'description' => 'Descrição',
            'active' => 'Ativo',
        ],
    ],
    'plans' => [
        'behavior' => [
            'title' => 'Comportamento',
            'has_generic_trial' => 'Possui período de teste genérico',
            'yes' => 'Sim',
            'no' => 'Não',
            'trial_same_for_all' => 'O período de teste será o mesmo para todos os usuários.',
            'trial_unique_per_user' => 'O período de teste será único para cada usuário.',
            'allow_promotion_codes' => 'Permitir Códigos Promocionais',
            'promotion_can_be_applied' => 'Códigos promocionais podem ser aplicados a este plano.',
            'no_promotion_codes' => 'Nenhum código promocional pode ser aplicado a este plano.',
            'collect_tax_ids' => 'Coletar CPFs',
            'tax_ids_collected' => 'CPFs serão coletados para este plano.',
            'tax_ids_not_collected' => 'CPFs não serão coletados para este plano.',
        ],
        'created_date' => 'Data de Criação',
        'last_modified_date' => 'Última Modificação',
    ],
    'prices' => [
        'sections' => [
            'plan_type' => [
                'title' => 'Plano e Tipo',
                'description' => 'Selecione o plano e defina como este preço deve ser cobrado.',
            ],
            'pricing' => [
                'title' => 'Preço',
                'description' => 'Defina o valor do preço e o uso incluído.',
            ],
            'features' => [
                'title' => 'Funcionalidades',
                'description' => 'Ative ou desative as funcionalidades incluídas neste preço.',
            ],
            'provider' => [
                'title' => 'Provedor',
                'description' => 'Referências do provedor de pagamento externo.',
            ],
            'metadata' => [
                'title' => 'Metadados',
                'description' => 'Metadados JSON estruturados associados a este preço.',
            ],
            'auditing' => [
                'title' => 'Auditoria',
                'description' => 'Timestamps rastreados automaticamente.',
            ],
        ],
        'form' => [
            'plan' => 'Plano',
            'type_helper' => 'ex: recorrente, único',
            'billing_scheme_helper' => 'Como a cobrança é calculada (por_unidade, escalonado, etc).',
            'tiers_mode_helper' => 'Se a cobrança for escalonada, escolha o modo (graduado, volume).',
            'unit_amount' => 'Valor Unitário (centavos)',
            'monthly_appointments' => 'Consultas Mensais',
            'monthly_appointments_helper' => 'Quantas consultas estão incluídas por mês.',
            'active_helper' => 'Se este preço pode ser adquirido.',
            'whatsapp_enabled' => 'WhatsApp Habilitado',
            'materials_enabled' => 'Materiais Habilitados',
            'provider_price_id' => 'ID do Preço no Provedor',
            'provider_price_id_helper' => 'Identificador deste preço no provedor de pagamento (ex: Stripe).',
        ],
        'created_date' => 'Data de Criação',
        'last_modified_date' => 'Última Modificação',
    ],
    'companies' => [
        'navigation_label' => 'Empresas',
        'model_label' => 'Empresa',
        'plural_model_label' => 'Empresas',
        'form' => [
            'owner' => 'Proprietário',
            'name' => 'Nome',
            'slug' => 'Slug',
            'tax_id' => 'CNPJ',
        ],
        'table' => [
            'owner' => 'Proprietário',
            'name' => 'Nome',
            'tax_id' => 'CNPJ',
            'plan' => 'Plano',
        ],
        'relation_managers' => [
            'employees' => [
                'title' => 'Membros',
                'role' => 'Função',
            ],
            'contractual_plans' => [
                'title' => 'Planos Contratuais',
                'form' => [
                    'plan' => 'Plano da Empresa',
                    'seats' => 'Cadeiras',
                    'monthly_appointments' => 'Consultas/mês por funcionário',
                    'status' => 'Status',
                    'overlap_error' => 'Já existe um plano ativo com vigência sobreposta para esta empresa.',
                    'starts_at' => 'Início da vigência',
                    'ends_at' => 'Fim da vigência',
                    'notes' => 'Observações',
                ],
                'table' => [
                    'plan' => 'Plano',
                    'seats' => 'Cadeiras',
                    'monthly_appointments' => 'Consultas/mês',
                    'status' => 'Status',
                    'starts_at' => 'Início',
                    'ends_at' => 'Fim',
                ],
            ],
        ],
    ],
    'users' => [
        'navigation_label' => 'Usuários',
        'model_label' => 'Usuário',
        'plural_model_label' => 'Usuários',
        'form' => [
            'fieldset_user' => 'Usuário',
            'fieldset_details' => 'Detalhes',
            'name' => 'Nome',
            'email' => 'E-mail',
            'password' => 'Senha',
            'tax_id' => 'CPF',
            'document_id' => 'RG',
            'company' => 'Empresa',
        ],
        'table' => [
            'name' => 'Nome',
            'email' => 'E-mail',
            'tax_id' => 'CPF',
            'document_id' => 'RG',
        ],
    ],
    'management_cluster' => [
        'navigation_label' => 'Gestão de Usuários',
    ],
    'permissions' => [
        'assign_role' => 'Atribuir Função',
        'user_already_has_role' => 'Usuário já possui esta função',
        'user_assigned_to_role' => 'Usuário foi atribuído à função %s',
    ],
    'pages' => [
        'edit_profile' => [
            'cpf' => 'CPF',
        ],
    ],
];

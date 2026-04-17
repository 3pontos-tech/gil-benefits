<?php

declare(strict_types=1);

return [
    'appointments' => [
        'infolist' => [
            'tabs' => [
                'consultation' => 'Consultoria',
                'client' => 'Cliente',
                'prontuario' => 'Perfil Financeiro',
            ],
            'consultation' => 'Dados da Consultoria',
            'client' => 'Sobre o Cliente',
            'client_email' => 'E-mail',
            'financial_profile' => 'Prontuário Financeiro',
            'financial_profile_description' => 'Perfil respondido pelo cliente antes da consultoria.',
            'no_anamnese' => 'Perfil não preenchido',
            'no_anamnese_description' => 'Este cliente ainda não preencheu o perfil financeiro.',
            'meeting_url_pending' => 'Aguardando confirmação',
        ],
    ],
    'documents' => [
        'navigation_label' => 'Materiais',
        'model_label' => 'Material',
        'plural_model_label' => 'Materiais',
        'table' => [
            'title' => 'Nome do Documento',
            'extension_type' => 'Tipo',
            'active' => 'Ativo',
        ],
        'form' => [
            'title' => 'Nome do Documento',
            'extension_type' => 'Tipo',
            'link' => 'Link',
            'active' => 'Ativo',
            'heading' => 'Novo Documento',
            'files' => 'Arquivo',
            'tab_file' => 'Arquivo',
            'tab_link' => 'Link',
            'type_hint' => 'Envie um arquivo OU defina um link. Se ambos forem fornecidos, o arquivo terá prioridade e o link será desconsiderado.',
        ],
    ],
    'share_documents' => [
        'action' => [
            'label' => 'Compartilhar',
            'heading' => 'Compartilhar Documento',
            'modal_description' => 'Apenas clientes que ainda não possuem acesso a este documento serão listados.',
            'form' => [
                'customer' => 'Cliente',
            ],
        ],
        'relation_manager' => [
            'title' => 'Compartilhado Com',
            'table' => [
                'employee' => 'Funcionário',
                'shared_at' => 'Compartilhado Em',
                'active' => 'Ativo',
            ],
            'actions' => [
                'deactivate' => 'Desativar',
                'activate' => 'Ativar',
            ],
        ],
    ],
];

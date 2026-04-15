<?php

declare(strict_types=1);

return [
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
            'active' => 'Ativo',
            'heading' => 'Novo Documento',
            'files' => 'Arquivo',
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

<?php

declare(strict_types=1);

return [
    'support_tickets' => [
        'navigation_label' => 'Chamados',
        'model_label' => 'Chamado',
        'plural_model_label' => 'Chamados',

        'sections' => [
            'requester' => 'Solicitante',
            'classification' => 'Classificação',
            'details' => 'Detalhes',
            'attachments' => 'Anexos',
            'metadata' => 'Informações técnicas',
        ],

        'columns' => [
            'protocol' => 'Protocolo',
            'category' => 'Categoria',
            'subject' => 'Assunto',
            'status' => 'Status',
            'requester' => 'Solicitante',
            'created_at' => 'Aberto em',
        ],

        'fields' => [
            'protocol' => 'Protocolo',
            'requester_name' => 'Nome',
            'requester_email' => 'E-mail',
            'visitor_company_name' => 'Empresa',
            'category' => 'Categoria',
            'subject' => 'Assunto',
            'description' => 'Descrição',
            'status' => 'Status',
            'attachment' => 'Anexo',
            'url' => 'URL de origem',
            'browser' => 'Navegador',
            'device' => 'Dispositivo',
            'environment' => 'Ambiente',
            'created_at' => 'Aberto em',
        ],

        'actions' => [
            'create' => 'Abrir chamado',
            'close' => 'Finalizar',
            'update_status' => 'Alterar status',
        ],

        'notifications' => [
            'created' => 'Chamado :protocol criado com sucesso!',
            'closed' => 'Chamado finalizado.',
            'status_updated' => 'Status atualizado.',
        ],

        'empty_state' => [
            'heading' => 'Nenhum chamado encontrado',
            'description' => 'Você ainda não abriu nenhum chamado.',
        ],
    ],
];

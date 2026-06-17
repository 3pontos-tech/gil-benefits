<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Suporte',

    'help_center' => [
        'navigation_label' => 'Abrir Chamado',
        'title' => 'Central de Ajuda',
        'section_visitor' => 'Seus dados',
        'section_category' => 'Tipo de solicitação',
        'section_details' => 'Detalhes do chamado',
        'fields' => [
            'visitor_name' => 'Nome',
            'visitor_email' => 'E-mail',
            'visitor_company_name' => 'Empresa (opcional)',
            'category' => 'Categoria',
            'subject' => 'Assunto',
            'description' => 'Descrição',
            'attachment' => 'Anexo (opcional)',
        ],
        'actions' => [
            'submit' => 'Enviar chamado',
        ],
        'notifications' => [
            'created' => 'Chamado :protocol criado com sucesso!',
            'rate_limited' => 'Você abriu chamados demais. Aguarde :seconds segundos e tente novamente.',
        ],
    ],
];

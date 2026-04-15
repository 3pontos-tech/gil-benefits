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
    ],
    'appointment_records' => [
        'previous_summary' => [
            'label' => 'Resumo do último atendimento',
            'modal_heading' => 'Resumo do último atendimento deste cliente',
            'modal_description' => 'Nota interna gerada pela IA no último atendimento publicado. Use para se preparar antes da sessão.',
            'close' => 'Fechar',
            'empty' => 'Este cliente ainda não tem resumo de atendimento anterior disponível.',
            'last_appointment_on' => 'Último atendimento em :date',
        ],
        'create' => [
            'label' => 'Criar Ata',
            'modal_heading' => 'Criar Ata',
            'modal_description' => 'Envie o documento da consulta. A IA gerará um rascunho da ata para sua revisão.',
            'submit' => 'Enviar para análise',
            'form' => [
                'document' => 'Documento (PDF, DOC ou DOCX)',
                'document_helper' => 'Tamanho máximo: 10 MB.',
            ],
            'started' => [
                'title' => 'Geração iniciada',
                'body' => 'Você será notificado quando a ata estiver pronta para revisão.',
            ],
        ],
        'review' => [
            'label_view' => 'Ver Ata',
            'label_review' => 'Revisar Ata',
            'submit_save' => 'Salvar alterações',
            'submit_publish' => 'Publicar para cliente',
            'save_draft' => 'Salvar rascunho',
        ],
    ],
];

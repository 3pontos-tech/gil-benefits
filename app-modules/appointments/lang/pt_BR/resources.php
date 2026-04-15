<?php

declare(strict_types=1);

return [
    'appointments' => [
        'label' => 'Agendamento',
        'plural' => 'Agendamentos',
        'navigation' => 'Agendamentos',

        'table' => [
            'columns' => [
                'consultant' => 'Consultor',
                'user' => 'Usuário',
                'appointment_at' => 'Data e Hora',
                'status' => 'Status',
                'created_at' => 'Criado em',
                'updated_at' => 'Atualizado em',
                'from' => 'De',
                'until' => 'Até',
            ],
            'actions' => [
                'view' => 'Ver',
                'edit' => 'Editar',
                'delete_selected' => 'Excluir selecionados',
            ],
        ],

        'infolist' => [
            'metadata' => 'Metadados',
            'appointment_info' => 'Informações da Consultoria',
            'anamnese' => 'Perfil do Usuário',
            'employee_documents' => 'Materiais do Colaborador',
            'documents' => [
                'title' => 'Nome do Documento',
                'type' => 'Tipo',
                'empty' => 'Nenhum material compartilhado com este colaborador.',
            ],
        ],

        'form' => [
            'meeting_url' => 'Link da Reunião',
        ],

        'exceptions' => [
            'slot_unavailable' => 'Este horário não está mais disponível. Por favor, selecione outro.',
            'consultant_unavailable' => 'Este consultor não está disponível para o horário selecionado.',
            'calendar_event_failed' => 'Falha ao criar o evento no Google Calendar. Tente salvar novamente ou verifique a integração.',
        ],

        'notifications' => [
            'cancelled' => [
                'title' => 'Agendamento Cancelado!',
                'body' => 'Seu agendamento foi cancelado. Verifique seu painel para mais detalhes.',
            ],
            'drafted' => [
                'title' => 'Agendamento em Rascunho',
                'body' => 'Seu agendamento foi salvo como rascunho. Em breve entraremos em contato para confirmar.',
            ],
            'pending' => [
                'title' => 'Agendamento em Andamento',
                'body' => 'Encontramos uma disponibilidade para o seu agendamento. Entraremos em contato em breve.',
            ],
            'scheduled' => [
                'title' => 'Agendamento Confirmado!',
                'body' => 'Seu agendamento foi confirmado. Verifique seu painel para mais detalhes.',
            ],
            'completed' => [
                'title' => 'Agendamento Concluído!',
                'body' => 'Seu agendamento foi concluído. Verifique seu painel para mais detalhes.',
            ],
        ],

        'wizard' => [
            'steps' => [
                'category_type' => 'Categoria de consultoria',
                'pick_datetime' => 'Escolher Data e Hora',
                'review_confirm' => 'Revisar e Confirmar',
            ],
            'labels' => [
                'category_type' => 'Selecione a categoria de consultoria',
                'date' => 'Data',
                'available_times' => 'Horários Disponíveis',
                'duration' => 'Duração',
                'duration_default' => '60 minutos',
                'summary' => 'Resumo',
                'notes' => 'Observações',
            ],
            'actions' => [
                'submit' => 'Iniciar pesquisa',
            ],
        ],
    ],
];

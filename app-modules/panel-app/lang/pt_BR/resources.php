<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'category_type' => 'Tipo de Atendimento',
        ],
        'cancel' => [
            'action_label' => 'Cancelar',
            'modal_heading_ontime' => 'Cancelar Agendamento',
            'modal_description_ontime' => 'Tem certeza que deseja cancelar? Seu crédito será restaurado.',
            'modal_heading_late' => 'Cancelar Agendamento',
            'modal_description_late' => 'Atenção: este agendamento ocorre em menos de 24 horas. Ao cancelar, seu crédito do período será consumido. Deseja continuar?',
            'modal_submit_label' => 'Sim, cancelar',
            'success' => 'Agendamento cancelado com sucesso.',
        ],
        'records' => [
            'view' => [
                'label' => 'Ver Ata',
                'modal_heading' => 'Ata do atendimento',
                'close' => 'Fechar',
            ],
        ],
        'feedback' => [
            'action_label' => 'Avaliar',
            'modal_heading' => 'Avalie sua consultoria',
            'modal_description' => 'Sua avaliação nos ajuda a melhorar o serviço.',
            'rating' => 'Nota',
            'comment' => 'Comentário (opcional)',
            'submit' => 'Enviar avaliação',
            'submitted' => 'Avaliação enviada com sucesso!',
        ],
        'pages' => [
            'create' => [
                'cannot_book_now' => 'Não é possível agendar agora',
                'no_appointments_available' => 'Você não possui agendamentos disponíveis neste mês ou já possui uma consultoria em andamento. Finalize a anterior para agendar outra.',
                'book_appointment' => 'Agendar Consultoria',
                'booked_successfully' => 'Consultoria agendada com sucesso',
                'booking_failed' => 'Falha ao agendar consultoria',
            ],
        ],
    ],
    'documents' => [
        'tabs' => [
            'shared' => 'Compartilhados comigo',
            'mine' => 'Meus Documentos',
        ],
        'table' => [
            'title' => 'Nome do Documento',
            'extension_type' => 'Tipo',
            'active' => 'Ativo',
            'consultant' => 'Consultor',
            'created_at' => 'Data de Envio',
        ],
        'form' => [
            'heading' => 'Novo Documento',
            'title' => 'Nome do Documento',
            'active' => 'Ativo',
            'files' => 'Arquivo',
        ],
    ],
];

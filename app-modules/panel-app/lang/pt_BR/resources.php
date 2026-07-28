<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'category_type' => 'Tipo de Atendimento',
        ],
        'cancel' => [
            'action_label' => 'Cancelar',
            'modal_heading' => 'Deseja cancelar sua consulta?',
            'modal_description' => 'Após a confirmação, a consulta será cancelada e não poderá ser restaurada automaticamente.',
            'notice_keeps_credit' => 'Você pode cancelar sua consulta até :hours horas antes do horário agendado. Em cancelamentos dentro desse prazo, seu crédito será restituído.',
            'notice_loses_credit' => 'Faltam menos de :hours horas para o horário agendado. Ao cancelar agora, seu crédito não será restituído.',
            'modal_submit_label' => 'Confirmar',
            'modal_cancel_label' => 'Cancelar',
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
                'back_to_list' => 'Voltar para a listagem',
            ],
        ],
    ],
    'credits' => [
        'navigation_label' => 'Meus Créditos',
        'title' => 'Meus Créditos',
        'columns' => [
            'status' => 'Status',
            'distributed_at' => 'Distribuído em',
            'purchased_at' => 'Comprado em',
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

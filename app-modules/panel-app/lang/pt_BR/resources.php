<?php

declare(strict_types=1);

return [
    'appointments' => [
        'table' => [
            'category_type' => 'Tipo de Atendimento',
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
];

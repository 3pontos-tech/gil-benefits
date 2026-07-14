<?php

declare(strict_types=1);

return [
    'scheduled' => [
        'subject' => 'Nova consulta agendada',
    ],
    'requested_admin' => [
        'subject' => 'Nova solicitação de agendamento aguardando atribuição',
    ],
    'completed' => [
        'subject' => 'Sua consulta foi concluída',
    ],
    'cancelled' => [
        'subject' => 'Consulta cancelada',
    ],
    'user_cancelled_late' => [
        'subject' => 'Consulta cancelada fora do prazo',
    ],
    'consultant_unassigned' => [
        'subject' => 'Uma consulta foi removida da sua agenda',
    ],
    'no_consultant' => 'consultor não atribuído',
];

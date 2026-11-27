<?php

declare(strict_types=1);

return [
    'user_registered' => [
        'title' => 'Novo usuário cadastrado',
        'body' => ':name acabou de se cadastrar no sistema.',
    ],
    'appointment_booked' => [
        'title' => 'Novo agendamento',
        'body' => ':name realizou um novo agendamento.',
    ],
    'appointment_cancelled' => [
        'title' => 'Agendamento cancelado',
        'body' => 'O agendamento de :name foi cancelado.',
    ],
    'appointment_completed' => [
        'title' => 'Agendamento concluído',
        'body' => 'O agendamento de :name foi concluído.',
    ],
    'stripe_webhook_failed' => [
        'title' => 'Webhook do Stripe falhou',
        'body' => 'O evento :type (:event_id) do Stripe não foi processado depois de todas as tentativas, e o Stripe não vai reenviá-lo. Reprocesse o job com queue:retry.',
    ],
    'unknown_user' => 'Usuário desconhecido',
];
